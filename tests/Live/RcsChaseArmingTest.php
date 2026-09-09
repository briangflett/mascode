<?php

/**
 * RCS chase arming — a manually-created Service Request must be chased too.
 *
 * WHY THIS IS A `cv scr` SCRIPT, NOT A PHPUnit TEST:
 * The same reason as tests/Live/ClientRepChangeTest.php and tests/Security/
 * (see docs/TESTING.md). The behaviour under test is two CiviRules rules arming
 * off real case events — the triggers, their conditions, the delayed-action
 * queue and the rule log all have to run in one live, fully-bootstrapped
 * CiviCRM. CI has no CiviCRM at all, and the PHPUnit Integration suite
 * self-skips in this WP-buildkit site. The CI-visible half of this feature is
 * tests/Unit/Service/RcsChaseOnCreateWiringTest.php.
 *
 * RUN (from anywhere inside the buildkit site, so cv can find the settings file):
 *   cd /home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext/mascode
 *   cv upgrade:db          # provisions mas_lifecycle_rcs_chase_on_create (upgrade_5012)
 *   cv scr tests/Live/RcsChaseArmingTest.php --user=brian.flett@masadvise.org
 * Exit code 0 = all pass; non-zero = at least one failure (red).
 *
 * `cv upgrade:db` first is not optional, and `cv flush` is NOT a substitute:
 * the rule is a CiviRules row written by an upgrade step, not a managed entity,
 * so a flush does not create it. Without it this file aborts with exit 2 rather
 * than reporting a green it has not earned.
 *
 * WHAT IT GUARDS
 * mas_lifecycle_rcs_chase arms on a status TRANSITION into "Request RCS". Only
 * one of the two intake paths produces one:
 *
 *   - Web form. The request_for_assistance_form FormProcessor creates the case
 *     at "Ongoing"; sending the ask email later advances it (see
 *     RcsRequestStatusSubscriber). A real transition — armed 19 of 19 on prod.
 *   - CiviCRM "New Case" UI. The form has NO default status at all (no
 *     case_status option value carries is_default = 1, so the select renders a
 *     "- select Case Status -" placeholder), so the coordinator picks one and
 *     picks "Request RCS", because they send the ask email in the same sitting.
 *     The case is created AT the arming status, never transitions into it, and
 *     armed 0 of 23 on prod — on the MAJORITY intake path.
 *
 * mas_lifecycle_rcs_chase_on_create closes that on the mas_new_case trigger.
 *
 * THE ASSERTIONS THAT ARE THE POINT OF THIS FILE are A2 and A3 together:
 * a manually-created SR is armed EXACTLY ONCE. Not "at least once" — a fix that
 * stacked a second 21/42 pair onto a live cadence would be worse than the
 * missing chase it replaced, and the coordinator does re-touch these cases
 * (18828 got a manual ask five days after an automated chase, with its 42-day
 * chase still queued). Exactly-once here is structural rather than guarded:
 * mas_new_case fires on op = create, which happens once per case, ever.
 *
 * SCENARIOS (each on its own independent case, so none can mask another):
 *   A  SR created AT "Request RCS"     -> A1 status preserved
 *                                        A2 on-create rule armed EXACTLY once
 *                                        A3 exactly one 21-day + one 42-day chase
 *                                        A4 transition rule did NOT also arm
 *   B  SR created at "Ongoing", then   -> B1 transition rule armed (the proven
 *      transitioned to "Request RCS"         path still works — measured, not
 *                                            assumed, so A is interpretable)
 *                                        B2 on-create rule did NOT also arm
 *   D  SR created at "Ongoing", left   -> neither rule armed (no over-reach:
 *      alone                                nothing has been asked of anyone)
 *   E  SR created at "RCS Completed"   -> neither rule armed (the guard is the
 *                                        arming status, not "any new SR")
 *   F  Project created at "Awaiting VC -> neither rule armed (service_request
 *      Project Definition"                  only; the four sibling chases are
 *                                           out of scope and measured clean)
 *   G  case A re-enters the status     -> arms AGAIN, via the TRANSITION rule.
 *      (-> "RCS Completed" -> back)         A genuine second entry must still
 *                                           chase; the obvious wrong fix is a
 *                                           once-per-case latch, which would
 *                                           silently stop chasing a client who
 *                                           returned one form and was asked
 *                                           again later.
 *
 * A4 and B2 are the mutual-exclusivity pair and are the reason two rules are
 * safe rather than a double-send hazard. D, E and F exist because a rule that
 * does not match is a silent non-event, so an over-broad condition set has no
 * other detector.
 *
 * FIXTURES: independent throwaway cases built through ONE closure that varies
 * only the case type and the creation status, each with a client organisation,
 * a coordinator and a client rep. Everything is hard-deleted at the end,
 * including the delayed-action queue rows the run armed, and anything a
 * previously crashed run left behind is swept first (matched on the
 * 'srintaketest' marker). It does NOT touch existing data.
 *
 * NOTE the case roles are added AFTER the case is created, which is what the
 * coordinator actually does (case 18852: case at 14:26:36, role at 14:27:25,
 * ask email at 14:28:23) and is deliberate here: it means the on-create rule
 * arms while the case has NO client rep. That is fine and is worth pinning —
 * LifecycleEmail::processAction() resolves 'client_rep' to a contact at
 * EXECUTION time, 21 and 42 days later, not when the chase is armed.
 *
 * SAFETY: chases are delayed 21 and 42 days, so nothing sends during this run.
 * Queue rows are deleted in the `finally` block; even if that were missed,
 * CiviRules re-checks conditions at release time with fresh data
 * (ignore_condition_with_delay = 0), so an item whose case is gone is skipped
 * and consumed rather than mailed — see scripts/cleanup-orphaned-chase-queue.php.
 */

/**
 * Static tracker — a plain `$failures` variable does NOT work here: under
 * `cv scr` the script body runs inside a method, so `global` in a helper
 * function refers to a different (always empty) variable and the summary line
 * reports success no matter what the assertions did.
 */
class T
{
    public static array $failures = [];
    public static int $passes = 0;
}

function note(string $s): void
{
    echo $s . "\n";
}

function pass(string $label): void
{
    T::$passes++;
    note("  [PASS] $label");
}

function fail(string $label, string $detail): void
{
    T::$failures[] = "$label — $detail";
    note("  [FAIL] $label — $detail");
}

function check(string $label, $got, $want): void
{
    if ($got === $want) {
        pass($label);
        return;
    }
    fail($label, sprintf('got %s, want %s', var_export($got, true), var_export($want, true)));
}

$stamp = 'srintaketest' . time();

// --- Preconditions -----------------------------------------------------------
// Look both rules up by NAME, never by id: the transition rule is id 9 on
// production and id 10 on the dev clone. A hardcoded id would make this file
// quietly test nothing.

$ruleIdByName = static function (string $name): ?array {
    $dao = \CRM_Core_DAO::executeQuery(
        "SELECT id, is_active FROM civirule_rule WHERE name = %1",
        [1 => [$name, 'String']]
    );
    if ($dao->fetch()) {
        return ['id' => (int) $dao->id, 'is_active' => (int) $dao->is_active];
    }
    return null;
};

$transition = $ruleIdByName('mas_lifecycle_rcs_chase');
$onCreate = $ruleIdByName('mas_lifecycle_rcs_chase_on_create');

foreach (['mas_lifecycle_rcs_chase' => $transition, 'mas_lifecycle_rcs_chase_on_create' => $onCreate] as $n => $row) {
    if (!$row) {
        note("ABORT: CiviRules rule \"$n\" does not exist in this environment.");
        note('       Provision both: cv upgrade:db   (or, on a fresh install,');
        note('       cv scr scripts/create-rcs-chase-rule.php --user=<admin>)');
        note('       Nothing was created.');
        exit(2);
    }
    if ($row['is_active'] !== 1) {
        note("ABORT: rule $n (id {$row['id']}) exists but is_active = {$row['is_active']}. Nothing was created.");
        exit(2);
    }
}
$transitionId = $transition['id'];
$onCreateId = $onCreate['id'];
note("Rules: mas_lifecycle_rcs_chase = id $transitionId, mas_lifecycle_rcs_chase_on_create = id $onCreateId. Both active.");

$queueName = \CRM_Civirules_Engine::QUEUE_NAME;

/** Firings of one rule recorded against one case. */
$firings = static function (int $ruleId, int $caseId): int {
    return (int) \CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) FROM civirule_rule_log
          WHERE rule_id = %1 AND entity_table = 'civicrm_case' AND entity_id = %2",
        [1 => [$ruleId, 'Integer'], 2 => [$caseId, 'Integer']]
    );
};

/**
 * Delayed-action queue rows armed for one case, as [id => release_time]. Same
 * best-effort extraction as scripts/cleanup-orphaned-chase-queue.php — the
 * queue stores a serialized task blob with no public accessor, so matching is
 * by pattern on purpose.
 */
$queueRows = static function (int $caseId) use ($queueName): array {
    $rows = [];
    $dao = \CRM_Core_DAO::executeQuery(
        "SELECT id, data, release_time FROM civicrm_queue_item WHERE queue_name = %1",
        [1 => [$queueName, 'String']]
    );
    while ($dao->fetch()) {
        $found = null;
        if (preg_match('/civicrm_case.*?"id";s:\d+:"(\d+)"/s', $dao->data, $m)) {
            $found = (int) $m[1];
        } elseif (preg_match('/s:7:"case_id";s:\d+:"(\d+)"/', $dao->data, $m)) {
            $found = (int) $m[1];
        } elseif (preg_match('/s:7:"case_id";i:(\d+)/', $dao->data, $m)) {
            $found = (int) $m[1];
        }
        if ($found === $caseId) {
            $rows[(int) $dao->id] = (string) $dao->release_time;
        }
    }
    return $rows;
};

$statusOf = static function (int $caseId): ?string {
    $case = \Civi\Api4\CiviCase::get(false)
        ->addSelect('status_id:name')->addWhere('id', '=', $caseId)
        ->execute()->first();
    return $case['status_id:name'] ?? null;
};

// --- Sweep fixtures stranded by an earlier crashed run -----------------------

foreach (
    \Civi\Api4\CiviCase::get(false)
        ->addSelect('id')->addWhere('subject', 'LIKE', '%srintaketest%')->execute() as $stale
) {
    foreach (array_keys($queueRows((int) $stale['id'])) as $qid) {
        \CRM_Core_DAO::executeQuery("DELETE FROM civicrm_queue_item WHERE id = %1", [1 => [$qid, 'Integer']]);
    }
    \CRM_Core_DAO::executeQuery(
        "DELETE FROM civirule_rule_log WHERE entity_table = 'civicrm_case' AND entity_id = %1",
        [1 => [(int) $stale['id'], 'Integer']]
    );
    \Civi\Api4\CiviCase::delete(false)->addWhere('id', '=', $stale['id'])->execute();
}
foreach (
    \Civi\Api4\Contact::get(false)
        ->addSelect('id')
        ->addClause('OR', ['last_name', 'LIKE', '%srintaketest%'], ['organization_name', 'LIKE', '%srintaketest%'])
        ->execute() as $stale
) {
    \Civi\Api4\Contact::delete(false)->addWhere('id', '=', $stale['id'])->setUseTrash(false)->execute();
}

// --- Fixtures ----------------------------------------------------------------

$createdContacts = [];
$createdCases = [];

$mk = function (string $type, array $vals) use (&$createdContacts): int {
    $create = \Civi\Api4\Contact::create(false)->addValue('contact_type', $type);
    foreach ($vals as $k => $v) {
        $create->addValue($k, $v);
    }
    $id = (int) $create->execute()->first()['id'];
    $createdContacts[] = $id;
    return $id;
};

$relTypes = \Civi\Api4\RelationshipType::get(false)
    ->addSelect('id', 'name_a_b')
    ->addWhere('name_a_b', 'IN', ['Case Client Rep is', 'Case Coordinator is'])
    ->execute()
    ->indexBy('name_a_b');
$repType = (int) $relTypes['Case Client Rep is']['id'];
$coordType = (int) $relTypes['Case Coordinator is']['id'];

/**
 * Build one independent case. Every fixture goes through here and differs ONLY
 * in $caseType and $createStatus.
 *
 * @return array{case:int, org:int}
 */
$makeCase = function (string $tag, string $caseType, string $createStatus) use (
    $mk,
    $stamp,
    &$createdCases,
    $repType,
    $coordType
): array {
    $orgId = $mk('Organization', ['organization_name' => "Org $tag $stamp"]);
    // 'Case Coordinator is' declares contact_sub_type_a = MAS_Rep; a plain
    // Individual is rejected with "Invalid Relationship".
    $vcId = $mk('Individual', ['first_name' => 'Vee', 'last_name' => "Cee$tag$stamp", 'contact_sub_type' => ['MAS_Rep']]);
    $repId = $mk('Individual', ['first_name' => 'Rhea', 'last_name' => "Rep$tag$stamp"]);

    $caseId = (int) \Civi\Api4\CiviCase::create(false)
        ->addValue('case_type_id:name', $caseType)
        ->addValue('subject', "Case $tag $stamp")
        ->addValue('status_id:name', $createStatus)
        ->addValue('start_date', date('Y-m-d'))
        ->addValue('contact_id', $orgId)
        ->execute()->first()['id'];
    $createdCases[] = $caseId;

    // Roles AFTER creation, as the coordinator does it. See the file docblock:
    // this means the on-create rule arms with no client rep on the case, which
    // is deliberate.
    \Civi\Api4\Relationship::create(false)
        ->addValue('contact_id_a', $vcId)->addValue('contact_id_b', $orgId)
        ->addValue('relationship_type_id', $coordType)->addValue('case_id', $caseId)
        ->addValue('is_active', true)->execute();
    \Civi\Api4\Relationship::create(false)
        ->addValue('contact_id_a', $repId)->addValue('contact_id_b', $orgId)
        ->addValue('relationship_type_id', $repType)->addValue('case_id', $caseId)
        ->addValue('is_active', true)->execute();

    return ['case' => $caseId, 'org' => $orgId];
};

try {
    $a = $makeCase('A', 'service_request', 'Request RCS');
    $b = $makeCase('B', 'service_request', 'Open');
    $d = $makeCase('D', 'service_request', 'Open');
    $e = $makeCase('E', 'service_request', 'RCS Completed');
    $f = $makeCase('F', 'project', 'Awaiting VC Project Definition');

    note("Fixtures: A(case={$a['case']}) B(case={$b['case']}) D(case={$d['case']}) "
        . "E(case={$e['case']}) F(case={$f['case']})");
    note('');

    // --- A: the defect ------------------------------------------------------
    note('A: SR created directly at "Request RCS" (manual intake — the defect)');
    check('A1: case ends AT "Request RCS" (coordinator\'s choice preserved)', $statusOf($a['case']), 'Request RCS');

    $aOnCreate = $firings($onCreateId, $a['case']);
    check('A2: armed EXACTLY once by the on-create rule', $aOnCreate, 1);

    $aQueue = $queueRows($a['case']);
    check('A3a: exactly two chases queued (the 21- and 42-day pair)', count($aQueue), 2);
    $offsets = [];
    foreach ($aQueue as $release) {
        $offsets[] = (int) round((strtotime($release) - time()) / 86400);
    }
    sort($offsets);
    check('A3b: queued at +21 and +42 days', $offsets, [21, 42]);

    check('A4: transition rule did NOT also arm (no double-arming)', $firings($transitionId, $a['case']), 0);

    // --- B: the control -----------------------------------------------------
    note('B: SR created at "Ongoing" then transitioned (web-form path)');
    \Civi\Api4\CiviCase::update(false)
        ->addWhere('id', '=', $b['case'])->addValue('status_id:name', 'Request RCS')->execute();
    check('B0: case is AT "Request RCS"', $statusOf($b['case']), 'Request RCS');
    $bTransition = $firings($transitionId, $b['case']);
    if ($bTransition > 0) {
        pass("B1: transition rule armed (fired $bTransition time(s)) — the proven path still works");
    } else {
        fail('B1: transition rule armed', 'the web-form path itself did not arm, so this run cannot '
            . 'interpret A either — the rule or its conditions are broken');
    }
    check('B2: on-create rule did NOT also arm (no double-arming)', $firings($onCreateId, $b['case']), 0);

    // --- D: no over-reach ---------------------------------------------------
    note('D: SR created at "Ongoing" and left alone');
    check('D1: still at "Ongoing"', $statusOf($d['case']), 'Open');
    check('D2: on-create rule NOT armed', $firings($onCreateId, $d['case']), 0);
    check('D3: transition rule NOT armed', $firings($transitionId, $d['case']), 0);
    check('D4: nothing queued', count($queueRows($d['case'])), 0);

    // --- E: a non-arming creation status ------------------------------------
    note('E: SR created at "RCS Completed"');
    check('E1: status untouched', $statusOf($e['case']), 'RCS Completed');
    check('E2: on-create rule NOT armed', $firings($onCreateId, $e['case']), 0);
    check('E3: nothing queued', count($queueRows($e['case'])), 0);

    // --- F: case-type guard -------------------------------------------------
    note('F: Project created at "Awaiting VC Project Definition"');
    check('F1: status untouched', $statusOf($f['case']), 'Awaiting VC Project Definition');
    check('F2: on-create rule NOT armed', $firings($onCreateId, $f['case']), 0);
    check('F3: transition rule NOT armed', $firings($transitionId, $f['case']), 0);

    // --- G: a genuine RE-entry must still arm -------------------------------
    note('G: case A leaves "Request RCS" and is asked again');
    \Civi\Api4\CiviCase::update(false)
        ->addWhere('id', '=', $a['case'])->addValue('status_id:name', 'RCS Completed')->execute();
    check('G1: A moved off to "RCS Completed"', $statusOf($a['case']), 'RCS Completed');
    \Civi\Api4\CiviCase::update(false)
        ->addWhere('id', '=', $a['case'])->addValue('status_id:name', 'Request RCS')->execute();
    check('G2: A is back at "Request RCS"', $statusOf($a['case']), 'Request RCS');

    $aTransitionAfter = $firings($transitionId, $a['case']);
    if ($aTransitionAfter > 0) {
        pass("G3: re-entry armed again via the TRANSITION rule (fired $aTransitionAfter time(s)): "
            . 'still once per ENTRY, not once per case');
    } else {
        fail(
            'G3: re-entry armed again via the transition rule',
            'the transition rule never fired for a real re-entry — a client who returned one form and '
            . 'was later asked again would silently never be chased'
        );
    }
    check('G4: on-create rule still fired only once (creation happens once, ever)', $firings($onCreateId, $a['case']), 1);
} catch (\Throwable $ex) {
    fail('rcs chase arming', get_class($ex) . ': ' . $ex->getMessage());
} finally {
    note('');
    note('Cleanup...');
    foreach ($createdCases as $id) {
        // Queue rows first: once the case is gone the blob can no longer be
        // matched back to it, and the row would sit in the queue until its
        // release_time (up to 42 days) before self-clearing.
        foreach (array_keys($queueRows((int) $id)) as $qid) {
            \CRM_Core_DAO::executeQuery("DELETE FROM civicrm_queue_item WHERE id = %1", [1 => [$qid, 'Integer']]);
        }
        // The rule log is an audit trail of real business events; rows pointing
        // at a deleted throwaway case are noise in it, so this run removes only
        // its own.
        \CRM_Core_DAO::executeQuery(
            "DELETE FROM civirule_rule_log WHERE entity_table = 'civicrm_case' AND entity_id = %1",
            [1 => [(int) $id, 'Integer']]
        );
        \Civi\Api4\CiviCase::delete(false)->addWhere('id', '=', $id)->execute();
    }
    foreach (array_unique(array_filter($createdContacts)) as $id) {
        \Civi\Api4\Contact::delete(false)->addWhere('id', '=', $id)->setUseTrash(false)->execute();
    }
}

// --- Summary -----------------------------------------------------------------

note('');
if (T::$failures) {
    note('RESULT: RED — ' . count(T::$failures) . ' failure(s), ' . T::$passes . ' pass(es)');
    foreach (T::$failures as $f) {
        note("  - $f");
    }
    exit(1);
}
note('RESULT: GREEN — all ' . T::$passes . ' assertions passed');
exit(0);

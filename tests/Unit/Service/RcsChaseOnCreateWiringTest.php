<?php

namespace Civi\Mascode\Test\Unit\Service;

use Civi\Mascode\Test\TestCase;

/**
 * A tripwire for the manual-intake RCS chase (mas_lifecycle_rcs_chase_on_create).
 *
 * WHAT THIS IS AND IS NOT
 * This is NOT a behavioural test — it reads LifecycleRuleProvisioner, the
 * upgrader and the CiviRules trigger registration as TEXT. It cannot be
 * anything else: provisioning a rule needs CRM_Civirules_BAO_* and API4, none
 * of which exist in CI, where the pipeline installs Composer packages only and
 * no CiviCRM. The real behavioural proof is tests/Live/RcsChaseArmingTest.php,
 * which builds five real cases and checks the rule log and the delayed-action
 * queue — but that cannot run in CI, so without this file CI has no view of the
 * feature at all.
 *
 * Source-matching is a blunt instrument and normally a smell. It earns its
 * place here on the same terms as tests/Unit/Security/AfformArgGuardWiringTest.php
 * and tests/Unit/Event/ClientRepWiringTest.php: the alternative is no CI
 * coverage, and every regression it catches is SILENT. Nothing errors and
 * nothing looks wrong — a client who asked MAS for help simply never gets
 * chased, which is exactly the defect this feature fixed and is invisible until
 * someone audits the queue months later.
 *
 * THE FOUR SILENT FAILURES PINNED HERE
 *   1. The rule built on `changed_case` instead of `mas_new_case`. It would then
 *      be a duplicate of the rule that already exists and would still never arm
 *      manual intake, because there is no transition to see.
 *   2. `case_status_changed` added to its condition set. At creation there is no
 *      previous value, so that condition can never be satisfied and the rule
 *      silently never matches.
 *   3. The description losing the MODE_PHRASES phrase. setLifecycleEmailMode()
 *      rewrites descriptions by EXACT-phrase match, so neutral wording makes the
 *      propose/auto flip skip this rule and the CiviRules UI then advertises the
 *      wrong send mode.
 *   4. The provisioning call being reachable from only ONE of the two entry
 *      points. Existing installs get the rule from `cv upgrade:db`; a brand-new
 *      `cv ext:enable` stamps schema_version straight to the newest revision so
 *      upgrade steps never run, and only scripts/create-rcs-chase-rule.php
 *      provisions it. Dropping either leaves a whole class of environment with
 *      the majority intake path unchased.
 *
 * NOTE the annotation: @coversNothing, NOT @covers. The @covers form makes
 * PHPUnit resolve the named class to build a coverage map, which autoloads the
 * provisioner and the CiviCRM classes it references — classes CI does not have.
 * That aborts the WHOLE suite before a test runs, and it does NOT reproduce
 * locally, where buildkit has CiviCRM bootstrapped. See docs/TESTING.md.
 *
 * @coversNothing
 */
class RcsChaseOnCreateWiringTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';
    private const PROVISIONER = self::ROOT . '/Civi/Mascode/Service/LifecycleRuleProvisioner.php';
    private const UPGRADER = self::ROOT . '/CRM/Mascode/Upgrader.php';
    private const TRIGGERS_JSON = self::ROOT . '/Civi/Mascode/CiviRules/triggers.json';
    private const BOOTSTRAP_SCRIPT = self::ROOT . '/scripts/create-rcs-chase-rule.php';

    private function contents(string $path, string $whatIfMissing): string
    {
        $this->assertFileExists($path, $whatIfMissing);
        return (string) file_get_contents($path);
    }

    /**
     * Extract one method body, so an assertion about a specific behaviour cannot
     * be satisfied by a coincidentally similar line elsewhere in an 800-line
     * file. Whole-file greps are the main way a source tripwire silently stops
     * testing what its message claims — "changed_case" and the condition names
     * appear in the transition-based sibling too, a few dozen lines away.
     */
    private function methodBody(string $source, string $signature, string $missingMessage): string
    {
        $start = strpos($source, $signature);
        $this->assertNotFalse($start, $missingMessage);

        // Up to the next method declaration at class-body indentation.
        //
        // The STATIC forms are not optional here, and leaving them out is a
        // silent no-op rather than an error: every method in
        // LifecycleRuleProvisioner is `public static` or `private static`, so a
        // delimiter list of only the non-static forms matches nothing, $end
        // stays false, and the "body" becomes signature-to-EOF — 890 of 1050
        // lines for the first method. Every assertion below then greps most of
        // the file and passes on text belonging to a different rule. That is
        // the exact failure this helper exists to prevent, so the list below
        // covers all six visibility/static combinations.
        $delimiters = [];
        foreach (['public', 'protected', 'private'] as $visibility) {
            $delimiters[] = "\n    $visibility function ";
            $delimiters[] = "\n    $visibility static function ";
        }

        $end = false;
        foreach ($delimiters as $next) {
            $at = strpos($source, $next, $start + 1);
            if ($at !== false && ($end === false || $at < $end)) {
                $end = $at;
            }
        }
        $this->assertNotFalse(
            $end,
            "methodBody() found no method following '$signature'. If that is genuinely the last method "
            . 'in the file, extend the delimiter list rather than letting the body run to EOF — an '
            . 'over-captured body makes every assertion on it grep unrelated code and pass for the '
            . 'wrong reason.'
        );
        return substr($source, $start, $end - $start);
    }

    public function testCreatedAtStatusBuilderUsesTheCreateTrigger(): void
    {
        $body = $this->methodBody(
            $this->contents(self::PROVISIONER, 'LifecycleRuleProvisioner is gone.'),
            'private static function ensureCreatedAtStatusChaseRule(',
            'ensureCreatedAtStatusChaseRule() is gone. It is the only builder that arms a chase on case '
            . 'CREATION; without it a Service Request opened directly at "Request RCS" is never chased.'
        );

        $this->assertStringContainsString(
            "WHERE name = 'mas_new_case'",
            $body,
            'The created-at-status builder no longer selects the mas_new_case trigger. On changed_case it '
            . 'would duplicate mas_lifecycle_rcs_chase and STILL never arm manual intake, because a case '
            . 'created at the status never transitions into it.'
        );
        // Match the SELECT specifically rather than the bare word: the docblock
        // legitimately mentions changed_case when contrasting the two rules, and
        // a whole-body grep would fire on a comment.
        $this->assertStringNotContainsString(
            "WHERE name = 'changed_case'",
            $body,
            'The created-at-status builder selects the changed_case trigger. It must arm on creation, '
            . 'not on a transition — see tests/Live/RcsChaseArmingTest.php scenarios A4 and B2.'
        );
    }

    public function testCreatedAtStatusBuilderOmitsTheStatusChangedCondition(): void
    {
        $body = $this->methodBody(
            $this->contents(self::PROVISIONER, 'LifecycleRuleProvisioner is gone.'),
            'private static function ensureCreatedAtStatusChaseRule(',
            'ensureCreatedAtStatusChaseRule() is gone.'
        );

        $this->assertStringNotContainsString(
            'case_status_changed',
            $body,
            'case_status_changed is back in the created-at-status condition set. At creation there is no '
            . 'previous value for it to compare against, so the rule would silently never match and the '
            . 'chase would go back to missing every manually-created Service Request.'
        );
        // The two conditions it MUST carry, matched independently so a quote-style
        // change or a phpcbf run does not break the tripwire.
        foreach (['case_type', 'case_status'] as $needed) {
            $this->assertStringContainsString(
                "'$needed'",
                $body,
                "The created-at-status condition set no longer resolves '$needed'. It must carry both "
                . 'case_type and case_status — dropping case_type would arm this chase on Project '
                . 'creation too.'
            );
        }
    }

    /**
     * N1: the NULL-link condition must be written FIRST.
     *
     * writeConditions() assigns ascending weights in array order, and
     * CRM_Civirules_Engine::areConditionsValid() ignores only the FIRST
     * condition's link — a later NULL link falls to its switch default, logs
     * "invalid condition_link operator" and forces the whole rule FALSE. So
     * swapping the two array entries silently kills the rule with no runtime
     * error. This is not hypothetical: on the dev clone
     * mas_lifecycle_vc_close_chase carries its NULL-link case_type last and is
     * dead there.
     */
    public function testTheNullLinkConditionIsWrittenFirst(): void
    {
        $body = $this->methodBody(
            $this->contents(self::PROVISIONER, 'LifecycleRuleProvisioner is gone.'),
            'private static function ensureCreatedAtStatusChaseRule(',
            'ensureCreatedAtStatusChaseRule() is gone.'
        );

        $caseTypeAt = strpos($body, "condIds['case_type']");
        $caseStatusAt = strpos($body, "condIds['case_status']");
        $this->assertNotFalse($caseTypeAt, 'The case_type condition row is gone from the builder.');
        $this->assertNotFalse($caseStatusAt, 'The case_status condition row is gone from the builder.');
        $this->assertLessThan(
            $caseStatusAt,
            $caseTypeAt,
            'case_type (condition_link NULL) is no longer written BEFORE case_status (link AND). '
            . 'writeConditions() weights them in array order and the engine only exempts the first '
            . "condition's link, so this ordering swap makes the rule silently never match."
        );

        // And the links themselves must still be NULL-then-AND.
        $nullLinkAt = strpos($body, 'case_type_id' . "' => [\$caseTypeId]]), null]");
        $this->assertNotFalse(
            $nullLinkAt,
            'The case_type condition no longer passes a NULL condition_link. The first condition must '
            . 'have no link operator.'
        );
    }

    public function testOnCreateRuleDescriptionCarriesTheModeFlipPhrase(): void
    {
        $source = $this->contents(self::PROVISIONER, 'LifecycleRuleProvisioner is gone.');
        $body = $this->methodBody(
            $source,
            'public static function ensureRcsChaseOnCreateRule(',
            'ensureRcsChaseOnCreateRule() is gone — nothing provisions the manual-intake RCS chase.'
        );

        // MODE_PHRASES keys the propose/auto flip on this exact string.
        $this->assertStringContainsString(
            'auto-mode (sent immediately)',
            $body,
            'The on-create rule description no longer carries the exact phrase '
            . '"auto-mode (sent immediately)". setLifecycleEmailMode() rewrites descriptions by '
            . 'exact-phrase match against MODE_PHRASES, so the flip would skip this rule and the '
            . 'CiviRules UI would advertise the wrong send mode.'
        );
        $this->assertStringContainsString(
            "'auto-mode (sent immediately)'",
            $source,
            'MODE_PHRASES no longer contains the auto phrasing this rule description depends on.'
        );
    }

    public function testTheCreateTriggerItRequiresIsRegistered(): void
    {
        $json = json_decode(
            $this->contents(self::TRIGGERS_JSON, 'Civi/Mascode/CiviRules/triggers.json is gone.'),
            true
        );
        $this->assertIsArray($json, 'triggers.json is not valid JSON.');

        $names = array_column($json, 'name');
        $this->assertContains(
            'mas_new_case',
            $names,
            'mas_new_case is no longer registered in triggers.json. ensureCreatedAtStatusChaseRule() '
            . 'requires that trigger row and throws without it, so the manual-intake chase would fail '
            . 'to provision. Note this registration does NOT happen on `cv flush` — it needs an '
            . 'install/upgrade.'
        );

        $byName = array_column($json, null, 'name');
        $this->assertSame(
            'create',
            $byName['mas_new_case']['op'] ?? null,
            'mas_new_case is no longer op=create. Arming the manual-intake chase depends on it firing '
            . 'when the case is created.'
        );
        $this->assertSame(
            'Case',
            $byName['mas_new_case']['object_name'] ?? null,
            'mas_new_case is no longer a Case trigger.'
        );
    }

    public function testBothProvisioningEntryPointsCallIt(): void
    {
        // Existing installs: cv upgrade:db.
        $this->assertStringContainsString(
            'ensureRcsChaseOnCreateRule',
            $this->contents(self::UPGRADER, 'CRM/Mascode/Upgrader.php is gone.'),
            'No upgrade step provisions the manual-intake RCS chase, so `cv upgrade:db` would leave '
            . 'production without it — the rule would exist in code and nowhere else.'
        );

        // Fresh installs: upgrade steps never run, because ext:enable stamps
        // schema_version straight to the newest revision.
        $this->assertStringContainsString(
            'ensureRcsChaseOnCreateRule',
            $this->contents(self::BOOTSTRAP_SCRIPT, 'scripts/create-rcs-chase-rule.php is gone.'),
            'The fresh-environment bootstrap script no longer provisions the manual-intake chase. A '
            . 'brand-new `cv ext:enable` stamps schema_version to the newest revision, so upgrade '
            . 'steps never run and this script is the ONLY thing that would create the rule there.'
        );
    }
}

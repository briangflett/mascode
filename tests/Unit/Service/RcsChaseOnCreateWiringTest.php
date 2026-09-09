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
        // Up to the next method declaration at class-body indentation. Accepts
        // protected/private too, so making a method non-public does not silently
        // swallow the rest of the class into one "body".
        $end = false;
        foreach (["\n    public function ", "\n    protected function ", "\n    private function "] as $next) {
            $at = strpos($source, $next, $start + 1);
            if ($at !== false && ($end === false || $at < $end)) {
                $end = $at;
            }
        }
        return $end === false ? substr($source, $start) : substr($source, $start, $end - $start);
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
        $this->assertStringNotContainsString(
            'changed_case',
            $body,
            'The created-at-status builder references changed_case. It must arm on creation, not on a '
            . 'transition — see tests/Live/RcsChaseArmingTest.php scenarios A4 and B2.'
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
        // The two conditions it MUST carry: the right case type and the right status.
        $this->assertStringContainsString(
            "foreach (['case_type', 'case_status'] as \$n)",
            $body,
            'The created-at-status condition set changed shape. It must resolve exactly case_type and '
            . 'case_status — dropping case_type would arm this chase on Project creation too.'
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

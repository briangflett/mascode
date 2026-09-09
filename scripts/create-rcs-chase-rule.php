<?php

/**
 * Creates the mas_lifecycle_rcs_chase rule: a Service Request enters
 * "Request RCS" → client_rep chased at 21/42 days until the RCS form returns
 * (which moves the SR to "RCS Completed" and cancels the pending chases).
 *
 * The only lifecycle chase on the service_request case type; all its siblings
 * are on project. Until task #931 this script BUILT the rule inline, with no
 * ensure*() method and no upgrade_NNNN caller, so `cv upgrade:db` never
 * provisioned it and it reached an environment only when someone ran this file.
 * It is now a thin wrapper like its siblings, and CRM_Mascode_Upgrader::
 * upgrade_5011() provisions it on existing installs.
 *
 * Send mode (immediate vs. a reviewable draft queued for click-send) is NOT
 * restated in this docblock on purpose — it lives in the action_params
 * LifecycleRuleProvisioner writes, and moves per environment via
 * tools/set_lifecycle_email_mode.php or the CiviRules action-config form.
 *
 * Provisions BOTH halves of the chase, because "a Service Request reaches
 * Request RCS" happens two structurally different ways:
 *   - mas_lifecycle_rcs_chase           — the case TRANSITIONS into the status
 *     (web-form intake: created at "Ongoing", advanced by the ask email).
 *   - mas_lifecycle_rcs_chase_on_create — the case is CREATED already at the
 *     status (manual intake in the CiviCRM "New Case" UI, which produces no
 *     transition at all and so armed nothing until upgrade_5012).
 * Both are needed for the chase to cover real intake; provisioning only the
 * first leaves the majority path silently unchased.
 *
 * Fresh-environment bootstrap only. Thin wrapper around LifecycleRuleProvisioner;
 * idempotent. Run register-lifecycle-email-action.php first on a brand-new
 * environment.
 *
 * This script is why the upgrade step is not the whole story: a brand-new
 * `cv ext:enable` stamps schema_version straight to the newest revision, so
 * upgrade_NNNN steps never run on a clean install and anything provisioned
 * ONLY there is silently absent. Both rules must therefore be reachable from
 * here as well as from CRM_Mascode_Upgrader.
 *
 * Usage: cv scr scripts/create-rcs-chase-rule.php --user=<admin>
 */

$p = \Civi\Mascode\Service\LifecycleRuleProvisioner::class;

echo json_encode([
    'mas_lifecycle_rcs_chase' => $p::ensureRcsChaseRule(),
    'mas_lifecycle_rcs_chase_on_create' => $p::ensureRcsChaseOnCreateRule(),
], JSON_PRETTY_PRINT) . "\n";

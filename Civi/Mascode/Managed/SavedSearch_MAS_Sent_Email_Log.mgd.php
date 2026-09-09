<?php

declare(strict_types=1);

/**
 * "MAS - Sent Email Log": every outbound email CiviCRM recorded, in one list.
 *
 * Why it exists: CiviCRM's outbound mail does not pass through the
 * info@masadvise.org mailbox, so that mailbox's Sent folder is not a record of
 * what CiviCRM sent. CiviCRM's own record of a send is an activity, but it is
 * spread across four activity types with no combined view. This search is that
 * view.
 *
 * Grain is one row per message, not per recipient — per-recipient delivery,
 * bounce and open data for bulk sends already lives in CiviMail's reports.
 * Recipients are therefore summarised as "first recipient + count" rather than
 * listed: real bulk sends here reach ~4,000 addresses, and concatenating those
 * into one table cell puts ~80KB of names in a single <td>.
 *
 * Access: the display sets acl_bypass, which means core REQUIRES it to be
 * loaded through an Afform the viewer may access — the bare SearchKit route
 * civicrm/search#/display/... returns Access denied. afformMASSentEmailLog is
 * that Afform and carries the gate ("edit all contacts"). This matters here
 * because production's WordPress contributor role — the Volunteer Consultants —
 * holds view_all_activities and view_all_contacts, so without acl_bypass every
 * VC could read this whole log from the bare route. Consequence to respect: if
 * this display is ever embedded on a second, less-gated Afform, that Afform
 * becomes the security boundary. See ang/README.md §"Security: staff-only
 * forms".
 *
 * Honest limit on reading this as an audit log: it lists what CiviCRM
 * *recorded*, not what was *delivered*. Mail sent by a path that writes no
 * activity — most CiviRules "send email" actions unless configured to record,
 * password resets, some system notifications — leaves no trace here. Delivery
 * truth lives with the mail relay, not CiviCRM.
 *
 * Types included, and what writes them:
 *   Email                 core Contact → Send Email
 *   Bulk Email            CiviMail mailing
 *   Sent Automated Email  LifecycleMailer (auto mode, and sent drafts)
 *   Reminder Sent         scheduled reminders with record_activity on
 * "Draft Email - Needs Review" is excluded: a draft has not been sent. Once a
 * draft is sent, LifecycleMailer writes a separate "Sent Automated Email".
 * If CiviContribute invoicing is ever enabled, add "Emailed Invoice" — it is
 * a genuine outbound type and currently has zero rows, so it is omitted.
 */
return [
  [
    'name' => 'SavedSearch_MAS_Sent_Email_Log',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    // 'always', not the 'unmodified' used by every sibling search here: nobody
    // is meant to hand-edit this one, and under 'unmodified' a single column
    // drag in the SearchKit UI would mark the row modified and permanently stop
    // cv flush from re-applying this file — dev and prod would then diverge
    // with nothing surfacing it. The cost, accepted: a UI tweak is silently
    // reverted on the next flush.
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'MAS_Sent_Email_Log',
        'label' => 'MAS - Sent Email Log',
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'activity_date_time',
            'activity_type_id:label',
            'subject',
            'status_id:label',
            'case_id',
            'case_id.Cases_SR_Projects_.MAS_SR_Case_Code',
            'case_id.Projects.MAS_Project_Case_Code',
            // GROUP_FIRST, not GROUP_CONCAT: see the docblock on cell size.
            // The explicit ORDER BY is load-bearing — GROUP_FIRST renders as
            // SUBSTRING_INDEX(GROUP_CONCAT(...), ...), so without it the value
            // is whichever row the join plan happens to read first and can move
            // when the plan changes. Alphabetical is arbitrary but stable.
            'GROUP_FIRST(Activity_ActivityContact_Contact_01.display_name ORDER BY Activity_ActivityContact_Contact_01.display_name ASC) AS recipient_first',
            // DISTINCT on the contact id, so the two bridge joins below cannot
            // inflate the count by cross-multiplying targets against sources.
            'COUNT(DISTINCT Activity_ActivityContact_Contact_01.id) AS recipient_count',
            'GROUP_FIRST(Activity_ActivityContact_Contact_02.display_name ORDER BY Activity_ActivityContact_Contact_02.display_name ASC) AS sender',
          ],
          'orderBy' => [],
          'where' => [
            [
              'activity_type_id:name',
              'IN',
              ['Email', 'Bulk Email', 'Sent Automated Email', 'Reminder Sent'],
            ],
            ['is_deleted', '=', FALSE],
            ['is_test', '=', FALSE],
          ],
          // One row per message. Without this the target join multiplies a
          // bulk send into one row per recipient.
          'groupBy' => ['id'],
          'join' => [
            // LEFT, not INNER: an activity with no target rows at all — a type
            // that writes none, or a hard-deleted contact whose
            // civicrm_activity_contact row cascaded away — must still appear in
            // the log rather than drop silently out of it. GROUP_FIRST over
            // zero targets yields NULL, which renders as an empty cell.
            [
              'Contact AS Activity_ActivityContact_Contact_01',
              'LEFT',
              'ActivityContact',
              ['id', '=', 'Activity_ActivityContact_Contact_01.activity_id'],
              ['Activity_ActivityContact_Contact_01.record_type_id:name', '=', '"Activity Targets"'],
            ],
            // Sender needs its own join. Activity.source_contact_id is a
            // pseudo-field with no implicit-join support, so
            // source_contact_id.display_name is silently dropped from the
            // result — no error, the key is simply absent. Reproduced on
            // 6.16.1; the cause is the pseudo-field, not that version.
            [
              'Contact AS Activity_ActivityContact_Contact_02',
              'LEFT',
              'ActivityContact',
              ['id', '=', 'Activity_ActivityContact_Contact_02.activity_id'],
              ['Activity_ActivityContact_Contact_02.record_type_id:name', '=', '"Activity Source"'],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SearchDisplay_MAS_Sent_Email_Log_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'MAS_Sent_Email_Log_Table',
        'label' => 'MAS - Sent Email Log Table',
        'saved_search_id.name' => 'MAS_Sent_Email_Log',
        'type' => 'table',
        // Forces this display to be reached through a permission-gated Afform
        // rather than the bare civicrm/search route. See the docblock.
        'acl_bypass' => TRUE,
        'settings' => [
          'description' => NULL,
          'sort' => [
            ['activity_date_time', 'DESC'],
          ],
          'limit' => 50,
          'pager' => ['show_count' => TRUE, 'expose_limit' => TRUE],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'activity_date_time',
              'label' => 'Sent',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'activity_type_id:label',
              'label' => 'Type',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => 'Subject',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'sender',
              'label' => 'Sent By',
              'sortable' => TRUE,
            ],
            // One recipient, alphabetically first — not "the" recipient. For
            // the 1:1 mail that dominates this log it is the entire recipient
            // list; for a bulk send the adjacent count is what carries the
            // meaning. Header is deliberately "A recipient", not "To", so the
            // cell does not read as the full addressee list.
            [
              'type' => 'field',
              'key' => 'recipient_first',
              'label' => 'A recipient',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'recipient_count',
              'label' => 'Recipients',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => 'Status',
              'sortable' => TRUE,
            ],
            // Case code, linked to Manage Case. A case carries the SR code or
            // the Project code, never both, so empty_value expresses the
            // either/or directly; SearchKit checks it before rewrite and runs
            // token replacement on it. A concatenating rewrite would render
            // "S26001P26038" if a converted case ever left both populated.
            // No cid needed: CaseView derives the client from the case id.
            [
              'type' => 'field',
              'key' => 'case_id.Cases_SR_Projects_.MAS_SR_Case_Code',
              'empty_value' => '[case_id.Projects.MAS_Project_Case_Code]',
              'label' => 'Case',
              'link' => [
                'path' => 'civicrm/contact/view/case?action=view&reset=1&id=[case_id]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '_blank',
              ],
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => ['name'],
    ],
  ],
];

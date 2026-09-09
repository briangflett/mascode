<?php

declare(strict_types=1);

/**
 * "MAS - Sent Email Log": every outbound email CiviCRM recorded, in one list.
 *
 * Background: production relays through Amazon SES rather than the
 * info@masadvise.org Microsoft 365 mailbox, so nothing CiviCRM sends ever
 * appears in that mailbox's Sent folder. CiviCRM's own record of a send is an
 * activity, but it is spread across four activity types with no combined view.
 * This search is that view.
 *
 * Grain is one row per message, not per recipient — per-recipient delivery,
 * bounce and open data for bulk sends already lives in CiviMail's reports, and
 * duplicating it here would bury the 1:1 mail. Bulk rows therefore list every
 * recipient in the Recipients cell (103 on a typical newsletter); that is
 * deliberate, since an audit log that hides who was written to answers the
 * wrong question.
 *
 * Coverage caveat worth knowing before trusting this as complete: it lists what
 * CiviCRM *recorded*, not what SES *delivered*. Mail sent by a path that writes
 * no activity — most CiviRules "send email" actions unless configured to
 * record, password resets, some system notifications — leaves no trace here.
 * The authoritative per-message log would be an SES Configuration Set event
 * destination, which is not currently configured.
 *
 * Types included, and what writes them:
 *   Email                 core Contact → Send Email
 *   Bulk Email            CiviMail mailing
 *   Sent Automated Email  LifecycleMailer (auto mode, and sent drafts)
 *   Reminder Sent         scheduled reminders with record_activity on
 * "Draft Email - Needs Review" is excluded: a draft has not been sent. Once a
 * draft is sent, LifecycleMailer writes a separate "Sent Automated Email".
 */
return [
  [
    'name' => 'SavedSearch_MAS_Sent_Email_Log',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
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
            'case_id.subject',
            'case_id.Cases_SR_Projects_.MAS_SR_Case_Code',
            'case_id.Projects.MAS_Project_Case_Code',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_01.display_name) AS recipients',
            'COUNT(DISTINCT Activity_ActivityContact_Contact_01.id) AS recipient_count',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_02.display_name) AS sender',
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
            // LEFT, not INNER: an email whose target contact was since deleted
            // must still appear in the log rather than vanish from it.
            [
              'Contact AS Activity_ActivityContact_Contact_01',
              'LEFT',
              'ActivityContact',
              ['id', '=', 'Activity_ActivityContact_Contact_01.activity_id'],
              ['Activity_ActivityContact_Contact_01.record_type_id:name', '=', '"Activity Targets"'],
            ],
            // Sender needs its own join: Activity.source_contact_id resolves as
            // a bare id, but the implicit join source_contact_id.display_name
            // is silently dropped from the result on CiviCRM 6.16 — verified
            // against production, not assumed.
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
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'MAS_Sent_Email_Log_Table',
        'label' => 'MAS - Sent Email Log Table',
        'saved_search_id.name' => 'MAS_Sent_Email_Log',
        'type' => 'table',
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
            ],
            [
              'type' => 'field',
              'key' => 'recipients',
              'label' => 'Recipients',
            ],
            [
              'type' => 'field',
              'key' => 'recipient_count',
              'label' => '#',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => 'Status',
              'sortable' => TRUE,
            ],
            // Case code, linked to Manage Case. Same link shape as the Drafts
            // tile: CaseView derives the client contact from the case id, so
            // no cid is needed (SearchKit would not supply one). One of the
            // two code fields is populated depending on case type; the rewrite
            // concatenates so whichever exists shows.
            [
              'type' => 'field',
              'key' => 'case_id.Cases_SR_Projects_.MAS_SR_Case_Code',
              'label' => 'Case',
              'rewrite' => '[case_id.Cases_SR_Projects_.MAS_SR_Case_Code][case_id.Projects.MAS_Project_Case_Code]',
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

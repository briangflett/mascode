<?php

// File: Civi/Mascode/Event/AfformSubmitSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Api4\Contact;
use Civi\Api4\MessageTemplate;
use Civi\Api4\AfformSubmission;
use Civi\Token\TokenProcessor;

class AfformSubmitSubscriber implements EventSubscriberInterface
{
    /**
     * Store entity IDs during form submission processing
     */
    private static array $submissionData = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // Subscriptions with a priority of > 0 happen before the data is saved to the database.
        // Subscriptions with a priority of < 0 happen after the data is saved to the database.
        // Data is saved to the database in
        //     civicrm/ext/afform/core/afform.php:  $dispatcher->addListener('civi.afform.submit',
        //          ['\Civi\Api4\Action\Afform\Submit', 'processGenericEntity'], 0);
        return [
            'civi.afform.submit' => ['onFormSubmit', -100],
        ];
    }

    /**
     * Process form submission to collect entity IDs and create relationships
     *
     * @param \Civi\Afform\Event\AfformSubmitEvent $event
     */
    public function onFormSubmit(AfformSubmitEvent $event): void
    {
        $afform = $event->getAfform();
        $formRoute = $afform['server_route'] ?? null;

        // Check if this is our target form
        if ($formRoute !== 'civicrm/mas-rcs-form') {
            return;
        }

        $entityName = $event->getEntityName();
        $entityId = $event->getEntityId();

        // *** EntityId is null if the entity is being created by this submission ***

        \Civi::log()->debug('AfformSubmitSubscriber: Processing entity', [
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'api_request_class' => get_class($event->getApiRequest()),
            'api_request_methods' => get_class_methods($event->getApiRequest())
        ]);

        // Get or create submission tracking data
        $sessionId = $this->getSessionId();
        if (!isset(self::$submissionData[$sessionId])) {
            self::$submissionData[$sessionId] = [];
        }

        // Store entity IDs based on entity name
        switch ($entityName) {
            case 'Organization1':
                self::$submissionData[$sessionId]['organization_id'] = $entityId;
                break;
            case 'Individual1': // President
                self::$submissionData[$sessionId]['president_id'] = $entityId;
                break;
            case 'Individual2': // Executive Director
                self::$submissionData[$sessionId]['executive_director_id'] = $entityId;
                break;
            case 'Individual3': // Primary Contact
                self::$submissionData[$sessionId]['primary_contact_id'] = $entityId;
                break;
            case 'Case1':
                self::$submissionData[$sessionId]['case_id'] = $entityId;
                // Update case status when processing Case1 (last entity processed)
                $this->updateCaseStatus($sessionId);
                // Send confirmation email
                $this->sendConfirmationEmail($sessionId);
                // Clean up after processing
                unset(self::$submissionData[$sessionId]);
                break;
        }
    }

    /**
     * Get unique session identifier for this submission
     */
    private function getSessionId(): string
    {
        $sessionId = session_id();
        if (!$sessionId) {
            // Fallback if no session (e.g., in testing)
            $sessionId = 'no-session-' . getmypid() . '-' . time();
        }
        return $sessionId;
    }

    /**
     * Update case status to "RCS Completed"
     *
     * @param string $sessionId
     */
    protected function updateCaseStatus(string $sessionId): void
    {
        try {
            $submissionData = self::$submissionData[$sessionId] ?? [];

            if (empty($submissionData['case_id'])) {
                \Civi::log()->warning('AfformSubmitSubscriber: No case ID found for status update', [
                    'session_id' => $sessionId,
                    'submission_data' => $submissionData
                ]);
                return;
            }

            // Get the "RCS Completed" status value
            $caseStatus = \Civi\Api4\OptionValue::get(false)
                ->addWhere('option_group_id:name', '=', 'case_status')
                ->addWhere('label', '=', 'RCS Completed')
                ->addSelect('value')
                ->execute()
                ->first();

            if (!$caseStatus) {
                \Civi::log()->error('AfformSubmitSubscriber: "RCS Completed" case status not found', [
                    'session_id' => $sessionId,
                    'case_id' => $submissionData['case_id']
                ]);
                return;
            }

            // Update the case status
            \Civi\Api4\CiviCase::update(false)
                ->addWhere('id', '=', $submissionData['case_id'])
                ->addValue('status_id', $caseStatus['value'])
                ->execute();

            \Civi::log()->info('AfformSubmitSubscriber: Case status updated to "RCS Completed"', [
                'case_id' => $submissionData['case_id'],
                'status_value' => $caseStatus['value'],
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            \Civi::log()->error('AfformSubmitSubscriber: Exception while updating case status', [
                'session_id' => $sessionId,
                'case_id' => $submissionData['case_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send confirmation email
     *
     * @param string $sessionId
     */
    protected function sendConfirmationEmail(string $sessionId): void
    {
        try {
            $submissionData = self::$submissionData[$sessionId] ?? [];
            $primaryContactId = $submissionData['primary_contact_id'] ?? null;

            if (empty($primaryContactId)) {
                \Civi::log()->warning('AfformSubmitSubscriber: No primary contact ID found for RCS Form', [
                    'session_id' => $sessionId,
                    'submission_data' => $submissionData
                ]);
                return;
            }

            // Get primary contact email details
            $contactDetails = Contact::get(false)
                ->addSelect('display_name', 'email_primary.email')
                ->addWhere('id', '=', $primaryContactId)
                ->execute()
                ->first();
            
            if (empty($contactDetails['email_primary.email'])) {
                \Civi::log()->warning('MAS RCS Form: No email found for contact ' . $primaryContactId);
                return;
            }

            // Get the message template
            $template = MessageTemplate::get(false)
                ->addSelect('msg_subject', 'msg_text', 'msg_html')
                ->addWhere('id', '=', 71)
                ->execute()
                ->first();

            if (!$template) {
                \Civi::log()->warning('MAS RCS Form: Message template 71 not found');
                return;
            }

            // Get the most recent submission for this form
            $submission = AfformSubmission::get(false)
                ->addSelect('id', 'afform_name', 'contact_id', 'data')
                ->addWhere('afform_name', '=', 'afformMASRCSForm')
                ->addOrderBy('id', 'DESC')
                ->setLimit(1)
                ->execute()
                ->first();

            $submissionData = '';
            if ($submission) {
                $submissionData = $this->formatSubmissionData($submission['data'] ?? []);
                \Civi::log()->info('MAS RCS Form: Using submission data', [
                    'submission_id' => $submission['id'],
                    'contact_id' => $submission['contact_id']
                ]);
            }

            // Prepare template content with submission data
            $subject = $template['msg_subject'];
            $textContent = $template['msg_text'] . "\n\n" . $submissionData;
            $htmlContent = $template['msg_html'] . "<br><br>" . nl2br($submissionData);

            // Use TokenProcessor for modern token replacement
            $tokenProcessor = new TokenProcessor(\Civi::dispatcher(), [
                'controller' => __CLASS__,
                'smarty' => FALSE,
                'schema' => ['contactId'],
            ]);

            $tokenProcessor->addMessage('subject', $subject, 'text/plain');
            $tokenProcessor->addMessage('text', $textContent, 'text/plain');
            $tokenProcessor->addMessage('html', $htmlContent, 'text/html');
            $tokenProcessor->addRow(['contactId' => $primaryContactId]);
            $tokenProcessor->evaluate();

            $row = $tokenProcessor->getRow(0);
            $templateContent = [
                'subject' => $row->render('subject'),
                'text' => $row->render('text'),
                'html' => $row->render('html'),
            ];

            // Send to primary contact
            $mailParams = [
                'from' => 'MAS <info@masadvise.org>',
                'toName' => $contactDetails['display_name'],
                'toEmail' => $contactDetails['email_primary.email'],
                'subject' => $templateContent['subject'],
                'text' => $templateContent['text'],
                'html' => $templateContent['html'],
            ];

            \CRM_Utils_Mail::send($mailParams);

            // Send to info@masadvise.org (using same processed content)
            $adminMailParams = [
                'from' => 'MAS <info@masadvise.org>',
                'toName' => 'MAS Admin',
                'toEmail' => 'info@masadvise.org',
                'subject' => $templateContent['subject'],
                'text' => $templateContent['text'],
                'html' => $templateContent['html'],
            ];

            \CRM_Utils_Mail::send($adminMailParams);

            \Civi::log()->info('MAS RCS Form confirmation emails sent successfully');

        } catch (\Exception $e) {
            \Civi::log()->error('Failed to send MAS RCS Form confirmation emails: ' . $e->getMessage());
        }
    }

    /**
     * Format submission data for inclusion in emails
     */
    private function formatSubmissionData(array $data): string
    {
        if (empty($data)) {
            return 'No submission data available.';
        }

        $formatted = '';
        
        foreach ($data as $entityName => $entityData) {
            if (!is_array($entityData)) {
                continue;
            }

            // Add entity section header
            $entityLabel = $this->getEntityLabel($entityName);
            $formatted .= "\n=== {$entityLabel} ===\n";

            foreach ($entityData as $record) {
                if (!is_array($record) || !isset($record['fields'])) {
                    continue;
                }

                foreach ($record['fields'] as $fieldName => $fieldValue) {
                    if ($fieldValue !== null && $fieldValue !== '') {
                        $fieldLabel = $this->getFieldLabel($fieldName);
                        $formatted .= "{$fieldLabel}: {$fieldValue}\n";
                    }
                }
                $formatted .= "\n"; // Separator between records
            }
        }

        return $formatted;
    }

    /**
     * Get user-friendly entity label
     */
    private function getEntityLabel(string $entityName): string
    {
        $labels = [
            'Organization1' => 'Organization Information',
            'Individual1' => 'President/Board Chair',
            'Individual2' => 'Executive Director', 
            'Individual3' => 'Primary Contact',
            'Case1' => 'Request Details',
        ];

        return $labels[$entityName] ?? $entityName;
    }

    /**
     * Get user-friendly field label
     */
    private function getFieldLabel(string $fieldName): string
    {
        $labels = [
            'organization_name' => 'Organization Name',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'job_title' => 'Job Title',
            'street_address' => 'Address',
            'city' => 'City',
            'state_province_id' => 'Province',
            'postal_code' => 'Postal Code',
            'subject' => 'Subject',
            'url' => 'Website',
            'do_not_email' => 'Email Preference',
        ];

        return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

}

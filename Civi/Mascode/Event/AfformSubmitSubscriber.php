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
        $formName = $afform['name'] ?? null;

        // Define forms that should trigger email confirmations
        $emailForms = [
            'civicrm/mas-rcs-form' => 'afformMASRCSForm',
            'civicrm/mas-sasf-form' => 'afformMASSASF',
            'civicrm/mas-sass-form' => 'afformMASSASS'
        ];

        // Check if this is one of our target forms
        if (!isset($emailForms[$formRoute]) || $emailForms[$formRoute] !== $formName) {
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

        // Store form type and entity IDs based on entity name
        self::$submissionData[$sessionId]['form_name'] = $formName;
        self::$submissionData[$sessionId]['form_route'] = $formRoute;
        
        // Handle different form types
        if ($formRoute === 'civicrm/mas-rcs-form') {
            // RCS Form - existing logic
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
        } else {
            // Survey Forms (SASS/SASF) - simpler structure
            switch ($entityName) {
                case 'Organization1':
                    self::$submissionData[$sessionId]['organization_id'] = $entityId;
                    break;
                case 'Individual1': // Primary Contact
                    self::$submissionData[$sessionId]['primary_contact_id'] = $entityId;
                    break;
                case 'Activity1':
                    self::$submissionData[$sessionId]['activity_id'] = $entityId;
                    // Send confirmation email for survey forms (last entity processed)
                    $this->sendConfirmationEmail($sessionId);
                    // Clean up after processing
                    unset(self::$submissionData[$sessionId]);
                    break;
            }
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
            $formName = $submissionData['form_name'] ?? 'Unknown Form';
            $formRoute = $submissionData['form_route'] ?? '';

            if (empty($primaryContactId)) {
                \Civi::log()->warning('AfformSubmitSubscriber: No primary contact ID found for form', [
                    'session_id' => $sessionId,
                    'form_name' => $formName,
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
                \Civi::log()->warning('AfformSubmitSubscriber: No email found for contact', [
                    'contact_id' => $primaryContactId,
                    'form_name' => $formName
                ]);
                return;
            }

            // Get the message template
            $template = MessageTemplate::get(false)
                ->addSelect('msg_subject', 'msg_text', 'msg_html')
                ->addWhere('id', '=', 71)
                ->execute()
                ->first();

            if (!$template) {
                \Civi::log()->warning('AfformSubmitSubscriber: Message template 71 not found', [
                    'form_name' => $formName
                ]);
                return;
            }

            // Get the most recent submission for this form
            $submission = AfformSubmission::get(false)
                ->addSelect('id', 'afform_name', 'contact_id', 'data')
                ->addWhere('afform_name', '=', $formName)
                ->addOrderBy('id', 'DESC')
                ->setLimit(1)
                ->execute()
                ->first();

            $formattedSubmissionData = '';
            if ($submission) {
                $formattedSubmissionData = $this->formatSubmissionData($submission['data'] ?? [], $formRoute);
                \Civi::log()->info('AfformSubmitSubscriber: Using submission data', [
                    'submission_id' => $submission['id'],
                    'contact_id' => $submission['contact_id'],
                    'form_name' => $formName
                ]);
            }

            // Prepare template content with submission data
            $subject = $template['msg_subject'];
            $textContent = $template['msg_text'] . "\n\n" . $formattedSubmissionData;
            $htmlContent = $template['msg_html'] . "<br><br>" . nl2br($formattedSubmissionData);

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

            \Civi::log()->info('AfformSubmitSubscriber: Confirmation emails sent successfully', [
                'form_name' => $formName,
                'primary_contact_id' => $primaryContactId
            ]);

        } catch (\Exception $e) {
            \Civi::log()->error('AfformSubmitSubscriber: Failed to send confirmation emails', [
                'form_name' => $formName ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format submission data for inclusion in emails
     */
    private function formatSubmissionData(array $data, string $formRoute = ''): string
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
            $entityLabel = $this->getEntityLabel($entityName, $formRoute);
            $formatted .= "\n=== {$entityLabel} ===\n";

            foreach ($entityData as $record) {
                if (!is_array($record) || !isset($record['fields'])) {
                    continue;
                }

                foreach ($record['fields'] as $fieldName => $fieldValue) {
                    if ($fieldValue !== null && $fieldValue !== '') {
                        $fieldLabel = $this->getFieldLabel($fieldName, $formRoute);
                        
                        // Format survey answers if they are numeric ratings
                        if ($formRoute !== 'civicrm/mas-rcs-form' && is_numeric($fieldValue) && $fieldValue >= 1 && $fieldValue <= 5) {
                            $scaleLabels = [
                                1 => 'Strongly Disagree',
                                2 => 'Disagree', 
                                3 => 'Neutral',
                                4 => 'Agree',
                                5 => 'Strongly Agree'
                            ];
                            $fieldValue = $fieldValue . ' (' . ($scaleLabels[$fieldValue] ?? 'Unknown') . ')';
                        }
                        
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
    private function getEntityLabel(string $entityName, string $formRoute = ''): string
    {
        if ($formRoute === 'civicrm/mas-rcs-form') {
            // RCS Form labels
            $labels = [
                'Organization1' => 'Organization Information',
                'Individual1' => 'President/Board Chair',
                'Individual2' => 'Executive Director', 
                'Individual3' => 'Primary Contact',
                'Case1' => 'Request Details',
            ];
        } else {
            // Survey Form labels (SASS/SASF)
            $labels = [
                'Organization1' => 'Organization Information',
                'Individual1' => 'Contact Information',
                'Activity1' => 'Survey Responses',
            ];
        }

        return $labels[$entityName] ?? $entityName;
    }

    /**
     * Get user-friendly field label
     */
    private function getFieldLabel(string $fieldName, string $formRoute = ''): string
    {
        // Common field labels for all forms
        $commonLabels = [
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

        // Survey question labels (for SASS/SASF forms)
        $surveyLabels = [
            'q01_mission_clear' => '1. Our mission is clear and understood by all staff and board members',
            'q02_vision_inspiring' => '2. We have an inspiring vision that guides our work',
            'q03_values_guide' => '3. Our organizational values clearly guide our decisions and actions',
            'q04_mission_relevant' => '4. Our mission remains relevant to current community needs',
            'q05_strategic_alignment' => '5. All our activities are clearly aligned with our mission',
            'q06_board_effective' => '6. Our board is effective at providing governance and oversight',
            'q07_roles_clear' => '7. Board and staff roles and responsibilities are clearly defined',
            'q08_policies_current' => '8. We have current and comprehensive governance policies',
            'q09_board_diverse' => '9. Our board reflects the diversity of our community',
            'q10_board_recruitment' => '10. We have effective board recruitment and orientation processes',
            'q11_financial_stable' => '11. Our organization is financially stable',
            'q12_budget_process' => '12. We have a sound budgeting and financial planning process',
            'q13_revenue_diverse' => '13. We have diversified revenue sources',
            'q14_financial_controls' => '14. We have strong financial controls and accountability measures',
            'q15_reserves_adequate' => '15. We maintain adequate financial reserves',
            'q16_programs_effective' => '16. Our programs are effective at achieving intended outcomes',
            'q17_data_collection' => '17. We regularly collect and analyze data on program performance',
            'q18_continuous_improvement' => '18. We use evaluation results for continuous program improvement',
            'q19_program_innovation' => '19. We regularly innovate and adapt our programs',
            'q20_impact_measurement' => '20. We effectively measure and communicate our impact',
            'q21_staff_skilled' => '21. Our staff have the skills and resources needed to do their jobs well',
            'q22_professional_development' => '22. We provide adequate professional development opportunities',
            'q23_succession_planning' => '23. We have effective succession planning and knowledge management',
            'q24_compensation_competitive' => '24. Our compensation and benefits are competitive',
            'q25_performance_management' => '25. We have effective performance management systems',
            'q26_communication_open' => '26. We have open and effective internal communication',
            'q27_culture_positive' => '27. Our organizational culture is positive and supportive',
            'q28_change_adaptable' => '28. We are adaptable and responsive to change',
            'q29_collaboration_strong' => '29. We have strong collaboration across departments/programs',
            'q30_learning_culture' => '30. We have a culture of learning and continuous improvement',
            'q31_stakeholder_engaged' => '31. We effectively engage with our key stakeholders',
            'q32_partnerships_strong' => '32. We have strong partnerships that advance our mission',
            'q33_reputation_positive' => '33. We have a positive reputation in our community',
            'q34_marketing_effective' => '34. Our marketing and communications are effective',
            'q35_advocacy_engaged' => '35. We effectively engage in advocacy and policy work when appropriate'
        ];

        // Check survey labels first for survey forms
        if ($formRoute !== 'civicrm/mas-rcs-form' && isset($surveyLabels[$fieldName])) {
            return $surveyLabels[$fieldName];
        }

        // Check common labels
        if (isset($commonLabels[$fieldName])) {
            return $commonLabels[$fieldName];
        }

        // Default formatting
        return ucwords(str_replace('_', ' ', $fieldName));
    }

}

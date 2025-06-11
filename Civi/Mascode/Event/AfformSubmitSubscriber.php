<?php

// File: Civi/Mascode/Event/AfformSubmitSubscriber.php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Api4\Email;
use Civi\Api4\Contact;

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
            'entity_id' => $entityId
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

            // Send to primary contact
            Email::send(false)
                ->setTemplateId(71)
                ->setContactId($primaryContactId)
                ->execute();

            // Send to info@masadvise.org
            Email::send(false)
                ->setTemplateId(71)
                ->setContactId($primaryContactId)
                ->addValue('to_email', 'info@masadvise.org')
                ->execute();

            \Civi::log()->info('MAS RCS Form confirmation emails sent successfully');

        } catch (\Exception $e) {
            \Civi::log()->error('Failed to send MAS RCS Form confirmation emails: ' . $e->getMessage());
        }
    }
}

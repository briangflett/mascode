<?php

namespace Civi\Mascode\Event;

use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Mascode\Util\CodeGenerator;
use Civi\Api4\Mailing;
use Civi\mascode\CiviRules\Trigger\MailingUnsubscribe;

class CaseEventListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_pre' => ['onPre', 0],
            'hook_civicrm_post' => ['onPost', 0],
            // 'hook_civicrm_caseSummary' => ['onCaseSummary', 0],
        ];
    }

    public function onPre(GenericHookEvent $event): void
    {
        $op = $event->op;
        $objectName = $event->objectName;
        $id = $event->id;
        $params = &$event->params;

        // Do what you previously did in mascode_civicrm_pre()
        // skip this hook for now
        return;
        
        if ($objectName == 'Case' && in_array($op, ['edit', 'create'])) {
            $caseTypeId = $params['case_type_id'] ?? null;
            if (!$caseTypeId) return;
            $caseType = \Civi\Api4\CaseType::get()
                ->addSelect('name')
                ->addWhere('id', '=', $caseTypeId)
                ->execute()
                ->first()['name'] ?? null;
            // If this is the creation of a Service Request or a Project...
            if (($caseType == 'service_request' || $caseType == 'project') && $op == 'create') {
                // Generate the MAS code
                $masCode = CodeGenerator::generate($caseType);
                // Get the Service Request custom field ID
                if ($caseType == 'service_request') {
                    $serviceId = CodeGenerator::getFieldId('mas_parameters', 'sr_code_last');
                } else {
                    $serviceId = CodeGenerator::getFieldId('mas_parameters', 'project_code_last');
                }
                // Update the MAS Case Code
                if ($serviceId) {
                    $params["custom_{$serviceId}"] = $masCode;
                }
            } else {
                // If this is the update of a Service Request...
                if ($caseType == 'service_request' && $op == 'edit') {
                    $caseStatusId = $params['case_status_id'] ?? null;
                    if (!$caseStatusId) return;
                    $caseStatus = \Civi\Api4\OptionValue::get()
                        ->addSelect('name')
                        ->addWhere('option_group_id:name', '=', 'case_status')
                        ->addWhere('value', '=', $caseStatusId)
                        ->execute()
                        ->first()['name'] ?? null;
                    // If this is the update of a Service Request to a status of "Project Created"
                    // Note that for case statuses, the name field is capitalized with spaces
                    if ($caseStatus == 'Project Created') {
                        // Get the pre-existing case information
                        $civiCase = \Civi\Api4\CiviCase::get()
                            ->addSelect('*', 'custom.*', 'case_contact.contact_id')
                            ->addJoin('CaseContact AS case_contact', 'LEFT')
                            ->addWhere('id', '=', $id)
                            ->execute()
                            ->first();
                        // If no related project, create one
                        if (!$civiCase['Cases_SR_Projects_.Related_Project_ID']) {
                            // Generate a new Project case with the appropriate ID
                            self::createProjectCase($civiCase); // Use self:: for static method call
                        }
                    }
                }
            }
        } else {
            if ($objectName === 'Contact' && in_array($op, ['edit', 'create'])) {
                // If editing the URL (website) field, make sure it starts with http://
                if (!empty($params['url']) && !preg_match('#^https?://#i', $params['url'])) {
                    $params['url'] = 'http://' . $params['url'];
                }
            }
        }
    }

    private static function createProjectCase($civiCase)
    {
        $masCode = CodeGenerator::generate('Project');
        $subject = $masCode . ' ' . $civiCase['subject'];
        $nina = 7608;
        $startDate = date('yyyy-mm-dd');
        $practiceArea = $civiCase['Cases_SR_Projects_.Practice_Area'];
        $relatedSR = $civiCase['Cases_SR_Projects_ . MAS_SR_Case_Code'];
        $clientID = $civiCase['case_contact.contact_id'];
        // Logic using API4 to create a 'Project' case
        $civiCase = \Civi\Api4\CiviCase::create(TRUE)
        ->addValue('case_type_id.name', 'project')
        ->addValue('subject', $subject)
        ->addValue('creator_id', $nina)
        ->addValue('start_date', $startDate)
        ->addValue('status_id.name', 'Active')
        ->addValue('Projects.Practice_Area', $practiceArea)
        ->addValue('MAS_Project_Case_Code', $masCode)
        ->addValue('Related_SR_Case_Code', $relatedSR)
        ->addValue(
            'contact_id',
            [
            $clientID,
            ]
        )
        ->execute();
    }

    public function onPost(GenericHookEvent $event): void
    {
        $op = $event->op;
        $objectName = $event->objectName;
        $objectId = $event->objectId;
        $objectRef = &$event->objectRef;

        // Do what you previously did in mascode_civicrm_post()
        // skip this hook for now
        return;
        
        // \Civi::log()->info("hook_civicrm_post fired", [
        //   'op' => $op,
        //   'objectName' => $objectName,
        //   'objectId' => $objectId,
        //   'objectRef' => $objectRef,
        // ]);

        if ($op === 'create' && $objectName === 'SubscriptionHistory') {
        /** @var \CRM_Contact_BAO_SubscriptionHistory $objectRef */
        if (
            $objectRef->status === 'Removed' &&
            $objectRef->method === 'Email'
        ) {
            // \Civi::log()->info("Unsub triggered for contact {$objectRef->contact_id} via group {$objectRef->group_id}");
            
            $trigger = new \Civi\Mascode\CiviRules\Trigger\MailingUnsubscribe();

            // \Civi::log()->info("Calling triggerRuleEvaluation on class: " . get_class($trigger));
            // \Civi::log()->info("Available methods: " . print_r(get_class_methods($trigger), true));

            $trigger->triggerRuleEvaluation($objectRef->contact_id, [
            'contact_id' => $objectRef->contact_id,
            'group_id' => $objectRef->group_id,
            'status' => $objectRef->status,
            'method' => $objectRef->method,
            ]);
        }

        // if ($op === 'create' && $objectName === 'MailingEvent/Unsubscribe') {
        //   $contactId = $objectRef->contact_id ?? null;
        //   $mailingId = $objectRef->mailing_id ?? null;

        //   if (!$contactId || !$mailingId) {
        //     \Civi::log()->warning('Missing contact_id or mailing_id in unsubscribe event.');
        //     return;
        //   }

        //   try {
        //     // Load mailing subject for context (optional)
        //     $mailing = Mailing::get()
        //       ->addSelect('subject')
        //       ->addWhere('id', '=', $mailingId)
        //       ->execute()
        //       ->first();

        //     xdebug_break();
        //     // Trigger the CiviRules rule
        //     $trigger = new MailingUnsubscribe();
        //     $trigger->triggerRuleEvaluation($contactId, [
        //       'contact_id' => $contactId,
        //       'mailing_id' => $mailingId,
        //       'mailing_subject' => $mailing['subject'] ?? '(Unknown)',
        //     ]);
        //   } catch (\Exception $e) {
        //     \Civi::log()->error('Error in PostHook::handle() unsubscribe logic: ' . $e->getMessage());
        //   }
        // }
        }
    }

    // Need to handle onCaseSummary as a traditional hook for now as it is expected a return value
    // 
    // public function onCaseSummary(\Civi\Core\Event\GenericHookEvent $event): void
    // {
    //     xdebug_break();
    //     $caseId = $event->caseID ?? NULL;
    //     if (empty($caseId)) {
    //         return;
    //     }

    //     try {
    //         $case = \Civi\Api4\CiviCase::get()
    //             ->addWhere('id', '=', $caseId)
    //             ->setLimit(1)
    //             ->execute()
    //             ->first();

    //         if (!$case) {
    //             return;
    //         }
    //     } catch (\Exception $e) {
    //         return;
    //     }

    //     $formattedEndDate = !empty($case['end_date'])
    //         ? \CRM_Utils_Date::customFormat($case['end_date'])
    //         : '';

    //     $html = '<table class="report crm-entity case-summary" style="margin-top: 1em;"><tbody><tr>';
    //     $html .= '<td class="label"><span class="crm-case-summary-label">End Date:</span>&nbsp;' . $formattedEndDate . '</td>';
    //     $html .= '</tr></tbody></table>';

    //     // *** Fix: Initialize $event->rows if it doesn't exist ***
    //     if (!isset($event->rows) || !is_array($event->rows)) {
    //         $event->rows = [];
    //     }

    //     $event->rows[] = [
    //         'label' => '',
    //         'value' => $html,
    //     ];
    // }

}

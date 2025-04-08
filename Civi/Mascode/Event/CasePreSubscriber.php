<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;
use Civi\Mascode\Util\CodeGenerator;

class CasePreSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_pre' => 'onPre',
        ];
    }

    public function onPre(GenericHookEvent $event): void
    {
        // xdebug_break();

        if (method_exists($event, 'getEntity') && method_exists($event, 'getAction')) {
            if ($event->getEntity() !== 'Case') {
                return;
            }

            $action = $event->getAction();
            $params = &$event->getParams();
            $caseId = $event->getId();

            if ($action === 'create') {
                $this->handleCreate($params);
            } elseif ($action === 'edit') {
                $this->handleEdit($caseId, $params);
            }
        }
    }

    protected function handleCreate(array &$params): void
    {
        if (empty($params['case_type_id'])) {
            return;
        }

        $caseType = civicrm_api4('CaseType', 'get', [
            'select' => ['name'],
            'where' => [['id', '=', $params['case_type_id']]],
        ])->first()['name'];

        if ($caseType === 'project') {
            $code = CodeGenerator::generate($caseType);
            $params['custom_project_code'] = $code;
            \Civi::log()->info("mascode: Assigned code $code to new project case.");
        }
    }

    protected function handleEdit($caseId, array &$params): void
    {
        if (empty($params['status_id'])) {
            return;
        }

        $case = civicrm_api4('Case', 'get', [
            'select' => ['id', 'case_type_id.label', 'status_id.label'],
            'where' => [['id', '=', $caseId]],
        ])->first();

        if ($case['case_type_id.label'] === 'Service Request' && $case['status_id.label'] === 'Project Created') {
            $this->createProjectCase($caseId);
        }
    }

    protected function createProjectCase($parentCaseId): void
    {
        // Add your existing project creation logic here
        // For now, we'll just log the intention:
        \Civi::log()->info("mascode: Creating project case from Service Request ID {$parentCaseId}.");
    }
}

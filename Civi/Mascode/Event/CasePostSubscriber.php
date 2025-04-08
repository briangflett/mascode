<?php

namespace Civi\Mascode\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi\Core\Event\GenericHookEvent;

class CasePostSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_post' => 'onPost',
        ];
    }

    public function onPost(GenericHookEvent $event): void
    {
        if ($event->getEntity() !== 'Case') {
            return;
        }

        // xdebug_break();

        $case = $event->getObject();
        $action = $event->getAction();

        if ($action === 'create') {
            $this->handleCreate($case);
        } elseif ($action === 'edit') {
            $this->handleEdit($case);
        }
    }

    protected function handleCreate(array $case): void
    {
        if ($case['case_type_id.label'] !== 'Project') {
            return;
        }

        $srId = $case['Projects.Related_SR_ID'] ?? null;
        if ($srId) {
            $sr = \Civi\Api4\CiviCase::get()
                ->addSelect('*', 'custom.*')
                ->addWhere('id', '=', $srId)
                ->execute()
                ->first();

            if ($sr) {
                // Copy custom fields from SR to project
                $update = \Civi\Api4\CiviCase::update()
                    ->addWhere('id', '=', $case['id']);

                foreach (['Practice_Area', 'Volunteer_Coordinator', 'SR_Source'] as $field) {
                    if (!empty($sr["Cases_SR_Projects_.{$field}"])) {
                        $update->addValue("Projects.{$field}", $sr["Cases_SR_Projects_.{$field}"]);
                    }
                }

                $update->execute();
                \Civi::log()->info("mascode: Populated Project {$case['id']} with data from SR {$srId}");
            }
        }
    }

    protected function handleEdit(array $case): void
    {
        // Add logic here if needed on post-edit
    }
}

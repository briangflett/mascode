<?php

namespace Civi\Mascode\Event;

use Civi\Core\Event\GenericHookEvent;
use CRM_Utils_Date;

class CaseSummaryListener
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_civicrm_caseSummary' => 'onCaseSummary',
        ];
    }

    public function onCaseSummary(GenericHookEvent $event): array
    {
        // sxdebug_break();

        $caseId = $event->getArg(0);

        if (empty($caseId)) {
            return [];
        }

        try {
            $case = \Civi\Api4\CiviCase::get()
                ->addWhere('id', '=', $caseId)
                ->setLimit(1)
                ->execute()
                ->first();
        } catch (\Exception $e) {
            return [];
        }

        if (!$case || empty($case['end_date'])) {
            return [];
        }

        $formattedEndDate = CRM_Utils_Date::customFormat($case['end_date']);
        $html = '<table class="report crm-entity case-summary" style="margin-top: 1em;"><tbody><tr>';
        $html .= '<td class="label"><span class="crm-case-summary-label">End Date:</span>&nbsp;' . $formattedEndDate . '</td>';
        $html .= '</tr></tbody></table>';

        return [[
            'label' => '',
            'value' => $html,
        ]];
    }
}

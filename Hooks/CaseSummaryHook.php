<?php

namespace Civi\Mascode\Hooks;

use CRM_Utils_Date;

class CaseSummaryHook
{

    public static function handle($caseId)
    {
        if (empty($caseId)) {
            return [];
        }

        try {
            $case = \Civi\Api4\CiviCase::get()
                ->addWhere('id', '=', $caseId)
                ->setLimit(1)
                ->execute()
                ->first();

            if (!$case) {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }


        $formattedEndDate = !empty($case['end_date'])
            ? CRM_Utils_Date::customFormat($case['end_date'])
            : '';

        //   Instead of returning the array of values and styling trough CSS
        //   return the HTML itself so you can reference the civi styling classes
        //   \CRM_Core_Resources::singleton()->addStyleFile('mascode', 'css/extras.css');

        $html = '<table class="report crm-entity case-summary" style="margin-top: 1em;"><tbody><tr>';
        $html .= '<td class="label"><span class="crm-case-summary-label">End Date:</span>&nbsp;' . $formattedEndDate . '</td>';
        $html .= '</tr></tbody></table>';
        return [
            [
                'label' => '',
                'value' => $html,
            ],
        ];
    }
}

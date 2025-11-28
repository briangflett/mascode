<?php

// file: Civi/Mascode/Util/CodeGenerator.php

namespace Civi\Mascode\Util;

class CodeGenerator
{
    /**
     * Generate a unique code for the given case type
     *
     * @param string $caseType The case type ('service_request' or 'project')
     * @return string The generated code
     */
    public static function generate($caseType)
    {
        $year = date('y');
        if ($caseType == 'service_request') {
            $lastServiceRequest = \Civi::settings()->get('mascode_last_service_request' . $year);
            if ($lastServiceRequest) {
                $nextServiceRequest = $lastServiceRequest + 1;
                \Civi::settings()->set('mascode_last_service_request' . $year, $nextServiceRequest);
                return 'R' . $year . str_pad($nextServiceRequest, 3, '0', STR_PAD_LEFT);
            } else {
                \Civi::settings()->set('mascode_last_service_request' . $year, 1);
                \Civi::settings()->revert('mascode_last_service_request' . ($year - 1));
                return 'R' . $year . '001';
            }
        } else {
            if ($caseType == 'project') {
                $lastProject = \Civi::settings()->get('mascode_last_project' . $year);
                if ($lastProject) {
                    $nextProject = $lastProject + 1;
                    \Civi::settings()->set('mascode_last_project' . $year, $nextProject);
                    return 'P' . $year . str_pad($nextProject, 3, '0', STR_PAD_LEFT);
                } else {
                    \Civi::settings()->set('mascode_last_project' . $year, 1);
                    \Civi::settings()->revert('mascode_last_project' . ($year - 1));
                    return 'P' . $year . '001';
                }
            } else {
                throw new \Exception("Invalid case type: $caseType");
            }
        }
    }

    /**
     * Get the ID of a custom field by group name and field name
     *
     * @param string $groupName Name of the custom group
     * @param string $fieldName Name of the custom field
     * @return int|null ID of the custom field or null if not found
     */
    public static function getFieldId($groupName, $fieldName)
    {
        try {
            // Get the CustomField by group and field names
            $field = \Civi\Api4\CustomField::get()
                ->addWhere('custom_group_id:name', '=', $groupName)
                ->addWhere('name', '=', $fieldName)
                ->setLimit(1)
                ->execute()
                ->first();

            return $field ? $field['id'] : null;
        } catch (\Exception $e) {
            \Civi::log()->error('CodeGenerator.php - Error getting field ID for {group}.{field}: {message}', [
                'group' => $groupName,
                'field' => $fieldName,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }
}

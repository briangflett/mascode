<?php

namespace Civi\Mascode\Hook;

use Civi\Api4\CustomField;
use Civi\Api4\CustomValue;

/**
 * Handles preservation of MAS case codes during contact merges.
 */
class CaseMergeHook
{
    /**
     * Preserves MAS case codes before a case is merged/copied.
     * 
     * @param int|null $caseId The case ID that will be copied during merge
     */
    public static function preserveCaseCodes(?int $caseId): void
    {
        // Return early if no case ID provided
        if ($caseId === null) {
            return;
        }
        
        try {
            // Get custom field IDs for MAS codes
            $customFields = self::getMasCodeCustomFields();
            
            if (empty($customFields)) {
                return;
            }
            
            // Retrieve existing MAS codes for this case
            $existingCodes = [];
            foreach ($customFields as $fieldInfo) {
                $codes = CustomValue::get($fieldInfo['custom_group_name'], false)
                    ->addSelect($fieldInfo['field_name'])
                    ->addWhere('entity_id', '=', $caseId)
                    ->execute();
                
                if (!empty($codes) && !empty($codes[0][$fieldInfo['field_name']])) {
                    $existingCodes[$fieldInfo['field_name']] = $codes[0][$fieldInfo['field_name']];
                }
            }
            
            // Store codes in cache for retrieval during case creation
            if (!empty($existingCodes)) {
                \Civi::cache()->set("mascode_merge_codes_{$caseId}", $existingCodes, 3600);
                \Civi::log()->info("MASCode: Preserved codes for case {$caseId}: " . json_encode($existingCodes));
            }
            
        } catch (\Exception $e) {
            \Civi::log()->error("MASCode: Failed to preserve case codes for case {$caseId}: " . $e->getMessage());
        }
    }
    
    /**
     * Retrieves preserved MAS codes for a newly created case.
     * 
     * @param int $originalCaseId The original case ID
     * @return array Array of preserved codes
     */
    public static function getPreservedCodes(int $originalCaseId): array
    {
        $cacheKey = "mascode_merge_codes_{$originalCaseId}";
        $codes = \Civi::cache()->get($cacheKey);
        
        if ($codes) {
            // Clear the cache after retrieval
            \Civi::cache()->delete($cacheKey);
            return $codes;
        }
        
        return [];
    }
    
    /**
     * Gets information about MAS code custom fields.
     * 
     * @return array Array of custom field information
     */
    private static function getMasCodeCustomFields(): array
    {
        static $fields = null;
        
        if ($fields === null) {
            try {
                $fields = [];
                
                // Get MAS SR Case Code field
                $srFields = CustomField::get(false)
                    ->addSelect('id', 'name', 'custom_group_id.name')
                    ->addWhere('name', '=', 'MAS_SR_Case_Code')
                    ->execute();
                
                if (!empty($srFields)) {
                    $fields[] = [
                        'field_name' => 'MAS_SR_Case_Code',
                        'custom_group_name' => $srFields[0]['custom_group_id.name']
                    ];
                }
                
                // Get MAS Project Case Code field
                $projectFields = CustomField::get(false)
                    ->addSelect('id', 'name', 'custom_group_id.name')
                    ->addWhere('name', '=', 'MAS_Project_Case_Code')
                    ->execute();
                
                if (!empty($projectFields)) {
                    $fields[] = [
                        'field_name' => 'MAS_Project_Case_Code',
                        'custom_group_name' => $projectFields[0]['custom_group_id.name']
                    ];
                }
                
            } catch (\Exception $e) {
                \Civi::log()->error("MASCode: Failed to get custom field info: " . $e->getMessage());
                $fields = [];
            }
        }
        
        return $fields;
    }
}
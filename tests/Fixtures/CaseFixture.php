<?php

namespace Civi\Mascode\Test\Fixtures;

/**
 * Test fixture for creating test cases
 */
class CaseFixture
{
    /**
     * Create a test service request case
     * 
     * @param int|null $clientContactId
     * @param array $overrides
     * @return array
     */
    public static function createServiceRequest(?int $clientContactId = null, array $overrides = []): array
    {
        $defaultData = [
            'case_type_id' => 1, // Assuming service request case type ID is 1
            'subject' => 'Test Service Request ' . rand(1000, 9999),
            'status_id' => 1, // Open status
            'start_date' => date('Y-m-d'),
            'details' => 'Test service request created by fixture',
        ];
        
        if ($clientContactId) {
            $defaultData['contact_id'] = $clientContactId;
        }
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create a test project case
     * 
     * @param int|null $clientContactId
     * @param array $overrides
     * @return array
     */
    public static function createProject(?int $clientContactId = null, array $overrides = []): array
    {
        $defaultData = [
            'case_type_id' => 2, // Assuming project case type ID is 2
            'subject' => 'Test Project ' . rand(1000, 9999),
            'status_id' => 1, // Open status
            'start_date' => date('Y-m-d'),
            'details' => 'Test project created by fixture',
        ];
        
        if ($clientContactId) {
            $defaultData['contact_id'] = $clientContactId;
        }
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create test case with MAS code
     * 
     * @param string $caseType 'service_request' or 'project'
     * @param string $masCode
     * @param array $overrides
     * @return array
     */
    public static function createWithMasCode(string $caseType, string $masCode, array $overrides = []): array
    {
        $caseData = $caseType === 'project' 
            ? self::createProject(null, $overrides)
            : self::createServiceRequest(null, $overrides);
            
        // Add MAS code to case data
        $caseData['custom_mas_code'] = $masCode;
        
        return $caseData;
    }
    
    /**
     * Create multiple test cases
     * 
     * @param int $count
     * @param string $caseType
     * @param array $baseOverrides
     * @return array
     */
    public static function createMultiple(int $count, string $caseType = 'service_request', array $baseOverrides = []): array
    {
        $cases = [];
        
        for ($i = 0; $i < $count; $i++) {
            $overrides = array_merge($baseOverrides, [
                'subject' => ucfirst($caseType) . ' ' . ($i + 1),
            ]);
            
            $cases[] = $caseType === 'project' 
                ? self::createProject(null, $overrides)
                : self::createServiceRequest(null, $overrides);
        }
        
        return $cases;
    }
    
    /**
     * Create a complete case scenario with client and MAS rep
     * 
     * @param string $caseType
     * @param array $caseOverrides
     * @return array Scenario data including case, client, and mas_rep
     */
    public static function createCompleteScenario(string $caseType = 'service_request', array $caseOverrides = []): array
    {
        // Create client contact
        $clientData = ContactFixture::createWithRole('client');
        
        // Create MAS representative
        $masRepData = ContactFixture::createWithRole('mas_rep');
        
        // Create case
        $caseData = $caseType === 'project' 
            ? self::createProject(null, $caseOverrides)
            : self::createServiceRequest(null, $caseOverrides);
        
        return [
            'case' => $caseData,
            'client' => $clientData,
            'mas_rep' => $masRepData,
        ];
    }
}
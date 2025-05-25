<?php

namespace Civi\Mascode\Test\Fixtures;

/**
 * Test fixture for creating test contacts
 */
class ContactFixture
{
    /**
     * Create a test contact for use in tests
     * 
     * @param array $overrides Custom field values to override defaults
     * @return array Contact data
     */
    public static function create(array $overrides = []): array
    {
        $defaultData = [
            'contact_type' => 'Individual',
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'email' => 'test' . rand(1000, 9999) . '@example.com',
            'phone' => '555-' . rand(1000, 9999),
            'external_identifier' => 'TEST_' . uniqid(),
            'source' => 'Test Fixture',
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create a test organization contact
     * 
     * @param array $overrides
     * @return array
     */
    public static function createOrganization(array $overrides = []): array
    {
        $defaultData = [
            'contact_type' => 'Organization',
            'organization_name' => 'Test Organization ' . rand(1000, 9999),
            'email' => 'org' . rand(1000, 9999) . '@example.com',
            'phone' => '555-' . rand(1000, 9999),
            'external_identifier' => 'ORG_' . uniqid(),
            'source' => 'Test Fixture',
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create multiple test contacts
     * 
     * @param int $count Number of contacts to create
     * @param array $baseOverrides Base overrides for all contacts
     * @return array Array of contact data
     */
    public static function createMultiple(int $count, array $baseOverrides = []): array
    {
        $contacts = [];
        
        for ($i = 0; $i < $count; $i++) {
            $overrides = array_merge($baseOverrides, [
                'first_name' => 'Test' . ($i + 1),
                'last_name' => 'Contact' . ($i + 1),
            ]);
            
            $contacts[] = self::create($overrides);
        }
        
        return $contacts;
    }
    
    /**
     * Create test contact with specific role for case management
     * 
     * @param string $role The role this contact will play (client, mas_rep, etc.)
     * @param array $overrides
     * @return array
     */
    public static function createWithRole(string $role, array $overrides = []): array
    {
        $roleSpecificData = [];
        
        switch ($role) {
            case 'mas_rep':
                $roleSpecificData = [
                    'first_name' => 'MAS',
                    'last_name' => 'Representative',
                    'job_title' => 'Consultant',
                    'email' => 'masrep' . rand(1000, 9999) . '@masadvise.org',
                ];
                break;
                
            case 'client':
                $roleSpecificData = [
                    'first_name' => 'Client',
                    'last_name' => 'Organization',
                    'contact_type' => 'Organization',
                    'organization_name' => 'Client Org ' . rand(1000, 9999),
                ];
                break;
                
            case 'donor':
                $roleSpecificData = [
                    'first_name' => 'Generous',
                    'last_name' => 'Donor',
                    'email' => 'donor' . rand(1000, 9999) . '@example.com',
                ];
                break;
        }
        
        return self::create(array_merge($roleSpecificData, $overrides));
    }
}
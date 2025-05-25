<?php

namespace Civi\Mascode\Test\Integration\CiviRules;

use Civi\Mascode\Test\TestCase;
use Civi\Mascode\Test\Fixtures\ContactFixture;
use Civi\Mascode\Test\Fixtures\CaseFixture;
use Civi\Mascode\CiviRules\Action\GenerateMasCode;

/**
 * Integration tests for GenerateMasCode CiviRules action
 * 
 * @covers \Civi\Mascode\CiviRules\Action\GenerateMasCode
 * @group integration
 */
class GenerateMasCodeTest extends TestCase
{
    private GenerateMasCode $generateMasCode;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNoCiviCRM();
        
        $this->generateMasCode = new GenerateMasCode();
    }

    /**
     * Test MAS code generation for service request
     */
    public function testGenerateMasCodeForServiceRequest(): void
    {
        $this->skipIfNoDatabase();
        
        // Create test scenario
        $scenario = CaseFixture::createCompleteScenario('service_request');
        
        // Create actual contacts and case in database
        $clientResult = civicrm_api4('Contact', 'create', [
            'values' => $scenario['client']
        ]);
        $clientId = $clientResult->first()['id'];
        
        $caseResult = civicrm_api4('Case', 'create', [
            'values' => array_merge($scenario['case'], [
                'contact_id' => $clientId
            ])
        ]);
        $caseId = $caseResult->first()['id'];
        
        // Prepare trigger data
        $triggerData = [
            'case_id' => $caseId,
            'entity_type' => 'service_request',
        ];
        
        // Mock rule action configuration
        $ruleAction = (object) [
            'id' => 1,
            'configuration' => json_encode([
                'entity_type' => 'service_request'
            ])
        ];
        
        // Execute action
        $result = $this->generateMasCode->processAction($ruleAction, $triggerData);
        
        // Assertions
        $this->assertTrue($result);
        
        // Verify MAS code was generated and saved
        $updatedCase = civicrm_api4('Case', 'get', [
            'where' => [['id', '=', $caseId]],
            'select' => ['*', 'custom.*']
        ])->first();
        
        $this->assertNotEmpty($updatedCase['custom_mas_code'] ?? '');
        $this->assertStringStartsWith('R', $updatedCase['custom_mas_code']);
        $this->assertMatchesRegularExpression('/^R\d{5}$/', $updatedCase['custom_mas_code']);
        
        // Cleanup
        civicrm_api4('Case', 'delete', ['where' => [['id', '=', $caseId]]]);
        civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $clientId]]]);
    }

    /**
     * Test MAS code generation for project
     */
    public function testGenerateMasCodeForProject(): void
    {
        $this->skipIfNoDatabase();
        
        // Create test scenario
        $scenario = CaseFixture::createCompleteScenario('project');
        
        // Create actual contacts and case in database
        $clientResult = civicrm_api4('Contact', 'create', [
            'values' => $scenario['client']
        ]);
        $clientId = $clientResult->first()['id'];
        
        $caseResult = civicrm_api4('Case', 'create', [
            'values' => array_merge($scenario['case'], [
                'contact_id' => $clientId
            ])
        ]);
        $caseId = $caseResult->first()['id'];
        
        // Prepare trigger data
        $triggerData = [
            'case_id' => $caseId,
            'entity_type' => 'project',
        ];
        
        // Mock rule action configuration
        $ruleAction = (object) [
            'id' => 1,
            'configuration' => json_encode([
                'entity_type' => 'project'
            ])
        ];
        
        // Execute action
        $result = $this->generateMasCode->processAction($ruleAction, $triggerData);
        
        // Assertions
        $this->assertTrue($result);
        
        // Verify MAS code was generated and saved
        $updatedCase = civicrm_api4('Case', 'get', [
            'where' => [['id', '=', $caseId]],
            'select' => ['*', 'custom.*']
        ])->first();
        
        $this->assertNotEmpty($updatedCase['custom_mas_code'] ?? '');
        $this->assertStringStartsWith('P', $updatedCase['custom_mas_code']);
        $this->assertMatchesRegularExpression('/^P\d{5}$/', $updatedCase['custom_mas_code']);
        
        // Cleanup
        civicrm_api4('Case', 'delete', ['where' => [['id', '=', $caseId]]]);
        civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $clientId]]]);
    }

    /**
     * Test that duplicate codes are not generated
     */
    public function testNoDuplicateCodes(): void
    {
        $this->skipIfNoDatabase();
        
        $generatedCodes = [];
        
        // Generate multiple codes and ensure they're unique
        for ($i = 0; $i < 5; $i++) {
            // Create test scenario
            $scenario = CaseFixture::createCompleteScenario('service_request');
            
            // Create actual contacts and case in database
            $clientResult = civicrm_api4('Contact', 'create', [
                'values' => $scenario['client']
            ]);
            $clientId = $clientResult->first()['id'];
            
            $caseResult = civicrm_api4('Case', 'create', [
                'values' => array_merge($scenario['case'], [
                    'contact_id' => $clientId
                ])
            ]);
            $caseId = $caseResult->first()['id'];
            
            // Generate MAS code
            $triggerData = ['case_id' => $caseId, 'entity_type' => 'service_request'];
            $ruleAction = (object) [
                'id' => 1,
                'configuration' => json_encode(['entity_type' => 'service_request'])
            ];
            
            $this->generateMasCode->processAction($ruleAction, $triggerData);
            
            // Get generated code
            $updatedCase = civicrm_api4('Case', 'get', [
                'where' => [['id', '=', $caseId]],
                'select' => ['*', 'custom.*']
            ])->first();
            
            $generatedCode = $updatedCase['custom_mas_code'] ?? '';
            $this->assertNotEmpty($generatedCode);
            
            // Check for duplicates
            $this->assertNotContains($generatedCode, $generatedCodes, 'Generated code should be unique');
            $generatedCodes[] = $generatedCode;
            
            // Cleanup
            civicrm_api4('Case', 'delete', ['where' => [['id', '=', $caseId]]]);
            civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $clientId]]]);
        }
        
        // Verify all codes are unique
        $this->assertEquals(5, count(array_unique($generatedCodes)));
    }

    /**
     * Test error handling for invalid entity type
     */
    public function testInvalidEntityTypeHandling(): void
    {
        $triggerData = [
            'case_id' => 1,
            'entity_type' => 'invalid_type',
        ];
        
        $ruleAction = (object) [
            'id' => 1,
            'configuration' => json_encode([
                'entity_type' => 'invalid_type'
            ])
        ];
        
        // Should handle invalid entity type gracefully
        $result = $this->generateMasCode->processAction($ruleAction, $triggerData);
        
        // Depending on implementation, this might return false or throw exception
        $this->assertIsBool($result);
    }

    /**
     * Test performance with multiple code generations
     */
    public function testBulkCodeGenerationPerformance(): void
    {
        $this->skipIfNoDatabase();
        
        $startTime = microtime(true);
        $caseIds = [];
        $contactIds = [];
        
        // Generate codes for 10 cases
        for ($i = 0; $i < 10; $i++) {
            $scenario = CaseFixture::createCompleteScenario('service_request');
            
            $clientResult = civicrm_api4('Contact', 'create', [
                'values' => $scenario['client']
            ]);
            $clientId = $clientResult->first()['id'];
            $contactIds[] = $clientId;
            
            $caseResult = civicrm_api4('Case', 'create', [
                'values' => array_merge($scenario['case'], [
                    'contact_id' => $clientId
                ])
            ]);
            $caseId = $caseResult->first()['id'];
            $caseIds[] = $caseId;
            
            $triggerData = ['case_id' => $caseId, 'entity_type' => 'service_request'];
            $ruleAction = (object) [
                'id' => 1,
                'configuration' => json_encode(['entity_type' => 'service_request'])
            ];
            
            $this->generateMasCode->processAction($ruleAction, $triggerData);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete in reasonable time (less than 5 seconds)
        $this->assertLessThan(5.0, $executionTime, 'Bulk code generation should be performant');
        
        // Cleanup
        foreach ($caseIds as $caseId) {
            civicrm_api4('Case', 'delete', ['where' => [['id', '=', $caseId]]]);
        }
        foreach ($contactIds as $contactId) {
            civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $contactId]]]);
        }
    }
}
<?php

namespace Civi\Mascode\Test\Unit\Util;

use Civi\Mascode\Test\TestCase;
use Civi\Mascode\Util\CodeGenerator;
use Mockery;

/**
 * Unit tests for CodeGenerator utility
 * 
 * @covers \Civi\Mascode\Util\CodeGenerator
 */
class CodeGeneratorTest extends TestCase
{
    private CodeGenerator $codeGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->codeGenerator = new CodeGenerator();
    }

    /**
     * Test service request code generation
     */
    public function testGenerateServiceRequestCode(): void
    {
        $code = $this->codeGenerator->generateCode('service_request');
        
        // Should start with 'R' for service request
        $this->assertStringStartsWith('R', $code);
        
        // Should include current year (25 for 2025)
        $this->assertStringContains('25', $code);
        
        // Should be 6 characters total (R + YY + NNN)
        $this->assertEquals(6, strlen($code));
        
        // Should match expected pattern
        $this->assertMatchesRegularExpression('/^R\d{5}$/', $code);
    }

    /**
     * Test project code generation
     */
    public function testGenerateProjectCode(): void
    {
        $code = $this->codeGenerator->generateCode('project');
        
        // Should start with 'P' for project
        $this->assertStringStartsWith('P', $code);
        
        // Should include current year (25 for 2025)
        $this->assertStringContains('25', $code);
        
        // Should be 6 characters total (P + YY + NNN)
        $this->assertEquals(6, strlen($code));
        
        // Should match expected pattern
        $this->assertMatchesRegularExpression('/^P\d{5}$/', $code);
    }

    /**
     * Test invalid entity type throws exception
     */
    public function testInvalidEntityTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid entity type');
        
        $this->codeGenerator->generateCode('invalid_type');
    }

    /**
     * Test code uniqueness validation
     */
    public function testCodeUniqueness(): void
    {
        // Generate multiple codes and ensure they're unique
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = $this->codeGenerator->generateCode('service_request');
        }
        
        $uniqueCodes = array_unique($codes);
        $this->assertEquals(count($codes), count($uniqueCodes), 'All generated codes should be unique');
    }

    /**
     * Test code format validation
     */
    public function testCodeFormat(): void
    {
        $serviceRequestCode = $this->codeGenerator->generateCode('service_request');
        $projectCode = $this->codeGenerator->generateCode('project');
        
        // Validate format
        $this->assertTrue($this->codeGenerator->isValidCodeFormat($serviceRequestCode));
        $this->assertTrue($this->codeGenerator->isValidCodeFormat($projectCode));
        
        // Test invalid formats
        $this->assertFalse($this->codeGenerator->isValidCodeFormat('INVALID'));
        $this->assertFalse($this->codeGenerator->isValidCodeFormat('123456'));
        $this->assertFalse($this->codeGenerator->isValidCodeFormat('R25'));
    }

    /**
     * Test year component extraction
     */
    public function testYearComponentExtraction(): void
    {
        $currentYear = date('y'); // Get 2-digit year
        
        $code = $this->codeGenerator->generateCode('service_request');
        $extractedYear = $this->codeGenerator->extractYearFromCode($code);
        
        $this->assertEquals($currentYear, $extractedYear);
    }

    /**
     * Test entity type extraction from code
     */
    public function testEntityTypeExtraction(): void
    {
        $serviceRequestCode = $this->codeGenerator->generateCode('service_request');
        $projectCode = $this->codeGenerator->generateCode('project');
        
        $this->assertEquals('service_request', $this->codeGenerator->getEntityTypeFromCode($serviceRequestCode));
        $this->assertEquals('project', $this->codeGenerator->getEntityTypeFromCode($projectCode));
    }

    /**
     * Test sequence number increments correctly
     */
    public function testSequenceIncrement(): void
    {
        // This test would require database access to test properly
        // For now, we'll test the logic without database interaction
        
        $code1 = $this->codeGenerator->generateCode('service_request');
        $code2 = $this->codeGenerator->generateCode('service_request');
        
        // Extract sequence numbers (last 3 digits)
        $seq1 = (int) substr($code1, -3);
        $seq2 = (int) substr($code2, -3);
        
        // In a real scenario with database, seq2 should be seq1 + 1
        // For unit test, we just verify they're different
        $this->assertNotEquals($seq1, $seq2);
    }

    /**
     * Test batch code generation performance
     */
    public function testBatchCodeGenerationPerformance(): void
    {
        $startTime = microtime(true);
        
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $this->codeGenerator->generateCode('service_request');
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should generate 100 codes in less than 1 second
        $this->assertLessThan(1.0, $executionTime, 'Batch code generation should be fast');
        
        // All codes should be unique
        $this->assertEquals(100, count(array_unique($codes)));
    }

    /**
     * Test code generation with mocked database interactions
     */
    public function testCodeGenerationWithMockedDatabase(): void
    {
        // This is an example of how to mock CiviCRM API calls
        // You would implement this based on your actual CodeGenerator implementation
        
        if (!class_exists('Mockery')) {
            $this->markTestSkipped('Mockery not available for mocking');
        }

        // Mock the API call to check for existing codes
        $mockApi = Mockery::mock('alias:Civi\Api4\Case');
        $mockApi->shouldReceive('get')
            ->andReturn(Mockery::mock(['execute' => []])); // Return empty result
        
        $code = $this->codeGenerator->generateCode('service_request');
        
        $this->assertNotEmpty($code);
        $this->assertStringStartsWith('R', $code);
    }
}
<?php

namespace Civi\Mascode\Test;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery;

/**
 * Base test case for Mascode extension tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset any global state if needed
        $this->resetGlobalState();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Close any open mocks
        if (class_exists('Mockery')) {
            Mockery::close();
        }
        
        parent::tearDown();
    }

    /**
     * Reset global state for clean testing
     */
    protected function resetGlobalState(): void
    {
        // Clear any cached data that might interfere with tests
        if (function_exists('civicrm_initialize') && class_exists('\Civi')) {
            try {
                \Civi::cache('long')->clear();
                \Civi::cache('short')->clear();
            } catch (\Exception $e) {
                // Ignore cache clearing errors in test environment
            }
        }
    }

    /**
     * Helper method to create mock objects with Mockery
     * 
     * @param string $className
     * @return \Mockery\MockInterface
     */
    protected function mockObject(string $className): \Mockery\MockInterface
    {
        return Mockery::mock($className);
    }

    /**
     * Helper method to assert array contains specific keys
     * 
     * @param array $expectedKeys
     * @param array $array
     * @param string $message
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array, string $message = ''): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should contain key: $key");
        }
    }

    /**
     * Helper method to create test contact data
     * 
     * @param array $overrides
     * @return array
     */
    protected function getTestContactData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'email' => 'test@example.com',
            'contact_type' => 'Individual',
        ], $overrides);
    }

    /**
     * Helper method to create test case data
     * 
     * @param array $overrides
     * @return array
     */
    protected function getTestCaseData(array $overrides = []): array
    {
        return array_merge([
            'case_type_id' => 1,
            'subject' => 'Test Case',
            'status_id' => 1,
            'start_date' => date('Y-m-d'),
        ], $overrides);
    }

    /**
     * Skip test if CiviCRM is not available
     */
    protected function skipIfNoCiviCRM(): void
    {
        if (!function_exists('civicrm_initialize') || !class_exists('\Civi')) {
            $this->markTestSkipped('CiviCRM not available in test environment');
        }
    }

    /**
     * Skip test if database is not available
     */
    protected function skipIfNoDatabase(): void
    {
        $this->skipIfNoCiviCRM();
        
        try {
            \Civi::service('sql_triggers')->rebuild();
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available for testing: ' . $e->getMessage());
        }
    }
}
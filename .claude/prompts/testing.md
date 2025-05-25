# Testing Prompt Templates

## Unit Test Creation

Create comprehensive unit tests for [CLASS_NAME] that:

- Test all public methods with various input scenarios
- Include edge cases and error conditions
- Mock external dependencies (CiviCRM API, database, external services)
- Follow PHPUnit best practices and CiviCRM testing patterns
- Achieve >80% code coverage for business logic
- Test both success and failure paths
- Validate return values and side effects

### Example Usage
```
Create unit tests for Civi\Mascode\Util\CodeGenerator that test:
- Valid code generation for different entity types
- Error handling for invalid inputs
- Code uniqueness validation
- Proper formatting of generated codes
```

## Integration Test Creation

Create integration tests for [FEATURE_NAME] that:

- Test actual CiviCRM API interactions without mocking
- Use real database transactions with proper cleanup
- Test complete workflows end-to-end
- Verify data persistence and retrieval
- Test CiviRules trigger/action integration
- Include form submission and processing workflows
- Test with realistic test data scenarios

### Example Usage
```
Create integration tests for the Service Request to Project conversion workflow that:
- Creates a test service request case
- Triggers the CiviRules action
- Verifies project case creation
- Validates proper data transfer
- Tests error scenarios (missing data, invalid states)
```

## E2E Test Creation

Create browser automation tests for [WORKFLOW_NAME] that:

- Test from user perspective using actual browser
- Include form submissions and validations
- Test error scenarios and user feedback
- Work across different browsers (Chrome, Firefox)
- Include accessibility testing where applicable
- Test anonymous and authenticated user flows

### Example Usage
```
Create E2E tests for the anonymous case status update form that:
- Loads form with valid checksum
- Tests form field validation
- Submits form and verifies success message
- Tests invalid checksum handling
- Verifies case update in CiviCRM backend
```

## CiviCRM-Specific Testing Patterns

### API4 Testing
```php
// Test API4 calls with proper setup
$this->assertAPISuccess(Contact::create()
    ->addValue('first_name', 'Test')
    ->execute());
```

### Event Testing
```php
// Test event subscribers
$event = new AfformSubmitEvent($formName, $submission);
$this->dispatcher->dispatch($event);
$this->assertTrue($event->wasProcessed());
```

### CiviRules Testing
```php
// Test CiviRules actions
$action = new GenerateMasCode();
$result = $action->processAction($ruleAction, $triggerData);
$this->assertNotEmpty($result['mas_code']);
```

## Performance Testing

Create performance tests for [FEATURE_NAME] that:

- Measure execution time for critical operations
- Test with large datasets (1000+ records)
- Identify memory usage patterns
- Test database query efficiency
- Validate caching effectiveness

### Example Usage
```
Create performance tests for CodeGenerator that:
- Generate 1000 codes and measure time
- Test code uniqueness validation performance
- Measure memory usage during bulk operations
- Validate database query count
```

## Security Testing

Create security tests for [FEATURE_NAME] that:

- Test input sanitization and validation
- Verify proper permission checks
- Test against SQL injection attempts
- Validate XSS protection
- Test anonymous access controls
- Verify sensitive data handling

### Example Usage
```
Create security tests for anonymous form access that:
- Test checksum validation with tampered URLs
- Verify proper data sanitization
- Test unauthorized access attempts
- Validate permission boundaries
```

## Test Data Management

### Test Data Setup
```php
// Create reusable test data fixtures
protected function createTestCase($type = 'service_request') {
    return Case::create()
        ->addValue('case_type_id', $this->getCaseTypeId($type))
        ->addValue('subject', 'Test Case')
        ->execute();
}
```

### Database Cleanup
```php
// Ensure proper cleanup after tests
protected function tearDown(): void {
    // Clean up test data
    $this->cleanupTestCases();
    parent::tearDown();
}
```

## Common Test Scenarios

### Form Processing Tests
- Test valid form submissions
- Test validation errors
- Test anonymous access with checksums
- Test data transformation and storage

### CiviRules Integration Tests
- Test trigger conditions
- Test action execution
- Test rule chain processing
- Test error handling in rules

### API Integration Tests
- Test API4 endpoint responses
- Test permission validation
- Test data filtering and sorting
- Test bulk operations

### Database Tests
- Test schema migrations
- Test data integrity constraints
- Test relationship handling
- Test custom field processing

---

*Use these templates to ensure comprehensive test coverage for all mascode extension features*
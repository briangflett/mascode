# Testing Guide

## Overview

This guide covers the testing strategy and workflows for the Mascode CiviCRM extension. Our testing approach focuses on reliability, maintainability, and comprehensive coverage to support the extension's evolution toward AI integration.

## Testing Philosophy

1. **Test-Driven Development**: Write tests before implementing features when possible
2. **Multiple Test Levels**: Unit, integration, and end-to-end testing
3. **CI/CD Integration**: Automated testing on every commit and pull request
4. **Quality Gates**: Code must pass all tests and quality checks before merge
5. **Performance Awareness**: Monitor test execution time and system performance

## Test Structure

```
tests/
├── Unit/                    # Fast, isolated unit tests
│   ├── Util/
│   ├── CiviRules/
│   └── Event/
├── Integration/             # Tests with CiviCRM API and database
│   ├── CiviRules/
│   ├── Event/
│   └── FormProcessor/
├── E2E/                     # End-to-end browser tests (future)
├── Fixtures/                # Test data factories
│   ├── ContactFixture.php
│   └── CaseFixture.php
├── TestCase.php             # Base test class
└── bootstrap.php            # Test environment setup
```

## Running Tests

### Prerequisites

```bash
# Install development dependencies
composer install

# Ensure CiviCRM environment is available
cv ext:enable mascode
```

### Basic Test Commands

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:integration

# Run tests with coverage
composer test:coverage

# Run individual test files
./vendor/bin/phpunit tests/Unit/Util/CodeGeneratorTest.php
```

### Code Quality Commands

```bash
# Run code style checks
composer lint

# Fix code style automatically
composer lint:fix

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

## Test Types

### Unit Tests

**Purpose**: Test individual classes and methods in isolation

**Characteristics**:
- Fast execution (< 1 second per test)
- No database or external dependencies
- Use mocking for dependencies
- High code coverage target (>80%)

**Example**:
```php
public function testGenerateServiceRequestCode(): void
{
    $code = $this->codeGenerator->generateCode('service_request');
    
    $this->assertStringStartsWith('R', $code);
    $this->assertEquals(6, strlen($code));
    $this->assertMatchesRegularExpression('/^R\d{5}$/', $code);
}
```

**Best Practices**:
- Test both success and failure paths
- Use descriptive test method names
- One assertion per test when possible
- Mock external dependencies

### Integration Tests

**Purpose**: Test component interactions with CiviCRM and database

**Characteristics**:
- Real database transactions
- CiviCRM API interactions
- Test complete workflows
- Proper cleanup after each test

**Example**:
```php
public function testGenerateMasCodeForServiceRequest(): void
{
    $this->skipIfNoDatabase();
    
    // Create test data in database
    $clientId = civicrm_api4('Contact', 'create', [
        'values' => ContactFixture::createWithRole('client')
    ])->first()['id'];
    
    // Test the actual workflow
    $result = $this->generateMasCode->processAction($ruleAction, $triggerData);
    
    // Verify results in database
    $this->assertTrue($result);
    
    // Cleanup
    civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $clientId]]]);
}
```

**Best Practices**:
- Always clean up test data
- Use transactions when possible
- Test with realistic data scenarios
- Verify side effects and state changes

### End-to-End Tests (Future)

**Purpose**: Test complete user workflows through browser automation

**Planned Tools**:
- Playwright or Selenium for browser automation
- Testing forms, case creation, and user interactions
- Cross-browser compatibility testing

## Test Fixtures

### ContactFixture

Provides factory methods for creating test contacts:

```php
// Create basic test contact
$contact = ContactFixture::create();

// Create contact with specific role
$masRep = ContactFixture::createWithRole('mas_rep');

// Create organization
$org = ContactFixture::createOrganization();

// Create multiple contacts
$contacts = ContactFixture::createMultiple(5);
```

### CaseFixture

Provides factory methods for creating test cases:

```php
// Create service request
$serviceRequest = CaseFixture::createServiceRequest($clientId);

// Create project
$project = CaseFixture::createProject($clientId);

// Create complete scenario with all related data
$scenario = CaseFixture::createCompleteScenario('service_request');
```

## Database Testing

### Test Database Setup

For local development:

```bash
# Create test database (if needed)
cv api System.createTestDatabase

# Run tests with test database
CIVICRM_UF=UnitTests composer test
```

### Transaction Management

```php
protected function setUp(): void
{
    parent::setUp();
    $this->skipIfNoDatabase();
    
    // Start transaction for test isolation
    \CRM_Core_Transaction::create(TRUE);
}

protected function tearDown(): void
{
    // Rollback transaction to clean up test data
    \CRM_Core_Transaction::rollbackIfFalse(FALSE);
    
    parent::tearDown();
}
```

## Mocking and Test Doubles

### Using Mockery

```php
use Mockery;

public function testWithMockedDependency(): void
{
    // Mock external API
    $mockApi = Mockery::mock('alias:Civi\Api4\Contact');
    $mockApi->shouldReceive('get')
        ->once()
        ->andReturn(Mockery::mock(['execute' => []]));
    
    // Test with mock
    $result = $this->serviceUnderTest->methodThatUsesApi();
    
    $this->assertNotNull($result);
}
```

### CiviCRM API Mocking

```php
public function testApiInteraction(): void
{
    // Mock CiviCRM API response
    $mockResult = [
        ['id' => 1, 'first_name' => 'Test', 'last_name' => 'Contact']
    ];
    
    // Use dependency injection to provide mock
    $service = new MyService($mockApiService);
    $result = $service->processContacts();
    
    $this->assertCount(1, $result);
}
```

## Performance Testing

### Test Execution Performance

```php
public function testBulkOperationPerformance(): void
{
    $startTime = microtime(true);
    
    // Perform bulk operation
    for ($i = 0; $i < 100; $i++) {
        $this->service->performOperation();
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    // Assert performance requirements
    $this->assertLessThan(2.0, $executionTime, 'Bulk operation should complete in under 2 seconds');
}
```

### Memory Usage Testing

```php
public function testMemoryUsage(): void
{
    $initialMemory = memory_get_usage();
    
    // Perform memory-intensive operation
    $this->service->processLargeDataset();
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    // Assert memory usage is reasonable (< 50MB)
    $this->assertLessThan(50 * 1024 * 1024, $memoryUsed);
}
```

## Continuous Integration

### GitHub Actions Workflow

Our CI pipeline runs on every push and pull request:

1. **Code Style Check**: Ensures PSR-12 compliance
2. **Static Analysis**: PHPStan analysis for potential issues
3. **Unit Tests**: Fast isolated tests
4. **Integration Tests**: Tests with CiviCRM
5. **Coverage Report**: Uploads to Codecov
6. **Security Audit**: Checks for vulnerable dependencies

### Quality Gates

All tests must pass before code can be merged:

- All unit tests pass
- Integration tests pass (or marked as known issues)
- Code coverage above 70%
- No critical static analysis issues
- Code style compliance

## Test Data Management

### Cleanup Strategy

```php
class CleanupTest extends TestCase
{
    private array $createdContacts = [];
    private array $createdCases = [];
    
    protected function createTestContact(): int
    {
        $result = civicrm_api4('Contact', 'create', [
            'values' => ContactFixture::create()
        ]);
        
        $contactId = $result->first()['id'];
        $this->createdContacts[] = $contactId;
        
        return $contactId;
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        foreach ($this->createdCases as $caseId) {
            civicrm_api4('Case', 'delete', ['where' => [['id', '=', $caseId]]]);
        }
        
        foreach ($this->createdContacts as $contactId) {
            civicrm_api4('Contact', 'delete', ['where' => [['id', '=', $contactId]]]);
        }
        
        parent::tearDown();
    }
}
```

### Test Data Isolation

- Use unique identifiers for test data
- Clean up after each test
- Use transactions when possible
- Avoid dependencies between tests

## Debugging Tests

### Running Single Tests

```bash
# Run specific test method
./vendor/bin/phpunit --filter testGenerateServiceRequestCode

# Run with verbose output
./vendor/bin/phpunit --verbose

# Run with debug information
./vendor/bin/phpunit --debug
```

### XDebug Integration

```bash
# Run tests with XDebug
XDEBUG_SESSION=1 ./vendor/bin/phpunit tests/Unit/Util/CodeGeneratorTest.php
```

### Test Output and Logging

```php
public function testWithLogging(): void
{
    // Enable logging for debugging
    \Civi::log()->info('Starting test', ['test' => __METHOD__]);
    
    $result = $this->service->performAction();
    
    \Civi::log()->info('Test result', ['result' => $result]);
    
    $this->assertTrue($result);
}
```

## Best Practices

### Test Organization

1. **Group Related Tests**: Use test classes for related functionality
2. **Descriptive Names**: Test method names should describe what is being tested
3. **Setup and Teardown**: Use setUp() and tearDown() for test preparation and cleanup
4. **Test Documentation**: Add docblocks explaining complex test scenarios

### Test Writing Guidelines

1. **AAA Pattern**: Arrange, Act, Assert
2. **One Concept per Test**: Each test should verify one specific behavior
3. **Independent Tests**: Tests should not depend on each other
4. **Fast Tests**: Keep unit tests fast for quick feedback

### Error Handling Tests

```php
public function testErrorHandling(): void
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid entity type');
    
    $this->codeGenerator->generateCode('invalid_type');
}
```

## Coverage and Reporting

### Coverage Reports

```bash
# Generate HTML coverage report
composer test:coverage

# View coverage report
open coverage-html/index.html
```

### Coverage Targets

- **Unit Tests**: >80% line coverage
- **Integration Tests**: >60% line coverage
- **Overall**: >70% line coverage

### Excluding Files from Coverage

```xml
<!-- In phpunit.xml.dist -->
<coverage>
    <exclude>
        <directory>tests/</directory>
        <directory>vendor/</directory>
        <file>mascode.civix.php</file>
    </exclude>
</coverage>
```

## Future Testing Roadmap

### AI Integration Testing

As we develop AI features:

1. **Mock AI Services**: Test AI integration without actual API calls
2. **Performance Testing**: Ensure AI features don't impact system performance
3. **Data Privacy Testing**: Verify sensitive data handling in AI workflows
4. **Fallback Testing**: Test behavior when AI services are unavailable

### Advanced Testing Tools

Future additions:

- **Mutation Testing**: Verify test quality with PestPHP or Infection
- **Browser Testing**: Playwright for E2E testing
- **Load Testing**: Performance testing with realistic data volumes
- **Security Testing**: Automated security vulnerability scanning

---

*For implementation examples, see the test files in the `tests/` directory*
*For CI/CD configuration, see `.github/workflows/ci.yml`*
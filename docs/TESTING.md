# Testing Guide

## Current Test Coverage

The extension has a basic PHPUnit test framework with fixtures. Tests run locally — no CI/CD pipeline is in place.

### Test Structure

```
tests/
├── Unit/
│   └── Util/
│       └── CodeGeneratorTest.php    # MAS code generation (R25xxx, P25xxx)
├── Integration/
│   └── CiviRules/
│       └── GenerateMasCodeTest.php  # End-to-end code generation with CiviCRM
├── Fixtures/
│   ├── ContactFixture.php           # Factory for test contacts
│   └── CaseFixture.php             # Factory for test cases
├── TestCase.php                     # Base test class
└── bootstrap.php                    # Test environment setup
```

## Running Tests

### Prerequisites

```bash
composer install
cv ext:enable mascode
```

### Commands

```bash
# Run all tests
composer test

# Run specific suites
composer test:unit
composer test:integration

# Run with coverage
composer test:coverage

# Run individual test
./vendor/bin/phpunit tests/Unit/Util/CodeGeneratorTest.php

# Run with XDebug
XDEBUG_SESSION=1 ./vendor/bin/phpunit tests/Unit/Util/CodeGeneratorTest.php
```

## Test Fixtures

### ContactFixture

```php
$contact = ContactFixture::create();
$masRep = ContactFixture::createWithRole('mas_rep');
$org = ContactFixture::createOrganization();
```

### CaseFixture

```php
$serviceRequest = CaseFixture::createServiceRequest($clientId);
$project = CaseFixture::createProject($clientId);
$scenario = CaseFixture::createCompleteScenario('service_request');
```

## Writing New Tests

### Unit Tests

Fast, isolated, no database. Use mocks for CiviCRM dependencies.

```php
public function testGenerateServiceRequestCode(): void
{
    $code = $this->codeGenerator->generateCode('service_request');
    $this->assertMatchesRegularExpression('/^R\d{5}$/', $code);
}
```

### Integration Tests

Use real CiviCRM database. Clean up after each test.

```php
protected function setUp(): void
{
    parent::setUp();
    $this->skipIfNoDatabase();
}
```

## Manual Testing Checklist

For changes without automated test coverage:

- [ ] Extension enables without errors (`cv ext:enable mascode`)
- [ ] Cache clears without issues (`cv flush`)
- [ ] No PHP errors or warnings in logs
- [ ] CiviRules actions register properly
- [ ] Forms render and submit successfully
- [ ] Anonymous form access works (if applicable)

---

*For implementation examples, see the test files in the `tests/` directory.*

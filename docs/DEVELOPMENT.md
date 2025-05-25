# Development Guide

## Overview

This guide provides setup instructions and development workflows for the Mascode CiviCRM extension. Mascode enhances CiviCRM functionality for Management Advisory Services (MAS), a nonprofit providing pro bono consulting services.

## Development Environment Setup

### Prerequisites

- **Platform**: Windows 11 + WSL2 + Ubuntu 22.04 (or similar Linux environment)
- **Web Stack**: Apache 2.4.52+, PHP 8.3+, MySQL 8.0+
- **Tools**: CiviCRM Buildkit, XDebug, VS Code
- **CMS**: WordPress + CiviCRM 6.1+

### Required Extensions

- **CiviRules** (org.civicoop.civirules) - Required for automation logic
- **Action Provider** - Recommended for FormProcessor integration
- **FormProcessor** - Recommended for enhanced form actions

### Initial Setup

1. **Clone the Repository**
   ```bash
   cd /path/to/civicrm/extensions/
   git clone https://github.com/briangflett/mascode.git
   ```

2. **Install Dependencies**
   ```bash
   cd mascode
   composer install  # If composer.json has dependencies
   ```

3. **Enable Extension**
   ```bash
   cv ext:enable mascode
   ```

4. **Verify Installation**
   ```bash
   cv ext:list | grep mascode
   cv api System.check
   ```

## Development Workflow

### Daily Development

1. **Start Development Session**
   ```bash
   cd /path/to/mascode
   git status
   cv ext:list | grep mascode
   ```

2. **Make Code Changes**
   - Edit files in your preferred editor
   - Follow PSR-12 coding standards
   - Use modern CiviCRM patterns (API4, EventDispatcher)

3. **Test Changes**
   ```bash
   cv flush  # Clear CiviCRM cache
   # Test functionality in browser or via API
   ```

4. **Check Logs**
   ```bash
   tail -f /path/to/civicrm/ConfigAndLog/CiviCRM.*.log
   ```

### Common Commands

```bash
# Clear all CiviCRM caches
cv flush

# Run development scripts with debugging
XDEBUG_SESSION=1 cv scr <script-name>

# Check extension status
cv ext:list | grep mascode

# Disable/enable extension for testing
cv ext:disable mascode
cv ext:enable mascode

# Run API calls for testing
cv api4 Contact.get
cv api4 Case.get +w case_type_id=1

# Check system status
cv api System.check
```

### Script Development

Development scripts are located in the `scripts/` directory:

```bash
# Run scripts with debugging enabled
XDEBUG_SESSION=1 cv scr setMascodeSettings.php
XDEBUG_SESSION=1 cv scr temp.php

# List available scripts
ls scripts/
```

## Code Organization

### Preferred Structure

```
Civi/Mascode/              # PSR-4 modern classes (preferred)
├── CiviRules/             # CiviRules components
├── Event/                 # Event subscribers
├── FormProcessor/         # FormProcessor actions
├── Hook/                  # Hook implementations
├── Patches/               # Patch management
└── Util/                  # Utility classes

CRM/Mascode/               # PSR-0 legacy classes (minimize use)
└── Form/                  # Legacy form controllers
```

### Coding Standards

- **PSR-12**: Follow PSR-12 coding standards
- **CiviCRM Conventions**: Use CiviCRM naming and patterns
- **Modern APIs**: Prefer API4 over API3, BAO, DAO
- **Event-Driven**: Use Symfony EventDispatcher when possible
- **Documentation**: PHPDoc blocks for all public methods

### Service Registration

Services are registered in `mascode.php`:

```php
function mascode_civicrm_container(ContainerBuilder $container) {
    $container->register('service.name', ServiceClass::class)
        ->setPublic(true)
        ->addTag('event_subscriber');
}
```

## Testing

### Manual Testing

1. **Extension Functionality**
   ```bash
   cv ext:disable mascode
   cv ext:enable mascode
   # Verify no errors in logs
   ```

2. **Form Processing**
   - Test anonymous form access with checksums
   - Verify form submissions and data processing
   - Check CiviRules triggers and actions

3. **Case Management**
   - Create service requests and verify code generation
   - Test service request to project conversion
   - Verify case relationships and data integrity

### Automated Testing

```bash
# Run PHPUnit tests (when available)
./vendor/bin/phpunit

# Check syntax
php -l mascode.php
find Civi/ -name "*.php" -exec php -l {} \;
```

### Performance Testing

```bash
# Monitor query performance
# Enable CiviCRM debug mode and check slow query logs

# Test bulk operations
XDEBUG_SESSION=1 cv scr test_bulk_operations.php
```

## Debugging

### XDebug Configuration

Ensure XDebug is configured for your development environment:

```ini
; In php.ini
xdebug.mode=debug
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003
```

### Common Issues

1. **Container Not Building**
   ```bash
   cv flush
   # Check error logs for service registration issues
   ```

2. **Events Not Firing**
   ```bash
   # Verify event subscriber registration
   cv api4 System.check
   ```

3. **Patches Not Applying**
   ```bash
   # Check file permissions and patch format
   ls -la Civi/Mascode/Patches/files/
   ```

4. **Cache Issues**
   ```bash
   cv flush
   cv api System.flush
   # Clear browser cache for frontend issues
   ```

### Logging

Use CiviCRM's PSR-3 logger for debugging:

```php
\Civi::log()->info('Debug message', ['context' => $data]);
\Civi::log()->error('Error occurred', ['exception' => $e]);
```

## Database Operations

### Schema Changes

Use CiviCRM's managed entity system for schema changes:

```php
// In managed entities
return [
    [
        'name' => 'CustomField_MasCode',
        'entity' => 'CustomField',
        'params' => [
            'version' => 4,
            'values' => [
                'name' => 'mas_code',
                'label' => 'MAS Code',
                // ... other field properties
            ],
        ],
    ],
];
```

### Data Migration

Create scripts in `scripts/` for data migration:

```php
// scripts/migrate_data.php
<?php
use Civi\Api4\Contact;

$contacts = Contact::get()
    ->addWhere('contact_type', '=', 'Individual')
    ->execute();

foreach ($contacts as $contact) {
    // Migration logic
}
```

## Security Considerations

### Anonymous Access

Always validate checksums for anonymous forms:

```php
// Validate checksum
if (!CRM_Contact_BAO_Contact_Utils::validChecksum($contactId, $checksum)) {
    throw new Exception('Invalid checksum');
}
```

### Input Validation

Sanitize all user inputs:

```php
$input = CRM_Utils_Request::retrieve('param', 'String', $this, TRUE);
$input = CRM_Utils_String::purifyHTML($input);
```

### Logging Security

Never log sensitive information:

```php
// Good
\Civi::log()->info('Contact updated', ['contact_id' => $contactId]);

// Bad - don't log sensitive data
\Civi::log()->info('Contact updated', ['password' => $password]);
```

## Performance Optimization

### Database Queries

- Use API4 for better caching and performance
- Avoid N+1 queries in loops
- Use appropriate indexes for custom queries

### Caching

```php
// Use CiviCRM's caching
$cache = Civi::cache('long');
$result = $cache->get($key);
if ($result === NULL) {
    $result = expensiveOperation();
    $cache->set($key, $result, 3600); // Cache for 1 hour
}
```

### Memory Management

- Be careful with large datasets
- Use generators for iterating over large collections
- Clean up resources in long-running scripts

## Deployment

### Staging Environment

1. Test all changes in staging environment
2. Run full test suite
3. Verify database migrations
4. Check performance impact

### Production Deployment

1. Backup database and files
2. Deploy extension files
3. Run any required migrations
4. Clear caches
5. Verify functionality

### Rollback Procedure

1. Disable extension if issues occur
2. Restore from backup if necessary
3. Investigate issues in development environment
4. Apply fixes and redeploy

## Contributing

### Pull Request Process

1. Create feature branch from main
2. Make changes following coding standards
3. Test thoroughly in development environment
4. Update documentation as needed
5. Submit PR with detailed description
6. Address code review feedback

### Code Review Checklist

- [ ] Follows PSR-12 coding standards
- [ ] Uses modern CiviCRM patterns
- [ ] Includes proper error handling
- [ ] Updates relevant documentation
- [ ] Tested in development environment
- [ ] No sensitive information exposed

## Resources

### Documentation

- [CiviCRM Developer Guide](https://docs.civicrm.org/dev/en/latest/)
- [CiviCRM API4 Documentation](https://docs.civicrm.org/dev/en/latest/api/v4/)
- [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
- [CiviRules Documentation](https://civirules.org/)

### Tools

- [CiviCRM Buildkit](https://github.com/civicrm/civicrm-buildkit)
- [CV CLI Tool](https://github.com/civicrm/cv)
- [PHPUnit Testing Framework](https://phpunit.de/)

### Community

- [CiviCRM Developer Chat](https://chat.civicrm.org/)
- [CiviCRM Stack Exchange](https://civicrm.stackexchange.com/)
- [CiviCRM Developer Forum](https://lab.civicrm.org/)

---

*For technical architecture details, see [docs/ARCHITECTURE.md](ARCHITECTURE.md)*
*For AI assistant instructions, see [.claude/context.md](../.claude/context.md)*
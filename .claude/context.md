# Claude Code Instructions for Mascode Extension

## Overview
The Mascode extension is a CiviCRM extension that provides custom automation and logic for Management Advisory Services (MAS), a nonprofit providing pro bono consulting services. This extension enhances CiviCRM functionality with case management, form processing, and automation features.

## Development Environment

### Platform
- **OS**: Windows 11 + WSL2 + Ubuntu 22.04
- **Web Stack**: Apache 2.4.52, PHP 8.3.11, MySQL 8.0.39
- **Tools**: CiviCRM Buildkit, XDebug, VS Code
- **CMS**: WordPress + CiviCRM

### Common Commands
```bash
# Clear CiviCRM cache
cv flush

# Run extension scripts (with XDebug)
XDEBUG_SESSION=1 cv scr <script-name>

# Check extension status
cv ext:list | grep mascode

# Enable/disable extension
cv ext:enable mascode
cv ext:disable mascode
```

## Testing & Quality Assurance

### Running Tests
```bash
# PHPUnit tests (if available)
./vendor/bin/phpunit

# Check for syntax errors
php -l mascode.php
php -l Civi/Mascode/**/*.php
```

### Code Standards
- Follow PSR-1, PSR-2, PSR-4 standards
- Use CiviCRM coding conventions
- Prefer modern patterns (API4, EventDispatcher, FormBuilder)
- Document all public methods with PHPDoc

## Architecture Guidelines

### Preferred Patterns
1. **API Usage**: Use CiviCRM API4 instead of API3, BAO, or DAO
2. **Events**: Use Symfony EventDispatcher instead of traditional hooks
3. **Forms**: Use FormBuilder (Afform) instead of Smarty forms
4. **Reports**: Use SearchKit instead of traditional reports
5. **Services**: Use Dependency Injection Container for service management

### File Organization
- **PSR-4 Classes**: Place in `Civi/Mascode/` namespace
- **Legacy Classes**: Only use `CRM/Mascode/` for compatibility
- **Templates**: Avoid Smarty templates, prefer FormBuilder
- **Scripts**: Development scripts in `scripts/` directory

## Key Components

### CiviRules Integration
- **Triggers**: Custom triggers for business events
- **Actions**: Automated actions for case management
- **Conditions**: Business logic conditions

### Event Subscribers
- **AfformSubmitSubscriber**: Handles form submissions with anonymous access
- **Event-driven**: Use EventDispatcher for loose coupling

### Core Utilities
- **CodeGenerator**: Generates unique MAS codes (R25001, P25001 format)
- **PatchManager**: Applies CiviCRM core patches
- **ErrorHandler**: Custom error handling and logging

## Development Workflow

### Making Changes
1. Always use `cv flush` after code changes
2. Test changes in development environment
3. Check logs for errors: `tail -f /path/to/civicrm/ConfigAndLog/CiviCRM.*.log`
4. Validate with real data before production

### Adding New Features
1. Create services in `Civi/Mascode/` namespace
2. Register services in `mascode_civicrm_container()`
3. Use event subscribers for loose coupling
4. Follow existing patterns for consistency

### Database Changes
- Use CiviCRM's managed entities for schema changes
- Place entity definitions in appropriate directories
- Test upgrade paths thoroughly

## File Structure Reference

```
/
├── Civi/Mascode/              # PSR-4 modern classes
│   ├── CiviRules/             # CiviRules components
│   ├── Event/                 # Event subscribers
│   ├── FormProcessor/         # FormProcessor actions
│   ├── Hook/                  # Hook implementations
│   ├── Patches/               # Patch management
│   └── Util/                  # Utility classes
├── CRM/Mascode/               # PSR-0 legacy classes (minimize use)
├── templates/                 # Smarty templates (avoid, use FormBuilder)
├── scripts/                   # Development scripts
├── extern/                    # Legacy data conversion scripts
├── ang/                       # Angular/Afform definitions
└── xml/                       # CiviCRM XML configurations
```

## Common Tasks

### Adding a New CiviRule Action
1. Create action class in `Civi/Mascode/CiviRules/Action/`
2. Extend appropriate base class
3. Implement required methods
4. Register in `actions.json`

### Creating Event Subscribers
1. Create subscriber in `Civi/Mascode/Event/`
2. Implement `EventSubscriberInterface`
3. Register in container with `event_subscriber` tag

### Applying Core Patches
1. Place patch files in `Civi/Mascode/Patches/files/`
2. Update `PatchManager` to include new patches
3. Patches apply automatically on install/upgrade

## Debugging & Troubleshooting

### Common Issues
- **Container not building**: Check service definitions and dependencies
- **Events not firing**: Verify event subscriber registration
- **Patches not applying**: Check file permissions and patch format
- **Forms not working**: Verify Afform configuration and permissions

### Logging
```php
// Use CiviCRM's PSR-3 logger
\Civi::log()->info('Debug message', ['context' => $data]);
\Civi::log()->error('Error message', ['exception' => $e]);
```

### Cache Issues
```bash
# Clear all caches
cv flush

# Clear specific caches
cv api System.flush
```

## Security Considerations

### Anonymous Form Access
- Always validate checksums for anonymous access
- Sanitize and validate all input data
- Use proper permission checks

### Data Handling
- Never log sensitive information
- Use proper escaping for output
- Validate all API inputs

## Future AI Integration

### Planned Features
- Contact intelligence and categorization
- Automated report generation
- Donor behavior prediction
- Form optimization suggestions

### Technical Approach
- Primary: OpenAI GPT-4 for function calling and analysis
- Secondary: Anthropic Claude for complex analysis
- Architecture: Direct API integration with PHP
- Fallback: Python microservice if advanced ML needed

## Resources

### Documentation
- [CiviCRM Developer Guide](https://docs.civicrm.org/dev/en/latest/)
- [CiviCRM API4 Documentation](https://docs.civicrm.org/dev/en/latest/api/v4/)
- [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
- [CiviRules Documentation](https://civirules.org/)

### Extension Dependencies
- **Required**: CiviRules (org.civicoop.civirules)
- **Recommended**: Action Provider, FormProcessor
- **Core Version**: CiviCRM 6.1+

## Notes for AI Assistants

### Coding Preferences
- Prioritize API4 over legacy APIs
- Use modern PHP features (PHP 8.3+)
- Follow CiviCRM extension best practices
- Maintain backward compatibility when possible

### Context Awareness
- This is a nonprofit organization extension
- Focus on automation and efficiency features
- Consider volunteer/staff workflow improvements
- Maintain data integrity and security

### Development Reminders
- Always test in development environment first
- Document significant changes in CHANGELOG.md
- Update CONTEXT.md with architectural changes
- Consider impact on existing data and workflows

---
*Last Updated: January 2025*
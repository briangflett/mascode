# Development Guide

## Quick Start

### Prerequisites
- CiviCRM 6.1+ with CiviRules extension enabled
- PHP 8.3+
- Development environment with XDebug

### Installation
```bash
# Clone to CiviCRM extensions directory
git clone https://github.com/briangflett/mascode.git

# Enable extension
cv ext:enable mascode

# Verify installation
cv ext:list | grep mascode
```

## Development Workflow

### Essential Commands
```bash
cv flush                              # Clear cache after changes
XDEBUG_SESSION=1 cv scr <script>     # Run scripts with debugging
cv api4 EntityName.action            # Test API calls
```

### Code Standards
- **API**: Use CiviCRM API4 exclusively
- **Classes**: PSR-4 in `Civi/Mascode/` namespace
- **Forms**: FormBuilder (Afform) preferred over Smarty
- **Events**: EventDispatcher over traditional hooks
- **Standards**: PSR-1/2/4, CiviCRM conventions

### Adding CiviRules Components

#### New Action
1. Create action class in `Civi/Mascode/CiviRules/Action/`
2. Extend `CRM_CivirulesActions_Generic_Api`
3. Register in `Civi/Mascode/CiviRules/actions.json`
4. Create form in `CRM/Mascode/CiviRules/Form/` (legacy namespace)
5. Create template in `templates/CRM/Mascode/CiviRules/Form/`

#### New Event Subscriber
1. Create in `Civi/Mascode/Event/`
2. Implement `EventSubscriberInterface`
3. Register in container with `event_subscriber` tag

### Testing
- Always run `cv flush` after code changes
- Test in development environment before production
- Use XDebug for debugging complex issues
- Check logs: `tail -f path/to/CiviCRM.*.log`

### Troubleshooting
- **Container issues**: Check service definitions and dependencies
- **Events not firing**: Verify subscriber registration and event names
- **Cache problems**: Run `cv flush` and check file permissions
- **Form errors**: Verify template paths and form class inheritance

## Extension Structure
See [ARCHITECTURE.md](ARCHITECTURE.md) for detailed component documentation.

## Resources
- [CiviCRM API4 Docs](https://docs.civicrm.org/dev/en/latest/api/v4/)
- [CiviRules Documentation](https://civirules.org/)
- [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
# Mascode CiviCRM Extension - Claude Context

## Extension Purpose
Mascode enhances CiviCRM for Management Advisory Services (MAS), a nonprofit providing pro bono consulting. Focus: case management automation, form processing, and workflow optimization.

## Architecture & Patterns

### Preferred Development Approach
- **API**: Always use CiviCRM API4 (not API3/BAO/DAO)
- **Events**: Symfony EventDispatcher over traditional hooks
- **Forms**: FormBuilder (Afform) for user-facing, CRM forms for admin tools
- **Reports**: SearchKit over custom reports
- **Classes**: PSR-4 in `Civi/Mascode/` namespace
- **Standards**: PSR-1/2/4, CiviCRM conventions, PHP 8.3+

### Approach Selection by Use Case
- **FormProcessor**: WordPress forms & external data processing (`Civi/Mascode/FormProcessor/Action/`)
- **CiviRules**: Trigger-based automated workflows (extend `CRM_CivirulesActions_Generic_Api`)
- **Symfony Events**: Complex business logic & system integration (`Civi/Mascode/Event/`, extend `AutoSubscriber`)
- **Standalone Forms**: Administrative tools & manual operations (CRM form + navigation menu + event subscriber)
- **Code Constraint**: Changes only within mascode extension, not core CiviCRM

### Key Components
- **CiviRules Integration**: Custom triggers/actions for business logic
- **Event Subscribers**: AfformPrefill/Submit for anonymous form access
- **Code Generator**: Unique MAS codes (R25001, P25001 format)
- **Patch Manager**: Auto-applies CiviCRM core patches

## File Structure
```
Civi/Mascode/              # Modern PSR-4 classes
├── CiviRules/             # Business logic automation
├── Event/                 # Event subscribers
├── FormProcessor/         # Form actions
├── Hook/                  # Hook implementations
├── Patches/               # Core patch management
└── Util/                  # Utilities (CodeGenerator, etc.)

CRM/Mascode/               # Legacy PSR-0 (minimize use)
templates/                 # Smarty templates (avoid)
xml/                       # CiviCRM configurations
```

## Development Workflow

### Essential Commands
```bash
cv flush                   # Clear cache after changes
cv scr <script>            # Run development scripts
cv ext:list | grep mascode # Check extension status
```

### File Patterns
**Include**: `*.php`, `*.js`, `*.json`, `*.xml`, `*.md`, `*.sql`, `*.tpl`, `*.yml`, `*.yaml`
**Exclude**: `vendor/`, `node_modules/`, `.git/`, `cache/`, `tmp/`

### Development Preferences
- **PHP Version**: 8.3
- **Code Style**: PSR-12
- **Max File Size**: 1MB
- **Auto Save**: Disabled

### Making Changes
1. Use `cv flush` after code modifications
2. Test in development environment first
3. Follow existing patterns for consistency
4. Document significant changes

### Adding CiviRules Components
1. Create class in appropriate `Civi/Mascode/CiviRules/` subdirectory
2. Extend correct base class (`CRM_CivirulesActions_Generic_Api` for actions)
3. Register in `Civi/Mascode/CiviRules/actions.json` or `triggers.json`
4. Create form class in `CRM/Mascode/CiviRules/Form/` (legacy namespace required)
5. Create template in `templates/CRM/Mascode/CiviRules/Form/`

### Service Registration
Services registered in `mascode_civicrm_container()` with Symfony DI container:
- Event subscribers auto-registered with `event_subscriber` tag
- Use dependency injection for service management

## Current Features

### Case Management
- Auto-generates MAS codes (R25001 for Service Requests, P25001 for Projects)
- Service Request → Project conversion automation
- Enhanced case role management

### Form Processing
- Anonymous form access with checksum validation
- Afform prefilling for complex scenarios
- Custom submission processing

### Core Patches
- Auto-applies patches for Afform context enhancement
- Case autofill behavior improvements

## AI Integration Strategy
- **Phase 1**: Direct API integration (OpenAI GPT-4 primary, Claude secondary)
- **Focus Areas**: Contact intelligence, report generation, donor analysis
- **Architecture**: PHP-first, microservices only if needed

## Dependencies
- **Required**: CiviRules extension, CiviCRM 6.1+, PHP 8.3+
- **Recommended**: Action Provider, FormProcessor

## Debugging
- **Logging**: Use `\Civi::log()->info()` with context arrays
- **Cache Issues**: Always run `cv flush` after changes
- **XDebug**: Available for development scripts

---
*Optimized for Claude Code efficiency - security details in local CLAUDE.md*
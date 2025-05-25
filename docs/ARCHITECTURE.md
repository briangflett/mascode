# Mascode Extension Documentation

## Overview

The Mascode extension provides custom automation and logic for Management Advisory Services (MAS), a nonprofit providing pro bono consulting services to other nonprofits. This extension enhances CiviCRM functionality with custom case management and form processing. In the future AI integration capabilities will be added, either through this extension or a separate one.

## Technology Stack

### Core Architecture

- **CiviCRM Extension**: Built using Civix framework
- **PHP Version**: 8.3+ required
- **Framework Components**:
  - Symfony Container (Dependency Injection)
  - Symfony EventDispatcher (Event-driven architecture)
  - CiviRules integration for business logic
  - FormProcessor/Action Provider integration
  - FormBuilder (Afform) & Searchkit CiviCRM core components

### Development Environment

- **Platform**: Windows 11 + WSL2 + Ubuntu 22.04
- **Web Stack**: Apache 2.4.52, PHP 8.3.11, MySQL 8.0.39
- **Tools**: CiviCRM Buildkit, XDebug, VS Code
- **CMS Integration**: WordPress + CiviCRM

## Extension Architecture

### Container Services

The extension uses Symfony's Dependency Injection Container for service management:

```php
// Services registered in mascode_civicrm_container()
- mascode.afform_prefill_subscriber: Event subscriber for form prefilling with anonymous access validation
- mascode.afform_submit_subscriber: Event subscriber for form post-submit processing with anonymous access validation
```

### CiviRules Integration

Business logic implemented via CiviRules for maintainability:

#### Triggers

- `mas_unsubscribe_mailingevent`: Mailing unsubscribe events
- `mas_new_case`: Case creation events

#### Actions

- `mas_create_project_from_sr`: Convert service requests to projects
- `mas_generate_mas_code`: Generate MAS codes for cases

#### Custom Components

- **ServiceRequestToProject**: Automated project creation from service requests
- **GenerateMasCode**: Automatic code generation for case tracking
- **MasAddRole**: FormProcessor action for case role assignment

### Core Utilities

- **CodeGenerator**: Generates unique MAS codes (R25001, P25001 format)
- **PatchManager**: Applies core CiviCRM patches for enhanced functionality
- **ErrorHandler**: Custom error handling and logging

## Key Features

### 1. Case Management Enhancement

- **Automatic Code Generation**: Service requests get codes like R25001, projects get P25001
- **Service Request to Project Conversion**: Automated workflow when Service Request status changes to Project Created
- **Case Role Management**: Enhanced relationship management for cases
- **Case Summary Enhancement**: Additional end date display

### 2. Form Processing

- **Anonymous Form Access**: Secure form access with checksum validation
- **Afform Prefilling**: Validate URL parameters and prefill forms with data not handled by FormBuilder (if required)
- **Afform Submission**: Create data not automatically handled by FormBuilder (eg. contact relationships)
- **Contact Form Customization**: Hide/modify contact form fields (WIP)

### 3. AI Integration Strategy

#### Phase 1: API-First Approach (Current Planning)

- **Primary LLM**: OpenAI GPT-4 (better function calling, lower cost)
- **Secondary LLM**: Anthropic Claude (complex analysis tasks)
- **Architecture**: Direct API integration with existing PHP stack
- **Integration Points**:
  - CiviCRM contact enhancement
  - Form analysis and optimization
  - Automated report generation
  - Donor behavior prediction

#### Phase 2: Advanced AI Features (Future)

- **LangChain Patterns**: Implement chain-of-thought processing in PHP
- **Simple AI Agents**: CiviCRM query agents, donor analysis agents
- **Document Processing**: AI-powered report generation

#### Phase 3: Microservices (If Needed)

- **Python Service**: Only if advanced ML features required
- **Hybrid Architecture**: PHP main app + Python AI microservice
- **API Bridge**: RESTful interface between PHP and Python components

### 4. Patch Management

Automated application of CiviCRM core patches:

- **32599.patch**: Afform context enhancement for token generation
- **32600.patch**: Case autofill behavior for form builder

## Installation & Configuration

### Requirements

- CiviCRM 6.1+
- PHP 8.3+
- CiviRules extension
- Action Provider extension (for FormProcessor integration)
- FormProcessor extension (for enhanced form actions)

### Installation Process

1. Install via CiviCRM extension manager
2. Extension automatically:
   - Creates required settings
   - Registers CiviRules components
   - Applies core patches
   - Sets up event subscribers

### Configuration

- **Admin Contact**: Automatically detected MAS Rep contact for project creation
- **Code Generation**: Automatic generation of Service Request and Project codes (Ryynnn & Pyynnn)
- **Patch Application**: Automatic during install/upgrade

## File Structure

### Core Classes

```
Civi/Mascode/               # psr4 PHP namespaces
├── CiviRules/                    # CiviRules components
│   ├── Action/
│   ├── Trigger/
│   └── Form/
├── Event/                        # Event subscribers
│   ├── AfformPrefillSubscriber.php
│   └── AfformSubmitSubscriber.php
├── FormProcessor/Action/         # FormProcessor actions
├── Hook/                         # Hook implementations
│   ├── PostInstallOrUpgradeHook.php
│   └── CaseSummaryHook.php
├── Patches/                      # Patch management
│   ├── PatchManager.php
│   └── files/                    # Patch files
├── Util/                         # Utility classes
│   └── CodeGenerator.php
└── CompilerPass.php              # Container configuration

CRM/Mascode/                # psr0 PEAR style class naming - use psr4 whenever possible
├── Contact/Form/Contact.php      # Contact form customization (WIP)
├── Form/                         # Form controllers (pre FormBuilder) - use FormBuilder whenever possible
├── Report/                       # Custom reports (pre Searchkit) - use Searchkit whenever possible
├── ErrorHandler.php              # Custom error handling
└── Upgrader.php                  # Extension upgrade logic
```

### Configuration Files

```
templates/                        # Smarty templates - use FormBuilder whenever possible
xml/Menu/                         # Menu definitions (WIP)
composer.json                     # Dependencies
info.xml                          # Extension metadata
mascode.php                       # Main extension file
services.yml                      # May use this to define services (Future)
```

### Other Functions

```
extern/                           # Inital data conversion was done by executing these functions from the browser
scripts/                          # Now scripts are executed from the terminal using "XDEBUG_SESSION=1 cv scr <file in scripts directory>"
```

## Development Patterns

### Event-Driven Architecture

```php
// Modern event subscriber pattern
class MySubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents(): array {
        return ['event.name' => 'methodName'];
    }
}
```

### Service Registration

```php
// Dependency injection container usage
function mascode_civicrm_container(ContainerBuilder $container) {
    $container->register('service.name', ServiceClass::class)
        ->addArgument(new Reference('dependency'))
        ->addTag('event_subscriber');
}
```

### API Integration Patterns

```php
// Future AI service integration
class AIService {
    public function enhanceContact($contactId) {
        $contact = $this->getCiviCRMData($contactId);
        $aiInsights = $this->llm->analyze($contact);
        return array_merge($contact, $aiInsights);
    }
}
```

## Future Roadmap

### Planned AI Features

1. **Contact Intelligence**: AI-powered contact categorization and insights
2. **Donor Analysis**: Predictive modeling for donor behavior
3. **Report Generation**: Automated narrative report creation
4. **Form Optimization**: AI-driven form improvement suggestions
5. **Case Management**: Intelligent case routing and priority assignment

### Technical Improvements

1. **Testing Framework**: PHPUnit test suite implementation
2. **Code Quality**: PHPStan/Psalm static analysis integration
3. **Documentation**: Enhanced inline documentation
4. **Performance**: Query optimization and caching strategies

## Maintenance & Support

### Cache Management

- **Container Cache**: Cleared automatically on install/upgrade
- **Development**: Use `cv flush` for cache clearing
- **Template Cache**: Managed by CiviCRM core

### Debugging

- **Logging**: Uses CiviCRM's PSR-3 logger via `Civi::log()`
- **Error Handling**: Custom error handler for extension-specific errors
- **Development Mode**: Enable CiviCRM debug for detailed logging

### Version Control

- **GitHub Repository**: https://github.com/briangflett/mascode
- **Branching**: Feature branches with PR workflow
- **Releases**: Tagged releases with semantic versioning

## Contributing

### Development Setup

1. Clone repository to CiviCRM extensions directory
2. Run `composer install` if dependencies exist
3. Enable extension in CiviCRM
4. Use `cv flush` after code changes

### Code Standards

- **PSR Standards**: Follow PSR-1, PSR-2, PSR-4
- **CiviCRM Conventions**: Use CiviCRM coding standards
- **Documentation**: PHPDoc blocks for all public methods
- **Testing**: Unit tests for business logic

### Pull Request Process

1. Create feature branch from main
2. Implement changes with tests
3. Update documentation
4. Submit PR with detailed description
5. Code review and approval required

## AI Instructions

### Development Preferences
- **API Usage**: Always use CiviCRM API4 instead of API3, BAO, DAO, etc.
- **Event Handling**: Use Symfony EventDispatcher instead of traditional hooks when possible
- **Forms**: Use FormBuilder (Afform) instead of traditional Smarty forms
- **Reports**: Use SearchKit instead of traditional custom reports
- **Code Style**: Follow PSR-4, use modern PHP features, maintain CiviCRM conventions
- **Testing**: Write unit tests for business logic, test all changes in development environment

### Common Commands
```bash
# Clear CiviCRM cache after changes
cv flush

# Run development scripts with debugging
XDEBUG_SESSION=1 cv scr <script-name>

# Check extension status
cv ext:list | grep mascode
```

### Documentation Maintenance
- Update CLAUDE.md for AI assistant instructions
- Update this CONTEXT.md for architectural changes
- Update CHANGELOG.md for version changes
- Regularly update documentation to pass context between conversations

### Resource Links
- [CiviCRM API4 Documentation](https://docs.civicrm.org/dev/en/latest/api/v4/)
- [CiviCRM Extension Development](https://docs.civicrm.org/dev/en/latest/extensions/)
- [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
- [CiviRules Documentation](https://civirules.org/)

---

_Last Updated: January 2025_
_Extension Version: 1.0.0_
_CiviCRM Compatibility: 6.1+_

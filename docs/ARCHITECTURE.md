# Mascode Extension Architecture

## Overview
The Mascode extension provides custom automation and workflow enhancements for Management Advisory Services (MAS), a nonprofit providing pro bono consulting services. Built as a modern CiviCRM extension using event-driven architecture and dependency injection.

## Core Architecture

### Technology Stack
- **CiviCRM Extension**: Built with Civix framework
- **PHP**: 8.3+ required
- **Container**: Symfony Dependency Injection
- **Events**: Symfony EventDispatcher for decoupled architecture
- **Integration**: CiviRules for business logic automation
- **Forms**: FormBuilder (Afform) preferred over legacy Smarty

### Design Principles
- **API-First**: CiviCRM API4 for all data operations
- **Event-Driven**: Loose coupling via EventDispatcher
- **Service-Oriented**: Dependency injection for service management
- **Modern PHP**: PSR-4 autoloading, PHP 8.3+ features
- **Automation-Focused**: Business logic via CiviRules where possible

## Extension Components

### Container Services
Services registered in `mascode_civicrm_container()`:
- `mascode.afform_prefill_subscriber`: Form prefilling with anonymous access validation
- `mascode.afform_submit_subscriber`: Post-submit processing with anonymous access validation

### CiviRules Integration

#### Actions
- `mas_create_project_from_sr`: Convert service requests to projects
- `mas_generate_mas_code`: Generate unique MAS codes for cases
- `mas_preident_relationship`: Create president relationships with employers
- `mas_ed_relationship`: Create executive director relationships with employers
- `mas_add_relationship_to_employer`: Create configurable relationships with employers

#### Triggers
- `mas_unsubscribe_mailingevent`: Mailing unsubscribe events
- `mas_new_case`: Case creation events

### Core Utilities
- **CodeGenerator**: Generates sequential MAS codes (R25001, P25001 format)
- **PatchManager**: Automatic application of CiviCRM core patches
- **ErrorHandler**: Custom error handling and logging

## Key Features

### Case Management Enhancement
- **Automatic Code Generation**: Sequential codes for service requests (R) and projects (P)
- **Workflow Automation**: Service Request → Project conversion via CiviRules
- **Relationship Management**: Enhanced contact-organization relationships
- **Status Tracking**: Custom case status workflows

### Form Processing
- **Anonymous Access**: Secure form access with checksum validation
- **Dynamic Prefilling**: Complex form prefill scenarios beyond core FormBuilder
- **Custom Actions**: Post-submission processing for complex workflows
- **Validation**: Enhanced form validation and error handling

### AI Integration Strategy

#### Phase 1: Direct API Integration (Planned)
- **Primary LLM**: OpenAI GPT-4 for function calling and cost efficiency
- **Secondary LLM**: Anthropic Claude for complex analysis tasks
- **Architecture**: PHP-native integration with existing stack
- **Use Cases**: Contact enhancement, report generation, donor analysis

#### Phase 2: Advanced Features (Future)
- **Intelligent Routing**: AI-powered case assignment
- **Predictive Analytics**: Donor behavior modeling
- **Content Generation**: Automated report narratives
- **Form Optimization**: AI-driven form improvement suggestions

### Patch Management
Automated application of CiviCRM core enhancements:
- **32599.patch**: Afform context enhancement for token generation
- **32600.patch**: Case autofill behavior improvements
- Applied automatically during install/upgrade

## File Structure

### Modern PSR-4 Classes (`Civi/Mascode/`)
```
├── CiviRules/
│   ├── Action/           # Business logic actions
│   ├── Trigger/          # Custom event triggers
│   └── Form/             # Configuration forms (when needed)
├── Event/
│   ├── AfformPrefillSubscriber.php
│   └── AfformSubmitSubscriber.php
├── FormProcessor/Action/ # FormProcessor integration
├── Hook/                 # CiviCRM hook implementations
├── Patches/             # Core patch management
│   ├── PatchManager.php
│   └── files/           # Patch files
├── Util/
│   └── CodeGenerator.php
└── CompilerPass.php     # Container configuration
```

### Legacy PSR-0 Classes (`CRM/Mascode/`)
```
├── CiviRules/Form/      # CiviRules form controllers (required for compatibility)
├── Contact/Form/        # Contact form customization
├── Form/                # Legacy form controllers (minimize use)
├── Report/              # Custom reports (prefer SearchKit)
├── ErrorHandler.php     # Custom error handling
└── Upgrader.php         # Extension upgrade logic
```

### Configuration & Templates
```
templates/               # Smarty templates (minimize use)
├── CRM/Mascode/CiviRules/Form/  # CiviRules form templates
xml/Menu/                # Menu definitions
ang/                     # Angular/Afform definitions
info.xml                 # Extension metadata
mascode.php              # Main extension file
```

## Development Patterns

### Event Subscriber Pattern
```php
class MySubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents(): array {
        return ['event.name' => 'methodName'];
    }
    
    public function methodName(Event $event): void {
        // Event handling logic
    }
}
```

### Service Registration
```php
function mascode_civicrm_container(ContainerBuilder $container) {
    $container->register('service.name', ServiceClass::class)
        ->addArgument(new Reference('dependency'))
        ->addTag('event_subscriber');
}
```

### CiviRules Action Pattern
```php
class MyAction extends \CRM_CivirulesActions_Generic_Api {
    protected function getApiEntity(): string { return 'EntityName'; }
    protected function getApiAction(): string { return 'create'; }
    
    protected function alterApiParameters($params, $triggerData): array {
        // Transform parameters for API call
        return $params;
    }
    
    public function userFriendlyConditionParams(): string {
        // Return human-readable description
    }
}
```

## Installation & Requirements

### Dependencies
- **CiviCRM**: 6.1+ required
- **PHP**: 8.3+ required
- **Extensions**: CiviRules (required), Action Provider (recommended), FormProcessor (recommended)

### Automatic Setup
- CiviRules component registration
- Core patch application
- Event subscriber registration
- Container service configuration

## Performance & Maintenance

### Cache Strategy
- **Container Cache**: Symfony DI container compilation
- **Event Cache**: EventDispatcher listener registration
- **Template Cache**: CiviCRM Smarty template compilation
- **Development**: Use `cv flush` for cache clearing

### Monitoring
- **Logging**: PSR-3 compliant via `Civi::log()`
- **Error Handling**: Custom error handler for extension-specific issues
- **Debug Mode**: CiviCRM debug mode compatibility

### Version Control
- **Repository**: GitHub-based with semantic versioning
- **Releases**: Tagged releases with migration guides
- **Branching**: Feature branches with PR workflow

## Future Roadmap

### Technical Enhancements
- PHPUnit test suite implementation
- Static analysis integration (PHPStan/Psalm)
- Performance optimization and query analysis
- Enhanced documentation and examples

### Feature Expansion
- Advanced AI integration capabilities
- Enhanced reporting and analytics
- Workflow automation improvements
- Integration with additional CiviCRM extensions

---
*Last Updated: June 2025*  
*Extension Version: 1.0.0*  
*CiviCRM Compatibility: 6.1+*
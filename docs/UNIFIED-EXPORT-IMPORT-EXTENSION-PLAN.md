# Unified Export/Import Extension Plan

## Overview

This document captures the key learnings from implementing unified export/import scripts for FormProcessor, Afforms, and CiviRules in the MASCode extension, and provides a roadmap for creating a generalized CiviCRM extension that any organization can use for cross-environment deployment.

## Key Learnings from MASCode Implementation

### Core Architecture Principles

1. **Single Source of Truth**: Eliminate separate dev/prod directories by storing components in environment-neutral format and applying conversions dynamically during export/import.

2. **Environment Auto-Detection**: Automatically detect source and target environments using configurable indicators (hostnames, database names, etc.).

3. **Dynamic ID Mapping**: Handle foreign key differences between environments through comprehensive ID mapping systems.

4. **Metadata-Driven Conversion**: Store conversion metadata alongside exported data to enable intelligent imports.

### Technical Patterns That Work

#### 1. Environment Detection
```php
function detectCurrentEnvironment() {
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) {
        return 'dev';
    }
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'production-domain.com') !== false) {
        return 'prod';
    }
    return 'dev'; // Default to dev for safety
}
```

#### 2. ID Mapping Strategy
- **Export**: Create ID→Name mappings for all foreign keys
- **Import**: Resolve names back to IDs in target environment
- **Common mapping categories**:
  - Location Types (Home/Work ID differences)
  - Case Types (custom case type IDs)
  - Message Templates (email template IDs)
  - Contact Types (organization subtypes)
  - Activity Types (custom activity types)
  - Relationship Types (custom relationships)

#### 3. File Structure Pattern
```
Component/
├── component_name.get.json      # Main component data
├── component_name.mappings.json # ID mappings for conversion
└── component_name.export.log    # Export metadata
```

#### 4. Conversion Functions Architecture
```php
// Generic conversion function pattern
function convertComponentForEnvironment($data, $sourceEnv, $targetEnv) {
    $mappings = getEnvironmentMappings($sourceEnv, $targetEnv);
    return applyIdConversions($data, $mappings);
}
```

### Component-Specific Learnings

#### FormProcessor
- **API**: Uses FormProcessor extension's `ExportToJson` class and `FormProcessorInstance.import` API
- **Key Conversions**: Location types (Home ID 1 ↔ Work ID 3 between environments)
- **Challenge**: Working with extension's file-based import API requiring temporary files

#### Afforms
- **API**: Uses `\Civi\Api4\Afform` for all operations
- **Key Conversions**: Layout IDs, message template IDs, case type IDs
- **Challenge**: Complex nested JSON structure requiring recursive ID conversion

#### CiviRules
- **API**: Uses `\Civi\Api4\CiviRulesRule`, `CiviRulesAction`, `CiviRulesCondition`, `CiviRulesTrigger`
- **Key Conversions**: Minimal - most CiviRules IDs are consistent across environments
- **Challenge**: Complex relationships between rules, actions, conditions, and triggers

## Proposed Generic Extension: "CiviEnvSync"

### Extension Scope

Create a standalone CiviCRM extension that provides:
1. **Web UI** for managing export/import operations
2. **API endpoints** for programmatic access
3. **CLI commands** for automation
4. **Plugin architecture** for supporting additional components

### Phase 1: Core Framework

#### 1.1 Extension Setup
```
org.civicrm.envsync/
├── info.xml
├── envsync.php
├── api/
│   └── v4/
├── Civi/
│   └── Envsync/
│       ├── Component/          # Component-specific handlers
│       ├── Environment/        # Environment detection & mapping
│       ├── Export/            # Export engine
│       └── Import/            # Import engine
├── templates/                 # Web UI templates
├── js/                       # Frontend JavaScript
└── sql/                      # Database schema
```

#### 1.2 Core Classes

**BaseComponent** - Abstract class for all component handlers
```php
abstract class BaseComponent {
    abstract public function export($identifier, $targetEnv);
    abstract public function import($data, $sourceEnv);
    abstract public function validate($data);
    abstract public function getIdMappings($data);
    abstract public function convertIds($data, $mappings);
}
```

**EnvironmentManager** - Handle environment detection and configuration
```php
class EnvironmentManager {
    public function detectEnvironment();
    public function getEnvironmentConfig($env);
    public function getMappingRules($sourceEnv, $targetEnv);
}
```

**ExportEngine** - Orchestrate export operations
```php
class ExportEngine {
    public function exportComponent($componentType, $identifier, $targetEnv);
    public function exportBatch($components, $targetEnv);
    public function createExportPackage($components, $targetEnv);
}
```

**ImportEngine** - Orchestrate import operations
```php
class ImportEngine {
    public function importComponent($componentType, $data, $options);
    public function importBatch($packageData, $options);
    public function validateImport($data, $componentType);
}
```

#### 1.3 Database Schema
```sql
-- Environment configurations
CREATE TABLE civicrm_envsync_environment (
    id int PRIMARY KEY AUTO_INCREMENT,
    name varchar(64) NOT NULL,
    hostname_pattern varchar(255),
    database_pattern varchar(255),
    is_active tinyint DEFAULT 1
);

-- Export/Import logs
CREATE TABLE civicrm_envsync_operation (
    id int PRIMARY KEY AUTO_INCREMENT,
    operation_type enum('export', 'import'),
    component_type varchar(64),
    component_identifier varchar(255),
    source_environment varchar(64),
    target_environment varchar(64),
    status enum('pending', 'running', 'completed', 'failed'),
    created_date timestamp DEFAULT CURRENT_TIMESTAMP,
    completed_date timestamp NULL,
    error_message text
);

-- ID mapping cache
CREATE TABLE civicrm_envsync_mapping (
    id int PRIMARY KEY AUTO_INCREMENT,
    source_environment varchar(64),
    target_environment varchar(64),
    entity_type varchar(64),
    source_id int,
    source_name varchar(255),
    target_id int,
    created_date timestamp DEFAULT CURRENT_TIMESTAMP
);
```

### Phase 2: Component Implementation

#### 2.1 Priority Components (based on MASCode learnings)
1. **Afforms** - Most complex, good test case
2. **FormProcessor** - Established patterns
3. **CiviRules** - Relationship complexity

#### 2.2 Component Handler Examples

**AfformComponent.php**
```php
class AfformComponent extends BaseComponent {
    public function export($formName, $targetEnv) {
        $form = \Civi\Api4\Afform::get()
            ->addWhere('name', '=', $formName)
            ->execute()
            ->first();
            
        $mappings = $this->createIdMappings($form);
        $converted = $this->convertForEnvironment($form, $targetEnv);
        
        return [
            'data' => $converted,
            'mappings' => $mappings,
            'metadata' => $this->createMetadata($formName, $targetEnv)
        ];
    }
    
    // Implementation of abstract methods...
}
```

#### 2.3 Web UI Components
- **Dashboard**: Overview of available components and recent operations
- **Export Wizard**: Step-by-step export configuration
- **Import Manager**: Upload and import packages with validation
- **Environment Config**: Manage environment settings and mappings
- **Operation History**: Log and status of all export/import operations

### Phase 3: Advanced Features

#### 3.1 Dependency Resolution
- Automatically detect and include dependent components
- Handle circular dependencies
- Provide dependency visualization

#### 3.2 Conflict Resolution
- Detect existing components during import
- Provide merge strategies (skip, update, rename)
- Show diff views for conflicts

#### 3.3 Batch Operations
- Export/import multiple components as packages
- Schedule automated sync operations
- Rollback capabilities

#### 3.4 Plugin Architecture
```php
interface ComponentPlugin {
    public function getComponentType(): string;
    public function isSupported(): bool;
    public function createHandler(): BaseComponent;
}
```

### Phase 4: Distribution & Community

#### 4.1 Extension Distribution
- Publish to CiviCRM extension directory
- Provide comprehensive documentation
- Create video tutorials

#### 4.2 Community Plugins
- Document plugin development API
- Provide template/skeleton plugins
- Maintain registry of community plugins

## Implementation Timeline

### Milestone 1: Framework (4-6 weeks)
- Core architecture and base classes
- Database schema and installation
- Basic web UI framework
- Environment detection system

### Milestone 2: First Component (2-3 weeks)
- Implement Afform component handler
- Basic export/import functionality
- Web UI for Afform operations
- Testing and validation

### Milestone 3: Additional Components (3-4 weeks)
- FormProcessor component handler
- CiviRules component handler
- Batch operations
- Enhanced UI features

### Milestone 4: Advanced Features (4-6 weeks)
- Dependency resolution
- Conflict resolution
- Plugin architecture
- Documentation and tutorials

### Milestone 5: Release (2-3 weeks)
- Final testing and bug fixes
- Performance optimization
- Extension directory submission
- Community outreach

## Technical Considerations

### Security
- Validate all imported data
- Sanitize file uploads
- Implement permission checks
- Log all operations for audit

### Performance
- Stream large exports/imports
- Implement progress indicators
- Cache mapping data
- Optimize database queries

### Reliability
- Transaction-based operations
- Rollback capabilities
- Comprehensive error handling
- Operation recovery mechanisms

### Extensibility
- Plugin architecture for new components
- Configurable mapping rules
- Custom validation hooks
- Event system for integrations

## Success Metrics

1. **Adoption**: Number of organizations using the extension
2. **Component Coverage**: Number of supported CiviCRM components
3. **Community Plugins**: Number of third-party component handlers
4. **Reliability**: Success rate of export/import operations
5. **Performance**: Time to export/import common component sets

## Conclusion

The unified export/import approach developed for MASCode demonstrates significant potential for generalization. By creating a dedicated extension with proper architecture, web UI, and plugin system, we can provide the CiviCRM community with a powerful tool for managing cross-environment deployments.

The key to success will be:
1. **Solid architecture** that can handle diverse component types
2. **Intuitive UI** that makes the tool accessible to non-technical users  
3. **Comprehensive documentation** that enables community contributions
4. **Robust testing** across different CiviCRM configurations
5. **Active maintenance** to support new CiviCRM versions and components

This extension could significantly improve CiviCRM development workflows and reduce the complexity of managing multi-environment deployments.
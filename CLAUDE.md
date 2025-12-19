# MASCode Extension Development Guide

## Quick Reference

- **Framework**: CiviCRM on WordPress
- **Branch**: master (single branch workflow)
- **Database Credentials**: `/home/brian/.config/development/databases.env`
- **CV Binary**: `/home/brian/buildkit/bin/cv --user=admin`
- **Cache Clear**: `/home/brian/buildkit/bin/cv flush` (run after all code changes)

## Development Approaches

### When to Use Each Pattern

1. **Afform Relationships** - Automatic relationship creation on form submission
   - **See**: [docs/AFFORM-RELATIONSHIPS.md](docs/AFFORM-RELATIONSHIPS.md)
   - For: RCS form, Survey forms
   - Pattern: Event subscribers handle post-submission relationship creation

2. **CiviRules** - Trigger-based automation
   - Best for: Automated responses to CiviCRM events, business rules
   - Pattern: Actions extend `CRM_CivirulesActions_Generic_Api`
   - Database: Tables start with `civirules_`

3. **Symfony Events** - Complex business logic
   - Best for: System integration, decoupled architecture
   - Pattern: Subscribers in `Civi/Mascode/Event/`, extend `AutoSubscriber`

4. **FormProcessor** - External data processing
   - Best for: WordPress forms, data import/export
   - Pattern: Actions in `Civi/Mascode/FormProcessor/Action/`

5. **CRM Forms** - Administrative functions
   - Best for: Backend admin tools, complex validation
   - Location: `CRM/Mascode/Form/` + `templates/CRM/Mascode/Form/`

### Form Development

**Afforms (Preferred for public/user forms)**:
- **Managed in Database** - Create/edit in FormBuilder UI
- Naming: Prefix with "afformMAS" (e.g., `afformMASRCSForm`)
- Deployment: Manually replicate or use API4 export/import
- **See**: "Afform Management" section below

**CRM Forms (For admin tools)**:
- Traditional approach for backend functionality
- Complex validation and business logic
- File-based in `CRM/Mascode/Form/` + `templates/`

## API4 Patterns (CRITICAL)

**ALWAYS use CiviCRM API4. NEVER use direct SQL.**

```php
// Use FALSE as first parameter to suppress permissions
\Civi\Api4\EntityName::action(FALSE)
    ->addWhere('field', '=', 'value')
    ->addValue('field', 'value')
    ->execute();

// ALWAYS use names, not IDs for cross-environment compatibility
\Civi\Api4\CustomField::get(FALSE)
    ->addWhere('custom_group_id:name', '=', 'Group_Name')
    ->addWhere('name', '=', 'field_name')
    ->execute();
```

## Afform Management

**Database-First Approach** (Current Method):
- Create and edit Afforms using FormBuilder UI in CiviCRM
- Afforms stored in database, not files
- Deployment: Manual replication or API4 export/import

**Why Database Storage?**:
- Avoids cross-environment ID conflicts
- No file sync issues between dev/prod
- Easier to modify and test in UI
- Clean separation from code changes

**Deployment Options**:

1. **Manual Replication** (Recommended for complex forms):
   - Recreate form in production using FormBuilder
   - Copy settings and field configurations
   - Most reliable for forms with many customizations

2. **API4 Export/Import** (For simple forms):
   ```php
   // Export from dev
   $afform = \Civi\Api4\Afform::get(FALSE)
     ->addWhere('name', '=', 'afformMASFormName')
     ->execute()->first();

   // Import to prod (after adjusting any environment-specific values)
   \Civi\Api4\Afform::create(FALSE)
     ->setValues($afform)
     ->execute();
   ```

**Key Principles**:
- **Naming**: Always prefix with "afformMAS"
- **Field References**: Use names, never IDs (custom fields, relationships, etc.)
- **Tags**: Use Client, VC, Dashlet, Admin, or Block tags (see `ang/README.md`)
- **Cache**: Run `cv flush` after any changes

## CV Command Patterns

```bash
# Script execution
cv scr /path/to/script.php --user=admin

# With debugging
XDEBUG_SESSION=1 cv scr /path/to/script.php --user=admin

# Extension management
cv ext:list | grep mascode
cv flush  # Always after code changes
```

## Documentation Map

**Core Development**:
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Extension architecture and components
- [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) - Development workflow and deployment
- [docs/INSTALLATION.md](docs/INSTALLATION.md) - Setup and installation

**Feature-Specific**:
- [docs/AFFORM-RELATIONSHIPS.md](docs/AFFORM-RELATIONSHIPS.md) - Automatic relationship creation (RCS form)
- [docs/TESTING.md](docs/TESTING.md) - Testing framework and practices
- [docs/VC_ACL_SETUP_INSTRUCTIONS.md](docs/VC_ACL_SETUP_INSTRUCTIONS.md) - Volunteer Consultant ACL setup

**Strategic/Planning**:
- [docs/AI-ROADMAP.md](docs/AI-ROADMAP.md) - Future AI extension vision
- [docs/UNIFIED-EXPORT-IMPORT-EXTENSION-PLAN.md](docs/UNIFIED-EXPORT-IMPORT-EXTENSION-PLAN.md) - Export/import architecture
- [docs/SASS_MIGRATION_PLAN.md](docs/SASS_MIGRATION_PLAN.md) - SASS form migration strategy

**Reference**:
- [docs/USER-GUIDE.md](docs/USER-GUIDE.md) - End-user documentation
- [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) - Contribution guidelines
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) - Production deployment process

## Code Verification Protocol

**Before using any CiviCRM entities, actions, or methods, verify they exist:**
- Check source in `/home/brian/buildkit/build/masdemo/web/wp-content/plugins/civicrm/civicrm/`
- Check extensions in `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext/`
- Never assume APIs exist without verification

## PHP Code Standards

- **Auto-formatter**: PHP Intelephense (PSR-12)
- Lowercase booleans: `false`, `true`, `null`
- Array syntax: `array()` not `array ()`
- Files auto-format on save

## Database Access

**Credentials**: `/home/brian/.config/development/databases.env`

**Development**:
- WordPress DB: `MASDEMO_WP_DB_NAME`
- CiviCRM DB: `MASDEMO_CIVI_DB_NAME`
- User/Pass: `MYSQL_ROOT_USER`, `MYSQL_ROOT_PASSWORD`

**Production** (via SSH):
- Access: `/home/brian/workspace/shell-scripts/db-tunnel.sh`
- Credentials: `PROD_DB_*` variables

## Release Process

1. Update version/releaseDate in `info.xml`
2. Commit and push to GitHub master branch
3. Pull from GitHub in production
4. Run `cv flush` in production

**Manual deployment only** - no automated releases.

## Common Commands

```bash
# Cache management (ALWAYS after code changes)
/home/brian/buildkit/bin/cv flush

# View extension status
/home/brian/buildkit/bin/cv ext:list | grep mascode

# Debug script execution
XDEBUG_SESSION=1 /home/brian/buildkit/bin/cv scr <script> --user=admin
```

## Need More Detail?

Refer to the appropriate documentation file in `docs/` based on the area you're working on. This file provides quick reference; detailed docs contain comprehensive information for specific features and workflows.

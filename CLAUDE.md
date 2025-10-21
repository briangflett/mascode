# MASCode Extension Development Guide

## Project Configuration

- **Framework**: Latest versions of CiviCRM on WordPress
- **Branch**: dev
- **Environment Details**: See CLAUDE.local.md for sensitive paths, credentials, and local configuration

## Preferred Development Approaches

### When to Use Each Approach

1. **FormProcessor**: For processing WordPress forms and external data submissions
   - Best for: WordPress form integration, data import/export, external API processing
   - Pattern: Create action class in `Civi/Mascode/FormProcessor/Action/`

2. **CiviRules**: For trigger-based logic and automated workflows
   - Best for: Automated responses to CiviCRM events, business rule enforcement
   - Pattern: Actions extend `CRM_CivirulesActions_Generic_Api`, use legacy PSR-0 forms
   - Database: CiviRules table names start with `civirules_`

3. **Symfony Events**: For event-based logic and cross-system integration
   - Best for: Complex business logic, system integration, decoupled architecture
   - Pattern: Create subscribers in `Civi/Mascode/Event/`, extend `AutoSubscriber`

4. **Standalone Forms + Backend Logic**: For manually triggered administrative functions
   - Best for: Admin tools, data management functions, one-off operations
   - Pattern: CRM form class + navigation menu + event subscriber for business logic
   - Example: Cases → "Move Cases Between Organizations" (recently implemented)

### Form Development Guidelines

- **Traditional CRM Forms**: Use for administrative functions, complex validation, legacy integration
  - Location: `CRM/Mascode/Form/` with matching templates in `templates/CRM/Mascode/Form/`
  - Best for: Backend admin tools, complex business logic forms

- **FormBuilder (Afforms) - Preferred**: Use for user-facing forms, simple data collection
  - Location: `ang/` directory with `.aff.html` and `.aff.json` files
  - Best for: Public forms, simple data entry, modern UI requirements
  - Naming: Always prefix with "afformMAS" (e.g., `afformMASProjectCloseVcReport`)

### Code Constraints
- **Limitation**: Changes can be made to the mascode extension only, not core CiviCRM code
- **Extension Directory**: All custom code must be within the extension namespace
- **API Usage**: Always use CiviCRM API4, never direct database access except in established patterns

## CiviCRM API and Afform Management

### API User Authentication
- **User Configuration**: See CLAUDE.local.md for specific user credentials and authentication details
- **CV Commands**: See CLAUDE.local.md for correct user parameter and command patterns
- **API4 Calls**: Always use API4 for operations in this working directory, following these patterns:
  - Use the `\Civi\Api4\` namespace for all API4 calls
  - Chain methods like `.get()`, `.create()`, `.update()`, `.delete()`
  - Use `.addWhere()` for filtering
  - Use `.addValue()` for setting values
  - Always add `FALSE` as the first parameter to suppress default permissions
  - End calls with `.execute()` to run the API request

### Afform Management Best Practices

**Deployment Method**: Use `civix export` to bundle Afforms into extension files
- **Export Afforms**: `civix export Afform afformMASFormName` from extension directory
- **Export SearchKit + Afforms**: `civix export SavedSearch [id]` exports search with all displays/forms
- **File Location**: Generated in `ang/` directory as `.aff.html` and `.aff.json` files
- **Version Control**: Commit generated files to Git for deployment across environments

**File-Based Deployment Benefits**:
- Afforms in extensions serve as default/base state
- User customizations are non-destructive (stored separately from packaged version)
- Built-in "Revert" button to restore packaged version
- No need for deployment scripts or API4 updates for form deployment

**Field Identification** (Critical for cross-environment compatibility):
- **Custom Fields**: Always use `custom_group_name.field_name` notation (e.g., `Project_Information.project_name`)
- **Never use numeric IDs**: IDs differ between development and production environments
- **Entity References**: Use names for option groups, relationship types, contact subtypes, etc.
- **Verification**: Check exported `.aff.html` and `.aff.json` files use names, not IDs

**When to Use API4 for Afforms**:
- Runtime queries: `\Civi\Api4\Afform::get()` to read form data
- One-off administrative operations (rare cases only)
- **Not for deployment**: Don't use API4 updates to deploy forms to production

**Naming Convention**: Always prefix with "afformMAS" (e.g., `afformMASProjectCloseVcReport`)
**Cache Management**: Run `cv flush` after Afform changes (see CLAUDE.local.md for exact paths)

### Verified API Patterns
```php
// Update custom fields (use names, not IDs)
\Civi\Api4\CustomField::update(FALSE)
    ->addWhere('custom_group_id:name', '=', 'Custom_Group_Name')
    ->addWhere('name', '=', 'field_name')
    ->addValue('help_pre', null)
    ->execute();

// Get custom field by group and field name
\Civi\Api4\CustomField::get(FALSE)
    ->addWhere('custom_group_id:name', '=', 'Custom_Group_Name')
    ->addWhere('name', '=', 'field_name')
    ->execute();

// Get Afform entities
\Civi\Api4\Afform::get(FALSE)
    ->addWhere('name', '=', 'afformMASFormName')
    ->execute();

// Runtime Afform operations (not for deployment)
\Civi\Api4\Afform::update(FALSE)
    ->addWhere('name', '=', 'afformMASFormName')
    ->addValue('layout', $newLayout)
    ->execute();
```

### CV Command Execution Patterns
```bash
# CORRECT: Use cv scr with file path (not -e flag)
cv scr /path/to/script.php --user=<username>

# INCORRECT: cv scr -e does not exist
cv scr -e 'php code here'

# WORKAROUND: Write temporary PHP files for complex operations
echo '<?php /* code */' > /tmp/script.php
cv scr /tmp/script.php --user=<username>

# GitHub CLI patterns
gh pr create --base master --head dev --title "Title" --body "Description"
gh pr merge [number] --squash
gh release create "vX.X.X" --title "Title" --notes "Release notes"
```

**Note**: See CLAUDE.local.md for specific CV binary paths, usernames, and authentication details.

### Release Automation Troubleshooting
```bash
# If release.sh hangs on interactive prompts:
# 1. Check working directory is clean
git status
git add . && git commit -m "Message"

# 2. Run manual release steps
# Update version in info.xml, CHANGELOG.md, releases.json
# Commit, push, create PR, merge, tag, release

# 3. Alternative: Use printf with input responses
printf "y\nChangelog item 1\nChangelog item 2\n\n" | ./.claude/commands/release.sh patch
```

### Custom Field Management
- **Use Names for Identification**: Always use `custom_group_id:name` and field `name` in API calls
- **Cross-Environment Compatibility**: Names remain consistent across dev/production, IDs do not
- **Field Updates**: Use `CustomField::update()` API with name-based queries (see Verified API Patterns)
- **Cache Management**: Always flush cache after custom field property changes

### Release Process Patterns
- **Interactive Script Issues**: If automated release script hangs on prompts, run manual release steps
- **Clean Working Directory**: Always commit changes before running release scripts
- **Manual Release Steps**: Update info.xml version/date → Update CHANGELOG.md → Update releases.json → Commit → PR → Tag → Release
- **PR Creation**: Use `gh pr create --base master --head dev` for explicit branch specification
- **Branch Management**: Always return to dev branch after release for continued development

## Extension Documentation

- **Architecture Details**: See `docs/ARCHITECTURE.md`
- **Development Guide**: See `docs/DEVELOPMENT.md`
- **Installation Guide**: See `docs/INSTALLATION.md`
- **User Guide**: See `docs/USER-GUIDE.md`
- **Extension Plan**: See `docs/UNIFIED-EXPORT-IMPORT-EXTENSION-PLAN.md`

## Release Process

**IMPORTANT**: Always use the automated release script:

```bash
./.claude/commands/release.sh [patch|minor|major]
```

The script automates:
1. **Pre-release checks**: Validates clean working directory and dev branch
2. **Version update**: Updates version and release date in `info.xml`
3. **Changelog update**: Updates `CHANGELOG.md` and `releases.json`
4. **Commit and push**: Commits version changes and pushes to dev branch
5. **Create PR**: Creates pull request from dev to master branch
6. **Merge to master**: Automatically merges pull request to update master branch
7. **Create release**: Creates GitHub release with tag and release notes
8. **Return to dev**: Switches back to dev branch for continued development

### Manual Process (Only if script unavailable)
1. Pre-release checks, version update, commit/tag, push changes
2. Create PR from dev to master, merge PR
3. Sync dev branch with master, verify release

## PHP Code Formatting

- **Auto-formatter**: PHP Intelephense with "Format" enabled automatically formats PHP files on save
- **Standards**: Follows PSR-12 coding standards
- **Key Conventions**: 
  - Use lowercase boolean constants: `false`, `true`, `null` (not `FALSE`, `TRUE`, `NULL`)
  - Array syntax: `array()` not `array ()`
  - Function braces on new lines
  - Consistent spacing and indentation
- **Note**: Files created should follow these conventions to prevent automatic reformatting on save

## Local Configuration

- **Sensitive Data**: See CLAUDE.local.md for database credentials and admin access (not in repo)
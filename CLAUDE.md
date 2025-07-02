# MASCode Extension Development Guide

## Project Configuration

- **Framework**: CiviCRM 6.1.0 on WordPress 6.8
- **Branch**: dev
- **Log File Directory**: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ConfigAndLog/`

## MASCode-Specific Development Context

- **Focus**: Unified export/import system for FormProcessor, Afforms, and CiviRules
- **Pattern Learned**: CiviRules actions extend `CRM_CivirulesActions_Generic_Api`
- **Form Integration**: Legacy PSR-0 classes required for CiviRules forms
- **API Usage**: Use `CiviRuleRuleAction` entity for saving action parameters
- **Database Note**: CiviRules table names start with civirules\_
- **API Namespace**: Use `/Civi/Api4/CiviCase` not `/Civi/Api4/Case`

## CiviCRM API and Afform Management

### API User Authentication
- **Correct User**: `brian.flett@masadvise.org` (not `admin`)
- **CV Commands**: Use `--user=brian.flett@masadvise.org` parameter

### Afform Management Best Practices
- **Updating Afforms**: Use CiviCRM API4 to update existing forms, not file manipulation
- **Custom Field Updates**: Use `CustomField::update()` API to modify field properties like `help_pre`
- **Cache Management**: Always flush cache after Afform or custom field changes using `/home/brian/buildkit/bin/cv flush`
- **Field Identification**: Custom fields can be identified by custom_group_id and field name
- **Form Layout Updates**: Export current forms first, then update deployment scripts with actual layouts
- **Deployment Script Updates**: Replace layout generation functions with exported layouts for accuracy

### Verified API Patterns
```php
// Update custom fields
\Civi\Api4\CustomField::update(FALSE)
    ->addWhere('custom_group_id', '=', $groupId)
    ->addWhere('name', '=', $fieldName)
    ->addValue('help_pre', null)
    ->execute();

// Get Afform entities
\Civi\Api4\Afform::get(FALSE)
    ->addWhere('name', '=', 'afformName')
    ->execute();

// Update Afform layouts
\Civi\Api4\Afform::update(FALSE)
    ->addWhere('name', '=', 'afformName')
    ->addValue('layout', $newLayout)
    ->execute();
```

### CV Command Execution Patterns
```bash
# CORRECT: Use cv scr with file path (not -e flag)
/home/brian/buildkit/bin/cv scr /path/to/script.php --user=brian.flett@masadvise.org

# INCORRECT: cv scr -e does not exist
/home/brian/buildkit/bin/cv scr -e 'php code here'

# WORKAROUND: Write temporary PHP files for complex operations
echo '<?php /* code */' > /tmp/script.php
/home/brian/buildkit/bin/cv scr /tmp/script.php --user=brian.flett@masadvise.org
```

### Deployment Script Management
- **Form Export Process**: Use API4 to export current form layouts from development environment
- **Layout Function Replacement**: Replace generic layout functions with actual exported form structures
- **Overwrite vs Skip**: Update deployment scripts to overwrite existing forms rather than skip them
- **Multi-Form Handling**: Use separate functions for different form variants (SASS vs SASF)

### Error Recovery Patterns
- **JSON Parsing Issues**: When processing large exports, break into smaller files
- **Complex String Replacement**: Use manual parsing and reconstruction for complex data structures
- **Function Finding**: Use bracket counting to find function boundaries in code replacement

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

## Local Configuration

- **Sensitive Data**: See CLAUDE.local.md for database credentials and admin access (not in repo)
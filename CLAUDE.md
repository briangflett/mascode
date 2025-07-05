# MASCode Extension Development Guide

## Project Configuration

- **Framework**: CiviCRM 6.1.0 on WordPress 6.8
- **Branch**: dev
- **Database Details**: See CLAUDE.local.md for sensitive database and directory information

## MASCode-Specific Development Context

- **Focus**: Unified export/import system for FormProcessor, Afforms, and CiviRules
- **Pattern Learned**: CiviRules actions extend `CRM_CivirulesActions_Generic_Api`
- **Form Integration**: Legacy PSR-0 classes required for CiviRules forms
- **API Usage**: Use `CiviRuleRuleAction` entity for saving action parameters
- **Database Note**: CiviRules table names start with civirules\_
- **API Namespace**: Use `/Civi/Api4/CiviCase` not `/Civi/Api4/Case`

## CiviCRM API and Afform Management

### API User Authentication
- **User Configuration**: See CLAUDE.local.md for specific user credentials and authentication details

### Afform Management Best Practices
- **Updating Afforms**: Use CiviCRM API4 to update existing forms, not file manipulation
- **Custom Field Updates**: Use `CustomField::update()` API to modify field properties like `help_pre`
- **Cache Management**: Always flush cache after Afform or custom field changes using CV flush command (see CLAUDE.local.md for exact paths)
- **Field Identification**: Custom fields can be identified by custom_group_id and field name
- **Form Layout Updates**: Export current forms first, then update deployment scripts with actual layouts
- **Deployment Script Updates**: Replace layout generation functions with exported layouts for accuracy
- **Afform Naming Convention**: Always prefix custom Afforms with "afformMAS" (e.g., afformMASProjectCloseVcReport)

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

### Deployment Script Management
- **Form Export Process**: Use API4 to export current form layouts from development environment
- **Layout Function Replacement**: Replace generic layout functions with actual exported form structures
- **Overwrite vs Skip**: Update deployment scripts to overwrite existing forms rather than skip them
- **Multi-Form Handling**: Use separate functions for different form variants (SASS vs SASF)

### Error Recovery Patterns
- **JSON Parsing Issues**: When processing large exports, break into smaller files
- **Complex String Replacement**: Use manual parsing and reconstruction for complex data structures
- **Function Finding**: Use bracket counting to find function boundaries in code replacement

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
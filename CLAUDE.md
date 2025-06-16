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
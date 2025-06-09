# MASCode Extension Development Guide

## Project Configuration

- **Framework**: CiviCRM 6.1.0 on WordPress 6.8
- **Branch**: dev

## System Paths

- **Project & Extension Root**: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext/mascode/`
- **CV Tool**: `/home/brian/buildkit/bin/cv`
- **Log File Directory**: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ConfigAndLog/`

## Essential Commands

```bash
# Cache management
/home/brian/buildkit/bin/cv flush

# Script execution with debugging
XDEBUG_SESSION=1 /home/brian/buildkit/bin/cv scr <script>

# Extension management
/home/brian/buildkit/bin/cv ext:list | grep mascode
```

## Development Environment Notes

- **XDebug**: Available for debugging
- **Logging**: Active with detailed error tracking
- **Cache**: Always run `cv flush` after code changes
- **Local Config**: See CLAUDE.local.md for database credentials and admin access (not in repo)

## Recent Development Context

- **Focus**: CiviRules actions for employer relationship management
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

## Development Workflow

1. Start Claude Code in the mascode extension directory: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext/mascode/`
2. Reference CiviCRM core files using absolute paths: `/home/brian/buildkit/build/masdemo/web/wp-content/plugins/civicrm/civicrm/`
3. Reference other CiviCRM extension files using absolute paths: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/civicrm/ext/`
4. Always flush cache after code changes: `/home/brian/buildkit/bin/cv flush`
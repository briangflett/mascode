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

## Local Configuration

- **Sensitive Data**: See CLAUDE.local.md for database credentials and admin access (not in repo)
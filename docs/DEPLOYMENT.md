# Deployment Guide

## Overview

The MASCode extension uses a combination of script-based, file-based, and manual deployments to move configurations between development and production environments.

## Deployment Strategy

### Automated Components (Script-Based)

| Component | Script | Notes |
|-----------|--------|-------|
| Custom Fields | `scripts/deploy_custom_fields.php` | Field groups and fields |
| CiviRules | `scripts/deploy_civirules.php` | Actions, triggers, conditions |

Use SearchKit searches to verify all custom fields and rules exist in both environments.

### File-Based Components (Version Controlled)

Afforms are stored in the extension at `ang/` and deployed via git pull:
- RCS Form (Request for Consulting Services)
- SASS Form (Short Self Assessment Survey)
- SASF Form (Full Self Assessment Survey)
- ProjectCloseClientFeedback Form
- ProjectCloseVCFeedback Form

These files originate from CiviCRM FormBuilder in dev (`/wp-content/uploads/civicrm/ang`) and are copied to the extension at `ang/`.

### Manual Components (UI-Based)

- **Form Processors**: Use CiviCRM's built-in export/import. See `scripts/deploy_form_processors.md`.

## Production Deployment Workflow

```bash
# 1. SSH to production
ssh mas-prod

# 2. Pull latest code
cd /path/to/civicrm/ext/mascode
git pull origin master

# 3. Run deployment scripts (if config changed)
cv scr scripts/deploy_custom_fields.php --user=admin
cv scr scripts/deploy_civirules.php --user=admin

# 4. Clear cache
cv flush
```

See [PRODUCTION-OPS.md](PRODUCTION-OPS.md) for detailed production procedures.

---

*For development workflow, see [DEVELOPMENT.md](DEVELOPMENT.md).*

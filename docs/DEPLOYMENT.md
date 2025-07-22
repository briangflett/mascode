# Deployment Guide

## Overview

The MASCode extension uses a combination of file-based and script-based deployments to reliably move configurations between development and production environments.

## Deployment Strategy

### Automated Components (Script-Based)
- **Custom Fields** (various field groups, using deploy_custom_fields.php)
- **CiviRules** (actions, triggers, conditions using deploy_civirules.php)

Use Searchkit searches to ensure all custom fields and rules are in both environments.  

### File-Based Components (Version Controlled)
- **RCS Form** (Request for Consulting Services) 
- **SASS Form** (Short Self Assessment Survey) 
- **SASF Form** (Full Self Assessment Survey) 

These files are created by the system in the development environment at /wp-content/uploads/civicrm/ang and copied to /wp-content/uploads/civicrm/ext/mascode/ang.

### Manual Components (UI-Based)
- **Form Processors** (reliable built-in export/import, see deploy_form_processors.md)

---
*This deployment guide is part of the MAS Extension unified development system. For development workflow, see [DEVELOPMENT.md](DEVELOPMENT.md).*
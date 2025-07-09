# Deployment Guide

## Overview

The MASCode extension uses a combination of file-based and script-based deployments to reliably move configurations between development and production environments.

## Deployment Strategy

### Automated Components (Script-Based)
- **SASS Custom Fields** (Short Self Assessment Survey questions)
- **SASF Custom Fields** (Full Self Assessment Survey questions)
- **CiviRules** (actions, triggers, conditions)  

### File-Based Components (Version Controlled)
- **RCS Form** (Request for Consulting Services) 
- **SASS Form** (Short Self Assessment Survey) 
- **SASF Form** (Full Self Assessment Survey) 
These files are created by the system in the development environment at /wp-content/uploads/civicrm/ang

### Manual Components (UI-Based)
- **Form Processors** (reliable built-in export/import)

---
*This deployment guide is part of the MAS Extension unified development system. For development workflow, see [DEVELOPMENT.md](DEVELOPMENT.md).*
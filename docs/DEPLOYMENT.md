# Deployment Guide

## Overview

The MASCode extension uses script-based deployment to reliably move configurations between development and production environments. This approach replaces the previous fragile export/import system with robust, environment-aware deployment scripts.

## Deployment Strategy

### Automated Components (Script-Based)
- **Self Assessment Surveys** (SASS/SASF)
- **CiviRules** (actions, triggers, conditions)  
- **RCS Form** (Request for Consulting Services)

### Manual Components (UI-Based)
- **Form Processors** (reliable built-in export/import)

## Environment Configuration

### Development Environment
- **Location**: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext/mascode/`
- **URL**: `https://masdemo.localhost/`
- **Database**: `mas_dev_civi`

### Production Environment  
- **Location**: Production CiviCRM extension directory
- **URL**: Production URL
- **Database**: Production database

## Automated Deployment Scripts

### 1. Self Assessment Surveys Deployment

**Script**: `scripts/deploy_self_assessment_surveys.php`

**Components Deployed**:
- Activity Types (Short/Full Self Assessment Survey)
- Unified Custom Field Group (35 questions)
- SASS Afform (21 questions)
- SASF Afform (35 questions)

**Configuration Required**:
```php
$config = [
    'sass_activity_type_value' => '1000',  // Update for production
    'sasf_activity_type_value' => '1001',  // Update for production
    'case_types' => [
        'service_request' => 3,             // Update for production
        'mas_project' => 4,                 // Update for production
    ],
];
```

**Deployment Command**:
```bash
cv scr scripts/deploy_self_assessment_surveys.php --user=admin
```

### 2. CiviRules Deployment

**Script**: `scripts/deploy_civirules.php`

**Components Deployed**:
- Custom CiviRules Actions (3)
- Custom CiviRules Triggers (2)
- CiviRules Conditions (as needed)

**Configuration Required**:
```php
$config = [
    'environment' => 'prod',  // Change from 'dev'
    // Add production-specific rule configurations
];
```

**Deployment Command**:
```bash
cv scr scripts/deploy_civirules.php --user=admin
```

### 3. RCS Form Deployment

**Script**: `scripts/deploy_rcs_form.php`

**Components Deployed**:
- Complete RCS Afform structure
- Organization, Individual, and Case entities integration
- Form field configurations and relationships

**Configuration Required**:
```php
$config = [
    'environment' => 'prod',
    'form_name' => 'afformMASRCSForm',
    'redirect_url' => 'https://production-url/thank-you/',
    'case_types' => [
        'service_request' => 3,  // Update for production
    ],
    'email_confirmation_template_id' => 71,  // Update for production
    // ... other production-specific IDs
];
```

**Deployment Command**:
```bash
cv scr scripts/deploy_rcs_form.php --user=admin
```

## Manual Deployment Process

### Form Processors

**Documentation**: `scripts/deploy_form_processors.md`

**Process**:
1. Export from development CiviCRM UI
2. Save `.json` files to `Civi/Mascode/FormProcessor/` directory
3. Import through production CiviCRM UI
4. Verify and test imported processors

## Complete Deployment Workflow

### Step 1: Development Environment
1. Develop and test all changes
2. Verify functionality with `cv flush` and testing
3. Commit changes to dev branch

### Step 2: Version Control
```bash
git add .
git commit -m "Description of changes"
git push origin dev
```

### Step 3: GitHub Workflow
1. Create Pull Request from dev to master
2. Review and merge PR
3. Use automated release script if creating release:
   ```bash
   ./.claude/commands/release.sh [patch|minor|major]
   ```

### Step 4: Production Deployment
```bash
# Pull latest changes in production
git pull origin master

# Update deployment script configurations with production IDs
# Edit configuration sections in:
# - scripts/deploy_self_assessment_surveys.php
# - scripts/deploy_civirules.php  
# - scripts/deploy_rcs_form.php

# Run deployment scripts in order
cv scr scripts/deploy_self_assessment_surveys.php --user=admin
cv scr scripts/deploy_civirules.php --user=admin  
cv scr scripts/deploy_rcs_form.php --user=admin

# Deploy Form Processors manually (see deploy_form_processors.md)

# Clear cache after deployment
cv flush
```

## Pre-Deployment Checklist

### Development Environment
- [ ] All changes tested and working
- [ ] Custom fields and activity types created
- [ ] CiviRules functioning correctly  
- [ ] Forms accessible and submitting properly
- [ ] Cache cleared with `cv flush`

### Production Preparation
- [ ] Production database IDs identified for:
  - Case types
  - Message templates  
  - Location types
  - Phone types
  - Custom field groups
- [ ] Deployment script configurations updated
- [ ] Form Processor export files ready
- [ ] Admin user credentials confirmed

### Post-Deployment Verification
- [ ] All forms accessible at expected URLs
- [ ] Self Assessment Surveys creating Activities
- [ ] RCS Form creating Service Request cases
- [ ] CiviRules firing on expected triggers
- [ ] Form Processors functioning correctly
- [ ] Email confirmations being sent
- [ ] Cache cleared with `cv flush`

## Troubleshooting

### Common Issues

**Script Timeouts**
- Break large deployments into smaller chunks
- Run scripts individually rather than all at once
- Check server timeout settings

**ID Mapping Errors**
- Verify production IDs are correct in configuration
- Check that referenced entities exist in production
- Use CiviCRM API Explorer to verify entity IDs

**Permission Errors**
- Ensure deployment user has appropriate CiviCRM permissions
- Check file system permissions for extension directory
- Verify database user has required privileges

**Form Access Issues**
- Check public access permissions for anonymous forms
- Verify URL routing and server configuration
- Test form accessibility from external networks

### Recovery Procedures

**Failed Deployment Recovery**
1. Restore from backup if necessary
2. Re-run failed deployment script with corrected configuration
3. Manual cleanup of partially created entities if needed
4. Test thoroughly before considering deployment complete

**Rollback Process**
1. Revert to previous Git commit in production
2. Restore database backup if entities were created
3. Clear cache and test functionality
4. Document issues for future deployment improvements

## Environment-Specific Considerations

### Development to Production ID Mapping

**Common Mappings**:
```php
// Case Types
'service_request' => 3,  // Often consistent
'mas_project' => 4,      // Often consistent  

// Location Types  
'Home' => 1,             // Usually consistent
'Work' => 3,             // May vary

// Message Templates
// Check production for actual IDs
```

### Security Considerations
- Never commit production credentials to repository
- Use environment variables for sensitive configuration
- Limit deployment script access to authorized users
- Verify SSL certificates for production URLs

## Version History

- **v1.0**: Initial deployment script system replacing export/import
- **Date**: 2025-06-18
- **Author**: MAS Team

---

*This deployment guide is part of the MAS Extension unified development system. For development workflow, see [DEVELOPMENT.md](DEVELOPMENT.md).*
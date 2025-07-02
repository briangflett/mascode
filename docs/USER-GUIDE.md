# Mascode User Guide

## Overview

Mascode enhances your CiviCRM system with automatic case management, comprehensive assessment tools, and improved forms specifically designed for MAS workflows.

## Key Features

### Automatic MAS Code Generation

- **Service Requests** get codes like `R25001`, `R25002`, etc.
- **Projects** get codes like `P25001`, `P25002`, etc.
- Codes are generated automatically when you create cases
- Codes are unique and sequential by year

### Service Request to Project Conversion

When you change a Service Request status to "Project Created":

- A new Project case is automatically created
- All relevant information is transferred
- Relationships are maintained
- You'll see a "Link Cases" activity connecting them

### Self Assessment Survey System

Two assessment tools help evaluate organizational readiness:

- **Short Self Assessment Survey (SASS)**: 21 key questions for quick evaluation
- **Full Self Assessment Survey (SASF)**: Complete 35-question comprehensive assessment
- Results are stored as Activities linked to the organization's case
- Unified question framework ensures consistency between surveys

### Request for Consulting Services (RCS) Form

- Comprehensive intake form for new consulting requests
- Integrates with case management system
- Captures organization details, contact information, and project requirements
- Automatically creates Service Request cases

### Enhanced Forms

- Website URLs automatically get `http://` added if you don't include it
- Anonymous form access for external users
- Improved validation and error handling
- Environment-specific deployment support

## Common Workflows

### Creating a Service Request

1. Go to **Cases → New Case**
2. Select **Service Request** as case type
3. Fill in client and request details
4. Save - MAS code is automatically generated

### Converting to Project

1. Open the Service Request case
2. Change status to **"Project Created"**
3. Save - Project case is automatically created
4. Check the **Activities** tab for the link confirmation

### Using Self Assessment Surveys

#### Short Assessment (SASS)
1. Navigate to the SASS form (21 questions)
2. Complete organizational assessment questions
3. Submit - creates Activity linked to organization's case
4. Results available in case Activities tab

#### Full Assessment (SASF)
1. Navigate to the SASF form (35 questions)
2. Complete comprehensive organizational assessment
3. Submit - creates Activity linked to organization's case
4. Results include all SASS questions plus additional detailed areas

### Processing RCS Form Submissions

1. External organizations complete RCS form
2. Form automatically creates:
   - Organization contact (if new)
   - Individual contact for submitter
   - Service Request case
   - Links contacts to case appropriately
3. Review new Service Request in case management
4. Follow standard workflow for project development

## Troubleshooting

### MAS Code Not Generated

- Check that case type is "Service Request" or "Project"
- Verify case was saved successfully
- Check CiviRules are enabled and functioning
- Contact support if issue persists

### Project Not Created from Service Request

- Ensure status was changed to exactly "Project Created"
- Check that client contact exists
- Verify CiviRules are active
- Look for error messages in case activities

### Self Assessment Survey Issues

- **Survey not submitting**: Check form permissions and anonymous access settings
- **Results not appearing**: Verify Activity creation and case linking
- **Missing questions**: Ensure custom field group is properly configured
- **Data not saving**: Check custom field permissions and database connectivity

### RCS Form Problems

- **Form not accessible**: Verify public access permissions and URL routing
- **Case not created**: Check case type configuration and required fields
- **Contact creation issues**: Verify contact permissions and required field validation
- **Email confirmations not sent**: Check message template configuration

### Deployment Issues

- **Forms missing after deployment**: Run appropriate deployment script for your component
- **IDs not matching**: Update deployment script configuration with production-specific IDs
- **Permission errors**: Verify user has appropriate CiviCRM permissions for deployment
- **Cache issues**: Run `cv flush` after any deployment or configuration changes

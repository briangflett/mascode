# Mascode User Guide

## Overview

Mascode enhances your CiviCRM system with automatic case management and improved forms specifically designed for MAS workflows.

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

### Enhanced Forms

- Website URLs automatically get `http://` added if you don't include it
- Anonymous form access for external users (coming soon)
- Improved validation and error handling

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

## Troubleshooting

### MAS Code Not Generated

- Check that case type is "Service Request" or "Project"
- Verify case was saved successfully
- Contact support if issue persists

### Project Not Created from Service Request

- Ensure status was changed to exactly "Project Created"
- Check that client contact exists
- Look for error messages in case activities

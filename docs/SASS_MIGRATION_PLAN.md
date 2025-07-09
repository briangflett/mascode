# afformMASSASS Production Migration Plan

## Overview

This document outlines the step-by-step process for migrating the Short Self Assessment Survey (SASS) form from development to production environment.

## Migration Summary

**Form**: afformMASSASS (Short Self Assessment Survey)
**Dependencies**: 
- Activity Type: Short Self Assessment Survey (SAS) 
- Custom Group: Short Self Assessment Survey (21 custom fields)
- Option Group: Yes/No
- Afform files in `/ang` directory

**Environment Differences**:
- **Identical**: Activity Type IDs, Custom Field structure, Option Group structure
- **Different**: Redirect URL only (`localhost` → `masadvise.org`)

## Development Environment Setup (Complete)

### ✅ **Components Created in Dev**:
1. **Activity Type**: Short Self Assessment Survey (SAS) - ID: 73
2. **Custom Group**: "Short Self Assessment Survey" - ID: 11
3. **Option Group**: Yes/No - ID: 219
4. **Custom Fields**: 21 fields (q01_board_established through q21_communication_guidelines)
5. **Afform Files**: Exported to `/ang` directory

### ✅ **Files Generated**:
```
/ang/afformMASSASS.aff.php   # Form metadata
/ang/afformMASSASS.aff.html  # Form layout
/ang/afformMASSASS.aff.json  # Complete definition
```

## Production Migration Process

### **Step 1: Development Environment (You - Complete)**

**Status**: ✅ **COMPLETED**

Scripts and files are ready for production deployment.

### **Step 2: Production Environment (You - To Do)**

#### **2.1 Prerequisites - Create Custom Infrastructure**

**Manually create these components in production (via CiviCRM UI)**:

1. **Activity Type**: "Short Self Assessment Survey (SAS)" 
   - Note the Value/ID assigned in production
   
2. **Option Group**: "Yes/No"
   - Create option group with Yes/No values
   - Note the ID assigned in production
   
3. **Custom Group**: "Short Self Assessment Survey"
   - Extends: Activity (with activity type from step 1)
   - Note the ID assigned in production

#### **2.2 Create Custom Fields**

**Run the custom fields deployment script**:
```bash
# Navigate to extension directory
cd /path/to/production/civicrm/ext/mascode

# Update configuration in deploy_sass_custom_fields.php
# Edit these values for production:
$config = [
    'environment' => 'prod',  // Change from 'dev'
    'custom_group_id' => [PROD_CUSTOM_GROUP_ID],  // ID from step 3 above
    'option_group_id' => [PROD_OPTION_GROUP_ID],  // ID from step 2 above
];

# Run the deployment script
cv scr scripts/deploy_sass_custom_fields.php --user=admin
```

**Expected Output**:
```
✓ Custom Group verified: Short Self Assessment Survey (ID: [ID])
✓ Option Group verified: Yes/No (ID: [ID])
✓ Created 21 new fields, 0 already existed
```

#### **2.3 Update Afform Files for Production**

**Copy afform files to production**:
```bash
# Copy ang directory files to production
cp -r /dev/extension/ang/afformMASSASS.* /prod/extension/ang/

# Update redirect URL in metadata file
# Edit /prod/extension/ang/afformMASSASS.aff.php
# Change: 'redirect' => 'https://masdemo.localhost/thank-you/'
# To:     'redirect' => 'https://www.masadvise.org/thank-you/'
```

#### **2.3 Clear Cache and Test**

```bash
# Clear CiviCRM cache
cv flush

# Test form access
curl -I https://www.masadvise.org/civicrm/mas-sass-form

# Test form submission (optional)
# Navigate to form URL and submit test data
```

### **Step 3: Verification Checklist**

#### **✅ Production Verification**:
- [ ] Activity Type "Short Self Assessment Survey (SAS)" exists
- [ ] Custom Group "Short Self Assessment Survey" exists with 21 fields
- [ ] Option Group "Yes/No" exists with Yes/No values
- [ ] Form accessible at: `https://www.masadvise.org/civicrm/mas-sass-form`
- [ ] Form redirects to: `https://www.masadvise.org/thank-you/` after submission
- [ ] Form creates Activity records with custom field data
- [ ] Form creates relationships between Organization and Individual
- [ ] All 21 questions display correctly with Yes/No radio buttons

#### **✅ Data Integrity**:
- [ ] Test form submission creates Activity with type ID 73
- [ ] Custom field data is saved correctly
- [ ] Organization and Individual records are linked
- [ ] No errors in CiviCRM logs

## Configuration Details

### **Environment-Specific Settings**

| Component | Development | Production | Notes |
|-----------|-------------|------------|-------|
| Activity Type ID | 73 | 73 | Should be identical |
| Custom Group ID | 11 | 11 | Should be identical |
| Option Group ID | 219 | 219 | Should be identical |
| Redirect URL | `https://masdemo.localhost/thank-you/` | `https://www.masadvise.org/thank-you/` | **Only difference** |
| Form Route | `civicrm/mas-sass-form` | `civicrm/mas-sass-form` | Identical |

### **Custom Fields Structure**

**Custom Group**: `Unified_Self_Assessment_Survey`
- **Extends**: Activity (Type ID: 73)
- **Fields**: 21 questions (q01_board_established through q21_communication_guidelines)  
- **Field Type**: String with Yes/No radio buttons
- **Option Group**: yes_no (ID: 219)

## Files and Scripts

### **Deployment Scripts**:
```
scripts/deploy_sass_custom_fields.php  # Creates custom fields and option groups
```

### **Afform Files**:
```
ang/afformMASSASS.aff.php   # Form metadata (UPDATE redirect URL for prod)
ang/afformMASSASS.aff.html  # Form layout (identical dev/prod)
ang/afformMASSASS.aff.json  # Complete definition (identical dev/prod)
```

## Troubleshooting

### **Common Issues**:

**1. Activity Type Not Found**
```bash
# Check if activity type exists
cv api4 OptionValue.get | grep "Short Self Assessment"
# If missing, update activity_type_value in config and re-run script
```

**2. Custom Fields Not Displaying**
```bash
# Check custom group
cv api4 CustomGroup.get | grep "Unified_Self_Assessment_Survey"
# Clear cache
cv flush
```

**3. Form Not Accessible**
```bash
# Check form exists
cv api4 Afform.get | grep "afformMASSASS"
# Check file permissions in /ang directory
```

**4. Option Group Missing**
```bash
# Check Yes/No option group
cv api4 OptionGroup.get | grep "yes_no"
# If missing, re-run deploy_sass_custom_fields.php
```

## Rollback Procedure

**If deployment fails**:
1. **Remove Custom Fields**: Delete custom group via CiviCRM UI
2. **Remove Activity Type**: Disable activity type via CiviCRM UI  
3. **Remove Option Group**: Delete option group via CiviCRM UI
4. **Remove Afform Files**: Delete `ang/afformMASSASS.*` files
5. **Clear Cache**: `cv flush`

## Success Criteria

**Migration is successful when**:
1. ✅ All infrastructure components exist in production
2. ✅ Form accessible at production URL
3. ✅ Form submission creates Activity with custom field data
4. ✅ Form redirects to production thank-you page
5. ✅ No errors in CiviCRM system logs
6. ✅ All 21 questions display with Yes/No options

## Next Steps

After successful migration:
1. **Test Form Thoroughly**: Submit test data and verify Activity creation
2. **Monitor Logs**: Check for any errors in the first few days
3. **Update Documentation**: Document any production-specific differences
4. **Version Control**: Commit any production-specific changes to Git

---

**Migration Plan Created**: July 8, 2025
**Target Environment**: Production CiviCRM
**Form**: afformMASSASS (Short Self Assessment Survey)
**Status**: Ready for Production Deployment
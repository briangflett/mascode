# Afform Relationship Management

## Overview
The MASCode extension automatically creates relationships when Afforms are submitted. This ensures proper contact-to-organization relationships are established without manual intervention.

## RCS Form (afformMASRCSForm)

### Automatic Relationship Creation
When the Request for Consulting Services form is submitted, the following relationships are automatically created:

#### Individual 1 (President/Board Chair)
- **Employee of** → Organization 1
- **President of** → Organization 1

#### Individual 2 (Executive Director)
- **Employee of** → Organization 1
- **Executive Director of** → Organization 1

#### Individual 3 (Primary Contact)
- **Employee of** → Organization 1
- **Case Client Rep is** → Organization 1 (case-specific relationship)

### Implementation Details

**File:** `Civi/Mascode/Event/AfformSubmitSubscriber.php`

**Method:** `createRCSRelationshipsPostCommit()`

**Process:**
1. Form submission triggers the `civi.afform.submit` event
2. AfformSubmitSubscriber collects entity IDs as each entity is processed
3. When Case1 (the final entity) is processed, relationship creation is triggered
4. Relationships are checked for existence before creation to avoid duplicates
5. Each relationship type is looked up by name (environment-agnostic)
6. Case-specific relationships include the case_id for proper context

### Relationship Type Mapping

The following relationship types must exist in CiviCRM:

| Relationship Type | Direction | Used For |
|------------------|-----------|----------|
| Employee of | Individual → Organization | All individuals |
| President of | Individual → Organization | Individual 1 |
| Executive Director of | Individual → Organization | Individual 2 |
| Case Client Rep is | Individual → Organization | Individual 3 (case-specific) |

### CiviRules Integration

**CiviRules Actions:**
- `EmployerRelationship` - Creates relationships based on employer_id and job title
- Used for manual contact creation outside of Afforms
- Skips execution gracefully when no employer_id is set (preventing transaction rollbacks)

**How It Works:**
1. **Afform Submission:** AfformSubmitSubscriber creates relationships directly
2. **Manual Entry:** CiviRules creates relationships when job title matches and employer_id is set
3. **Duplicate Prevention:** Both systems check for existing relationships before creating

### Error Handling

**Transaction Safety:**
- Relationship creation wrapped in try-catch blocks
- Individual relationship failures don't prevent contact creation
- All failures are logged with detailed context

**Logging:**
- All relationship creation attempts logged at INFO level
- Failures logged at ERROR level with full exception details
- Session ID tracked for debugging multi-entity submissions

### Troubleshooting

**Contact created but no relationships:**
1. Check CiviCRM logs: `/web/wp-content/uploads/civicrm/ConfigAndLog/CiviCRM.*.log`
2. Search for "AfformSubmitSubscriber" entries
3. Verify relationship types exist and use correct names
4. Confirm organization ID is valid

**Relationship already exists errors:**
- This is normal behavior - system checks and skips duplicates
- Logged at INFO level, not an error

**CiviRules causing rollbacks:**
- Ensure `EmployerRelationship` action properly overrides `processAction()`
- Verify it returns early when no employer_id is found
- Check that it doesn't throw exceptions when skipping

## Survey Forms (SASS/SASF)

Survey forms use a simpler structure:
- Individual 1 serves as primary contact
- No automatic relationship creation currently implemented
- Relationships managed manually or via CiviRules

## Extension to Other Forms

To add relationship creation to other Afforms:

1. Add form route to `$emailForms` array in `AfformSubmitSubscriber::onFormSubmit()`
2. Add entity tracking in the appropriate case block
3. Create relationships in a dedicated method (similar to `createRCSRelationshipsPostCommit()`)
4. Use `createRelationshipIfNotExists()` or `createCaseRelationshipIfNotExists()` helpers

**Example:**
```php
case 'NewForm1':
    self::$submissionData[$sessionId]['entity_id'] = $entityId;
    $this->createNewFormRelationships(self::$submissionData[$sessionId], $sessionId);
    break;
```

## Testing

### Manual Testing
1. Submit afformMASRCSForm with new contacts for Individual 1, 2, and 3
2. Verify all contacts created successfully
3. Check each contact's Relationships tab:
   - Individual 1: Employee of + President of
   - Individual 2: Employee of + Executive Director of
   - Individual 3: Employee of + Case Client Rep is
4. Verify Case Client Rep relationship shows correct case ID

### Log Verification
```bash
tail -f /home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ConfigAndLog/CiviCRM.*.log | grep AfformSubmitSubscriber
```

### Database Verification
```php
// Check relationships for contact
$rels = \Civi\Api4\Relationship::get(false)
  ->addWhere('contact_id_a', '=', $contactId)
  ->addWhere('is_active', '=', true)
  ->addSelect('id', 'contact_id_b.display_name', 'relationship_type_id:label', 'case_id')
  ->execute();
```

## Best Practices

1. **Always use relationship type names**, never IDs (ensures cross-environment compatibility)
2. **Check for existing relationships** before creating to avoid duplicates
3. **Wrap creation in try-catch** to prevent transaction rollbacks
4. **Log all attempts** with sufficient context for debugging
5. **Use case-specific relationships** when relationship context matters
6. **Test in development** before deploying to production
7. **Clear cache** after code changes: `cv flush`

## Related Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md) - Overall extension architecture
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
- Main CLAUDE.md - Environment configuration and API patterns

# Afform and SearchKit Management

## Storage Strategy

**All Afforms and SearchKit searches are stored in the DATABASE, not as files.**

This approach:
- Avoids cross-environment ID conflicts
- Prevents file sync issues between dev/prod
- Allows easier UI-based editing and testing
- Separates form configuration from code

## Deployment to Production

### Option 1: Manual Replication (Recommended)
Recreate forms manually in production using FormBuilder UI:
- Most reliable for complex forms
- Ensures proper configuration in production environment
- No ID mapping issues

### Option 2: API4 Export/Import
For simpler forms, use API4:

```bash
# In development - export Afform
cv scr /tmp/export_afform.php --user=admin
```

```php
// export_afform.php
$afform = \Civi\Api4\Afform::get(FALSE)
  ->addWhere('name', '=', 'afformMASFormName')
  ->execute()->first();
echo json_encode($afform, JSON_PRETTY_PRINT);
```

Then manually import in production after adjusting any environment-specific values.

### Option 3: SearchKit Export/Import
Use CiviCRM's built-in Export/Import for SearchKit searches:
- Navigate to Search → Manage Searches
- Export from dev, Import to prod
- Built-in tool handles most ID mapping

## Tags

- **`Client`** - Client-facing forms 
  - Public forms that clients interact with
  - Examples: RCS Form, Self-Assessment Surveys, Client Feedback

- **`VC`** - Volunteer Consultant forms and searches 
  - Forms and searches used by volunteer consultants
  - Examples: VC Feedback, My Cases, Service Request searches

- **`Dashlet`** - Dashboard widgets 
  - SearchKit displays shown on dashboards
  - Examples: Projects by Status, Projects by Year

- **`Admin`** - Administrative forms 
  - Backend administrative tools and forms
  - Future use

- **`Block`** - Reusable form blocks and components
  - Shared fieldsets used across multiple forms
  - Examples: Project fields, Contact fields, Custom group blocks

## Naming Convention

All custom forms must be prefixed with `afformMAS` or `afblockMAS`:
- Forms: `afformMAS{FormName}` (e.g., `afformMASRCSForm`)
- Blocks: `afblockMAS{BlockName}` (e.g., `afblockMASContactFields`)
- Searches: `afsearchMAS{SearchName}` (optional, e.g., `afsearchMASProjects`)

**Note**: Names are stored in database. The `ang/` directory may contain some legacy file-based forms but new forms should be database-only.

Use Searchkit and Afform for custom forms and reports
Save them in the database.
Export them from dev and import them into prod
- using Export/Import for Searchkit
- using API4 for Afform 
Or just recreate them manually

# Tags

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

## File Naming Convention

All custom forms must be prefixed with `afformMAS` or `afblockMAS`:
- Forms: `afformMAS{FormName}.aff.html` + `.aff.json`
- Blocks: `afblockMAS{BlockName}.aff.html` + `.aff.json`
- Searches: `afsearchMAS{SearchName}.aff.html` + `.aff.json` (optional)

# MASCode Extension Development Guide

**Note:** When updating key project information in this file, also update the summary in `/home/brian/workspace/claude/context/mas-claude-context/claude-code/projects/mascode.md`

## Quick Reference

- **Framework**: CiviCRM on WordPress
- **Branch**: master (single branch workflow)
- **Database Credentials**: `/home/brian/.config/development/databases.env`
- **CV Binary**: `/home/brian/buildkit/bin/cv --user=admin`
- **Cache Clear**: `/home/brian/buildkit/bin/cv flush` (run after all code changes)

## Development Approaches

### When to Use Each Pattern

1. **Afform Relationships** - Automatic relationship creation on form submission
   - **See**: [docs/AFFORM-RELATIONSHIPS.md](docs/AFFORM-RELATIONSHIPS.md)
   - For: RCS form, Survey forms
   - Pattern: Event subscribers handle post-submission relationship creation

2. **CiviRules** - Trigger-based automation
   - Best for: Automated responses to CiviCRM events, business rules
   - Pattern: Actions extend `CRM_CivirulesActions_Generic_Api`
   - Database: Tables start with `civirules_`

3. **Symfony Events** - Complex business logic
   - Best for: System integration, decoupled architecture
   - Pattern: Subscribers in `Civi/Mascode/Event/`, extend `AutoSubscriber`

4. **FormProcessor** - External data processing
   - Best for: WordPress forms, data import/export
   - Pattern: Actions in `Civi/Mascode/FormProcessor/Action/`

5. **CRM Forms** - Administrative functions
   - Best for: Backend admin tools, complex validation
   - Location: `CRM/Mascode/Form/` + `templates/CRM/Mascode/Form/`

### Form Development

**Afforms (Preferred for public/user forms)**:
- **Managed in Database** - Create/edit in FormBuilder UI
- Naming: Prefix with "afformMAS" (e.g., `afformMASRCSForm`)
- Deployment: Manually replicate or use API4 export/import
- **See**: "Afform Management" section below

**CRM Forms (For admin tools)**:
- Traditional approach for backend functionality
- Complex validation and business logic
- File-based in `CRM/Mascode/Form/` + `templates/`

## API4 Patterns (CRITICAL)

**For complete API4 patterns, CV commands, and code verification protocol, see:**
**[/home/brian/workspace/claude/context/mas-claude-context/claude-code/global/protocols/api4.md](/home/brian/workspace/claude/context/mas-claude-context/claude-code/global/protocols/api4.md)**

**Quick Reference:**
- **ALWAYS use CiviCRM API4** - NEVER use direct SQL
- **Use FALSE** as first parameter to suppress permissions
- **Use names, not IDs** for cross-environment compatibility
- **Common pattern**: `\Civi\Api4\EntityName::action(FALSE)->addWhere()->execute()`
- **Verify first**: Always check source code before using APIs

## Afform Management

**Database-First Approach** (Current Method):
- Create and edit Afforms using FormBuilder UI in CiviCRM
- Afforms stored in database, not files
- Deployment: Manual replication or API4 export/import

**Why Database Storage?**:
- Avoids cross-environment ID conflicts
- No file sync issues between dev/prod
- Easier to modify and test in UI
- Clean separation from code changes

**Deployment Options**:

1. **Manual Replication** (Recommended for complex forms):
   - Recreate form in production using FormBuilder
   - Copy settings and field configurations
   - Most reliable for forms with many customizations

2. **API4 Export/Import** (For simple forms):
   ```php
   // Export from dev
   $afform = \Civi\Api4\Afform::get(FALSE)
     ->addWhere('name', '=', 'afformMASFormName')
     ->execute()->first();

   // Import to prod (after adjusting any environment-specific values)
   \Civi\Api4\Afform::create(FALSE)
     ->setValues($afform)
     ->execute();
   ```

**Key Principles**:
- **Naming**: Always prefix with "afformMAS"
- **Field References**: Use names, never IDs (custom fields, relationships, etc.)
- **Tags**: Use Client, VC, Dashlet, Admin, or Block tags (see `ang/README.md`)
- **Cache**: Run `cv flush` after any changes

## CV Command Patterns

**See [protocols/api4.md](/home/brian/workspace/claude/context/mas-claude-context/claude-code/global/protocols/api4.md) for complete CV command reference**

**Quick commands:**
```bash
cv flush  # ALWAYS after code changes
cv scr /path/to/script.php --user=admin
XDEBUG_SESSION=1 cv scr /path/to/script.php --user=admin
```

## Documentation Map

**Core Development**:
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Extension architecture and components
- [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) - Development workflow and deployment
- [docs/INSTALLATION.md](docs/INSTALLATION.md) - Setup and installation

**Feature-Specific**:
- [docs/AFFORM-RELATIONSHIPS.md](docs/AFFORM-RELATIONSHIPS.md) - Automatic relationship creation (RCS form)
- [docs/TESTING.md](docs/TESTING.md) - Testing framework and practices
- [docs/VC_ACL_SETUP_INSTRUCTIONS.md](docs/VC_ACL_SETUP_INSTRUCTIONS.md) - Volunteer Consultant ACL setup
- [docs/PRODUCTION-OPS.md](docs/PRODUCTION-OPS.md) - Production operations

**Reference**:
- [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) - Contribution guidelines
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) - Production deployment process

## Code Verification Protocol

**See [protocols/api4.md](/home/brian/workspace/claude/context/mas-claude-context/claude-code/global/protocols/api4.md#api-and-code-verification-critical) for complete verification procedures**

**Critical:** Before using any CiviCRM entities, actions, or methods, verify they exist in source code - never assume APIs exist

## PHP Code Standards

- **Auto-formatter**: PHP Intelephense (PSR-12)
- Lowercase booleans: `false`, `true`, `null`
- Array syntax: `array()` not `array ()`
- Files auto-format on save

## Database Access

**Credentials**: `/home/brian/.config/development/databases.env`

**Development**:
- WordPress DB: `MASDEMO_WP_DB_NAME`
- CiviCRM DB: `MASDEMO_CIVI_DB_NAME`
- User/Pass: `MYSQL_ROOT_USER`, `MYSQL_ROOT_PASSWORD`

**Production** (via SSH):
- Access: `/home/brian/workspace/shell-scripts/db-tunnel.sh`
- Credentials: `PROD_DB_*` variables

## Release Process

1. Update version/releaseDate in `info.xml`
2. Commit and push to GitHub master branch
3. Pull from GitHub in production
4. Run `cv flush` in production

**Manual deployment only** - no automated releases.

## Common Commands

```bash
# Cache management (ALWAYS after code changes)
/home/brian/buildkit/bin/cv flush

# View extension status
/home/brian/buildkit/bin/cv ext:list | grep mascode

# Debug script execution
XDEBUG_SESSION=1 /home/brian/buildkit/bin/cv scr <script> --user=admin
```

## Playwright Browser Access (CiviCRM Admin)

Klaus can browse the CiviCRM admin UI via Playwright MCP using cookie injection (no login form needed):

1. **Generate cookies**: `wp eval` with `wp_generate_auth_cookie()` for user ID 42 (brian.flett), valid 24h
2. **Inject**: `browser_run_code` → `context.addCookies()` — logged_in cookie at `/`, secure_auth at `/wp-admin`
3. **Navigate**: `https://masdemo.localhost/wp-admin/admin.php?page=CiviCRM`

**Requires**: Playwright MCP with `--ignore-https-errors` flag. See Klaus memory `reference_playwright_civicrm_auth.md` for full recipe.

## Session Lifecycle

- **Start**: `/bootstrap` (loads Klaus context, checks pending handoffs)
- **End**: `/wrapup` (logs summary to Postgres, handles handoffs, checks git)

Klaus capabilities are provided via the globally available `klaus-workflows`, `bootstrap`, and `wrapup` skills.

---

## Need More Detail?

Refer to the appropriate documentation file in `docs/` based on the area you're working on. This file provides quick reference; detailed docs contain comprehensive information for specific features and workflows.

---

**Last Updated**: 2026-04-07

# Mascode CiviCRM Extension Development

## Project Context

You are helping develop the `mascode` CiviCRM extension for Management Advisory Services (MAS), a nonprofit providing pro bono consulting to other nonprofits.

## Key Instructions

1. **Working Scope**:

   - Make ALL changes only within this extension directory
   - NEVER modify CiviCRM core or other extensions

2. **Reference Access**:

   - Read CiviCRM core: `/home/brian/buildkit/build/masdemo/web/wp-content/plugins/civicrm/civicrm`
   - Read CiviCRM extensions: `/home/brian/buildkit/build/masdemo/web/wp-content/uploads/civicrm/ext`
   - Read Wordpress Elementor Pro extension: `/home/brian/buildkit/build/masdemo/web/wp-content/plugins/elementor-pro`
   - Use these for understanding APIs, patterns, and best practices

3. **Development Guidelines**:

   - Use CiviCRM API4 instead of API3, BAO, DAO when possible
   - Follow PSR-12 coding standards
   - Use Symfony EventDispatcher instead of traditional hooks
   - Prefer modern object oriented PHP practices (PHP 8.3+)

4. **Architecture**:

   - CiviRules integration for business logic
   - FormProcessor actions for form handling
   - Event subscribers for hook handling
   - Utility classes in `Civi\Mascode\Util\`

5. **Key Files to Understand**:
   - `CONTEXT.md` - Detailed project context
   - `info.xml` - Extension metadata
   - `mascode.php` - Main hook file
   - CiviRules actions in `Civi/Mascode/CiviRules/Action/`

## First Steps

1. Read `CONTEXT.md` for complete project understanding
2. Review the current file structure
3. Ask about specific development goals

## Remember

Focus on helping MAS customize their CiviCRM implementation while maintaining clean, maintainable code that follows CiviCRM best practices.

# Claude Code Workspace Rules

## File Modification Guidelines

- Always backup existing files before major changes
- Update documentation when modifying architecture
- Run `cv flush` after code changes to clear CiviCRM cache
- Follow PSR-12 coding standards and CiviCRM conventions
- Test all changes in development environment before production

## Priority Files

1. `docs/ARCHITECTURE.md` - Keep updated with technical decisions and roadmap
2. `composer.json` - Maintain dependencies and scripts
3. `info.xml` - Extension metadata and requirements
4. `.claude/context.md` - AI assistant instructions and context
5. `mascode.php` - Main extension file and hook implementations

## Architectural Principles

- See context.md for complete architecture patterns and development approach
- Follow established conventions and technical standards defined in context.md

## Development Workflow

### Before Making Changes
1. Read `.claude/context.md` for current instructions
2. Review `docs/ARCHITECTURE.md` for technical context
3. Check git status and ensure clean working directory
4. Verify CiviCRM extension is enabled and functioning

### After Making Changes
1. Run `cv flush` to clear CiviCRM cache
2. Test functionality in browser/API
3. Check CiviCRM logs for errors
4. Update relevant documentation
5. Consider impact on existing workflows

### Common Commands
```bash
# Clear CiviCRM cache
cv flush

# Run development scripts with debugging
XDEBUG_SESSION=1 cv scr <script-name>

# Check extension status
cv ext:list | grep mascode

# View recent logs
tail -f /path/to/civicrm/ConfigAndLog/CiviCRM.*.log
```

## Documentation Updates Required For

- New CiviRules actions, triggers, or conditions
- Changes to database schema or managed entities
- New API endpoints or external integrations
- Architectural pattern changes
- New event subscribers or service registrations
- Security-related changes
- Performance optimizations

## Testing Strategy

- **Unit Tests**: Test business logic in isolation
- **Integration Tests**: Test CiviCRM API interactions
- **Manual Testing**: Test user workflows in browser
- **Performance Testing**: Monitor query performance and caching

## Security Considerations

- **Anonymous Access**: Always validate checksums for anonymous forms
- **Input Validation**: Sanitize and validate all user input
- **Logging**: Never log sensitive information (passwords, tokens, etc.)
- **Permissions**: Verify proper permission checks for all operations

## AI Integration Guidelines

- Design with future AI features in mind
- Document decision-making processes for AI training
- Consider API interfaces that AI could consume
- Plan for automated testing of AI-enhanced features

## Code Review Checklist

- [ ] Code follows PSR-12 standards
- [ ] Uses modern CiviCRM patterns (API4, EventDispatcher, etc.)
- [ ] Includes proper error handling and logging
- [ ] Updates relevant documentation
- [ ] Tested in development environment
- [ ] No sensitive information exposed
- [ ] Cache cleared after changes
- [ ] Follows established architectural patterns

## Emergency Procedures

### Extension Breaks CiviCRM
1. Disable extension: `cv ext:disable mascode`
2. Check error logs for specific issues
3. Revert recent changes if necessary
4. Clear cache: `cv flush`
5. Re-enable after fixes: `cv ext:enable mascode`

### Database Issues
1. Check CiviCRM database logs
2. Review recent schema changes
3. Use CiviCRM's managed entity system for rollbacks
4. Restore from backup if necessary

## Performance Guidelines

- Use API4 for database operations (better caching)
- Minimize database queries in loops
- Use CiviCRM's caching mechanisms
- Monitor memory usage for large data operations
- Consider background processing for heavy tasks

---

*Last Updated: January 2025*
*For technical architecture details, see docs/ARCHITECTURE.md*
*For development setup, see docs/DEVELOPMENT.md*
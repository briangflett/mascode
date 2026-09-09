# Development Backlog

## Completed (v1.0.3)

- [x] **Self Assessment Survey System**: SASS/SASF with unified 35-question framework
- [x] **Request for Consulting Services Form**: Complete RCS form with case integration
- [x] **Deployment Script System**: Replace export/import with robust deployment scripts
- [x] **CiviRules Integration**: Custom actions, triggers, and automated deployment
- [x] **Service Request to Project Conversion**: Automated workflow via CiviRules
- [x] **MAS Code Generation**: Automated sequential code generation (R25XXX, P25XXX)
- [x] **Environment-Specific Deployment**: Production-ready configuration management

## In Progress

| Priority | Description | Status | Links |
|----------|-------------|--------|---------|
| High | **Production Deployment Testing** | Testing | Deploy all components to production environment |
| Medium | **Form Processor Integration** | Planning | Integrate existing Form Processors with deployment system |
| Medium | **Documentation Review** | In Progress | Comprehensive documentation update and consolidation |

## Near Term (Next Release)

| Priority | Description | Effort | Notes |
|----------|-------------|--------|---------|
| High | **Troubleshooting Guide** | Small | Comprehensive troubleshooting documentation |
| High | **Security Documentation** | Small | Document security considerations and best practices |
| Medium | **API Documentation** | Medium | Document CiviRules actions, triggers, and utility classes |
| Medium | **Performance Optimization** | Medium | Review and optimize deployment script performance |
| Low | **Contact Customization** | Small | Remove legal name, nickname, SIC code; add "formerly known as" |

## Future Enhancements

### Phase 1: Workflow Improvements
- **Project Completion Form**: Web form for project completion and reporting
- **Enhanced Reporting**: Advanced analytics and dashboard components
- **Workflow Optimization**: Streamline case management workflows
- **Email Template Integration**: Standardized email templates for all processes

### Phase 2: Integration Expansion  
- **External API Integration**: Connect with third-party nonprofit tools
- **Advanced Form Processing**: Complex multi-step form workflows
- **Bulk Operations**: Batch processing for large data operations
- **Mobile Optimization**: Mobile-friendly form and interface improvements

### Phase 3: AI Integration
- **Direct LLM Integration**: OpenAI GPT-4 for function calling and analysis
- **Intelligent Case Routing**: AI-powered case assignment and prioritization
- **Predictive Analytics**: Donor behavior and project success modeling
- **Content Generation**: Automated report narratives and summaries
- **Form Optimization**: AI-driven form improvement suggestions

### Phase 4: Platform Expansion
- **Multi-Tenant Support**: Support for multiple MAS-like organizations
- **Advanced Security**: Enhanced security and audit capabilities
- **Performance Scaling**: Optimization for large-scale deployments
- **Integration Hub**: Central hub for nonprofit technology integrations

## Filed — RCS lifecycle follow-ups

Both found while fixing the manual-intake chase arming (upgrade_5012, 2026-09-09)
and deliberately NOT absorbed into that change.

### RCS chase cadence has no terminal step

Five Service Requests — **18766, 18767, 18782, 18797, 18813** — have had both
chases sent, their queue drained, and are STILL in "Request RCS". The cadence
sends at 21 and 42 days and then simply stops, so a fully-chased dead request is
indistinguishable from a live one, forever, in every dashboard that counts the
status.

This matters more now, not less: arming manual intake adds ~31 cases a year to a
pipeline with no exit. Fixing the leak without fixing this trades a silent
under-chase for a silently growing queue of undead requests.

Options worth weighing: a third cadence step that moves the case to
"No Client Response" instead of mailing; or a scheduled sweep on age-in-status.
The first keeps the decision inside the existing rule machinery; the second is
easier to tune but is another cron job.

| Priority | Effort | Notes |
|----------|--------|-------|
| Medium | Small–Medium | Needs a coordinator decision on how long "no response" takes to declare |

### Dev clone: mas_lifecycle_vc_close_chase is silently dead (rule 11)

Found during the PR #30 review. On the dev clone, rule 11's NULL-link
`case_type` condition sits at weight 24 — **after** both `AND` conditions
(`case_status_changed` 22, `case_status` 23). `CRM_Civirules_Engine::areConditionsValid()`
exempts only the *first* condition's link, so the later NULL link hits the
switch's `default:` branch, logs "invalid condition_link operator" and forces
the rule FALSE. The VC close-report chase therefore never fires in dev.

**Production is NOT affected** — checked 2026-09-09, all seven `mas_*` rules
there have their NULL-link `case_type` condition sorting first (rule 11:
weights 12/13/14). This is stale dev data, probably from hand-building the rule
in dev before `LifecycleRuleProvisioner` existed.

It still matters, because it makes **dev an unreliable place to test the VC
close chase** — it will appear broken there for a reason that has nothing to do
with the code under test. Either re-weight the dev row or re-clone.

| Priority | Effort | Notes |
|----------|--------|-------|
| Low (dev only) | Tiny | Re-weight rule 11's conditions in dev, or pick it up on the next /mas-clone |

### Prod CiviRules errors: "Contact ID is not numeric" and Relationship.create mandatory keys

The August/September production logs carry recurring
`CiviRules Contact ID is not numeric for Case` warnings and
`Civirules api3 action exception: Mandatory key(s) missing ... Relationship.create`
errors, several coinciding with Service Request creation. Unrelated to the chase
— a different CiviRule is failing — but it is failing repeatedly and silently on
production, and nobody has read the stack.

| Priority | Effort | Notes |
|----------|--------|-------|
| Medium | Small to diagnose | Start from the log timestamps; identify which rule/action raises it |

## Technical Debt

| Priority | Description | Impact | Effort |
|----------|-------------|--------|---------|
| Medium | **Legacy Export/Import Cleanup** | Low | Remove old export/import scripts and references |
| Low | **Code Documentation** | Medium | Add comprehensive PHPDoc blocks |
| Low | **Unit Test Coverage** | High | Expand automated test coverage |
| Low | **Static Analysis** | Medium | Implement PHPStan/Psalm for code quality |

## Archived/Completed

- ~~Create a project when a service request is closed with "Project Created" status~~ ✅ **Completed**
- ~~Automatically put http:// in web~~ ✅ **Completed**  
- ~~Integrate with web service request form~~ ✅ **Completed via RCS Form**
- ~~Unified export/import system~~ ✅ **Replaced with deployment scripts**

## Version Planning

- **v1.0.4**: Troubleshooting guide, security docs, performance optimization
- **v1.1.0**: Project completion form, enhanced reporting, workflow improvements
- **v1.2.0**: External integrations, advanced form processing
- **v2.0.0**: AI integration, intelligent features

---

*Last Updated: 2026-09-09*  
*For development workflow, see [DEVELOPMENT.md](docs/DEVELOPMENT.md)*

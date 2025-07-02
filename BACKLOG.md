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

*Last Updated: 2025-06-18*  
*For development workflow, see [DEVELOPMENT.md](docs/DEVELOPMENT.md)*

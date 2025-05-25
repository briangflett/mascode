# Code Review Prompt Templates

## General Code Review

Please review the following code for [COMPONENT_NAME] and check for:

### Code Quality
- PSR-12 coding standards compliance
- Proper variable and method naming conventions
- Code organization and structure
- Documentation and comments quality
- Code complexity and maintainability

### CiviCRM Best Practices
- Use of API4 instead of legacy APIs (API3, BAO, DAO)
- Proper use of Symfony EventDispatcher
- Following CiviCRM extension patterns
- Correct service registration and dependency injection
- Proper hook implementation

### Security Review
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- Permission and access control
- Sensitive data handling

### Performance Considerations
- Database query optimization
- Memory usage efficiency
- Caching implementation
- Bulk operation handling

## CiviRules Component Review

Review this CiviRules [ACTION/TRIGGER/CONDITION] for:

### Functionality
- Correct implementation of required methods
- Proper parameter handling
- Error handling and validation
- Integration with CiviCRM data model

### Configuration
- Proper form handling for admin configuration
- Validation of configuration parameters
- Clear user interface and help text
- Proper data serialization/deserialization

### Example Usage
```
Review the GenerateMasCode CiviRules action for:
- Proper code generation logic
- Error handling for duplicate codes
- Integration with case management
- Performance with bulk operations
```

## Event Subscriber Review

Review this event subscriber for [EVENT_TYPE] events:

### Event Handling
- Correct event subscription registration
- Proper event data access and manipulation
- Error handling and logging
- Performance impact on event processing

### Integration
- Compatibility with CiviCRM core events
- Proper service dependency management
- Clean separation of concerns
- Testability of event logic

### Example Usage
```
Review the AfformSubmitSubscriber for:
- Proper form data validation
- Anonymous access security
- Integration with form processing
- Error handling and user feedback
```

## API Integration Review

Review this API integration code for:

### API Usage
- Proper API4 implementation patterns
- Correct entity and action usage
- Error handling for API calls
- Batch operation efficiency

### Data Handling
- Input validation and transformation
- Output formatting and filtering
- Relationship handling
- Custom field processing

### Example Usage
```
Review the contact enhancement API code for:
- Proper contact data retrieval
- Validation of enhancement data
- Error handling for API failures
- Performance with large datasets
```

## Security-Focused Review

Conduct a security review of [COMPONENT_NAME] focusing on:

### Input Validation
- All user inputs properly validated
- SQL injection prevention measures
- XSS protection implementation
- File upload security (if applicable)

### Access Control
- Proper permission checks
- Anonymous access validation
- Checksum validation for secure URLs
- Session and authentication handling

### Data Protection
- Sensitive data not logged
- Proper data encryption where needed
- Secure data transmission
- Privacy compliance considerations

### Example Usage
```
Security review of anonymous form access:
- Checksum generation and validation
- URL parameter sanitization
- Form data validation
- Error message information disclosure
```

## Performance Review

Review [COMPONENT_NAME] for performance optimization:

### Database Operations
- Query efficiency and indexing
- N+1 query prevention
- Proper use of CiviCRM caching
- Bulk operation optimization

### Memory Management
- Memory usage in loops
- Large dataset handling
- Object cleanup and garbage collection
- Resource management

### Caching Strategy
- Appropriate cache usage
- Cache invalidation logic
- Performance measurement
- Bottleneck identification

### Example Usage
```
Performance review of CodeGenerator utility:
- Database query optimization for uniqueness checks
- Memory usage during bulk code generation
- Caching of configuration data
- Performance under high load
```

## Architecture Review

Review the architectural design of [FEATURE_NAME]:

### Design Patterns
- Appropriate use of design patterns
- Separation of concerns
- Dependency management
- Extensibility and maintainability

### Integration Points
- Clean interfaces between components
- Proper abstraction layers
- Error propagation and handling
- Testing and mockability

### Future Considerations
- Scalability planning
- AI integration preparation
- Upgrade path considerations
- Technical debt assessment

### Example Usage
```
Architecture review of case management enhancement:
- Component interaction design
- Data flow optimization
- Integration with CiviCRM core
- Extensibility for future features
```

## Testing Review

Review the test coverage and quality for [COMPONENT_NAME]:

### Test Coverage
- Unit test completeness
- Integration test scenarios
- Edge case coverage
- Error condition testing

### Test Quality
- Test organization and structure
- Mock usage and test isolation
- Test data management
- Assertion quality and clarity

### Example Usage
```
Review tests for ServiceRequestToProject workflow:
- Complete workflow testing
- Error scenario coverage
- Test data cleanup
- Performance test inclusion
```

## Documentation Review

Review documentation for [COMPONENT_NAME]:

### Technical Documentation
- Code documentation completeness
- API documentation accuracy
- Architecture decision records
- Setup and configuration guides

### User Documentation
- Clear usage instructions
- Troubleshooting guides
- Example scenarios
- Migration guides

### Example Usage
```
Review documentation for CiviRules integration:
- Action configuration instructions
- Troubleshooting common issues
- Example rule configurations
- Integration best practices
```

---

*Use these templates to ensure thorough and consistent code reviews across all mascode extension components*
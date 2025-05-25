# Architecture Prompt Templates

## System Architecture Analysis

Analyze the current architecture of [SYSTEM_COMPONENT] and provide:

### Current State Assessment
- Component responsibilities and boundaries
- Data flow and interaction patterns
- Technology stack and dependencies
- Performance characteristics
- Scalability limitations

### Architecture Patterns
- Design patterns currently in use
- Adherence to SOLID principles
- Separation of concerns implementation
- Abstraction and encapsulation quality

### Integration Points
- External system dependencies
- CiviCRM core integration points
- API surface and contracts
- Event-driven architecture usage

### Example Usage
```
Analyze the case management architecture focusing on:
- Service request to project conversion workflow
- CiviRules integration patterns
- Data consistency and validation
- Performance bottlenecks
```

## Architecture Decision Record (ADR)

Create an Architecture Decision Record for [DECISION_TOPIC]:

### Context
- Business requirements driving the decision
- Technical constraints and considerations
- Current system limitations
- Stakeholder concerns

### Decision
- Chosen approach and rationale
- Alternative options considered
- Trade-offs and compromises
- Implementation strategy

### Consequences
- Positive impacts and benefits
- Negative impacts and risks
- Migration requirements
- Future implications

### Example Usage
```
Create ADR for choosing API4 over legacy CiviCRM APIs:
- Performance and maintainability benefits
- Migration effort from existing code
- Future compatibility considerations
- Developer experience improvements
```

## Microservices vs Monolith Analysis

Evaluate whether [FEATURE_AREA] should be:

### Monolithic Integration
- Single deployment and maintenance
- Simplified data consistency
- Direct function calls and shared memory
- Easier debugging and testing

### Microservices Approach
- Independent scaling and deployment
- Technology diversity options
- Fault isolation capabilities
- Team autonomy and ownership

### CiviCRM Extension Context
- Extension lifecycle management
- CiviCRM upgrade compatibility
- Resource sharing and permissions
- User experience consistency

### Example Usage
```
Evaluate AI integration architecture:
- PHP-based API integration vs Python microservice
- Real-time vs batch processing requirements
- Data privacy and security considerations
- Deployment and maintenance complexity
```

## Data Architecture Design

Design the data architecture for [DATA_DOMAIN]:

### Data Model
- Entity relationships and constraints
- Data flow and transformation points
- Validation and business rules
- Historical data and versioning

### Storage Strategy
- Database design and normalization
- Custom fields vs entity tables
- Indexing and query optimization
- Data archival and cleanup

### Integration Patterns
- API design for data access
- Event-driven data updates
- Synchronization with external systems
- Caching and performance optimization

### Example Usage
```
Design data architecture for MAS code generation:
- Code format and uniqueness constraints
- Relationship to cases and projects
- Historical tracking requirements
- Performance optimization for bulk operations
```

## Security Architecture Review

Review the security architecture for [SYSTEM_AREA]:

### Authentication & Authorization
- User authentication mechanisms
- Permission and role-based access
- Anonymous access patterns
- Session management

### Data Protection
- Sensitive data identification
- Encryption at rest and in transit
- Data access logging and auditing
- Privacy compliance requirements

### Attack Surface Analysis
- Input validation and sanitization
- API security and rate limiting
- Cross-site scripting prevention
- SQL injection protection

### Example Usage
```
Review security architecture for anonymous form access:
- Checksum-based authentication
- Data validation and sanitization
- Error handling and information disclosure
- Audit logging requirements
```

## Performance Architecture Planning

Plan the performance architecture for [PERFORMANCE_AREA]:

### Performance Requirements
- Response time targets
- Throughput requirements
- Scalability expectations
- Resource utilization limits

### Optimization Strategies
- Caching implementation
- Database query optimization
- Asynchronous processing
- Resource pooling

### Monitoring and Metrics
- Key performance indicators
- Monitoring and alerting strategy
- Performance testing approach
- Capacity planning

### Example Usage
```
Plan performance architecture for bulk case processing:
- Batch processing design
- Memory usage optimization
- Database connection management
- Progress tracking and error recovery
```

## API Architecture Design

Design the API architecture for [API_DOMAIN]:

### API Design Principles
- RESTful design patterns
- Resource modeling and naming
- HTTP status code usage
- Error handling and responses

### CiviCRM Integration
- API4 entity and action patterns
- Permission and access control
- Custom field handling
- Relationship management

### Versioning and Evolution
- API versioning strategy
- Backward compatibility
- Deprecation and migration
- Documentation and discoverability

### Example Usage
```
Design API architecture for AI-enhanced contact management:
- Contact data enrichment endpoints
- Batch processing capabilities
- Real-time vs asynchronous processing
- Integration with existing CiviCRM workflows
```

## Event-Driven Architecture Design

Design event-driven architecture for [EVENT_DOMAIN]:

### Event Modeling
- Event types and schemas
- Event sourcing patterns
- Event ordering and causality
- Event persistence and replay

### Event Processing
- Synchronous vs asynchronous processing
- Event handler registration
- Error handling and retry logic
- Dead letter queue management

### CiviCRM Event Integration
- Symfony EventDispatcher usage
- CiviCRM hook integration
- Custom event definitions
- Event subscriber lifecycle

### Example Usage
```
Design event-driven architecture for case workflow automation:
- Case status change events
- Automated action triggers
- Workflow state management
- Integration with CiviRules
```

## Migration Architecture Planning

Plan migration architecture for [MIGRATION_SCOPE]:

### Migration Strategy
- Big bang vs phased approach
- Data migration requirements
- Rollback and recovery planning
- Testing and validation strategy

### Compatibility Management
- Backward compatibility requirements
- API versioning during migration
- User experience continuity
- Training and change management

### Risk Mitigation
- Data loss prevention
- Performance impact minimization
- Rollback procedures
- Monitoring and alerting

### Example Usage
```
Plan migration from custom forms to FormBuilder:
- Form functionality mapping
- Data preservation requirements
- User workflow continuity
- Testing and validation approach
```

## AI Integration Architecture

Design AI integration architecture for [AI_FEATURE]:

### AI Service Integration
- Local vs cloud-based processing
- API integration patterns
- Real-time vs batch processing
- Model versioning and updates

### Data Pipeline Design
- Data preparation and preprocessing
- Feature engineering requirements
- Model input/output handling
- Result integration and storage

### Performance and Scalability
- Response time requirements
- Cost optimization strategies
- Caching and optimization
- Fallback and error handling

### Example Usage
```
Design AI integration for donor behavior prediction:
- Data collection and preprocessing
- Model training and deployment
- Real-time prediction serving
- Result presentation and action triggers
```

---

*Use these templates to ensure comprehensive architectural analysis and decision-making for the mascode extension*
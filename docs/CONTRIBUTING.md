# Contributing to Mascode

## Getting Started

### Development Setup

See [Development Guide](DEVELOPMENT.md) for complete setup instructions.

### Code Standards

- **Coding Style**: [PER Coding Style](https://www.php-fig.org/per/coding-style/) (successor to PSR-12)
- **CiviCRM Patterns**: Use API4, EventDispatcher, modern patterns
- **Testing**: Unit tests for business logic, integration tests for workflows

## Contribution Workflow

### 1. Create Issue

- Use appropriate [issue template](https://github.com/briangflett/mascode/issues/new/choose)
- Discuss approach before large changes in [Discussions](https://github.com/briangflett/mascode/discussions)

### 2. Development Process

```bash
# Create feature branch
git checkout -b feature/description

# Make changes following code standards
composer run quality  # Run all quality checks

# Commit with conventional commits
git commit -m "feat: add automatic URL prefixing"
```

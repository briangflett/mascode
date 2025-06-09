# Mascode Extension Automation Commands

This directory contains automation scripts for the Mascode CiviCRM extension development and deployment workflow.

## Available Commands

### 🚀 Release Automation
```bash
# Create a new patch release (1.0.2 -> 1.0.3)
./.claude/commands/release.sh patch

# Create a new minor release (1.0.2 -> 1.1.0)
./.claude/commands/release.sh minor

# Create a new major release (1.0.2 -> 2.0.0)
./.claude/commands/release.sh major
```

**What it does:**
- Increments version number in `info.xml`
- Updates `CHANGELOG.md` with release notes
- Updates `releases.json` for CiviCRM extension manager
- Commits changes and pushes to dev branch
- Creates and merges PR to master
- Creates GitHub release with tag
- Returns to dev branch for continued development

### 🌐 Production Deployment
```bash
# Deploy with CiviRules and relationship processing
./.claude/commands/deploy.sh --civirules --relationships

# Dry run to see what would happen
./.claude/commands/deploy.sh --dry-run

# Deploy specific version
./.claude/commands/deploy.sh --version 1.0.2

# Deploy only extension files
./.claude/commands/deploy.sh
```

**What it does:**
- Exports CiviRules from development (if requested)
- Creates deployment package from git
- Uploads and extracts to production server
- Imports CiviRules to production (if requested)
- Processes employer relationships (if requested)
- Clears caches and verifies deployment

### 📝 Quick Commands
See `quick-commands` file for simple one-liner commands for code analysis, documentation, and development tasks.

## Prerequisites

### For Release Script
- GitHub CLI (`gh`) installed and authenticated
- Clean working directory on dev branch
- Git configured with appropriate permissions

### For Deploy Script
- SSH access to production server configured
- Production server details configured in script
- CiviCRM CV tool available on production

## Configuration

### Production Server Settings
Edit `deploy.sh` to match your production environment:

```bash
PROD_SERVER="user@production-server.com"
PROD_PATH="/path/to/wordpress"
PROD_EXT_PATH="$PROD_PATH/wp-content/uploads/civicrm/ext/mascode"
```

### Release Notes
The release script will prompt you to enter changelog items interactively.

## Safety Features

- ✅ **Validation checks** - Ensures correct directory and prerequisites
- ✅ **Confirmation prompts** - User must confirm before destructive operations
- ✅ **Dry run mode** - Preview changes without making them
- ✅ **Error handling** - Scripts exit on any error
- ✅ **Backup creation** - Production deployment backs up current version

## Troubleshooting

### Release Script Issues
```bash
# If release fails partway through:
git checkout dev
git reset --hard HEAD~1  # If commit was made
# Fix issue and retry
```

### Deploy Script Issues
```bash
# Check SSH connection
ssh user@production-server.com

# Check production paths
ssh user@production-server.com "ls -la /path/to/extensions/"

# Restore from backup if needed
ssh user@production-server.com "cd /path/to/extensions && mv mascode.backup.YYYYMMDD_HHMMSS mascode"
```

## Usage Examples

### Complete Release Workflow
```bash
# 1. Develop features on dev branch
git checkout dev
# ... make changes ...
git commit -m "Add new feature"

# 2. Create release
./.claude/commands/release.sh patch

# 3. Deploy to production
./.claude/commands/deploy.sh --civirules --relationships
```

### Testing Before Production
```bash
# Test release process
./.claude/commands/release.sh patch

# Test deployment (dry run)
./.claude/commands/deploy.sh --dry-run --civirules

# Deploy for real
./.claude/commands/deploy.sh --civirules
```

## File Structure After Setup
```
.claude/
├── commands/
│   ├── README.md          # This file
│   ├── release.sh         # Automated release workflow
│   ├── deploy.sh          # Production deployment
│   └── quick-commands     # Simple development commands
├── context.md             # Project context for AI
├── prompts/               # AI prompt templates
├── settings.json          # Claude Code settings
└── ...
```
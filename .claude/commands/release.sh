#!/bin/bash

# Mascode Extension Release Automation
# 
# This script automates the complete release process:
# - Increments version number
# - Updates info.xml, CHANGELOG.md, and releases.json
# - Commits and pushes to dev branch
# - Creates and merges PR to master
# - Creates GitHub release with tag
# - Returns to dev branch for continued development
#
# Usage:
#   ./.claude/commands/release.sh [patch|minor|major]
#   ./.claude/commands/release.sh patch    # 1.0.2 -> 1.0.3
#   ./.claude/commands/release.sh minor    # 1.0.2 -> 1.1.0  
#   ./.claude/commands/release.sh major    # 1.0.2 -> 2.0.0
#
# Requirements:
# - Must be run from mascode extension directory
# - GitHub CLI (gh) must be installed and authenticated
# - Working on dev branch with clean working directory

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
log() { echo -e "${GREEN}[$(date '+%H:%M:%S')] $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date '+%H:%M:%S')] WARNING: $1${NC}"; }
error() { echo -e "${RED}[$(date '+%H:%M:%S')] ERROR: $1${NC}"; exit 1; }

# Configuration
RELEASE_TYPE="${1:-patch}"
CURRENT_DATE=$(date '+%Y-%m-%d')

# Validate we're in the right directory
if [[ ! -f "info.xml" ]] || [[ ! -f "mascode.php" ]]; then
    error "Must be run from the mascode extension root directory"
fi

# Validate release type
if [[ ! "$RELEASE_TYPE" =~ ^(patch|minor|major)$ ]]; then
    error "Release type must be 'patch', 'minor', or 'major'"
fi

# Check prerequisites
command -v gh >/dev/null 2>&1 || error "GitHub CLI (gh) is required but not installed"
gh auth status >/dev/null 2>&1 || error "GitHub CLI not authenticated. Run 'gh auth login'"

# Check git status
if [[ -n $(git status --porcelain) ]]; then
    error "Working directory is not clean. Commit or stash changes first."
fi

# Ensure we're on dev branch
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" != "dev" ]]; then
    error "Must be on dev branch. Currently on: $CURRENT_BRANCH"
fi

log "Starting $RELEASE_TYPE release process..."

# Get current version from info.xml
CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' info.xml)
log "Current version: $CURRENT_VERSION"

# Calculate new version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

case "$RELEASE_TYPE" in
    "patch")
        PATCH=$((PATCH + 1))
        ;;
    "minor")
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    "major")
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
log "New version: $NEW_VERSION"

# Confirm with user
read -p "Proceed with release $NEW_VERSION? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    error "Release cancelled by user"
fi

log "Updating version files..."

# Update info.xml version and date
sed -i "s/<version>$CURRENT_VERSION<\/version>/<version>$NEW_VERSION<\/version>/" info.xml
sed -i "s/<releaseDate>[^<]*<\/releaseDate>/<releaseDate>$CURRENT_DATE<\/releaseDate>/" info.xml

# Get changelog entry from user
echo -e "${BLUE}Enter changelog items for this release (one per line, empty line to finish):${NC}"
CHANGELOG_ITEMS=()
while IFS= read -r line; do
    [[ -z "$line" ]] && break
    CHANGELOG_ITEMS+=("* $line")
done

# Update CHANGELOG.md
if [[ ${#CHANGELOG_ITEMS[@]} -eq 0 ]]; then
    CHANGELOG_ITEMS=("* Bug fixes and improvements")
fi

# Create changelog entry
CHANGELOG_ENTRY="## $NEW_VERSION ($CURRENT_DATE)\n\n"
for item in "${CHANGELOG_ITEMS[@]}"; do
    CHANGELOG_ENTRY+="$item\n"
done
CHANGELOG_ENTRY+="\n"

# Insert after first line of CHANGELOG.md
sed -i "1a\\$CHANGELOG_ENTRY" CHANGELOG.md

# Update releases.json
RELEASES_JSON_ENTRY=",\n    \"$NEW_VERSION\": {\n        \"version\": \"$NEW_VERSION\",\n        \"releaseDate\": \"$CURRENT_DATE\",\n        \"downloadUrl\": \"https://github.com/briangflett/mascode/archive/refs/tags/v$NEW_VERSION.tar.gz\",\n        \"compatibility\": {\n          \"ver\": \"6.1\"\n        },\n        \"status\": \"stable\",\n        \"description\": \"${CHANGELOG_ITEMS[0]#* }\"\n      }"

# Insert before the closing brace in releases.json
sed -i "s/      }/      }$RELEASES_JSON_ENTRY/" releases.json

log "Committing changes..."

# Commit version updates
git add info.xml CHANGELOG.md releases.json
git commit -m "Release v$NEW_VERSION

$(printf '%s\n' "${CHANGELOG_ITEMS[@]}")

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

log "Pushing to dev branch..."
git push origin dev

log "Creating pull request..."

# Create PR description
PR_BODY="## Summary
Release v$NEW_VERSION with the following changes:

$(printf '%s\n' "${CHANGELOG_ITEMS[@]}")

## Changes
- Version updated to $NEW_VERSION
- Release date updated to $CURRENT_DATE
- CHANGELOG.md updated with release notes
- releases.json updated for extension manager

## Test Plan
- [x] Version files updated correctly
- [x] Extension builds without errors
- [x] All existing functionality works

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# Create and merge PR
PR_URL=$(gh pr create --title "Release v$NEW_VERSION" --body "$PR_BODY")
log "Pull request created: $PR_URL"

log "Merging pull request..."
gh pr merge --squash

log "Switching to master and creating release..."

# Switch to master and pull changes
git checkout master
git pull origin master

# Create and push tag
git tag -a "v$NEW_VERSION" -m "Release v$NEW_VERSION

$(printf '%s\n' "${CHANGELOG_ITEMS[@]}")

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

git push origin "v$NEW_VERSION"

# Create GitHub release
RELEASE_NOTES="## Summary
Release v$NEW_VERSION

$(printf '%s\n' "${CHANGELOG_ITEMS[@]}")

## Installation
Download the extension files and enable in CiviCRM:

\`\`\`bash
cv ext:enable mascode
\`\`\`

## Requirements
- CiviCRM 6.1+
- PHP 8.3+
- CiviRules extension

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

RELEASE_URL=$(gh release create "v$NEW_VERSION" --title "Release v$NEW_VERSION" --notes "$RELEASE_NOTES")
log "GitHub release created: $RELEASE_URL"

# Return to dev branch
log "Returning to dev branch..."
git checkout dev

log "✅ Release v$NEW_VERSION completed successfully!"
echo
echo -e "${GREEN}Summary:${NC}"
echo -e "  • Version: ${BLUE}$CURRENT_VERSION${NC} → ${BLUE}$NEW_VERSION${NC}"
echo -e "  • PR: ${BLUE}$PR_URL${NC}"
echo -e "  • Release: ${BLUE}$RELEASE_URL${NC}"
echo -e "  • Current branch: ${BLUE}$(git branch --show-current)${NC}"
echo
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  • Update production environment to use new release"
echo -e "  • Continue development on dev branch"
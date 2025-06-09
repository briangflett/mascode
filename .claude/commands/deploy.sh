#!/bin/bash

# Mascode Extension Production Deployment
#
# This script automates deployment of the mascode extension to production,
# including CiviRules import and employer relationship processing.
#
# Usage:
#   ./.claude/commands/deploy.sh [options]
#
# Options:
#   --civirules        Deploy CiviRules (export from dev, import to production)
#   --relationships    Process employer relationships from job titles
#   --dry-run         Show what would be done without making changes
#   --version VERSION Specify version to deploy (default: latest)
#
# Examples:
#   ./.claude/commands/deploy.sh --civirules --relationships
#   ./.claude/commands/deploy.sh --dry-run
#   ./.claude/commands/deploy.sh --version 1.0.2
#
# Requirements:
# - SSH access to production server configured
# - Production environment variables set
# - CiviCRM CV tool available on production

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Helper functions
log() { echo -e "${GREEN}[$(date '+%H:%M:%S')] $1${NC}"; }
warn() { echo -e "${YELLOW}[$(date '+%H:%M:%S')] WARNING: $1${NC}"; }
error() { echo -e "${RED}[$(date '+%H:%M:%S')] ERROR: $1${NC}"; exit 1; }

# Default configuration
DEPLOY_CIVIRULES=false
PROCESS_RELATIONSHIPS=false
DRY_RUN=false
VERSION=""

# Production server configuration (customize these)
PROD_SERVER="mas@masadvise.org"
PROD_PATH="/home/mas/web/masadvise.org/public_html"
PROD_EXT_PATH="$PROD_PATH/wp-content/uploads/civicrm/ext/mascode"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --civirules)
            DEPLOY_CIVIRULES=true
            shift
            ;;
        --relationships)
            PROCESS_RELATIONSHIPS=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --version)
            VERSION="$2"
            shift 2
            ;;
        -h|--help)
            echo "Usage: $0 [--civirules] [--relationships] [--dry-run] [--version VERSION]"
            exit 0
            ;;
        *)
            error "Unknown option: $1"
            ;;
    esac
done

# Validate we're in the right directory
if [[ ! -f "info.xml" ]] || [[ ! -f "mascode.php" ]]; then
    error "Must be run from the mascode extension root directory"
fi

# Get version info
if [[ -z "$VERSION" ]]; then
    VERSION=$(grep -oP '<version>\K[^<]+' info.xml)
fi

log "Starting deployment to production..."
log "Version: $VERSION"
log "Deploy CiviRules: $DEPLOY_CIVIRULES"
log "Process Relationships: $PROCESS_RELATIONSHIPS"
log "Dry Run: $DRY_RUN"

if [[ "$DRY_RUN" == "true" ]]; then
    warn "DRY RUN MODE - No changes will be made"
fi

# Confirm deployment
if [[ "$DRY_RUN" == "false" ]]; then
    read -p "Deploy to production server $PROD_SERVER? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        error "Deployment cancelled by user"
    fi
fi

# Export CiviRules if requested
if [[ "$DEPLOY_CIVIRULES" == "true" ]]; then
    log "Exporting CiviRules from development..."
    
    if [[ "$DRY_RUN" == "true" ]]; then
        log "[DRY RUN] Would export CiviRules to JSON files"
    else
        # Export CiviRules to JSON
        /home/brian/buildkit/bin/cv scr scripts/export_civirules.php
        
        # Verify export files exist
        if [[ ! -d "Civi/Mascode/CiviRules/rules" ]]; then
            error "CiviRules export failed - rules directory not found"
        fi
        
        log "CiviRules exported successfully"
    fi
fi

# Deploy extension files to production
log "Deploying extension files to production..."

if [[ "$DRY_RUN" == "true" ]]; then
    log "[DRY RUN] Would sync extension files to: $PROD_SERVER:$PROD_EXT_PATH"
else
    # Create temporary deployment package
    TEMP_DIR=$(mktemp -d)
    PACKAGE_FILE="$TEMP_DIR/mascode-v$VERSION.tar.gz"
    
    log "Creating deployment package..."
    git archive --format=tar.gz --prefix=mascode/ HEAD > "$PACKAGE_FILE"
    
    # Copy to production server
    log "Uploading to production server..."
    scp "$PACKAGE_FILE" "$PROD_SERVER:/tmp/"
    
    # Extract and deploy on production
    ssh "$PROD_SERVER" << EOF
        set -e
        cd "$PROD_PATH/wp-content/uploads/civicrm/ext"
        
        # Backup current version
        if [[ -d "mascode" ]]; then
            mv mascode mascode.backup.\$(date +%Y%m%d_%H%M%S)
        fi
        
        # Extract new version
        tar -xzf /tmp/mascode-v$VERSION.tar.gz
        
        # Set permissions
        chown -R mas:mas mascode
        chmod -R 755 mascode
        
        # Clean up
        rm /tmp/mascode-v$VERSION.tar.gz
        
        echo "Extension files deployed successfully"
EOF
    
    # Clean up local temp file
    rm -rf "$TEMP_DIR"
    
    log "Extension files deployed to production"
fi

# Import CiviRules on production
if [[ "$DEPLOY_CIVIRULES" == "true" ]]; then
    log "Importing CiviRules on production server..."
    
    if [[ "$DRY_RUN" == "true" ]]; then
        log "[DRY RUN] Would import CiviRules on production"
    else
        ssh "$PROD_SERVER" << EOF
            set -e
            cd "$PROD_EXT_PATH"
            
            # Run CiviRules import
            /home/mas/buildkit/bin/cv scr scripts/import_civirules.php
            
            # Flush cache
            /home/mas/buildkit/bin/cv flush
            
            echo "CiviRules imported successfully"
EOF
        
        log "CiviRules imported on production"
    fi
fi

# Process employer relationships
if [[ "$PROCESS_RELATIONSHIPS" == "true" ]]; then
    log "Processing employer relationships on production..."
    
    if [[ "$DRY_RUN" == "true" ]]; then
        log "[DRY RUN] Would process employer relationships on production"
    else
        warn "This will create employer relationships based on job titles"
        read -p "Continue with relationship processing? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            ssh "$PROD_SERVER" << EOF
                set -e
                cd "$PROD_EXT_PATH"
                
                # Run employer relationship script
                /home/mas/buildkit/bin/cv scr scripts/create_employer_relationships.php
                
                echo "Employer relationships processed successfully"
EOF
            
            log "Employer relationships processed on production"
        else
            warn "Skipping employer relationship processing"
        fi
    fi
fi

# Final steps
log "Running final deployment steps..."

if [[ "$DRY_RUN" == "true" ]]; then
    log "[DRY RUN] Would clear caches and verify deployment"
else
    ssh "$PROD_SERVER" << EOF
        set -e
        cd "$PROD_PATH"
        
        # Clear all caches
        /home/mas/buildkit/bin/cv flush
        
        # Verify extension is enabled and working
        /home/mas/buildkit/bin/cv ext:list | grep mascode || {
            echo "ERROR: Extension not found after deployment"
            exit 1
        }
        
        # Check for any system errors
        /home/mas/buildkit/bin/cv api System.check
        
        echo "Deployment verification completed"
EOF
    
    log "Production caches cleared and deployment verified"
fi

log "✅ Deployment completed successfully!"
echo
echo -e "${GREEN}Summary:${NC}"
echo -e "  • Version deployed: ${BLUE}$VERSION${NC}"
echo -e "  • Production server: ${BLUE}$PROD_SERVER${NC}"
echo -e "  • CiviRules deployed: ${BLUE}$DEPLOY_CIVIRULES${NC}"
echo -e "  • Relationships processed: ${BLUE}$PROCESS_RELATIONSHIPS${NC}"
echo
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  • Test functionality on production website"
echo -e "  • Monitor logs for any issues"
echo -e "  • Update production documentation if needed"
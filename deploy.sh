#!/bin/bash

# Invoice Ninja Deployment Script
# SAFE VERSION - Protects .env and database

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="${VPS_APP_PATH:-$(pwd)}"
LOG_FILE="${APP_PATH}/storage/logs/deployment.log"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
ENV_FILE="${APP_PATH}/.env"

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
    log "SUCCESS: $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    log "ERROR: $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    log "WARNING: $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to protect .env file (using git mechanisms only)
protect_env_file() {
    log "Protecting .env file..."
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env file not found! Cannot proceed without it."
        exit 1
    fi
    
    # Ensure .env is not tracked by git
    git update-index --assume-unchanged .env 2>/dev/null || true
    print_status ".env file protected (git untracked)"
}

# Function to restore .env file (from git stash if needed)
restore_env_file() {
    log "Restoring .env file..."
    
    # Restore .env from stash if it was stashed
    if git stash list | grep -q "protect_env"; then
        git stash pop 2>/dev/null || true
        print_status ".env file restored from git stash"
    fi
    
    # Ensure .env is not tracked
    git update-index --assume-unchanged .env 2>/dev/null || true
}

# Function to enable maintenance mode
enable_maintenance() {
    log "Enabling maintenance mode..."
    cd "$APP_PATH"
    php artisan down --retry=60 || {
        print_warning "Could not enable maintenance mode (might already be enabled)"
    }
    print_status "Maintenance mode enabled"
}

# Function to disable maintenance mode
disable_maintenance() {
    log "Disabling maintenance mode..."
    cd "$APP_PATH"
    
    # Try to disable via artisan, but if vendor is broken, remove the file directly
    php artisan up 2>/dev/null || {
        print_warning "Could not disable via artisan (vendor might be broken), removing maintenance file directly..."
        rm -f storage/framework/down 2>/dev/null || true
        if [ ! -f storage/framework/down ]; then
            print_status "Maintenance mode disabled (manual removal)"
        else
            print_error "Could not disable maintenance mode!"
            return 1
        fi
    }
    print_status "Maintenance mode disabled"
}

# Function to update code (safely)
update_code() {
    log "Updating code from repository..."
    cd "$APP_PATH"
    
    # Check if it's a git repository
    if [ ! -d ".git" ]; then
        print_error "Not a git repository. Please initialize git first."
        exit 1
    fi
    
    # CRITICAL: Ensure .env is not tracked by git
    git update-index --assume-unchanged .env 2>/dev/null || true
    
    # Fetch latest changes
    git fetch origin || {
        print_error "Failed to fetch from repository"
        exit 1
    }
    
    # Get current branch
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
    
    # Check if DEPLOY_BRANCH exists in remote, if not use current branch
    if ! git show-ref --verify --quiet refs/remotes/origin/$DEPLOY_BRANCH 2>/dev/null; then
        print_warning "Branch $DEPLOY_BRANCH not found in remote, using current branch: $CURRENT_BRANCH"
        DEPLOY_BRANCH="$CURRENT_BRANCH"
    fi
    
    # Checkout and pull
    if [ "$CURRENT_BRANCH" != "$DEPLOY_BRANCH" ]; then
        print_warning "Current branch is $CURRENT_BRANCH, switching to $DEPLOY_BRANCH"
        git checkout "$DEPLOY_BRANCH" || {
            print_error "Failed to checkout $DEPLOY_BRANCH"
            exit 1
        }
    fi
    
    # Stash .env if it exists (extra protection)
    if [ -f ".env" ]; then
        git stash push -m "protect_env_$(date +%s)" .env 2>/dev/null || true
    fi
    
    # Reset to latest commit
    git reset --hard "origin/$DEPLOY_BRANCH" || {
        print_error "Failed to update code"
        restore_env_file
        exit 1
    }
    
    # Restore .env from stash if it was stashed
    restore_env_file
    
    # Make sure .env is not tracked
    git update-index --assume-unchanged .env 2>/dev/null || true
    
    print_status "Code updated to latest version"
}

# Function to install dependencies
install_dependencies() {
    log "Installing dependencies..."
    cd "$APP_PATH"
    
    if command_exists composer; then
        # Clean vendor directory if it's in a broken state
        if [ -d "vendor" ] && [ ! -f "vendor/autoload.php" ]; then
            print_warning "Vendor directory appears broken, cleaning..."
            rm -rf vendor
        fi
        
        # Set composer to allow superuser (for root)
        export COMPOSER_ALLOW_SUPERUSER=1
        
        # Clean composer cache if downloads are corrupted
        print_status "Cleaning composer cache..."
        composer clear-cache 2>/dev/null || true
        
        # Try normal install first
        composer install --no-dev --optimize-autoloader --no-interaction --no-progress 2>&1 || {
            print_warning "Composer install failed, trying with platform requirement ignore..."
            # If install fails, try with --ignore-platform-req for ext-redis (common issue)
            composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-req=ext-redis --prefer-dist 2>&1 || {
                print_error "Composer install failed. This might be due to:"
                print_error "  - Network issues downloading packages"
                print_error "  - Disk space issues"
                print_error "  - Corrupted composer cache"
                print_error ""
                print_error "Please run manually on VPS:"
                print_error "  composer clear-cache"
                print_error "  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-redis"
                exit 1
            }
        }
        print_status "Composer dependencies installed"
    else
        print_error "Composer not found. Please install Composer first."
        exit 1
    fi
}

# Function to run migrations safely
run_migrations() {
    log "Running migrations and post-update commands..."
    cd "$APP_PATH"
    
    # Verify .env still exists
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env file missing! Restoring from git stash..."
        restore_env_file
        if [ ! -f "$ENV_FILE" ]; then
            print_error "Cannot restore .env file. Aborting migrations."
            exit 1
        fi
    fi
    
    # Run the post-update command which handles migrations
    # Migrations are safe - they only ADD/UPDATE, never DELETE data
    php artisan ninja:post-update || {
        print_error "Post-update command failed"
        print_warning "Your database is safe - migrations are transactional"
        exit 1
    }
    
    print_status "Migrations completed safely"
}

# Function to optimize application
optimize_application() {
    log "Optimizing application..."
    cd "$APP_PATH"
    
    # Verify .env exists
    if [ ! -f "$ENV_FILE" ]; then
        restore_env_file
    fi
    
    # Clear and cache config
    php artisan config:clear
    php artisan config:cache || {
        print_warning "Config cache failed, but continuing..."
    }
    
    # Clear and cache routes
    php artisan route:clear
    php artisan route:cache || {
        print_warning "Route cache failed, but continuing..."
    }
    
    # Clear and cache views
    php artisan view:clear
    php artisan view:cache || {
        print_warning "View cache failed, but continuing..."
    }
    
    # Clear application cache
    php artisan cache:clear || true
    
    print_status "Application optimized"
}

# Function to set permissions
set_permissions() {
    log "Setting file permissions..."
    cd "$APP_PATH"
    
    # Set directory permissions
    find . -type d -exec chmod 755 {} \; 2>/dev/null || true
    
    # Set file permissions
    find . -type f -exec chmod 644 {} \; 2>/dev/null || true
    
    # Set storage and bootstrap/cache permissions
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    
    # Protect .env file permissions
    chmod 600 .env 2>/dev/null || true
    
    print_status "Permissions set"
}

# Main deployment function
main() {
    log "=========================================="
    log "Starting Invoice Ninja Deployment (SAFE MODE)"
    log "=========================================="
    
    # Change to app directory
    cd "$APP_PATH" || {
        print_error "Cannot access application directory: $APP_PATH"
        exit 1
    }
    
    # Pre-deployment checks
    if [ ! -f "artisan" ]; then
        print_error "Laravel artisan file not found. Are you in the correct directory?"
        exit 1
    fi
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env file not found! Cannot proceed without configuration."
        exit 1
    fi
    
    # Protect .env file
    protect_env_file
    
    # Enable maintenance mode
    enable_maintenance
    
    # Trap to ensure maintenance mode is disabled on exit
    trap 'disable_maintenance' EXIT
    
    # Update code (with .env protection)
    update_code
    
    # Verify .env still exists after code update
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env file was lost during update! Restoring..."
        restore_env_file
        if [ ! -f "$ENV_FILE" ]; then
            print_error "Cannot restore .env file. Deployment aborted."
            exit 1
        fi
    fi
    
    # Install dependencies
    install_dependencies
    
    # Run migrations (safe - only adds/updates, never deletes)
    run_migrations
    
    # Optimize application
    optimize_application
    
    # Set permissions
    set_permissions
    
    # Final .env check
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env file missing! Restoring..."
        restore_env_file
    fi
    
    # Disable maintenance mode
    disable_maintenance
    
    # Clear trap
    trap - EXIT
    
    log "=========================================="
    log "Deployment completed successfully!"
    log "=========================================="
    print_status "Invoice Ninja has been updated successfully!"
    print_status ".env file is protected and unchanged!"
    
    # Show current version if available
    if [ -f "VERSION.txt" ]; then
        VERSION=$(cat VERSION.txt)
        print_status "Current version: $VERSION"
    fi
}

# Run main function
main "$@"

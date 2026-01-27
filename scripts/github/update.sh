#!/bin/bash

# This script should be executed from $PROJECT_REMOTE_DIR/$PROJECT_REMOTE_WEBROOT
set -e

# Validate input parameters
if [ "$#" -ne 2 ]; then
    echo "Error: Missing required parameters"
    echo "Usage: $0 PROJECT_REMOTE_DIR PROJECT_REMOTE_WEBROOT"
    exit 1
fi

PROJECT_REMOTE_DIR=$1
PROJECT_REMOTE_WEBROOT=$2
DRUSH="${PROJECT_REMOTE_DIR}/${PROJECT_REMOTE_WEBROOT}/vendor/bin/drush"
TIMESTAMP=$(date "+%Y%m%d_%H%M%S")
LOG_FILE="${PROJECT_REMOTE_DIR}/logs/update_${TIMESTAMP}.log"

# Create logs directory if it doesn't exist
mkdir -p "${PROJECT_REMOTE_DIR}/logs"

# Function for logging
log_message() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo -e "$message" | tee -a "$LOG_FILE"
}

# Function for error handling
handle_error() {
    local exit_code=$?
    local command=$1
    log_message "❌ ERROR: Command '$command' failed with exit code $exit_code"
    # Disable maintenance mode in case of failure
    $DRUSH state:set system.maintenance_mode 0
    exit $exit_code
}

# Set trap for error handling
trap 'handle_error "$BASH_COMMAND"' ERR

# Validate Drush existence
if [ ! -f "$DRUSH" ]; then
    log_message "❌ ERROR: Drush not found at $DRUSH"
    exit 1
fi

log_message "🚀 Starting Drupal update process..."

# Create dumps directory if it doesn't exist
mkdir -p "${PROJECT_REMOTE_DIR}/dumps"

# Enable maintenance mode
log_message "🔒 Enabling maintenance mode..."
$DRUSH state:set system.maintenance_mode 1

# Database backup
BACKUP_FILE="${PROJECT_REMOTE_DIR}/dumps/dump_${TIMESTAMP}.sql"
log_message "💾 Creating database backup..."
$DRUSH sql:dump --gzip --result-file="$BACKUP_FILE"

# Clear cache before update
log_message "🧹 Clearing cache before update..."
$DRUSH cache:rebuild

# Update database
log_message "🔄 Updating database..."
$DRUSH updatedb -y

# Import configuration
log_message "📥 Importing Drupal configuration..."
$DRUSH config:import -y

# Import configuration
log_message "📥 Importing Drupal translations..."
$DRUSH local:update -y

# Clear cache after update
log_message "🧹 Clearing cache after update..."
$DRUSH cache:rebuild

# Disable maintenance mode
log_message "🔓 Disabling maintenance mode..."
$DRUSH state:set system.maintenance_mode 0

# Cleanup old database dumps
log_message "🗑️ Cleaning up old database dumps..."
cd "${PROJECT_REMOTE_DIR}/dumps/" || exit 1
if [ "$(ls -1 | wc -l)" -gt 3 ]; then
    ls -1tr | head -n -3 | while IFS= read -r file; do
        chmod -R 755 "$file"
        rm -rf "$file"
        log_message "   Removed old dump: $file"
    done
fi

# Cleanup old releases
log_message "🗑️ Cleaning up old releases..."
cd "${PROJECT_REMOTE_DIR}/releases/" || exit 1
if [ "$(ls -1 | wc -l)" -gt 3 ]; then
    ls -1tr | head -n -3 | while IFS= read -r dir; do
        chmod -R 755 "$dir"
        rm -rf "$dir"
        log_message "   Removed old release: $dir"
    done
fi

log_message "✅ Update process completed successfully!"

# Print log file location
echo "📝 Detailed log available at: $LOG_FILE"

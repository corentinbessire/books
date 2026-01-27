#!/bin/bash

# Exit on error
set -e

# Enable debug mode if needed
# set -x

# Validate required environment variables
required_vars=(
  "SSH_HOST"
  "PROJECT_REMOTE_DIR"
  "PROJECT_REMOTE_WEBROOT"
)

for var in "${required_vars[@]}"; do
  if [ -z "${!var}" ]; then
    echo "Error: Required environment variable $var is not set"
    exit 1
  fi
done

PROJECT_RELEASES_DIR="$PROJECT_REMOTE_DIR/releases"

# SSH key path for GitHub Actions
SSH_KEY="$HOME/.ssh/deploy_key"

# Configure SSH command with options
SSH_COMMAND="ssh -i $SSH_KEY"
if [ -n "$SSH_PORT" ]; then
  SSH_COMMAND="$SSH_COMMAND -p $SSH_PORT"
fi

if [ -n "$SSH_OPTIONS" ]; then
  SSH_COMMAND="$SSH_COMMAND $SSH_OPTIONS"
fi

SSH="$SSH_COMMAND $SSH_HOST"

# Find the latest archive
ARCHIVE_DIR="build/dist/"
ARCHIVE_NAME=$(ls -Art $ARCHIVE_DIR | grep tar.gz | tail -n 1)

if [ -z "$ARCHIVE_NAME" ]; then
  echo "Error: No archive found in $ARCHIVE_DIR"
  exit 1
fi

RELEASE_NAME="$(basename $ARCHIVE_NAME .tar.gz)"
RELEASE_DIR="$PROJECT_RELEASES_DIR/$RELEASE_NAME"

echo "🚀 Starting deployment process..."
echo "📦 Release name: $RELEASE_NAME"
echo "📂 Release directory: $RELEASE_DIR"

# Create release directory
echo "📁 Creating release directory..."
$SSH "mkdir -p $RELEASE_DIR" || {
  echo "Error: Failed to create release directory"
  exit 1
}

# Upload archive
echo "⬆️ Uploading new release archive..."
rsync -avzP -e "$SSH_COMMAND" "build/dist/${ARCHIVE_NAME}" "$SSH_HOST:${RELEASE_DIR}/" || {
  echo "Error: Failed to upload archive"
  exit 1
}

# Extract archive
echo "📂 Extracting archive ${ARCHIVE_NAME}..."
$SSH "cd $RELEASE_DIR && tar -xaf $ARCHIVE_NAME && rm $ARCHIVE_NAME" || {
  echo "Error: Failed to extract archive"
  exit 1
}

# Prepare release
echo "🔧 Preparing new release..."
$SSH "
  set -e
  rm -rf $RELEASE_DIR/web/sites/files;
  rm -rf $RELEASE_DIR/private_files;
  rm -rf $RELEASE_DIR/web/sites/default/settings.local.php
  rm -rf $RELEASE_DIR/web/.htaccess
  ln -nsf $PROJECT_REMOTE_DIR/shared/private_files $RELEASE_DIR/private_files
  ln -nsf $PROJECT_REMOTE_DIR/shared/files $RELEASE_DIR/web/sites/default/files
  ln -nsf $PROJECT_REMOTE_DIR/shared/settings.local.php $RELEASE_DIR/web/sites/default/settings.local.php
  ln -nsf $PROJECT_REMOTE_DIR/shared/.htaccess $RELEASE_DIR/web/.htaccess
" || {
  echo "Error: Failed to prepare release"
  exit 1
}

# Update symlink
echo "🔄 Updating symlink to new release..."
$SSH "ln -nsf $RELEASE_DIR/ $PROJECT_REMOTE_DIR/$PROJECT_REMOTE_WEBROOT" || {
  echo "Error: Failed to update symlink"
  exit 1
}

# Run update script
echo "🔄 Running update script..."
$SSH "cd $PROJECT_REMOTE_DIR/$PROJECT_REMOTE_WEBROOT && bash scripts/github/update.sh $PROJECT_REMOTE_DIR $PROJECT_REMOTE_WEBROOT" || {
  echo "Error: Failed to run update script"
  exit 1
}

echo "✅ Deployment completed successfully!"

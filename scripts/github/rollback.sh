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

echo "🔄 Starting remote rollback process..."

# Execute the rollback script on the remote server
$SSH "cd $PROJECT_REMOTE_DIR/$PROJECT_REMOTE_WEBROOT && bash scripts/github/rollback-remote.sh $PROJECT_REMOTE_DIR $PROJECT_REMOTE_WEBROOT" || {
  echo "Error: Failed to run rollback script"
  exit 1
}

echo "✅ Rollback completed successfully!"

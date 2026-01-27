# GitHub Actions Setup Guide

This guide explains how to configure GitHub Actions for CI/CD in this Drupal project.

## Prerequisites

Before setting up GitHub Actions, ensure you have:

1. A GitHub repository for this project
2. SSH access to your deployment server(s)
3. Server(s) configured with the expected directory structure:
   ```
   /var/www/project/           # PROJECT_REMOTE_DIR
   ├── current/                # PROJECT_REMOTE_WEBROOT (symlink to active release)
   ├── releases/               # Release archives
   ├── shared/                 # Shared files across releases
   │   ├── files/              # Drupal public files
   │   ├── private_files/      # Drupal private files
   │   ├── settings.local.php  # Local settings
   │   └── .htaccess           # Apache config
   ├── dumps/                  # Database backups
   └── logs/                   # Deployment logs
   ```

## Step 1: Generate SSH Keys

If you don't already have a deployment key:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy_key
```

Add the public key to your server's `~/.ssh/authorized_keys`:

```bash
cat ~/.ssh/github_deploy_key.pub >> ~/.ssh/authorized_keys
```

## Step 2: Configure Repository Secrets

Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **Secrets**

Add the following repository secrets:

| Secret | Description | How to get it |
|--------|-------------|---------------|
| `SSH_PRIVATE_KEY` | Private SSH key content | `cat ~/.ssh/github_deploy_key` |
| `SSH_KNOWN_HOSTS` | Known hosts entry for your server | `ssh-keyscan -H your-server.com` |
| `SLACK_WEBHOOK_URL` | Slack incoming webhook URL (optional) | Create at https://api.slack.com/messaging/webhooks |

### Getting SSH_KNOWN_HOSTS

Run this command for each server you'll deploy to:

```bash
ssh-keyscan -H dev.example.com >> known_hosts
ssh-keyscan -H staging.example.com >> known_hosts
ssh-keyscan -H production.example.com >> known_hosts
```

Then copy the entire content of `known_hosts` as the secret value.

## Step 3: Create GitHub Environments

Go to your GitHub repository → **Settings** → **Environments**

Create three environments:

### 1. Development Environment

- Click **New environment**
- Name: `development`
- No protection rules needed

### 2. Staging Environment

- Click **New environment**
- Name: `staging`
- Optional: Add reviewers if you want approval before staging deploys

### 3. Production Environment

- Click **New environment**
- Name: `production`
- **Recommended protection rules:**
  - ✅ Required reviewers: Add team members who must approve production deploys
  - ✅ Deployment branches: Select "Selected branches" → Add rule `v*` (only tags)

## Step 4: Configure Environment Variables

For each environment, add the following variables:

Go to **Settings** → **Environments** → Select environment → **Environment variables**

| Variable | Description | Example |
|----------|-------------|---------|
| `SSH_HOST` | Server hostname or IP | `dev.example.com` |
| `SSH_PORT` | SSH port | `22` |
| `SSH_OPTIONS` | Extra SSH options (optional) | `-o StrictHostKeyChecking=no` |
| `PROJECT_REMOTE_DIR` | Absolute path to project on server | `/var/www/myproject` |
| `PROJECT_REMOTE_WEBROOT` | Symlink name for current release | `current` |
| `SLACK_NOTIFICATIONS` | Enable Slack notifications | `true` |

### Example Configuration

**Development:**
```
SSH_HOST=dev.example.com
SSH_PORT=22
PROJECT_REMOTE_DIR=/var/www/dev-site
PROJECT_REMOTE_WEBROOT=current
SLACK_NOTIFICATIONS=true
```

**Staging:**
```
SSH_HOST=staging.example.com
SSH_PORT=22
PROJECT_REMOTE_DIR=/var/www/staging-site
PROJECT_REMOTE_WEBROOT=current
SLACK_NOTIFICATIONS=true
```

**Production:**
```
SSH_HOST=prod.example.com
SSH_PORT=22
PROJECT_REMOTE_DIR=/var/www/prod-site
PROJECT_REMOTE_WEBROOT=current
SLACK_NOTIFICATIONS=true
```

## Step 5: Prepare Your Server

On each deployment server, ensure:

### 1. Create the directory structure

```bash
PROJECT_DIR="/var/www/myproject"

mkdir -p $PROJECT_DIR/{releases,shared,dumps,logs}
mkdir -p $PROJECT_DIR/shared/{files,private_files}
```

### 2. Create shared configuration files

```bash
# Copy your settings.local.php
cp settings.local.php $PROJECT_DIR/shared/

# Copy your .htaccess if customized
cp .htaccess $PROJECT_DIR/shared/
```

### 3. Set permissions

```bash
# Adjust user/group as needed for your web server
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 775 $PROJECT_DIR/shared/files
chmod -R 775 $PROJECT_DIR/shared/private_files
```

### 4. Ensure required tools are installed

- PHP 8.3+ with required extensions
- Composer
- MySQL/MariaDB client
- rsync
- gzip

## Using the Workflows

### CI Workflow (Automatic)

The CI workflow runs automatically on:
- Pull requests to `main`, `stage`, or `develop`
- Pushes to `main`, `stage`, or `develop`
- Tag pushes

It runs these checks:
- Composer install and validation
- Theme build (npm)
- PHPCS (coding standards)
- PHPStan (static analysis)

### Deploy Workflow (Manual)

1. Go to **Actions** → **Deploy**
2. Click **Run workflow**
3. Select the target branch
4. Select the environment (development/staging/production)
5. Click **Run workflow**

The workflow will:
1. Install Composer dependencies (without dev)
2. Build the theme
3. Create a release archive
4. Upload to the server
5. Extract and configure symlinks
6. Run database updates and config import
7. Clear caches

### Rollback Workflow (Manual)

If a deployment causes issues:

1. Go to **Actions** → **Rollback**
2. Click **Run workflow**
3. Select the environment to rollback
4. Click **Run workflow**

The rollback will:
1. Find the previous release
2. Restore the corresponding database backup
3. Switch the symlink to the previous release
4. Run database updates and clear caches

### Security Audit Workflow (Automatic + Manual)

The security audit workflow checks for known vulnerabilities in dependencies:

**Triggers:**
- Daily at 6 AM UTC (scheduled)
- On push to `main`, `stage`, `develop` when `composer.json`, `composer.lock`, or theme `package.json`/`package-lock.json` change
- Manual trigger via Actions UI

**What it does:**
1. Runs `composer audit` for PHP dependencies
2. Runs `npm audit` for Node.js dependencies
3. Creates GitHub issues for **all vulnerable packages** (both direct and transitive)
4. Updates existing issues if vulnerability info changes
5. Automatically closes issues when vulnerabilities are resolved
6. For transitive dependencies, shows which parent package requires it

**Issue Labels:**
- `security` - All security-related issues
- `composer` - PHP/Composer dependency issues
- `npm` - Node/npm dependency issues
- `direct` - Direct dependency (in composer.json/package.json)
- `transitive` - Transitive dependency (required by another package)

**Note:** The workflow requires `issues: write` permission to create/update issues. This is configured in the workflow file.

### Drupal Dependency Updates Workflow (Weekly + Manual)

The dependency updates workflow tracks available updates for Drupal modules:

**Triggers:**
- Weekly on Monday at 7 AM UTC (scheduled)
- On push to `main`, `stage`, `develop` when `composer.lock` changes
- Manual trigger via Actions UI

**What it does:**
1. Runs `composer outdated` to find all outdated packages
2. Filters to **only Drupal packages** (`drupal/*`) - both direct and transitive dependencies
3. Determines update type by comparing versions:
   - **Major** (e.g., 1.x → 2.x) → `priority: high` label
   - **Minor** (e.g., 1.1 → 1.2) → `priority: medium` label
   - **Patch** (e.g., 1.1.0 → 1.1.1) → `priority: low` label
4. Creates one GitHub issue per outdated module
5. Updates existing issues if a newer version becomes available
6. Automatically closes issues when modules are updated
7. For transitive dependencies, shows which parent package requires it

**Issue Labels:**
- `dependency-update` - All update issues
- `drupal` - Drupal module/theme
- `direct` - Direct dependency (in composer.json)
- `transitive` - Transitive dependency (required by another package)
- `priority: high` - Major version updates (red)
- `priority: medium` - Minor version updates (yellow)
- `priority: low` - Patch version updates (green)

**Issue Content Includes:**
- Current and latest version comparison
- Update command (`composer update drupal/module`)
- Link to changelog on Drupal.org
- Pre/post update checklist

## Workflow Triggers Summary

| Workflow | Trigger | When to use |
|----------|---------|-------------|
| CI | Automatic on PR/push | Code quality checks |
| Security Audit | Daily + on dependency changes | Vulnerability detection |
| Dependency Updates | Weekly + on composer.lock change | Track Drupal module updates |
| Deploy | Manual via Actions UI | Deploy to any environment |
| Rollback | Manual via Actions UI | Revert a bad deployment |

## Troubleshooting

### SSH Connection Failed

1. Verify `SSH_PRIVATE_KEY` secret contains the full private key including headers
2. Verify `SSH_KNOWN_HOSTS` contains your server's host key
3. Check that the public key is in the server's `authorized_keys`
4. Test SSH locally: `ssh -i /path/to/key user@server`

### Permission Denied on Server

1. Check file ownership matches web server user
2. Verify the SSH user has write access to `PROJECT_REMOTE_DIR`
3. Check if SELinux or AppArmor is blocking access

### Deployment Stuck in Maintenance Mode

If a deployment fails mid-way, the site may be stuck in maintenance mode:

```bash
ssh user@server
cd /var/www/project/current
vendor/bin/drush state:set system.maintenance_mode 0
vendor/bin/drush cache:rebuild
```

### Missing Database Backup for Rollback

If rollback fails due to missing backup, manually restore from the dumps directory:

```bash
cd /var/www/project
gunzip -c dumps/dump_YYYYMMDD_HHMMSS.sql.gz | vendor/bin/drush sql:cli
```

### CI Job Fails on PHPCS/PHPStan

These are code quality issues that need to be fixed in your code:
- Run `ddev phpcs` locally to see PHPCS errors
- Run `ddev phpstan analyse` locally to see PHPStan errors
- Fix the issues and push again

## Security Recommendations

1. **Use deploy keys**: Create a dedicated SSH key pair for GitHub Actions instead of using personal keys
2. **Limit key permissions**: The server user should only have access to the project directory
3. **Protect production**: Always require reviewers for production deployments
4. **Rotate secrets**: Periodically rotate SSH keys and update secrets
5. **Audit logs**: Review deployment logs in GitHub Actions and on the server

## Migration from GitLab CI

If you were previously using GitLab CI:

1. The GitLab CI configuration (`.gitlab-ci.yml`) is preserved and still functional
2. Both CI systems can coexist if you mirror to GitLab
3. Server scripts in `scripts/gitlab/` remain for GitLab, `scripts/github/` for GitHub Actions
4. Environment variable names are the same; just configure them in GitHub instead of GitLab

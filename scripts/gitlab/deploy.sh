#! /bin/bash

# SSH host must be different when we deliver on local and from jenkins.
# To set the ssh_host, use "export SSH_HOST=user@host" before launching this script.
if [ -z "$SSH_HOST" ]
then
  SSH_HOST="smile@pci-vd.forge.intranet"
fi

if [ -z "$PROJECT_REMOTE_DIR" ]
then
  PROJECT_REMOTE_DIR="/var/www/pci-vd"
fi

PROJECT_RELEASES_DIR="$PROJECT_REMOTE_DIR/releases"
SSH_COMMAND="ssh"
SSH="$SSH_COMMAND $SSH_HOST"

ARCHIVE_DIR="build/dist/"
ARCHIVE_NAME=$(ls -Art $ARCHIVE_DIR | grep tar.gz | tail -n 1)
RELEASE_NAME="$(basename $ARCHIVE_NAME .tar.gz)"
RELEASE_DIR="$PROJECT_RELEASES_DIR/$RELEASE_NAME"

echo "Uploading new release archive:"
$SSH "mkdir -p $RELEASE_DIR"
rsync -avzP -e "$SSH_COMMAND" "build/dist/${ARCHIVE_NAME}" "$SSH_HOST:/${RELEASE_DIR}/"
echo "Extracting archive $(ARCHIVE_NAME)"
$SSH "cd $RELEASE_DIR && tar -xaf $ARCHIVE_NAME 2>&1;
cd $RELEASE_DIR && rm $ARCHIVE_NAME 2>&1;"

echo "Preparing new release in folder $RELEASE_DIR:"
$SSH "rm -rf $RELEASE_DIR/web/sites;
ln -nsf $PROJECT_REMOTE_DIR/shared/private_files $RELEASE_DIR/private_files;
ln -nsf $PROJECT_REMOTE_DIR/shared/web/sites $RELEASE_DIR/web/sites;
cp $PROJECT_REMOTE_DIR/current/web/.htaccess $RELEASE_DIR/web/;
"
echo "Changing symbolic link for new release and launching Drupal updates:"
$SSH "ln -nsf $RELEASE_DIR/ $PROJECT_REMOTE_DIR/current"

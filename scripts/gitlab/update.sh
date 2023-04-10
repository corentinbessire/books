#!/bin/bash

set -e

if [ -z "$PROJECT_REMOTE_DIR" ]
then
  PROJECT_REMOTE_DIR="~/sites/books.gealion.ch"
fi

DRUSH="${PROJECT_REMOTE_DIR}/current/vendor/bin/drush"

echo -e "[notice] Enable maintenance_mode"
$DRUSH state:set system.maintenance_mode 1

echo -e "[notice] Dump database"
$DRUSH sql:dump --gzip --result-file=${PROJECT_REMOTE_DIR}/backup/dump_$(hostname -s)_$(date "+%Y-%m-%d-%Hh%Mm%Ss").sql

echo -e "[notice] Run clear cache"
$DRUSH cr

echo -e "[notice] Update database"
$DRUSH updatedb -y

echo -e "[notice] Export prod config split"
$DRUSH csex prod -y

echo -e "[notice] Import Drupal configuration"
$DRUSH config:import  -y

echo -e "[notice] Run clear cache"
$DRUSH cr

echo -e "[notice] Disable maintenance_mode"
$DRUSH state:set system.maintenance_mode 0

echo -e "[notice] Delete old database dumps files"
cd ${PROJECT_REMOTE_DIR}/dumps/
ls -1tr | head -n -5 | xargs -d '\n' rm -rf --


echo -e "[notice] Delete old release"
cd ${PROJECT_REMOTE_DIR}/releases/
ls -1tr | head -n -5 | xargs -d '\n' rm -rf --

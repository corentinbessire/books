#! /bin/bash

tar --exclude='./web/sites' --exclude='./vendor' --exclude='./web/core' --exclude='./web/modules/contrib' --exclude='./web/themes/contrib' --exclude='./web/profiles/contrib' --exclude='./web/libraries' -zcvf $(date "+%Y%m%d_%H%M").tgz .

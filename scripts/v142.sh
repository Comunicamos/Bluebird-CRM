#!/bin/sh
#
# v142.sh
#
# Project: BluebirdCRM
# Authors: Brian Shaughnessy and Ken Zalewski
# Organization: New York State Senate
# Date: 2013-07-24
#

prog=`basename $0`
script_dir=`dirname $0`
execSql=$script_dir/execSql.sh
readConfig=$script_dir/readConfig.sh
drush=$script_dir/drush.sh

. $script_dir/defaults.sh

if [ $# -ne 1 ]; then
  echo "Usage: $prog instanceName" >&2
  exit 1
fi

instance="$1"

if ! $readConfig --instance $instance --quiet; then
  echo "$prog: $instance: Instance not found in config file" >&2
  exit 1
fi

app_rootdir=`$readConfig --ig $instance app.rootdir` || app_rootdir="$DEFAULT_APP_ROOTDIR"

## Enable new modules
echo "Enabling nyss_deletetrashed module"
$drush $instance en nyss_deletetrashed -y -q
echo "Enabling nyss_exportpermissions module"
$drush $instance en nyss_exportpermissions -y -q
echo "Enabling nyss_loadsampledata module"
$drush $instance en nyss_loadsampledata -y -q

## 7022 create and populate long form school district table
echo "Creating and populating school district code lookup table..."
$execSql $instance -f $app_rootdir/scripts/sql/schoolDistrictCodes.sql -q

### Cleanup ###
echo "Cleaning up by performing clearCache"
$script_dir/clearCache.sh $instance

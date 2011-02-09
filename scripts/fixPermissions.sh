#!/bin/sh
#
# fixPermissions.sh - Set Bluebird directory permissions appropriately.
#
# Project: BluebirdCRM
# Author: Ken Zalewski
# Organization: New York State Senate
# Date: 2010-09-13
# Revised: 2011-02-09
#

prog=`basename $0`
script_dir=`dirname $0`
readConfig=$script_dir/readConfig.sh

if [ `id -u` -ne 0 ]; then
  echo "$prog: This script must be run by root." >&2
  exit 1
fi

. $script_dir/defaults.sh

appdir=`$readConfig --global app.rootdir` || appdir="$DEFAULT_APP_ROOTDIR"
datdir=`$readConfig --global data.rootdir` || datdir="$DEFAULT_DATA_ROOTDIR"
impdir=`$readConfig --global import.rootdir` || impdir="$DEFAULT_IMPORT_ROOTDIR"
webdir=`$readConfig --global drupal.rootdir` || webdir="$DEFAULT_DRUPAL_ROOTDIR"

appowner=`$readConfig --global app.rootdir.owner`
datowner=`$readConfig --global data.rootdir.owner`
impowner=`$readConfig --global import.rootdir.owner`
webowner=`$readConfig --global drupal.rootdir.owner`

appperms=`$readConfig --global app.rootdir.perms`
datperms=`$readConfig --global data.rootdir.perms`
impperms=`$readConfig --global import.rootdir.perms`
webperms=`$readConfig --global drupal.rootdir.perms`

set -x

[ "$appowner" ] && chown -R "$appowner" "$appdir/"
[ "$appperms" ] && chmod -R "$appperms" "$appdir/"

[ "$datowner" ] && chown -R "$datowner" "$datdir/"
[ "$datperms" ] && chmod -R "$datperms" "$datdir/"

[ "$impowner" ] && chown -R "$impowner" "$impdir/"
[ "$impperms" ] && chmod -R "$impperms" "$impdir/"

[ "$webowner" ] && chown -R "$webowner" "$webdir/"
[ "$webperms" ] && chmod -R "$webperms" "$webdir/"

exit 0

#!/bin/sh
#
# dedupeSetup.sh - All in one dedupe setup script.
#
# Project: BluebirdCRM
# Author: Graylin Kim
# Organization: New York State Senate
# Date: 2011-11-03
# Revised: 2011-11-03
#

prog=`basename $0`
script_dir=`dirname $0`
execSql=$script_dir/execSql.sh
readConfig=$script_dir/readConfig.sh
dedupe_dir=$script_dir/../modules/nyss_dedupe

usage () {
  echo "Usage: $prog [--help|-h] <instance_name>"
}

if [ $# -eq 0 ]; then
  usage; exit 1;
fi

while [ $# -gt 0 ]; do
  case "$1" in
    -h|--help) shift; usage; exit 1;;
    -*) echo "Invalid option '$1'."; usage; exit 1;;
    *) instance="$1"; shift;;
  esac
done

if ! $readConfig --instance $instance --quiet; then
  echo "$prog: '$instance' instance not found in config file" >&2
  exit 1
fi

omis_rule_id=`$execSql -i $instance --quiet -c "SELECT id FROM civicrm_dedupe_rule_group WHERE name='Individual Omis'"`

if [ ! "$omis_rule_id" ]; then
  echo "Creating entries for the OMIS ruleset"
  $execSql -i $instance -c "
      INSERT INTO civicrm_dedupe_rule_group
        (contact_type, threshold, level, is_default, name)
      VALUES
        ('Individual', 15, 'Strict', 0, 'Individual Omis');

      -- This user variable lets us be more flexible instead
      -- of chosing a specific id and hoping for the best.
      SET @last_dedupe_rule_id:=LAST_INSERT_ID();

      INSERT INTO civicrm_dedupe_rule
        (dedupe_rule_group_id, rule_table, rule_field, rule_length, rule_weight)
      VALUES
        (@last_dedupe_rule_id, 'civicrm_contact', 'first_name', NULL, 5),
        (@last_dedupe_rule_id, 'civicrm_contact', 'last_name', NULL, 5),
        (@last_dedupe_rule_id, 'civicrm_address', 'street_address', NULL, 5);"
else
  echo "Existing OMIS rule detected, no action taken."
fi


#Update suffixes tables
echo "Rebuilding Suffix tables."
$execSql -i $instance -f $dedupe_dir/output/suffixes.sql

#Update nickname tables
echo "Rebuilding Nickname tables."
$execSql -i $instance -f $dedupe_dir/output/nicknames.sql

## Add/Drop the tables, functions, and triggers
## We might want to split some of this out in the future
echo "Rebuilding shadow tables, triggers, and functions."
$execSql -f $dedupe_dir/shadow.sql -i $instance

## Force an update on all the relevant fields
## This is a bit of a hack right now banking on us not having other contact types
## in the system and never using the name portion of the civicrm_address. In the
## future we might want to take a better approach and be more change proof.
echo "Populating dedupe tables from civicrm tables."
$execSql -i $instance -c "
    UPDATE civicrm_contact SET contact_type='Individual' WHERE contact_type='Individual';
    UPDATE civicrm_contact SET contact_type='Organization' WHERE contact_type='Organization';
    UPDATE civicrm_contact SET contact_type='Household' WHERE contact_type='Household';
    UPDATE civicrm_address SET name=NULL WHERE name IS NULL;
"

<?php
/*
** bluebird_config.php - Initial configuration for Drupal and CiviCRM settings
**
** Project: BluebirdCRM
** Author: Ken Zalewski
** Organization: New York State Senate
** Date: 2010-09-10
** Revised: 2012-03-05
*/

define('BASE_DIR', realpath(dirname(__FILE__).'/../../'));
define('DEFAULT_CONFIG_FILENAME', 'bluebird.cfg');
define('PROG', basename(__FILE__));


/*
** Locate the Bluebird configuration file.
*/
function get_config_filepath($filename)
{
  $cfg_file = null;

  if (file_exists(BASE_DIR."/$filename")) {
    $cfg_file = BASE_DIR."/$filename";
  }
  else if (($cfg_file = getenv('BLUEBIRD_CONFIG_FILE')) === false) {
    $cfg_file = "/etc/$filename";
  }
  
  return $cfg_file;
} // get_config_filepath()


/*
** Retrieve an array of all configuration parameters within the Bluebird
** configuration file.  The array is indexed by the config groups.  Each
** value is itself an array of name-value pairs.
*/
function get_bluebird_config($filename = null)
{
  static $bbini = null;
  static $s_filename= null;

  /* Do not re-read the configuration file within the same HTTP request,
  ** unless a different config file is specified.
  */
  if ($bbini && $s_filename == $filename) {
    return $bbini;
  }

  $s_filename = $filename;   // save the filename for subsequent calls

  if (empty($filename)) {
    $filename = DEFAULT_CONFIG_FILENAME;
  }

  $cfg_file = get_config_filepath($filename);

  if (!file_exists($cfg_file)) {
    error_log("$cfg_file: Bluebird configuration file not found.");
    return null;
  }

  $bbini = parse_ini_file($cfg_file, true);
  if ($bbini === false) {
    error_log("$cfg_file: Unable to parse the Bluebird configuration file.");
    return null;
  }

  return $bbini;
} // get_bluebird_config()


/*
** Retrieve an array of configuration parameters for a given CRM instance.
** If no instance is specified, attempt to determine the instance from the
** HTTP_HOST server variable, or the INSTANCE_NAME environment variable.
**
** The instance can be a short name (hostname only), or a server name (fully
** qualified domain name).
*/
function get_bluebird_instance_config($instance = null, $filename = null)
{
  static $s_bbcfg = null;
  static $s_instance = null;
  static $s_filename = null;

  $shortname = null;

  if ($instance == null) {
    if (isset($_SERVER['HTTP_HOST'])) {
      $instance = $_SERVER['HTTP_HOST'];
    }
    else if (($instance = getenv('INSTANCE_NAME')) === false) {
      error_log("Unable to determine CRM instance name.");
      return null;
    }
  }

  /* Do not re-read the configuration file within the same HTTP request,
  ** unless a different instance or config file is specified.
  */
  if ($s_bbcfg && $s_instance == $instance && $s_filename == $filename) {
    return $s_bbcfg;
  }

  $s_bbcfg = array();
  $s_instance = $instance;
  $s_filename = $filename;

  $firstdot = strpos($instance, '.');
  $is_ip_address_check = explode(".",$instance);
  if ($firstdot === false) {
    $shortname = $instance;
    $default_base_domain = "crm.nysenate.gov";
  }
  // dp - added for local bluebird configs
  // to only have a ip address
  else if (count($is_ip_address_check) == 4 && ($is_ip_address_check[0] == "192" || $is_ip_address_check[0] == "10")){
    $shortname = "bluebird";
    $default_base_domain = "bluebird";
    $instance = "bluebird";
  }
  else {
    $shortname = substr($instance, 0, $firstdot);
    $default_base_domain = substr($instance, $firstdot + 1);
  }
  $instance_key = 'instance:'.$shortname;
  $bbini = get_bluebird_config($filename);

  if ($bbini && isset($bbini[$instance_key])) {
    // If successful, merge the globals into the instance-specific params.
    if (isset($bbini['globals'])) {
      $s_bbcfg = array_merge($s_bbcfg, $bbini['globals']);
    }
    if (isset($bbini[$instance_key])) {
      $s_bbcfg = array_merge($s_bbcfg, $bbini[$instance_key]);
    }
  }
  else {
    error_log(PROG.": CRM instance [$instance] could not be configured.");
    return null;
  }

  $db_url = 'mysql://'.$s_bbcfg['db.user'].':'.$s_bbcfg['db.pass'].'@'.$s_bbcfg['db.host'].'/';
  $db_basename = isset($s_bbcfg['db.basename']) ? $s_bbcfg['db.basename'] : $shortname;
  $base_domain = isset($s_bbcfg['base.domain']) ? $s_bbcfg['base.domain'] : $default_base_domain;
  $civicrm_db_url = $db_url.$s_bbcfg['db.civicrm.prefix'].$db_basename;
  $drupal_db_url = $db_url.$s_bbcfg['db.drupal.prefix'].$db_basename;
  $log_db_url = $db_url.$s_bbcfg['db.log.prefix'].$db_basename;
  $data_basename = isset($s_bbcfg['data.basename']) ? $s_bbcfg['data.basename'] : $shortname;

  // Prepend a period on the base_domain if it's not empty.
  if (!empty($base_domain) && $base_domain[0] != '.') {
    $base_domain = '.'.$base_domain;
  }

  // Add some extra convenience parameters.
  $s_bbcfg['civicrm_db_url'] = $civicrm_db_url;
  $s_bbcfg['drupal_db_url'] = $drupal_db_url;
  $s_bbcfg['log_db_url'] = $log_db_url;
  $s_bbcfg['base_dir'] = BASE_DIR;
  $s_bbcfg['base_domain'] = $base_domain;
  $s_bbcfg['data_dirname'] = "$data_basename$base_domain";
  $s_bbcfg['servername'] = "$shortname$base_domain";
  $s_bbcfg['shortname'] = $shortname;
  $s_bbcfg['install_class'] = substr(strrchr(BASE_DIR, '_'), 1);
  return $s_bbcfg;
} // get_bluebird_instance_config()


/*
** Return the value of the given key.  If the key is found within the
** provide instance group, use that.  Otherwise, attempt to locate the
** key in the 'globals' group.
*/
function get_key_value($ini, $instance, $keyname)
{
  if (isset($ini['instance:'.$instance][$keyname])) {
    return $ini['instance:'.$instance][$keyname];
  }
  else if (isset($ini['globals'][$keyname])) {
    return $ini['globals'][$keyname];
  }
  else {
    return false;
  }
} // get_key_value()


/*
** Return an array value with the provided key.  If the key does not exist,
** return the provided default value.  If no default value is given,
** return NULL.
*/
function get_config_value($cfgset, $key, $defval)
{
  if (isset($cfgset[$key])) {
    return $cfgset[$key];
  }
  else if ($defval !== null) {
    return $defval;
  }
  else {
    return null;
  }
} // get_config_value()

<?php

// Project: BluebirdCRM
// Authors: Stefan Crain, Graylin Kim, Ken Zalewski
// Organization: New York State Senate
// Date: 2012-10-26
// Revised: 2012-12-20

// ./Redistricting.php -S skelos --batch 2000 --log 5 --max 10000
error_reporting(E_ERROR | E_PARSE | E_WARNING);
set_time_limit(0);

define('DEFAULT_BATCH_SIZE', 1000);
define('DEFAULT_THREADS', 3);
define('UPDATE_NOTES', 1);
define('UPDATE_DISTRICTS', 2);
define('UPDATE_ADDRESSES', 4);
define('UPDATE_GEOCODES', 8);
define('UPDATE_ALL', UPDATE_NOTES|UPDATE_DISTRICTS|UPDATE_ADDRESSES|UPDATE_GEOCODES);
define('REDIST_NOTE', 'REDIST2012');
define('INSTATE_NOTE', 'IN-STATE');
define('OUTOFSTATE_NOTE', 'OUT-OF-STATE');

// Parse the following user options
require_once 'script_utils.php';
$shortopts = "b:l:m:f:naoig:sct:pGN";
$longopts = array("batch=", "log=", "max=", "startfrom=", "dryrun", "addressmap", "outofstate", "instate", "usegeocoder=", "useshapefiles", "usecoordinates", "threads=", "purgenotes", "geocodeonly", "nonotes");
$optlist = civicrm_script_init($shortopts, $longopts);

if ($optlist === null) {
    $stdusage = civicrm_script_usage();
    $usage = '[--batch SIZE] [--log "TRACE|DEBUG|INFO|WARN|ERROR|FATAL"] [--max COUNT] [--startfrom ADDRESS_ID] [--dryrun] [--purgenotes] [--addressmap] [--outofstate] [--instate] [--threads COUNT] [--usegeocoder {geocoder|yahoo|google}] [--useshapefiles] [--usecoordinates] [--geocodeonly] [--nonotes]';
    error_log("Usage: ".basename(__FILE__)."  $stdusage  $usage\n");
    exit(1);
}

// Use user options to configure the script
$BB_LOG_LEVEL = $LOG_LEVELS[strtoupper(get($optlist, 'log', 'TRACE'))][0];
$BB_UPDATE_FLAGS = UPDATE_ALL;
$opt_batch_size = get($optlist, 'batch', DEFAULT_BATCH_SIZE);
$opt_dry_run = get($optlist, 'dryrun', false);
$opt_geocode_only = get($optlist, 'geocodeonly', false);
$opt_no_notes = get($optlist, 'nonotes', false);
$opt_max = get($optlist, 'max', 0);
$opt_startfrom = get($optlist, 'startfrom', 0);
$opt_outofstate = get($optlist, 'outofstate', false);
$opt_addressmap = get($optlist, 'addressmap', false);
$opt_instate = get($optlist, 'instate', false);
$opt_usegeocoder = get($optlist, 'usegeocoder', '');
$opt_useshapefiles = get($optlist, 'useshapefiles', false);
$opt_usecoordinates = get($optlist, 'usecoordinates', false);
$opt_threads = get($optlist, 'threads', DEFAULT_THREADS);
$opt_purgenotes = get($optlist, 'purgenotes', false);

// Use instance settings to configure for SAGE
$bbcfg = get_bluebird_instance_config($optlist['site']);
$sage_base = array_key_exists('sage.api.base', $bbcfg) ? $bbcfg['sage.api.base'] : false;
$sage_key = array_key_exists('sage.api.key', $bbcfg) ? $bbcfg['sage.api.key'] : false;
if (!($sage_base && $sage_key)) {
    error_log(bbscript_log("fatal", "sage.api.base and sage.api.key must be set in your bluebird.cfg file."));
    exit(1);
}

// Dump the active options when in debug mode
bbscript_log("DEBUG", "Option: INSTANCE={$optlist['site']}");
bbscript_log("DEBUG", "Option: BATCH_SIZE=$opt_batch_size");
bbscript_log("DEBUG", "Option: LOG_LEVEL=$BB_LOG_LEVEL");
bbscript_log("DEBUG", "Option: DRY_RUN=".($opt_dry_run ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: GEOCODE_ONLY=".($opt_geocode_only ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: NO_NOTES=".($opt_no_notes ? $opt_no_notes : "FALSE"));
bbscript_log("DEBUG", "Option: SAGE_API=$sage_base");
bbscript_log("DEBUG", "Option: SAGE_KEY=$sage_key");
bbscript_log("DEBUG", "Option: INSTATE=".($opt_instate ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: OUTOFSTATE=".($opt_outofstate ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: ADDRESSMAP=".($opt_addressmap ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: STARTFROM=".($opt_startfrom ? $opt_startfrom : "NONE"));
bbscript_log("DEBUG", "Option: MAX=".($opt_max ? $opt_max : "NONE"));
bbscript_log("DEBUG", "Option: USE_SHAPEFILES=".($opt_useshapefiles ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: USE_COORDINATES=".($opt_usecoordinates ? "TRUE" : "FALSE"));
bbscript_log("DEBUG", "Option: THREADS=$opt_threads");
bbscript_log("DEBUG", "Option: USE_GEOCODER=".($opt_usegeocoder ? $opt_usegeocoder : "FALSE"));

// District mappings for Notes, Distinfo, and SAGE
$FIELD_MAP = array(
    'CD' => array('db'=>'congressional_district_46', 'sage'=>'congressional_code'),
    'SD' => array('db'=>'ny_senate_district_47', 'sage'=>'senate_code'),
    'AD' => array('db'=>'ny_assembly_district_48', 'sage'=>'assembly_code'),
    'ED' => array('db'=>'election_district_49', 'sage'=>'election_code'),
    'CO' => array('db'=>'county_50', 'sage'=>'county_code'),
    'CLEG' => array('db'=>'county_legislative_district_51', 'sage'=>'cleg_code'),
    'TOWN' => array('db'=>'town_52', 'sage'=>'town_code'),
    'WARD' => array('db'=>'ward_53', 'sage'=>'ward_code'),
    'SCHL' => array('db'=>'school_district_54', 'sage'=>'school_code'),
    'CC' => array('db'=>'new_york_city_council_55', 'sage'=>'council_code'),
    'LAT' => array('db'=>'geo_code_1', 'sage'=>'latitude'),
    'LON' => array('db'=>'geo_code_2', 'sage'=>'longitude'),
);

$DIST_FIELDS = array('CD', 'SD', 'AD', 'ED', 'CO',
                     'CLEG', 'TOWN', 'WARD', 'SCHL', 'CC');
$ADDR_FIELDS = array('LAT', 'LON');
$NULLIFY_INSTATE = array('CD', 'SD', 'AD', 'ED');
$NULLIFY_OUTOFSTATE = $DIST_FIELDS;

// Construct the url with all our options...
$bulkdistrict_url = "$sage_base/json/bulkdistrict/body?threadCount=$opt_threads&key=$sage_key&useGeocoder=".($opt_usegeocoder ? "1&geocoder=$opt_usegeocoder" : "0")."&useShapefiles=".($opt_useshapefiles ? 1 : 0);

// Track the full time it takes to run the redistricting process.
$script_start_time = microtime(true);

// Get CiviCRM database connection
require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/DAO.php';
$config =& CRM_Core_Config::singleton();
$dao = new CRM_Core_DAO();
$db = $dao->getDatabaseConnection()->connection;

if ($opt_dry_run) {
  $BB_UPDATE_FLAGS = 0;
}
else if ($opt_geocode_only) {
  $BB_UPDATE_FLAGS = UPDATE_GEOCODES;
}

if ($opt_no_notes) {
  $BB_UPDATE_FLAGS &= ~UPDATE_NOTES;
}

if ($opt_purgenotes) {
    purge_notes($db);
}

// Map old district numbers to new district numbers if addressMap option is set
if ($opt_addressmap) {
    address_map($db);
}

if ($opt_outofstate) {
    handle_out_of_state($db);
}

if ($opt_instate) {
    handle_in_state($db, $opt_startfrom, $opt_batch_size, $opt_max,
                    $bulkdistrict_url, $opt_usecoordinates);
}

$elapsed_time = round(get_elapsed_time($script_start_time), 3);
bbscript_log("INFO", "Completed all tasks in $elapsed_time seconds.");
exit(0);



function purge_notes($db)
{
  global $BB_UPDATE_FLAGS;

  bbscript_log("TRACE", "==> purge_notes()");

  if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
    // Remove any redistricting notes that already exist
    $q = "DELETE FROM civicrm_note
          WHERE entity_table='civicrm_contact'
          AND subject LIKE '".REDIST_NOTE."%'";
    bb_mysql_query($q, $db, true);
    $row_cnt = mysql_affected_rows($db);
    bbscript_log("INFO", "Removed all $row_cnt redistricting notes from the database.");
  }
  else {
    bbscript_log("INFO", "UPDATE_NOTES disabled - No notes were deleted");
  }
  bbscript_log("TRACE", "<== purge_notes()");
} // purge_notes()



function address_map($db)
{
  global $BB_UPDATE_FLAGS;

  bbscript_log("TRACE", "==> address_map()");

  $address_map_changes = 0;
  bbscript_log("INFO", "Mapping old district numbers to new district numbers");
  $district_cycle = array(
    '17'=>18, '18'=>25, '25'=>26, '26'=>28, '27'=>17, '28'=>29, '29'=>27,
    '44'=>49, '46'=>44, '49'=>53, '53'=>58, '58'=>63
  );

  if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
    bb_mysql_query("BEGIN", $db, true);
  }

  $q = "SELECT id, ny_senate_district_47
        FROM civicrm_value_district_information_7";
  $result = bb_mysql_query($q, $db, true);
  $num_rows = mysql_num_rows($result);
  $actions = array();
  while (($row = mysql_fetch_assoc($result)) != null) {
    $district = $row['ny_senate_district_47'];
    if (isset($district_cycle[$district])) {
      if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
        $q = "UPDATE civicrm_value_district_information_7
              SET ny_senate_district_47 = {$district_cycle[$district]}
              WHERE id = {$row['id']};";
        bb_mysql_query($q, $db, true);
        $address_map_changes++;
        if ($address_map_changes % 1000 == 0) {
          bbscript_log("DEBUG", "$address_map_changes mappings so far");
        }
      }

      if (isset($actions[$district])) {
        $actions[$district]++;
      } else {
        $actions[$district] = 1;
      }
    }
  }

  if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
    bb_mysql_query("COMMIT", $db, true);
    bbscript_log("INFO", "Completed district mapping with $address_map_changes changes");
  }
  else {
    bbscript_log("INFO", "UPDATE_DISTRICTS disabled - No changes were made");
  }

  foreach ($actions as $district => $fix_count) {
    bbscript_log("INFO", " $district => {$district_cycle[$district]}: $fix_count");
  }
  bbscript_log("TRACE", "<== address_map()");
} // address_map()



function handle_out_of_state($db)
{
  global $BB_UPDATE_FLAGS;

  bbscript_log("TRACE", "==> handle_out_of_state()");


  if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
    // Delete any out-of-state notes that already exist
    $q = "DELETE FROM civicrm_note
          WHERE entity_table='civicrm_contact'
          AND subject like '".REDIST_NOTE." ".OUTOFSTATE_NOTE."%'";
    bb_mysql_query($q, $db, true);
    $row_cnt = mysql_affected_rows($db);
    bbscript_log('TRACE', "Removed $row_cnt ".OUTOFSTATE_NOTE." notes");
  }
  else {
    bbscript_log('TRACE', 'UPDATE_NOTES disabled - No notes were removed');
  }

  // Retrieve all out-of-state addresses with distinfo
  $result = retrieve_addresses($db, 0, 0, false);
  $total_outofstate = mysql_num_rows($result);

  while ($row = mysql_fetch_assoc($result)) {
    if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
      $note_updates = nullify_district_info($db, $row, false);
      if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
        insert_redist_note($db, OUTOFSTATE_NOTE, 'NOLOOKUP', $row,
                           null, $note_updates);
      }
    }
  }

  if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
    bbscript_log("INFO", "Completed nullifying districts for $total_outofstate out-of-state addresses.");
  }
  else {
    bbscript_log("INFO", "UPDATE_DISTRICTS disabled - No updates were made to out-of-state addresses.");
  }
  bbscript_log("TRACE", "<== handle_out_of_state()");
} // handle_out_of_state()



function handle_in_state($db, $startfrom = 0, $batch_size, $max_addrs = 0,
                         $url, $use_coords)
{
  bbscript_log("TRACE", "==> handle_in_state()");
  // Start a timer and a counter for results
  $time_start = microtime(true);
  $counters = array("TOTAL" => 0,
                    "MATCH" => 0,
                    "NOMATCH" => 0,
                    "INVALID" => 0,
                    "ERROR" => 0,
                    "HOUSE" => 0,
                    "STREET" => 0,
                    "ZIP5" => 0,
                    "SHAPEFILE" => 0,
                    "CURL" => 0,
                    "MYSQL" => 0);

  $start_id = $startfrom;
  $total_rec_cnt = 0;
  $batch_rec_cnt = $batch_size;  // to prime the while() loop

  bbscript_log("INFO", "Beginning batch processing of address records");

  while ($batch_rec_cnt == $batch_size) {
    // If max specified, then possibly constrain the batch size
    if ($max_addrs > 0 && $max_addrs - $total_rec_cnt < $batch_size) {
      $batch_size = $max_addrs - $total_rec_cnt;
      if ($batch_size == 0) {
        bbscript_log("DEBUG", "Max address count ($max_addrs) reached");
        break;
      }
    }

    // Retrieve a batch of in-state addresses with distinfo
    $mysql_result = retrieve_addresses($db, $start_id, $batch_size, true);
    $formatted_batch = array();
    $orig_batch = array();
    $batch_rec_cnt = mysql_num_rows($mysql_result);

    if ($batch_rec_cnt == 0) {
      bbscript_log("TRACE", "No more rows to retrieve");
      break;
    }

    bbscript_log("DEBUG", "Query complete; about to fetch batch of $batch_rec_cnt records");

    while ($row = mysql_fetch_assoc($mysql_result)) {
      $addr_id = $row['id'];
      $total_rec_cnt++;

      // Save the original row for later; we'll need it when saving.
      $orig_batch[$addr_id] = $row;

      // Format for the bulkdistrict API
      $row = clean_row($row);

      // Attempt to fill in missing addresses with supplemental info
      $street = trim($row['street_name'].' '.$row['street_type']);
      if ($street == '') {
        if ($row['supplemental_address_1']) {
          $street = $row['supplemental_address_1'];
        } else if ($row['supplemental_address_2']) {
          $street = $row['supplemental_address_2'];
        }
      }

      // Remove any PO Box information from street address.
      if (preg_match('/^p\.?o\.?\s+(box\s+)?[0-9]+$/i', $street)) {
        $street = '';
      }

      // Format the address for sage
      $formatted_batch[$addr_id] = array(
        'street' => $street,
        'town' => $row['city'],
        'state' => $row['state'],
        'zip5' => $row['postal_code'],
        'apt' => null,
        'building' => $row['street_number'],
        'building_chr' => $row['street_number_suffix'],
      );

      // If requested, use the coordinates already in the system
      if ($use_coords) {
        $formatted_batch[$addr_id]['latitude'] = $row['geo_code_1'];
        $formatted_batch[$addr_id]['longitude'] = $row['geo_code_2'];
      }
    }

    bbscript_log("DEBUG", "Done fetching record batch; sending to SAGE");

    // Send formatted addresses to SAGE for geocoding & district assignment
    $batch_results = distassign($formatted_batch, $url, $counters);

    bbscript_log("DEBUG", "About to process batch results from SAGE");

    if ($batch_results && count($batch_results) > 0) {
      process_batch_results($db, $orig_batch, $batch_results, $counters);
      report_stats($total_rec_cnt, $counters, $time_start);
    }
    else {
      bbscript_log("ERROR", "No batch results; skipping processing for address IDs starting at $start_id.");
    }

    $start_id = $addr_id + 1;
    bbscript_log("INFO", "$total_rec_cnt address records fetched so far");
  }

  bbscript_log("INFO", "Completed assigning districts to in-state addresses.");
  bbscript_log("TRACE", "<== handle_in_state()");
} // handle_in_state()



function retrieve_addresses($db, $start_id = 0, $max_res = 0, $in_state = true)
{
  global $FIELD_MAP, $DIST_FIELDS;

  bbscript_log("TRACE", "==> retrieve_addresses()");

  $limit_clause = ($max_res > 0 ? "LIMIT $max_res" : "");
  $state_compare_op = $in_state ? '=' : '!=';
  $dist_colnames = array();

  foreach ($DIST_FIELDS as $abbrev) {
    $dist_colnames[] = "di.".$FIELD_MAP[$abbrev]['db'];
  }

  $q = "SELECT a.id, a.contact_id,
               a.street_address, a.street_number, a.street_number_suffix,
               a.street_name, a.street_type, a.city, a.postal_code,
               a.supplemental_address_1, a.supplemental_address_2,
               a.geo_code_1, a.geo_code_2,
               sp.abbreviation AS state,
               di.id as district_id,
              ".implode(",\n", $dist_colnames)."
     FROM civicrm_address a
     JOIN civicrm_state_province sp
     LEFT JOIN civicrm_value_district_information_7 di ON (di.entity_id = a.id)
     WHERE a.state_province_id=sp.id
       AND sp.abbreviation $state_compare_op 'NY'
       AND a.id >= $start_id
     ORDER BY a.id ASC
     $limit_clause";

  // Run query to obtain a batch of addresses
  bbscript_log("DEBUG", "Retrieving addresses starting at id $start_id with limit $max_res");
  bbscript_log("TRACE", "SQL query:\n$q");
  $res = bb_mysql_query($q, $db, true);
  bbscript_log("DEBUG", "Finished retrieving addresses");
  bbscript_log("TRACE", "<== retrieve_addresses()");
  return $res;
} // retrieve_addresses()



function distassign(&$fmt_batch, $url, &$cnts)
{
  bbscript_log("TRACE", "==> distassign()");

  // Attach the json data
  bbscript_log("TRACE", "About to encode address batch in JSON");
  $json_batch = json_encode($fmt_batch);

  // Initialize the cURL request
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $json_batch);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-length: ".strlen($json_batch)));
  bbscript_log("TRACE", "About to send API request to SAGE using cURL [url=$url]");
  $response = curl_exec($ch);

  // Record the timings for the request and close
  $curl_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

  $cnts['CURL'] += $curl_time;
  bbscript_log("TRACE", "CURL: fetched in ".round($curl_time, 3)." seconds");
  curl_close($ch);

  // Return null on any kind of response error
  if ($response === null) {
    bbscript_log("ERROR", "Failed to receive a CURL response");
    $results = null;
  }
  else {
    bbscript_log("TRACE", "About to decode JSON response");
    $results = @json_decode($response, true);

    if ($results === null && json_last_error() !== JSON_ERROR_NONE) {
      bbscript_log("ERROR", "Malformed JSON Response");
      bbscript_log("DEBUG", "CURL DATA: $response");
      $results = null;
    }
    else if (count($results) == 0) {
      bbscript_log("ERROR", "Empty response from SAGE. SAGE server is likely offline.");
      $results = null;
    }
    else if (isset($results['message'])) {
      bbscript_log("ERROR", "SAGE server encountered a problem: ".$results['message']);
      $results = null;
    }
  }

  bbscript_log("TRACE", "<== distassign()");
  return $results;
} // distassign()



function process_batch_results($db, &$orig_batch, &$batch_results, &$cnts)
{
  global $BB_UPDATE_FLAGS, $DIST_FIELDS, $ADDR_FIELDS;

  bbscript_log('TRACE', '==> process_batch_results()');

  $batch_cntrs = array(
     'TOTAL'=>count($batch_results), 'MATCH'=>0,
     'HOUSE'=>0, 'STREET'=>0, 'ZIP5'=>0, 'SHAPEFILE'=>0,
     'NOMATCH'=>0, 'INVALID'=>0, 'ERROR'=>0, 'MYSQL'=>0);

  $batch_start_time = microtime(true);

  $addr_ids = array_keys($orig_batch);
  $addr_lo_id = min($addr_ids);
  $addr_hi_id = max($addr_ids);

  if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
    // Delete all notes associated with the current address batch.
    delete_batch_notes($db, $addr_lo_id, $addr_hi_id);
  }
  else {
    bbscript_log("INFO", "UPDATE_NOTES disabled - No notes were removed and none will be added");
  }

  // Iterate over all batch results and update Bluebird tables accordingly.
  bbscript_log('DEBUG', "Updating ".count($batch_results)." records");

  bb_mysql_query('BEGIN', $db, true);

  foreach ($batch_results as $batch_res) {
    $address_id = $batch_res['address_id'];
    $status_code = $batch_res['status_code'];
    $message = $batch_res['message'];
    $orig_rec = $orig_batch[$address_id];

    switch ($status_code) {
    case 'HOUSE':
    case 'STREET':
    case 'ZIP5':
    case 'SHAPEFILE':
      $batch_cntrs['MATCH']++;
      $batch_cntrs[$status_code]++;
      bbscript_log("TRACE", "[MATCH - $status_code][$message] on record #$address_id");

      // Determine differences between original record and SAGE results.
      $changes = calculate_changes($DIST_FIELDS, $orig_rec, $batch_res);
      $subj_abbrevs = $changes['abbrevs'];
      $note_updates = $changes['notes'];
      $sql_updates = $changes['sqldata'];

      if (count($sql_updates) > 0) {
        if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
          if ($orig_rec['district_id']) {
            update_district_info($db, $address_id, $sql_updates);
          }
          else {
            insert_district_info($db, $address_id, $sql_updates);
          }
        }
        else {
          bbscript_log("TRACE", "UPDATE_DISTRICTS disabled - district information for id=$address_id not updated");
        }
      }

      // Shape file lookups can result in new/changed coordinates.
      if ($status_code == 'SHAPEFILE') {
        $changes = calculate_changes($ADDR_FIELDS, $orig_rec, $batch_res);
        $geonote = array("GEO_ACCURACY: {$batch_res['geo_accuracy']}",
                         "GEO_METHOD: {$batch_res['geo_method']}");
        $note_updates = array_merge($note_updates, $changes['notes'], $geonote);
        $sql_updates = $changes['sqldata'];

        if (count($sql_updates) > 0) {
          if ($BB_UPDATE_FLAGS & UPDATE_GEOCODES) {
            update_geocodes($db, $address_id, $sql_updates);
          }
          else {
            bbscript_log("TRACE", "UPDATE_GEOCODES disabled - Geocoordinates for id=$address_id not updated");
          }
        }
      }

      if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
        insert_redist_note($db, INSTATE_NOTE, $status_code, $orig_rec,
                           $subj_abbrevs, $note_updates);
      }
      break;

    case 'NOMATCH':
    case 'INVALID':
      $batch_cntrs[$status_code]++;
      bbscript_log('WARN', "[NOMATCH][$message] on record #$address_id");
      if ($BB_UPDATE_FLAGS & UPDATE_DISTRICTS) {
        $note_updates = nullify_district_info($db, $orig_rec, true);
        if ($BB_UPDATE_FLAGS & UPDATE_NOTES) {
          insert_redist_note($db, INSTATE_NOTE, $status_code, $orig_rec,
                             null, $note_updates);
        }
      }
      else {
        bbscript_log('TRACE', "UPDATE_DISTRICTS disabled - Cannot nullify district info for id=$address_id");
      }
      break;

    default:
      $batch_cntrs['ERROR']++;
      bbscript_log('ERROR', "Unknown status [$status_code] on record #$address_id with message [$message]");
    }
  }

  bb_mysql_query("COMMIT", $db, true);
  $batch_cntrs['MYSQL'] = round(get_elapsed_time($batch_start_time), 4);
  bbscript_log("TRACE", "Updated database in {$batch_cntrs['MYSQL']} secs");

  bbscript_log("INFO", "Stats for current batch:");

  foreach ($batch_cntrs as $key => $val) {
    $cnts[$key] += $val;
    bbscript_log("INFO", "  $key = $val [total={$cnts[$key]}]");
  }
  
  bbscript_log('TRACE', '<== process_batch_results()');
} // process_batch_results()



function delete_batch_notes($db, $lo_id, $hi_id)
{
  bbscript_log("TRACE", "==> delete_batch_notes()");

  // Delete only notes in the current batch
  $q = "DELETE FROM n USING civicrm_note n
        JOIN civicrm_address a ON n.entity_id = a.contact_id
        WHERE a.id BETWEEN $lo_id AND $hi_id
        AND n.subject LIKE '".REDIST_NOTE." ".INSTATE_NOTE."%'
        AND preg_capture('/[[]id=([0-9]+)[]]/', n.subject, 1)
            BETWEEN $lo_id AND $hi_id";
  bb_mysql_query($q, $db, true);
  $row_cnt = mysql_affected_rows($db);
  bbscript_log("INFO", "Removed $row_cnt notes for address IDs from $lo_id to $hi_id");
  bbscript_log("TRACE", "<== delete_batch_notes()");
} // delete_batch_notes()



// Determine the differences, value-by-value, between an augmented
// Bluebird address record (address + distinfo) and the SAGE
// response after distassigning and/or geocoding that record.
function calculate_changes(&$fields, &$db_rec, &$sage_rec)
{
  global $FIELD_MAP, $NULLIFY_INSTATE;

  $changes = array('notes'=>array(), 'abbrevs'=>array(), 'sqldata'=>array());
  $address_id = $sage_rec['address_id'];

  foreach ($fields as $abbr) {
    $dbfld = $FIELD_MAP[$abbr]['db'];
    $sagefld = $FIELD_MAP[$abbr]['sage'];
    $db_val = get($db_rec, $dbfld, 'NULL');
    $sage_val = get($sage_rec, $sagefld, 'NULL');

    if ($db_val != $sage_val) {
      if ($sage_val != 'NULL' || in_array($abbr, $NULLIFY_INSTATE)) {
        // If the SAGE value for the current field is "null" (and the original
        // value was not null), then the field will be nullified only if it's
        // one of the four primary district fields (CD, SD, AD, or ED).
        if ($sage_val == 'NULL') {
          $sage_val = 0;
        }
        $changes['abbrevs'][] = $abbr;
        $changes['sqldata'][$dbfld] = $sage_val;
        $changes['notes'][] = "$abbr:$db_val=>$sage_val";
      }
      else {
        $changes['notes'][] = "$abbr:$db_val~=$db_val";
      }
    }
    else {
      $changes['notes'][] = "$abbr:$db_val==$sage_val";
    }
  }

  return $changes;
} // calculate_changes()



function update_district_info($db, $address_id, $sqldata)
{
  $sql_updates = array();
  foreach ($sqldata as $colname => $value) {
    if ($colname == 'town_52') {
      $sql_updates[] = "$colname = '$value'";
    }
    else {
      $sql_updates[] = "$colname = $value";
    }
  }

  $q = "UPDATE civicrm_value_district_information_7 di
        SET ".implode(', ', $sql_updates)."
        WHERE di.entity_id = $address_id";
  bb_mysql_query($q, $db, true);
} // update_district_info()



function insert_district_info($db, $address_id, $sqldata)
{
  $cols = 'entity_id';
  $vals = "$address_id";

  foreach ($sqldata as $colname => $value) {
    $cols .= ", $colname";
    if ($colname == 'town_52') {
      $vals .= ", '$value'";
    }
    else {
      $vals .= ", $value";
    }
  }

  $q = "INSERT INTO civicrm_value_district_information_7 ( $cols )
        VALUES ( $vals )";
  bb_mysql_query($q, $db, true);
} // insert_district_info()



function nullify_district_info($db, $row, $instate = true)
{
  global $FIELD_MAP, $NULLIFY_INSTATE, $NULLIFY_OUTOFSTATE;

  $sql_updates = array();
  $note_updates = array();
  $dist_abbrevs = ($instate ? $NULLIFY_INSTATE : $NULLIFY_OUTOFSTATE);

  foreach ($dist_abbrevs as $abbrev) {
    $colname = $FIELD_MAP[$abbrev]['db'];
    $sql_updates[$colname] = 0;
    $note_updates[] = "$abbrev:".get($row, $colname, 'NULL')."=>0";
  }

  if ($row['district_id']) {
    update_district_info($db, $row['id'], $sql_updates);
  }
  else {
    insert_district_info($db, $row['id'], $sql_updates);
  }
  return $note_updates;
} // nullify_district_info()



function update_geocodes($db, $address_id, $sqldata)
{
  $sql_updates = array();
  foreach ($sqldata as $colname => $value) {
    $sql_updates[] = "$colname = $value";
  }

  $update_str = implode(', ', $sql_updates);
  bbscript_log("TRACE", "Saving new geocoordinates: $update_str");
  $q = "UPDATE civicrm_address
        SET $update_str
        WHERE id=$address_id";
  bb_mysql_query($q, $db, true);
} // update_geocodes()



function insert_redist_note($db, $note_type, $match_type, &$row,
                            $abbrevs, &$update_notes)
{
  // Create a new contact note describing the state before
  // and after redistricting.
  $addr_id = $row['id'];
  $contact_id = $row['contact_id'];

  if (!$contact_id) {
    bbscript_log('WARN', "No contact ID for address record id=$addr_id; unable to create an $note_type [$match_type] note");
    return;
  }

  $note = "== ".REDIST_NOTE." ==\n".
          "ADDRESS_ID: $addr_id\n".
          "NOTE_TYPE: $note_type\n".
          "MATCH_TYPE: $match_type\n".
          "ADDRESS: ".$row['street_number'].' '.$row['street_number_suffix'].' '.$row['street_name'].' '.$row['street_type'].', '.$row['city'].', '.$row['state'].' '.$row['postal_code']."\n";

  if ($update_notes && is_array($update_notes)) {
    $note .= "UPDATES:\n".implode("\n", $update_notes);
  }

  $subj_ext = '';
  if ($note_type == OUTOFSTATE_NOTE || $match_type == 'NOMATCH'
      || $match_type == 'INVALID' || $match_type == 'NOLOOKUP') {
    $action = 'NULLIFIED';
  }
  else if ($abbrevs && count($abbrevs) > 0) {
    $action = 'UPDATED';
    $subj_ext = ": ".implode(',', $abbrevs);
  }
  else {
    $action = 'VERIFIED';
  }
  
  $subject = REDIST_NOTE." $note_type $action [id=$addr_id]$subj_ext";

  $note = mysql_real_escape_string($note, $db);
  $subject = mysql_real_escape_string($subject, $db);
  $q = "INSERT INTO civicrm_note (entity_table, entity_id, note, contact_id,
                                  modified_date, subject, privacy)
        VALUES ('civicrm_contact', $contact_id, '$note', 1,
                '".date("Y-m-d")."', '$subject', 0)";
  bb_mysql_query($q, $db, true);
} // insert_redist_note()



function report_stats($total_found, $cnts, $time_start)
{
  bbscript_log("TRACE", "==> report_stats()");

  // Compute percentages for certain counts
  $percent = array(
    "MATCH" => 0,
    "NOMATCH" => 0,
    "INVALID" => 0,
    "ERROR" => 0,
    "HOUSE" => 0,
    "STREET" => 0,
    "ZIP5" => 0,
    "SHAPEFILE" => 0
  );

  // Timer for debug
  $time = get_elapsed_time($time_start);
  $Records_per_sec = round($cnts['TOTAL'] / $time, 1);
  $Mysql_per_sec = ($cnts['MYSQL'] == 0 ) ? 0 : round($cnts['TOTAL'] / $cnts['MYSQL'], 1);
  $Curl_per_sec = ($cnts['CURL'] == 0 ) ? 0 : round($cnts['TOTAL'] / $cnts['CURL'], 1);

  // Update the percentages using the counts
  foreach ($percent as $key => $value) {
    $percent[$key] = round($cnts[$key] / $cnts['TOTAL'] * 100, 2);
  }

  $seconds_left = round(($total_found - $cnts['TOTAL']) / $Records_per_sec, 0);
  $finish_at = date('Y-m-d H:i:s', (time() + $seconds_left));

  bbscript_log("INFO", "-------  ------- ---- ---- ---- ---- ");
  bbscript_log("INFO", "[DONE @]      $finish_at (in ".intval($seconds_left/60).":".($seconds_left%60).")");
  bbscript_log("INFO", "[COUNT]      {$cnts['TOTAL']}");
  bbscript_log("INFO", "[TIME]       ".round($time, 4));
  bbscript_log("INFO", "[SPEED]  [TOTAL] $Records_per_sec per second (".$cnts['TOTAL']." in ".round($time, 3).")");
  bbscript_log("TRACE","[SPEED]  [MYSQL] $Mysql_per_sec per second (".$cnts['TOTAL']." in ".round($cnts['MYSQL'], 3).")");
  bbscript_log("TRACE","[SPEED]  [CURL] $Curl_per_sec per second (".$cnts['TOTAL']." in ".round($cnts['CURL'], 3).")");
  bbscript_log("INFO", "[MATCH]  [TOTAL] {$cnts['MATCH']} ({$percent['MATCH']} %)");
  bbscript_log("INFO","[MATCH]  [HOUSE] {$cnts['HOUSE']} ({$percent['HOUSE']} %)");
  bbscript_log("INFO","[MATCH]  [STREET] {$cnts['STREET']} ({$percent['STREET']} %)");
  bbscript_log("INFO","[MATCH]  [ZIP5]  {$cnts['ZIP5']} ({$percent['ZIP5']} %)");
  bbscript_log("INFO","[MATCH]  [SHAPE] {$cnts['SHAPEFILE']} ({$percent['SHAPEFILE']} %)");
  bbscript_log("INFO", "[NOMATCH] [TOTAL] {$cnts['NOMATCH']} ({$percent['NOMATCH']} %)");
  bbscript_log("INFO", "[INVALID] [TOTAL] {$cnts['INVALID']} ({$percent['INVALID']} %)");
  bbscript_log("INFO", "[ERROR]  [TOTAL] {$cnts['ERROR']} ({$percent['ERROR']} %)");
  bbscript_log("TRACE", "<== report_stats()");
} // report_stats()



function clean_row($row)
{
  $match = array('/ AVENUE( EXT)?$/',
                 '/ STREET( EXT)?$/',
                 '/ PLACE/',
                 '/ EAST$/',
                 '/ WEST$/',
                 '/ SOUTH$/',
                 '/ NORTH$/',
                 '/^EAST (?!ST|AVE|RD|DR)/',
                 '/^WEST (?!ST|AVE|RD|DR)/',
                 '/^SOUTH (?!ST|AVE|RD|DR)/',
                 '/^NORTH (?!ST|AVE|RD|DR)/');

  $replace = array(' AVE$1',
                   ' ST$1',
                   ' PL',
                   ' E',
                   ' W',
                   ' S',
                   ' N',
                   'E ',
                   'W ',
                   'S ',
                   'N ');

  $s = preg_replace("/[.,']/", "", strtoupper(trim($row['street_name'])));
  $row['street_name'] = preg_replace($match, $replace, $s);

  $s = preg_replace("/[.,']/", "", strtoupper(trim($row['street_type'])));
  $row['street_type'] = preg_replace($match, $replace, $s);
  return $row;
} // clean_row()



function get($array, $key, $default)
{
  // blank, null, and 0 values are bad.
  if (isset($array[$key]) && $array[$key] != null && $array[$key] !== ''
      && $array[$key] !== 0 && $array[$key] !== '0'
      && $array[$key] !== '00' && $array[$key] !== '000') {
    return $array[$key];
  }
  else {
    return $default;
  }
} // get()


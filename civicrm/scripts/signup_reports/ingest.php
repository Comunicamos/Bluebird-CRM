#!/usr/bin/php
<?php

$prog = basename(__FILE__);
$script_dir = dirname(__FILE__);

require_once "utils.php";
require_once 'nysenate_api/SignupForm.php';
require_once 'nysenate_api/get_services/xmlrpc-api.inc';
require_once realpath(dirname(__FILE__).'/../script_utils.php');

add_packages_to_include_path();

// Load the config file and bootstrap the environment
if(! $config = parse_ini_file("$script_dir/reports.cfg", true)) {
    die("$prog: config file reports.cfg not found.");
}

$env = array(
    'domain' => $config['xmlrpc']['domain'],
    'apikey' => $config['xmlrpc']['apikey'],
    'conn'   => get_connection($config['database'])
);


// Process the command line options and dispatch accordingly
$short_opts = 'hascgub:f:';
$long_opts = array('help','all','senators','committees','geocode','signups','batch=','first=');
$usage = "[--help|-h] [--all|-a] [--senators|-s] [--committees|-c] [--geocode|-g] [--signups|-u] [--batch|-b BATCH] [--first|-f FIRST_PERSON_ID]";
if(!($optList = process_cli_args($short_opts, $long_opts)) || $optList['help'] )
    die("$prog $usage\n");

if($optList['senators'] || $optList['all'])
    updateSenators($optList, $env);

if($optList['committees'] || $optList['all'])
    updateCommitties($optList, $env);

if($optList['signups'] || $optList['all'])
    updateSignups($optList, $env);

if($optList['geocode'] || $optList['all'])
    geocodeAddresses($optList, $env);


function updateSenators($optList, $env) {
    //TODO: If senators get removed from this list (new senators come in) we
    //currently have no way to reflect that fact. Will cause future problems.
    list($domain, $apikey, $conn) = array_values($env);

    $view_service = new viewsGet($domain, $apikey);
    $senators = $view_service->get(array('view_name'=>'senators'));
    foreach($senators as $senator) {
        $node_service = new nodeGet($domain, $apikey);
        $senatorData = $node_service->get(array('nid'=>$senator['nid']));

        //Clean basic information
        $nid = (int)$senatorData['nid'];
        $title = mysql_real_escape_string($senatorData['title'], $conn);

        //Get the district number
        $node_service = new nodeGet($domain, $apikey);
        $districtData = $node_service->get(array('nid'=>$senatorData['field_senators_district'][0]['nid']));
        $district = (int)$districtData['field_district_number'][0]['value'];

        //Get the list id
        $list_title = $senatorData['field_bronto_mailing_list'][0]['value'];
        if(!$list_title) {
            echo "Skipping senator: $title; no mailing list found.\n";
            continue;
        }
        $list_id = get_list_id($list_title, $conn);

        //Insert/Update the senator
        echo "Updating senator: $title...\n";
        $sql = "INSERT INTO senator (nid, title, district, list_id) VALUES ($nid, '$title', $district, $list_id) ON DUPLICATE KEY UPDATE title='$title', district=$district, list_id=$list_id";
        if(! $result = mysql_query($sql,$conn) )
            die(mysql_error($conn)."\n".$sql);
    }
}

function updateCommitties($optList, $env) {
    //TODO: If committees wind up not on this list (decommissioned) we don't update
    //to reflect that fact. Could cause a problem in the future.
    list($domain, $apikey, $conn) = array_values($env);

    $view_service = new viewsGet($domain, $apikey);
    $committees = $view_service->get(array('view_name'=>'committees'));
    foreach($committees as $committee) {
        $node_service = new nodeGet($domain, $apikey);
        $committeeData = $node_service->get(array('nid'=>$committee['nid']));

        //Clean basic information
        $nid = (int)$committeeData['nid'];
        $chair_nid = (int)$committeeData['field_chairs'][0]['nid'];
        $title = mysql_real_escape_string($committeeData['title'], $conn);

        //Get the list id
        $list_title = $committeeData['field_bronto_mailing_list'][0]['value'];
        if(!$list_title) {
            echo "Skipping committee: $title; no mailing list found.\n";
            continue;
        }
        $list_id = get_list_id($list_title, $conn);

        //Insert/Update the committee
        //TODO: Use ON DUPLICATE KEY UPDATE instead, plays nicers with foreign key constraints
        echo "Updating committee: $title...\n";
        $sql = "INSERT INTO committee (nid, title, chair_nid, list_id) VALUES ($nid, '$title', $chair_nid, $list_id) ON DUPLICATE KEY UPDATE title='$title', chair_nid=$chair_nid, list_id=$list_id";
        if(! $result = mysql_query($sql,$conn) )
            die(mysql_error($conn)."\n".$sql);
    }
}


function updateSignups($optList, $env) {
    list($domain, $apikey, $conn) = array_values($env);
    $limit = $optList['batch'] ? $optList['batch'] : 500; //default to 500

    //Starting point can be user supplied or queried from the database for
    //new contacts only
    if($optList['first']!==NULL)
        $start_id = (int)$optList['first'];
    else
        $start_id = get_start_id($conn)+1;

    while(TRUE) {
        $old_start_id = $start_id;

        echo "Fetching the next $limit records starting from ".($start_id?$start_id:0).".\n";
        $signup_service = new SignupForm($apikey, $domain);
        $signupData = $signup_service->getRawEntries(NULL,NULL,$start_id,NULL,$limit);
        if(!$signupData["accounts"] || count($signupData["accounts"]) == 0)
		    break;

        echo "Processing batch....\n";
        foreach($signupData['accounts'] as $account) {
            //Output a quick warning letting us know something wierd is happening
            $num_lists = count($account['lists']);
            if($num_lists > 1)
                echo "account['name']={$account['name']} has {$num_lists} lists associated with it.";
            elseif($num_lists == 0) {
                //There we no lists on this account...wtf? Die...
                die("Account with no lists found...".print_r($account,TRUE));
            }

            $list_id=get_list_id($account['lists'][0]['name'],$conn);

            //Store all the contacts in the database, get_person_id makes new rows as needed.
            //Associate the contact with the last list associated with this account
            //I currently believe each account only has 1 list associated with it...
            foreach($account['contacts'] as $contact) {
                $person_id = get_person_id($contact, $conn);

                //Move up our starting point as necessary
                if($person_id >= $start_id)
                    $start_id = $person_id+1;

                foreach($contact['issues'] as $issue) {
                    $issue = mysql_real_escape_string($issue, $conn);
                    $sql = "INSERT IGNORE INTO issue (person_id,issue) VALUES ($person_id, '$issue')";
                    if(!$result = mysql_query($sql, $conn))
                        die(mysql_error($conn)."\n".$sql);

                }

                $sql = "INSERT IGNORE INTO signup (list_id,person_id) VALUES ($list_id, $person_id)";
                if(!$result = mysql_query($sql,$conn))
                    die(mysql_error($conn)."\n".$sql);

            }
        }
        echo "Inserted $old_start_id to $start_id signup records.\n";
    }
}

function geocodeAddresses($optList, $env) {
    // Bootstrap CiviCRM so we can use the SAGE module
    $conn = $env['conn'];
    $root = dirname(dirname(dirname(dirname(__FILE__))));
    $_SERVER["HTTP_HOST"] = $_SERVER['SERVER_NAME'] = 'sd99';
    require_once "$root/drupal/sites/default/civicrm.settings.php";
    require_once "$root/civicrm/custom/php/CRM/Utils/SAGE.php";

    //Format the row as civicrm likes to see it.
    $sql = "SELECT id,
                   address1 as street_address,
                   address2 as street_address2,
                   city as city,
                   state as state_province,
                   zip as postal_code
            FROM person WHERE district IS NULL ORDER BY id ASC";

    if(! $result = mysql_query($sql, $conn) )
        die(mysql_error($conn)."\n".$sql);

    while($row = mysql_fetch_assoc($result)) {
    	//geocode, dist assign and format address
    	echo "Geocoding: {$row['street_address']} {$row['city']}, {$row['state_province']} {$row['postal_code']}\n";
    	CRM_Utils_SAGE::lookup($row);

        //Supply zero as a default so we can find the bad ones later
    	if(!isset($row['custom_47_-1']) || !$row['custom_47_-1']) {
    	    echo "[NOTICE] Address --^ could not be geocoded.";
    	    $row['custom_47_-1'] = 0;
        }

        $sql = "UPDATE person SET district={$row['custom_47_-1']} WHERE id={$row['id']}";
        if(! $inner_result = mysql_query($sql,$conn) )
            die(mysql_error($conn)."\n".$sql);
    }
    echo "\n";
}


function get_start_id($conn) {
    if(!$result = mysql_query("SELECT max(id) as max_id FROM person",$conn))
        die(mysql_error($conn)."\n".$sql);

    $row = mysql_fetch_assoc($result);
    return $row['max_id'];
}

function get_person_id($contact, $conn) {
    $id = (int)$contact['id'];
    $first_name = mysql_real_escape_string($contact['firstName'],$conn);
    $last_name = mysql_real_escape_string($contact['lastName'],$conn);
    $address1 = mysql_real_escape_string($contact['address1'],$conn);
    $address2 = mysql_real_escape_string($contact['address2'],$conn);
    $city = mysql_real_escape_string($contact['city'],$conn);
    $state = mysql_real_escape_string($contact['state'],$conn);
    $zip = mysql_real_escape_string($contact['zip'],$conn);
    $phone = mysql_real_escape_string($contact['phoneMobile'],$conn);
    $email = mysql_real_escape_string($contact['email'],$conn);
    $status = mysql_real_escape_string($contact['status'],$conn);
    $created = date('Y-m-d H:i:s',(int)$contact['created']);
    $modified = date('Y-m-d H:i:s',(int)$contact['modified']);

    $sql = "SELECT id FROM person WHERE id=$id";
    if($result = mysql_query($sql,$conn)) {

        //Existing Person
        if($row = mysql_fetch_assoc($result)) {
            return $id;

        //New Person
        } else {
            $sql = "
                INSERT INTO person
                    (id, first_name, last_name, address1, address2, city, state, zip, phone, email, status, created, modified)
                VALUES
                    ($id,'$first_name','$last_name','$address1','$address2','$city','$state','$zip','$phone','$email','$status','$created','$modified')
            ";
            if($result = mysql_query($sql, $conn)) {
                return $id;
            }
        }
    }

    die(mysql_error($conn)."\n".$sql);
}

function get_list_id($title, $conn) {
    $title = mysql_real_escape_string($title, $conn);

    $sql = "SELECT id FROM list WHERE title='$title'";
    if($result = mysql_query($sql,$conn) ) {

        //Existing List
        if($row = mysql_fetch_assoc($result)) {
            return $row['id'];

        //New list
        } else {
            $sql = "INSERT INTO list (title) VALUES ('$title')";
            if($result = mysql_query($sql,$conn))
                return mysql_insert_id($conn);
        }
    }

    //Something went wrong with mysql, so die.
    die(mysql_error($conn)."\n".$sql);
}


?>
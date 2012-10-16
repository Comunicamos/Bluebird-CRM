<?php
require_once 'CRM/Core/Error.php';
require_once 'CRM/Utils/IMAP.php';
require_once 'CRM/Core/DAO.php';

class CRM_IMAP_AJAX {
    private static $db = null;

    private static $server = "{webmail.senate.state.ny.us/imap/notls}";
    private static $imap_accounts = array();
    private static $bbconfig = null;

    /* setupImap()
     * Parameters: None.
     * Returns: None.
     * This function loads the Bluebird config then parses out the
     * listed IMAP accounts and stores the data in an array
     * so they can be looped through later.
     */
    private static function setupImap() {
        // Pull Bluebird config and assign it to the $bbconfig variable in case we need it later
        require_once dirname(__FILE__).'/../../../../../civicrm/scripts/bluebird_config.php';
        self::$bbconfig = get_bluebird_instance_config();

        // The format of the accounts is:
        // user1|pass1,user2|pass2,user3|pass3
        // So we'll split on commas then split on pipes to assign to user and pass variables
        $imapAccounts = explode(',', self::$bbconfig['imap.accounts']);
        foreach($imapAccounts as $imapAccount) {
            list($user, $pass) = explode('|', $imapAccount);
            self::$imap_accounts[] = array( 'user'  =>  $user,
                                            'pass'  =>  $pass);
        }

    }

    /* db()
     * Parameters: None.
     * Returns: The database object for the instance.
     * Occasionally we'll need the raw database connection to do
     * some processing, this will get the database connection from
     * CiviCRM and set it to a static variable.
     */
    private static function db() {
        // Load the DAO Object and pull the connection
        if (self::$db == null) {
            $nyss_conn = new CRM_Core_DAO();
            $nyss_conn = $nyss_conn->getDatabaseConnection();
            self::$db = $nyss_conn->connection;
        }
        return self::$db;
    }

    /* get($key)
     * Parameters: $key: The name of the input in the GET message.
     * Returns: The escaped string.
     * We want to be able to escape the string so when we use
     * the key in a query, it's already sanitized.
     */
    private static function get($key) {
        // Call mysql_real_escape_string using the db() connection object
        return mysql_real_escape_string($_GET[$key], self::db());
    }

    /* getUnmatchedMessages()
     * Parameters: None.
     * Returns: A JSON Object of messages in all IMAP inboxes.
     * This function grabs all of the messages in each IMAP Inbox,
     * populates and parses the variables to send back, and then
     * encodes it as a JSON object and shoots it back.
     */
    public static function getUnmatchedMessages() {
        require_once 'CRM/Utils/IMAP.php';
        // Pull all of the IMAP usernames into the $imap_accounts variable
        self::setupImap();
        $messages = array();

        // Loop through the imap accounts and assign an "imap id"
        for($imap_id = 0; $imap_id < count(self::$imap_accounts); $imap_id++) {
            // $imap will be your connection to the IMAP server
            $imap = new CRM_Utils_IMAP(self::$server,
                                    self::$imap_accounts[$imap_id]['user'],
                                    self::$imap_accounts[$imap_id]['pass']);
            // Search for all UIDs that meet the criteria of ""
            // Then get the headers for some basic information.
            $ids = imap_search($imap->conn(),"",SE_UID);
            $headers = imap_fetch_overview($imap->conn(),implode(',',$ids),FT_UID);

            // Loop through the headers and check to make sure they're valid UIDs
            foreach($headers as $header) {
                if( in_array($header->uid,$ids)) {
                    // Get the message based on the UID of the header.
                    $message = $imap->getmsg_uid($header->uid);

                    $matches = array();

                    // Read the from: sender in the format:
                    // From: "First Last" <email address>
                    // or
                    // From: First Last mailto:emailaddress

                    $details = ($message->plainmsg) ? $message->plainmsg : strip_tags($message->htmlmsg);
                    $tempDetails = preg_replace("/(=|\r\n|\r|\n)/i", "", $details);
  

                    $count = preg_match("/From:(?:\s*)(?:(?:\"|'|&quot;)(.*?)(?:\"|'|&quot;)|(.*?))(?:\s*)(?:\[mailto:|<|&lt;)(.*?)(?:]|>|&gt;)/", $tempDetails, $matches);
                    // Was this message forwarded or is this a raw message from the sender?
                    $forwarded = false;

                    // If you can find the From: text that means it was forwarded,
                    // so parse it out and use that.
                    if ($count > 0) {
                        $header->from_email = $matches[3];
                        $header->from_name = !empty($matches[1]) ? $matches[1] : $matches[2];
                        $header->forwarder = htmlentities($header->from);
                        $header->forwarder_time = date("Y-m-d H:i A", $header->udate); 
                        $forwarded = true;
                    } else {
                        // Otherwise, search for a name and email address from
                        // the header and assume the person who sent it in
                        // is submitting the activity.
                        $count = preg_match("/[\"']?(.*?)[\"']?\s*(?:\[mailto:|<)(.*?)(?:[\]>])/", $header->from, $matches);
                        $header->from_email = $matches[2];
                        $header->from_name = $matches[1];
                        $header->forwarder = htmlentities($header->from);
                    }

                    // We don't want the fwd: or re: on any messages, that's silly
                    $header->subject = preg_replace("/(fwd:|fw:|re:) /i", "", $header->subject);

                    // Set the imap_id of this message (the mailbox it came from)
                    // And then set the date to be blank because we'll just pull
                    // it from the forwarded message.
                    $header->imap_id = $imap_id;

                    // Search for the format "Date: blah blah blah"
                    // This is most formats from Lotus Notes and iNotes
                    if($forwarded) {
                        $count = preg_match("/Date:\s*(.*)/", $details, $matches);
                        if($count == 0) {
                            // Uhoh, that one didn't work, let's try this format that gmail uses:
                            // "On Month Day, Year, at Hour:Min AMPM, "
                            $countOnAt = preg_match("/On\s+(.*), at (.*), (.*)/i", $details, $matches);
                            if($countOnAt > 0) {
                                $header->date = date("Y-m-d H:i A", strtotime($matches[1].' '.$matches[2]));
                            }
                        } else if(isset($matches[1])) {
                            $header->date = date("Y-m-d H:i A", strtotime($matches[1]));
                        } 
                    }
                    else {
                        // It's not forwarded, pull from header
                        $header->date = date("Y-m-d H:i A", strtotime($header->date));
                    }

                    // gracefully fail to get the date
                    if ( substr($header->date, 0, 4) == "1969"){
                      $header->date  = '0000-00-00 00:00:00';
                    };

                    // Assign the header variable into the $messages array.
                    // The reason we stored our variables in $header is
                    // because there's already existing information we want in there.
                    $messages[$header->uid] = $header;
                }
            }
        }
        
        // Encode the messages variable and return it to the AJAX call
        echo json_encode($messages);
        CRM_Utils_System::civiExit();
    }

    /* getMessageDetails()
     * Parameters: None.
     * Returns: None.
     * This function sets up a connection to the IMAP server with the
     * specified connection ID, and retrieves the message based on UID
     */
    public static function getMessageDetails() {
        // Setup the IMAP variables and connect to the IMAP server
        self::setupImap();
        $id = self::get('id');
        $imap_id = self::get('imapId');
        $imap = new CRM_Utils_IMAP(self::$server,
                                    self::$imap_accounts[$imap_id]['user'],
                                    self::$imap_accounts[$imap_id]['pass']);
        // Pull the message via the UID and output it as plain text if possible
        $email = $imap->getmsg_uid($id);
        $matches = array();

        // HAVE MERCY. I copied and pasted this from the previous section,
        // this should be separated into a function.
        $details = ($email->plainmsg) ? preg_replace("/(\r\n|\r|\n)/", "<br>", $email->plainmsg) : $email->htmlmsg;
        $tempDetails = preg_replace("/(=|\r\n|\r|\n)/i", "", $details);
  
        // Read the from: sender in the format:
        // From: "First Last" <email address>
        // or
        // From: First Last mailto:emailaddress
        $count = preg_match("/From:(?:\s*)(?:(?:\"|'|&quot;)(.*?)(?:\"|'|&quot;)|(.*?))(?:\s*)(?:\[mailto:|<|&lt;)(.*?)(?:]|>|&gt;)/", $tempDetails, $matches);

        // Was this message forwarded or is this a raw message from the sender?
        $forwarded = false;

        $forwardedName = '';
        $forwardedEmail = '';

        // If you can find the From: text that means it was forwarded,
        // so parse it out and use that.
        if ($count > 0) {
            $fromEmail = $matches[3];
            $fromName = !empty($matches[1]) ? $matches[1] : $matches[2];
            $forwardedName = $email->sender[0]->personal;
            $forwardedEmail = $email->sender[0]->mailbox . '@' . $email->sender[0]->host;
            $forwardedTime = $dateSent = date("Y-m-d H:i A", $email->time); 
            $forwarded = true;
        } else {
            // Otherwise, search for a name and  address from
            // the header and assume the person who sent it in
            // is submitting the activity.
            $fromName = $email->sender[0]->personal;
            $fromEmail = $email->sender[0]->mailbox . '@' . $email->sender[0]->host;
            $forwardedName = $forwardedEmail = '';
        }

        $subject = preg_replace("/(fwd:|fw:|re:) /i", "", $email->subject);

        // Search for the format "Date: blah blah blah"
        // This is most formats from Lotus Notes and iNotes
        if($forwarded) {
            $count = preg_match("/Date:\s*(.*)/", $details, $matches);
            if($count == 0) {
                // Uhoh, that one didn't work, let's try this format that gmail uses:
                // "On Month Day, Year, at Hour:Min AMPM, "
                $countOnAt = preg_match("/On\s+(.*), at (.*), (.*)/i", $details, $matches);
                if($countOnAt > 0) {
                    $dateSent = date("Y-m-d H:i A", strtotime($matches[1].' '.$matches[2]));
                }
            } else if(isset($matches[1])) {
                 $dateSent = date("Y-m-d H:i A", strtotime($matches[1]));
            } 
        }
        else {
            // It's not forwarded, pull from header
            $dateSent = date("Y-m-d H:i A", strtotime($email->date));
        }
        // gracefully fail to get the date
        if ( substr($dateSent,0, 4) == "1969"){
          $dateSent = '0000-00-00 00:00:00';
        };

        $returnMessage = array('uid'    =>  $id,
                               'imapId' =>  $imap_id,
                               'fromName'   =>  mb_convert_encoding($fromName, 'UTF-8'),
                               'fromEmail'  =>  $fromEmail,
                               'forwardedName'  =>  mb_convert_encoding($forwardedName, 'UTF-8'),
                               'forwardedEmail' =>  $forwardedEmail,
                               'forwardedDate' =>  $forwardedTime,
                               'subject'    =>  mb_convert_encoding($subject, 'UTF-8'),
                               'details'  =>  mb_convert_encoding($details, 'UTF-8'),
                               'date'   =>  $dateSent);
        echo json_encode($returnMessage);
        CRM_Utils_System::civiExit();
    }

    /* deleteMessage()
     * Parameters: None.
     * Returns: None.
     * This function connects to the IMAP server with the specified user name
     * and password, then deletes the message based on the UID
     */
    public static function deleteMessage() {
        // Set up IMAP variables
        self::setupImap();
        $id = self::get('id');
        $imap_id = self::get('imapId');
        $imap = new CRM_Utils_IMAP(self::$server,
                                    self::$imap_accounts[$imap_id]['user'],
                                    self::$imap_accounts[$imap_id]['pass']);
        // Delete the message with the specified UID
        $status = $imap->deletemsg_uid($id);
        echo json_encode($status);
        CRM_Utils_System::civiExit();
    }

    /* getContacts
     * Paramters: None.
     * Returns: None.
     * This function will grab the inputs from the GET variable and
     * do a search for contacts and return them as a JSON object.
     * Only returns Records with Primary emails & addresse (so no dupes)
     */
    public static function getContacts() {
        $start = microtime(true);
        $s = self::get('s');
        $first_name = self::get('first_name');
        $last_name = self::get('last_name');
        $email_address = self::get('email_address');
        $street_address = self::get('street_address');
        $city = self::get('city');
        $state_id = self::get('state');
        $phone = self::get('phone');
        $query = <<<EOQ
SELECT DISTINCT *
FROM civicrm_contact AS contact
  JOIN civicrm_address AS address ON contact.id=address.contact_id
  JOIN civicrm_state_province AS state ON address.state_province_id=state.id
  JOIN civicrm_phone as phone ON phone.contact_id=contact.id
  JOIN civicrm_email as email ON email.contact_id=contact.id
WHERE contact.is_deleted=0
  AND state.id='$state_id'
  AND address.is_primary = '1'
  AND email.is_primary = '1'
  AND address.city LIKE '$city%'
  AND contact.first_name LIKE '$first_name%'
  AND contact.last_name LIKE '$last_name%'
  AND address.street_address LIKE '$street_address%'
  AND email.email LIKE '$email_address%'
  AND phone.phone LIKE '%$phone%'
ORDER BY contact.sort_name
EOQ;
        $result = mysql_query($query, self::db());
        $results = array();
        while($row = mysql_fetch_assoc($result)) {
            $results[] = $row;
        }
        echo json_encode(array_values($results));
        $end = microtime(true);
        if(self::get('debug')) echo $end-$start;
        mysql_close(self::$db);
        CRM_Utils_System::civiExit();
    }

    /* assignMessage()
     * Parameters: None.
     * Returns: None.
     * Takes message information and saves it as an activity and assigns it to
     * the selected contact ID.
     */ 
    public static function assignMessage() {
        self::setupImap();
        $messageUid = self::get('messageId');
        $contactIds = self::get('contactId');
        $imapId = self::get('imapId');
        $imap = new CRM_Utils_IMAP(self::$server, self::$imap_accounts[$imapId]['user'], self::$imap_accounts[$imapId]['pass']);
        $email = $imap->getmsg_uid($messageUid);
        $senderName = $email->sender[0]->personal;
        $senderEmailAddress = $email->sender[0]->mailbox . '@' . $email->sender[0]->host;
        $originEmailAddress = $email->sender[1]->mailbox . '@' . $email->sender[1]->host;

        $date = $email->date;
        $subject = preg_replace("/(fwd:|fw:|re:)\s?/", "", $email->subject);
        $body = ($email->plainmsg) ? str_replace("\n",'<br>',$email->plainmsg) : $email->htmlmsg;
        
        require_once 'api/api.php';

        // Get the user information for the person who forwarded the email.
        $params = array( 
            'email' => $senderEmailAddress,
            'version' => 3,
        );

        $result = civicrm_api('contact', 'get', $params );

        // HAVE MERCY. I copied and pasted this from the previous section,
        // this should be separated into a function.
        $details = ($email->plainmsg) ? preg_replace("/(\r\n|\r|\n)/", "<br>", $email->plainmsg) : $email->htmlmsg;
        $tempDetails = preg_replace("/(=|\r\n|\r|\n)/i", "", $details);
  
        // Read the from: sender in the format:
        // From: "First Last" <email address>
        // or
        // From: First Last mailto:emailaddress
        $count = preg_match("/From:(?:\s*)(?:(?:\"|'|&quot;)(.*?)(?:\"|'|&quot;)|(.*?))(?:\s*)(?:\[mailto:|<|&lt;)(.*?)(?:]|>|&gt;)/", $tempDetails, $matches);

        // Was this message forwarded or is this a raw message from the sender?
        $forwarded = false;
        // If you can find the From: text that means it was forwarded,
        // so parse it out and use that.
        if ($count > 0) {
            $fromEmail = $matches[3];
        } else {
            // Otherwise, search for a name and  address from
            // the header and assume the person who sent it in
            // is submitting the activity.
            $fromEmail = $email->sender[0]->mailbox . '@' . $email->sender[0]->host;
        }

        if (($result['is_error']==1) && ($result['values'])){
          $forwarderId = $result['id'];
        }

        $contactIds = explode(',', $contactIds);
        foreach($contactIds as $contactId) {

          // On match add email to user 
           $params = array( 
            'contact_id' => $contactId,
            'email' => $fromEmail,
            'version' => 3,
          );
          $result = civicrm_api( 'email','create',$params );


          // Submit the activity information and assign it to the right user
          $params = array(
              'activity_type_id' => 12,
              'source_contact_id' => $forwarderId,
              'assignee_contact_id' => $forwarderId,
              'target_contact_id' => $contactId,
              'subject' => $subject,
              'status_id' => 2,
              'details' => $body,
              'version' => 3
          );

          $activity = civicrm_api('activity', 'create', $params);
          
          self::assignTag($activity['id'], 0, self::getInboxPollingTagId());
          // Now we need to assign the tag to the activity.

        }
        // Move the message to the archive folder!
        $imap->movemsg_uid($messageUid, 'Archive');
        CRM_Utils_System::civiExit();
    }

    public static function assignTag($inActivityIds = null, $inContactIds = null, $inTagIds = null) {
        $activityIds    =   ($inActivityIds) ? $inActivityIds : self::get('activityIds');
        $contactIds     =   ($inContactIds) ? $inContactIds : self::get('contactIds');
        $tagIds         =   ($inTagIds) ? $inTagIds : self::get('tagIds');
        $activityIds    =   split(',', $activityIds);
        $contactIds     =   split(',', $contactIds);
        $tagIds         =   split(',', $tagIds);

        // If there are no tagIds or it's zero, return an error message
        // via JSON so we can display it to the user.
        if(is_null($tagIds) || $tagIds == 0) {
            $returnCode = array('code'      =>  'ERROR',
                                'message'   =>  'No valid tags.');
            echo json_encode($returnCode);
            CRM_Utils_System::civiExit();
        }
        require_once 'api/api.php';

        require_once 'CRM/Core/DAO.php';
        $nyss_conn = new CRM_Core_DAO();
        $nyss_conn = $nyss_conn->getDatabaseConnection();
        $conn = $nyss_conn->connection;

        foreach($tagIds as $tagId) {
            foreach($contactIds as $contactId) {
                if($contactId == 0)
                    break;
                $params = array( 
                                'entity_table'  =>  'civicrm_contact',
                                'entity_id'     =>  $contactId,
                                'tag_id'        =>  $tagId,
                                'version'       =>  3,
                                );

                $result = civicrm_api('entity_tag', 'create', $params );
                if($result['is_error']) {
                    $returnCode = array('code'      =>  'ERROR',
                                        'message'   =>  "Problem with Contact ID: {$contactId}");
                    echo json_encode($returnCode);
                    CRM_Utils_System::civiExit();
                }
            }
            foreach($activityIds as $activityId) {
                if($activityId == 0)
                    break;
                $query = "SELECT * FROM civicrm_entity_tag
                            WHERE entity_table='civicrm_activity'
                            AND entity_id={$activityId}
                            AND tag_id={$tagId};";
                $result = mysql_query($query, $conn);

                if(mysql_num_rows($result) == 0) {
                    $query = "INSERT INTO civicrm_entity_tag(entity_table,entity_id,tag_id)
                              VALUES('civicrm_activity',{$activityId},{$tagId});";
                    $result = mysql_query($query, $conn);
                    if($result) {
                     // echo "ADDED TAG TO ACTIVITY!\n";
                    } else {
                      error_log("COULD NOT ADD TAG TO ACTIVITY!\n");
                    }
                }
            }
        }
        $returnCode = array('code'    =>  'SUCCESS');
        echo json_encode($returnCode);
        //the following causes exit before the loop in assignMessage can complete. commenting it allows multi-match
        //CRM_Utils_System::civiExit();
    }

    public static function getMatchedMessages() {
        require_once 'CRM/Core/BAO/Tag.php';
        require_once 'CRM/Core/BAO/EntityTag.php';
        require_once 'CRM/Activity/BAO/ActivityTarget.php';

        // getEntitiesByTag  = get activities id's that are tagged with inbox polling tag
        $tag     = new CRM_Core_BAO_Tag();
        $tag->id = self::getInboxPollingTagId();
        $result = CRM_Core_BAO_EntityTag::getEntitiesByTag($tag);

        foreach($result as $id) {
            // pull in full activity record 
            $params = array('version'   =>  3,
                            'activity'  =>  'get',
                           'id' => $id,
                            );
            $activity = civicrm_api('activity', 'get', $params);
            $activity_node = $activity['values'][$id];

            // get the user the activity is attached to
            $user_id = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($id);
            if($user_id){
                $params = array('version'   =>  3,
                            'activity' => 'get',
                            'id' => $user_id[0],
                        );
                $contact = civicrm_api('contact', 'get', $params);
                $contact_node = $contact['values'][$user_id[0]];
            }


            // find out who the forwarder is
            $params = array('version'   =>  3,
                            'id' => $activity_node['source_contact_id'],
            );
            $forwarder = civicrm_api('contact', 'get', $params );
            $forwarder_node = $forwarder['values'][$activity_node['source_contact_id']];

            $date =  date('m-d-y h:i A', strtotime($activity_node['activity_date_time'])); 
            // message to return 
            $returnMessage[$id] = array('activitId'    =>  $id,
                            'contactId' =>  $contact_node['contact_id'],
                            'fromName'   =>  $contact_node['display_name'],
                            'fromEmail'  =>  $contact_node['email'],
                            'forwarderName' => $forwarder_node['display_name'],
                            'forwarder' => $forwarder_node['email'],
                            'activityId' => $activity_node['id'],
                            'subject'    =>  $activity_node['subject'],
                            'details'  =>  $activity_node['details'],
                            'date'   =>  $date);
         }
        echo json_encode($returnMessage);
        CRM_Utils_System::civiExit();
    }

    public static function getActivityDetails() {

        $activitId = self::get('id');
        $userId = self::get('contact');

        require_once 'CRM/Core/BAO/Tag.php';
        require_once 'CRM/Core/BAO/EntityTag.php';
        require_once 'CRM/Activity/BAO/ActivityTarget.php';

            $params = array('version'   =>  3,
                            'activity'  =>  'get',
                           'id' => $activitId,
            );
            $activity = civicrm_api('activity', 'get', $params);
            $activity_node = $activity['values'][$activitId];

            $params = array('version'   =>  3,
                        'activity' => 'get',
                        'id' => $userId,
                    );
            $contact = civicrm_api('contact', 'get', $params);
            $contact_node = $contact['values'][$userId];
 

            $params = array('version'   =>  3,
                            'id' => $activity_node['source_contact_id'],
            );
            $forwarder = civicrm_api('contact', 'get', $params );
            $forwarder_node = $forwarder['values'][$activity_node['source_contact_id']];

            $date =  date('m-d-y h:i A', strtotime($activity_node['activity_date_time'])); 

        $returnMessage = array('uid'    =>  $activitId,
                                'fromName'   =>  $contact_node['display_name'],
                                'fromEmail'  =>  $contact_node['email'],
                                'forwardedName' => $forwarder_node['display_name'],
                                'forwardedEmail' => $forwarder_node['email'],
                                'subject'    =>  $activity_node['subject'],
                                'details'  =>  $activity_node['details'],
                                'date'   =>  $date);


        echo json_encode($returnMessage);
        CRM_Utils_System::civiExit();
    }
    
    // delete activit and enttity ref 
    public static function deleteActivity() {
        require_once 'api/api.php';
        $id = self::get('id');
        
        // deleteing a activity
        $params = array( 
            'id' => $id,
            'activity_type_id' => 1,
            'version' => 3,
        );
        $result = civicrm_api( 'activity','delete',$params );

        // deleteing a entity is hard via api without entity id, time to use sql 
        $tagid = self::getInboxPollingTagId();
        $query = <<<EOQ
DELETE FROM `civicrm_entity_tag`
WHERE `entity_id` =  $id
AND `tag_id` = $tagid
EOQ;
        $result = mysql_query($query, self::db());
        $results = array();
        while($row = mysql_fetch_assoc($result)) {
            $results[] = $row;
        }
        echo json_encode($result);
        mysql_close(self::$db);
        CRM_Utils_System::civiExit();
    }

    // remove the activity tag
    public static function unproccessedActivity() {
        require_once 'api/api.php';
        $id = self::get('id');
        $contact = self::get('contact');
        $tagid = self::getInboxPollingTagId();

        // deleteing a entity is hard via api without entity id, time to use sql 
        $tagid = self::getInboxPollingTagId();
        $query = <<<EOQ
DELETE FROM `civicrm_entity_tag`
WHERE `entity_id` =  $id
AND `tag_id` = $tagid
EOQ;
        $result = mysql_query($query, self::db());
        $results = array();
        while($row = mysql_fetch_assoc($result)) {
            $results[] = $row;
        }
        echo json_encode($result);
        mysql_close(self::$db);

        CRM_Utils_System::civiExit();

    }

    // reAssignActivity 
    public static function reassignActivity() {
        require_once 'api/api.php';
        $id = self::get('id');
        $contact = self::get('contact');
        $change = self::get('change');
        $results = array();

        // want to update the activity_target, time to use sql 
        // get the the record id please 
        $tagid = self::getInboxPollingTagId();
        $query = <<<EOQ
SELECT id
FROM `civicrm_activity_target`
WHERE `activity_id` = $id
AND `target_contact_id` = $contact
EOQ;

        $activity_id = mysql_query($query, self::db());
        while($row = mysql_fetch_assoc($activity_id)) {
            // print_r($row['id']);
            $row_id = $row['id']; 

            // UPDATE `senate_prod_c_skelos`.`civicrm_activity_target` SET `target_contact_id` = '285159' WHERE `civicrm_activity_target`.`id` =539082;
            $Update = <<<EOQ
UPDATE `civicrm_activity_target`
SET  `target_contact_id`= $change
WHERE `id` =  $row_id
EOQ;

            // change the row           
            $Updated_results = mysql_query($Update, self::db());
                while($row = mysql_fetch_assoc($Updated_results)) {
                     $results[] = $row; 
                }
        }

        echo json_encode($results);
        mysql_close(self::$db);
        CRM_Utils_System::civiExit();
    }

    public static function getTags() {
        require_once 'api/api.php';
        $name = self::get('s');
        $i = 0;
        $results = array();

        $query = <<<EOQ
SELECT id, name
FROM `civicrm_tag`
WHERE `parent_id` ='296' && `name` LIKE '%$name%'
EOQ;
        $result = mysql_query($query, self::db());
        while($row = mysql_fetch_assoc($result)) {
            array_push( $results,  array("label"=>$row['name'], "value"=>$row['id']));
            $i++;
        }
        $final_results = array('items'=> $results);
        echo json_encode($final_results);
        mysql_close(self::$db);
        CRM_Utils_System::civiExit();
    }




    public static function addTags() {
        require_once 'api/api.php';
        $tag_ids = self::get('tags');
        $activityId = self::get('activityId');
        $contactId = self::get('contactId');
        self::assignTag($activityId, $contactId, $tag_ids);
    }

    function getInboxPollingTagId() {
      require_once 'api/api.php';

      // Check if the tag exists
      $params = array(
        'name' => 'Inbox Polling Unprocessed',
        'version' => 3,
      );
      $result = civicrm_api('tag', 'get', $params);
      if($result && isset($result['id'])) {
        return $result['id'];
      }

      // If there's no tag, create it.
      $params = array( 
      'name' => 'Inbox Polling Unprocessed',
      'description' => 'Tag noting that this activity has been created by Inbox Polling and is still Unprocessed.',
      'version' => 3,
      );
      $result = civicrm_api('tag', 'create', $params);
      if($result && isset($result['id'])) {
        return $result['id'];
      }
    }
    public static function createNewContact() {
        //http://skelos/civicrm/imap/ajax/createNewContact?first_name=dan&last_name=pozzi&email=dpozzie@gmail.com&street_address=26%20Riverwalk%20Way&city=Cohoes
        $first_name = $_GET["first_name"];
        $last_name = $_GET["last_name"];
        $email = $_GET["email_address"];
        $phone = $_GET["phone"];
        $street_address = $_GET["street_address"];
        $street_address_2 = $_GET["street_address_2"];
        $postal_code = $_GET["postal_code"];
        $city = $_GET["city"];
 
        if(!($first_name) && !($last_name) && !($email))
        {
            $returnCode = array('code'      =>  'ERROR',
                                'status'    =>  '1',
                                'message'   =>  'Required: First Name, Last Name, and Email');
            echo json_encode($returnCode);
            CRM_Utils_System::civiExit();
        }

        require_once 'api/api.php';
        require_once 'CRM/Core/BAO/Address.php';

        //First, you make the contact
        $params = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'contact_type' => 'Individual',
            'version' => 3,
        );

        $contact = civicrm_api('contact','create', $params);

        //And then you attach the contact to the Address! which is at $contact['id']
        $address_params = array(
            'contact_id' => $contact['id'],
            'street_address' => $street_address,        
            'supplemental_address_1' => $street_address_2,
            'city' => $city,
            'postal_code' => $postal_code,
            'is_primary' => 1,
            'country_id' => 1228,
            'location_type_id' => 1,
            'version' => 3,
        );

        $address = civicrm_api('address', 'create', $address_params);
        if(($contact['is_error'] == 0) && ($address['is_error'] == 0))
        {
            $returnCode = array('code'      =>  'SUCCESS',
                                'status'    =>  '0',
                                'contact' => $contact['id']
                                );
            echo json_encode($returnCode);
            CRM_Utils_System::civiExit();
        } else {
            $returnCode = array('code'      =>  'ERROR',
                                'status'    =>  '1',
                                'message'   =>  'Error adding Contact or Address Details'
                                );
            echo json_encode($returnCode);
            CRM_Utils_System::civiExit();
        }
    }

}
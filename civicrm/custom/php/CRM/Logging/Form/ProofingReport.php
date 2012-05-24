<?php

/*
 * NYSS 5260
 * SOS Log Proofing Report
 * Created: May, 2012
 * Author:  Brian Shaughnessy
 */

require_once 'CRM/Core/Form.php';

/**
 * This class generates form components
 * 
 */
class CRM_Logging_Form_ProofingReport extends CRM_Core_Form
{
    /**
     * pre-form data checks
     *
     * @return void
     * @access public
     */
    function preProcess( ) {
        
        //handle breadcrumbs
        $url = CRM_Utils_System::url( 'civicrm/logging/proofingreport', 'reset=1' );
        $breadCrumb = array ( array( 'url'   => $url,
                                     'title' => ts('Log Proofing Report') ) );
        CRM_Utils_System::appendBreadCrumb( $breadCrumb );
        
        //set page title
        CRM_Utils_System::setTitle( ts('Generate Log Proofing Report') );
    }
    
    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    public function buildQuickForm() {

        $this->addElement( 'text', 'jobID', ts('Job ID') );

        $this->addDate( 'start_date', ts('Date from'), false, array( 'formatType' => 'custom') );
        $this->addDate( 'end_date', ts('...to'), false, array( 'formatType' => 'custom') );

        require_once 'CRM/Core/BAO/PdfFormat.php';
        $this->add( 'select', 'pdf_format_id', ts( 'Page Format' ),
                     array( 0 => ts( '- default -' ) ) + CRM_Core_BAO_PdfFormat::getList( true ) );

        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Generate PDF Report'),
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'upload',
                                         'name'      => ts('Generate Print Report'),
                                         'isDefault' => true   ),
                                 array ( 'type'      => 'back',
                                         'name'      => ts('Cancel') ),
                                 )
                           );

        $this->addFormRule( array( 'CRM_Logging_Form_ProofingReport', 'formRule' ), $this );
    }
    
    /**
     * Set default values
     */
    function setDefaultValues( ) {

        $defaults = array( 'year'           => date('Y'),
                           'pdf_format_id'  => 1895,
                           );
        return $defaults;
    }

    /**  
     * global form rule  
     *  
     * @param array $fields  the input form values  
     * @param array $files   the uploaded files if any  
     * @param array $options additional user data  
     *  
     * @return true if no errors, else array of errors  
     * @access public  
     * @static  
     */  
    static function formRule( $fields, $files, $self ) 
    {
        //CRM_Core_Error::debug_var('fields',$fields);
        $errors = array( );

        if ( empty($fields['jobID']) &&
             empty($fields['start_date']) &&
             empty($fields['end_date']) ) {

            $errors['jobID'] = ts('You must select a Job ID or date field to run this report.');
        }

        return $errors;
    }
    
    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess() {

        //CRM_Core_Error::debug_var('this', $this);

        require_once 'CRM/Utils/PDF/Utils.php';
        require_once 'api/api.php';

        //get form parameters and create sql criteria
        $formParams = $this->controller->exportValues( $this->_name );
        //CRM_Core_Error::debug_var('formParams', $formParams);

        $sqlParams = array();
        $sqlWhere  = 1;
        $startDate = $endDate = '';
        if ( $formParams['jobID'] ) {
            $sqlParams[] = "log_job_id = '{$formParams['jobID']}'";
        }
        if ( $formParams['start_date'] ) {
            $startDate = date( 'Y-m-d', strtotime($formParams['start_date']) );
            $sqlParams[] = "log_date >= '{$startDate} 00:00:00'";
        }
        if ( $formParams['end_date'] ) {
            $endDate = date( 'Y-m-d', strtotime($formParams['end_date']) );
            $sqlParams[] = "log_date <= '{$endDate} 23:59:59'";
        }
        $sqlWhere = implode(' ) AND ( ', $sqlParams);

        $bbconfig = get_bluebird_instance_config();
        $logDB    = $bbconfig['db.log.prefix'].$bbconfig['db.basename'];
        $civiDB   = $bbconfig['db.civicrm.prefix'].$bbconfig['db.basename'];

        $dateNow  = date('F jS Y h:i a');

        //begin construction of html
        $html  = self::_reportCSS();
        $html .= "<h2>SOS Proofing Report: $dateNow</h2>";

        if ( $startDate || $endDate ) {
          $dateRange = '';
          if ( $startDate && !$endDate ) {
            $dateRange = "$startDate &#8211; Now";
          } elseif ( !$startDate && $endDate ) {
            $dateRange = "Before $endDate";
          } else {
            $dateRange = "$startDate &#8211; $endDate";
          }
          $html .= "<h3>Date Range: $dateRange</h3>";
        }

        if ( $formParams['jobID'] ) {
          $html .= "<h3>Job ID: {$formParams['jobID']}</h3>";
        }

        $html .= "<table>
                    <tr>
                      <th>When</th>
                      <th>Altered Contact</th>
                      <th>Street and Mailing Address</th>
                      <th>Gender/DOB/Phone</th>
                      <th>Contact Email</th>
                      <th>Tag(s)</th>
                    </tr>";

        //get contacts with changes to either the contact object or tag
        CRM_Core_DAO::executeQuery("SET SESSION group_concat_max_len = 100000;");
        $query = "SELECT id, DATE_FORMAT(log_date, '%m/%d/%Y') as logDate, null as tagList
                  FROM {$logDB}.log_civicrm_contact
                  WHERE ( $sqlWhere )
                    AND log_action != 'Initialization'
                  UNION
                  SELECT et.entity_id, DATE_FORMAT(log_date, '%m/%d/%Y') as logDate, GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ')
                  FROM {$logDB}.log_civicrm_entity_tag et
                  JOIN {$civiDB}.civicrm_tag t
                    ON et.tag_id = t.id
                  WHERE ( $sqlWhere )
                    AND entity_table = 'civicrm_contact'
                    AND log_action != 'Initialization'
                  GROUP BY et.entity_id;";                  
        //CRM_Core_Error::debug_var('query',$query);
        $dao = CRM_Core_DAO::executeQuery($query);

        while ( $dao->fetch() ) {
            //CRM_Core_Error::debug_var('dao',$dao);
            $params = array( 'version' => 3,
                             'id'      => $dao->id,
                             );
            $cDetails = civicrm_api('contact','getsingle',$params);

            $html .= "<tr>
                        <td>{$dao->logDate}</td>
                        <td>{$cDetails['display_name']}</td>
                        <td>{$cDetails['street_address']}<br />
                            {$cDetails['supplemental_address_1']}<br />
                            {$cDetails['city']}, {$cDetails['state_name']} {$cDetails['postal_code']}
                            </td>
                        <td>{$cDetails['gender']}<br />
                            {$cDetails['date_of_birth']}<br />
                            {$cDetails['phone']}
                            </td>
                        <td>{$cDetails['email']}</td>
                        <td>{$dao->tagList}</td>
                      </tr>";
        }

        //add summary counts
        $html .= "<tr class='tableSummary'>
                    <td>Contact's Changed:</td>
                    <td colspan='5'>{$dao->N}</td>
                  </tr>";

        //close table
        $html .= "</table>";

        //now generate pdf
        $actionName = $this->controller->getButtonName( );
        if ( $actionName == '_qf_ProofingReport_next' ) { //PDF
            CRM_Utils_PDF_Utils::html2pdf( $html,
                                           'LogProofingReport.pdf',
                                           false,
                                           $formParams['pdf_format_id'] );
        } elseif ( $actionName == '_qf_ProofingReport_upload' ) { //Print
            echo $html;
        }
        
        CRM_Utils_System::civiExit( );

    }//postProcess

    //generate css
    function _reportCSS() {
      $css = "
<style type='text/css'>
<!--
h2, h3 {
    font-family: Arial, Helvetica, sans-serif;
    font-weight: normal;
}
h3 {
    font-weight: bold;
}
table {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
}
th {
    background-color: #CCCCCC;
    vertical-align: top;
    border-bottom: solid 1px #999999;
    border-right: solid 1px #999999;
    padding: 4px;
}
td {
    vertical-align: top;
    border-bottom: solid 1px #CCCCCC;
    border-right: solid 1px #CCCCCC;
    padding: 4px;
}
tr.tableSummary td {
    font-weight: bold;
    background-color: #CCCCCC;
}
-->
</style>
";

      return $css;
    }
}
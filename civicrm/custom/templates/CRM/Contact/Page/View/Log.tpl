{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div id="changeLog" class="view-content">
    <h3>{ts}Change Log:{/ts} {$displayName}</h3>{*NYSS*}
   {if $useLogging}
     <br />
     <div class='hiddenElement' id='instance_data'> </div>
   {else}
    <div class="form-item">
     {if $logCount > 0 }  	
       <table>
       <tr class="columnheader"><th>{ts}Changed By{/ts}</th><th>{ts}Change Date{/ts}</th><th>Description</th></tr>{*NYSS*}
       {foreach from=$log item=row}
         <tr class="{cycle values="odd-row,even-row"}">
            <td> {$row.image}&nbsp;<a href="{crmURL p='civicrm/contact/view' q="action=view&reset=1&cid=`$row.id`"}">{$row.name}</a></td>
            <td>{$row.date|crmDate}</td>
            <td>{$row.description}</td>{*NYSS*}
         </tr>
       {/foreach}
       </table>
     {else}
     <div class="messages status">	
      <div class="icon inform-icon"></div> &nbsp;
      {ts}No modifications have been logged for this contact.{/ts}
     </div>	
     {/if}
    </div>
   {/if}
 </p>
</div>

{if $useLogging}
{literal}
  <script type="text/javascript">
  cj( document ).ready( function ( ) {
    var dataURL = {/literal}"{$instanceUrl}"{literal};
    //NYSS 6960
    cj.ajax({
      url: dataURL,
      beforeSend: function(){
        var procHtml = '<div id="contact-log-report_processing" class="dataTables_processing">Processing...</div>';
        cj('#instance_data').before(procHtml);
      },
      success: function( content ) {
        cj('#contact-log-report_processing').hide();
        cj('#instance_data').show( ).html( content );
      }
    });
  });

  //NYSS 5719
  cj('div#changeLog div#instance_data .report-pager .crm-pager-nav a').live("click", function(e) {
    cj.ajax({
      url: this.href + '&snippet=4&section=2',
      success: function( content ) {
        cj('div#changeLog div#instance_data').html(content);
      }
    });
    return false;
  });

  cj('input[name="PagerBottomButton"], input[name="PagerTopButton"]').live("click", function(e) {
    var crmpid  = (this.name == 'PagerBottomButton') ? cj('input[name="crmPID_B"]').val() : cj('input[name="crmPID"]').val();
    cj.ajax({
      url: cj('div#changeLog div#instance_data .report-pager .crm-pager-nav a:first').attr('href') + '&snippet=4&section=2&crmPID=' + crmpid,
      success: function( content ) {
        cj('div#changeLog div#instance_data').html(content);
      }
    });
    return false;
  });
</script>
{/literal}
{/if}

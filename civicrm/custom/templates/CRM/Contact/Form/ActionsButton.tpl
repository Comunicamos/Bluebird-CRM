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
{* this template is used for the dropdown menu of the "Actions" button on contacts. *}

<div id="crm-contact-actions-wrapper">
	<div id="crm-contact-actions-link"><span><div class="icon dropdown-icon"></div>{ts}Actions{/ts}</span></div>
		<div class="ac_results" id="crm-contact-actions-list">
			<div class="crm-contact-actions-list-inner">
			  <div class="crm-contact_activities-list">
			  {include file="CRM/Activity/Form/ActivityLinks.tpl" as_select=false}
			  </div>
			  
              <div class="crm-contact_print-list">
              <ul class="contact-print">
                  <li class="crm-contact-print">
                 		<a class="print" title="{ts}Printer-friendly view of this page.{/ts}" href='{crmURL p='civicrm/contact/view/print' q="reset=1&print=1&cid=$contactId"}' target="_blank">{*NYSS*}
                 		<span><div class="icon print-icon"></div>{ts}Print Summary{/ts}</span>
                 		</a>
                  </li>
                  <li>
                        <a class="vcard " title="{ts}vCard record for this contact.{/ts}" href="{crmURL p='civicrm/contact/view/vcard' q="reset=1&cid=$contactId"}"><span><div class="icon vcard-icon"></div>{ts}vCard{/ts}</span>
                        </a>
                  </li>
                 {if $dashboardURL }
                   <li class="crm-contact-dashboard">
                      <a href="{$dashboardURL}" class="dashboard " title="{ts}dashboard{/ts}">
                       	<span><div class="icon dashboard-icon"></div>{ts}Contact Dashboard{/ts}</span>
                       </a>
                   </li>
                 {/if}
                 {if !empty($userRecordUrl)}
                   <li class="crm-contact-user-record">
                      <a href="{$userRecordUrl}" class="user-record " title="{ts}User Record{/ts}">
                         <span><div class="icon user-record-icon"></div>{ts}User Record{/ts}</span>
                      </a>
                   </li>
                 {/if}
                 
                 {*NYSS 4715*}
                 {* Check for permissions to provide Restore and Delete Permanently buttons for contacts that are in the trash. *}
                 {if call_user_func(array('CRM_Core_Permission','check'), 'delete contacts')}
                     {assign var='deleteParams' value=$urlParams|cat:"&reset=1&delete=1&cid=$contactId"}
                     <li class="crm-delete-action crm-contact-delete-action">
                         <a href="{crmURL p='civicrm/contact/view/delete' q=$deleteParams}" class="delete" title="{ts}Delete{/ts}">
                         <span><div class="icon delete-icon"></div>{ts}Delete Contact{/ts}</span>
                         </a>
                     </li>
                 {/if}
                 
			  </ul>
			  </div>
			  <div class="crm-contact_actions-list">
			  <ul class="contact-actions">
			  	{foreach from=$actionsMenuList.moreActions item='row'}
					{if $row.href}
					<li class="crm-action-{$row.ref}">
						<a href="{$row.href}&cid={$contactId}" title="{$row.title}">{$row.title}</a>
					</li>
					{/if}
				{/foreach}
              </ul>
              </div>
			  
			  
			  <div class="clear"></div>
			</div>
		</div>
	</div>
{literal}
<script type="text/javascript">

cj('body').click(function() {
    cj('#crm-contact-actions-list').hide();
});
	
cj('#crm-contact-actions-list').click(function(event){
    event.stopPropagation();
});

cj('#crm-contact-actions-list li').hover(
	function(){ cj(this).addClass('ac_over');},
	function(){ cj(this).removeClass('ac_over');}
);

cj('#crm-contact-actions-link').click(function(event) {
	cj('#crm-contact-actions-list').toggle();
	event.stopPropagation();
});

</script>
{/literal}

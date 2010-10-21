{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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
<div class="crm-accordion-wrapper crm-demographics-accordion crm-accordion-closed">
 <div class="crm-accordion-header">
  <div class="icon crm-accordion-pointer"></div> 
	{$title} 
  </div><!-- /.crm-accordion-header -->
  <div id="demographics" class="crm-accordion-body">
  
  <div class="leftColumn">
  <div class="form-item">
        <span class="labels">{$form.gender_id.label}</span>
        <span class="fields">
        {$form.gender_id.html|crmInsert:onclick:'showOtherGender()'}
        <span class="crm-clear-link">(<a href="#" title="unselect" onclick="unselectRadio('gender_id', '{$form.formName}'); return false;">{ts}clear{/ts}</a>)</span>
        </span>
  </div>
  <div id="showOtherGender" class="form-item" style="display:none;">
        {if $customId}{assign var='custom_45' value=custom_45_`$customId`}
        {else}{assign var='custom_45' value='custom_45_-1'}{/if}
        <span class="labels">{$form.$custom_45.label}</span>
        <span class="fields">{$form.$custom_45.html}</span>
  </div>
  <div class="form-item">
        <span class="labels">{$form.birth_date.label}</span>
        <span class="fields">{include file="CRM/common/jcalendar.tpl" elementName=birth_date}</span>
  </div>
  <div class="form-item">
       <span class="labels">{$form.is_deceased.label}</span>
       <span class="fields">{$form.is_deceased.html}</span>
  </div>
  <div id="showDeceasedDate" class="form-item">
       <span class="labels">{$form.deceased_date.label}</span>
       <span class="fields">{include file="CRM/common/jcalendar.tpl" elementName=deceased_date}</span>
  </div> 
  </div>
  
  <div class="rightColumn">
  <div class="form-item">
        {if $customId}{assign var='custom_58' value=custom_58_`$customId`}
        {else}{assign var='custom_58' value='custom_58_-1'}{/if}
        <span class="labels">{$form.$custom_58.label}</span>
        <span class="fields">{$form.$custom_58.html}</span>
  </div>
  <div class="form-item">
        {if $customId}{assign var='custom_62' value=custom_62_`$customId`}
        {else}{assign var='custom_62' value='custom_62_-1'}{/if}
        <span class="labels">{$form.$custom_62.label}</span>
        <span class="fields">{$form.$custom_62.html}</span>
  </div>
  </div>
  
  <div class="clear"></div>
  
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{literal}
<script type="text/javascript">
    showDeceasedDate( );    
    function showDeceasedDate( )
    {
        if (document.getElementsByName("is_deceased")[0].checked) {
      	    show('showDeceasedDate');
        } else {
	    hide('showDeceasedDate');
        }
    }
	showOtherGender( );    
    function showOtherGender( )
    {
        var x=document.getElementsByName("gender_id");
  		if (x[2].checked){
      	    show('showOtherGender');
        } else {
	    	hide('showOtherGender');
        }
    }
</script>
{/literal}
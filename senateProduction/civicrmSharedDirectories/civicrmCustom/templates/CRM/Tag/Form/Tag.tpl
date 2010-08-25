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
{* this template is used for adding/editing tags  *}
<style>
.hit {ldelim}padding-left:10px;{rdelim}
.tree li {ldelim}padding-left:10px;{rdelim}
#Tag .tree .collapsable .hit {ldelim}background:url('{$config->resourceBase}/i/menu-expanded.png') no-repeat left 8px;padding-left: 9px;cursor:pointer{rdelim}
#Tag .tree .expandable .hit {ldelim}background:url('{$config->resourceBase}/i/menu-collapsed.png') no-repeat left 6px;padding-left: 9px;cursor:pointer{rdelim}
#Tag #tagtree .highlighted {ldelim}background-color:lightgrey;{rdelim}
</style>
<script type="text/javascript">

options = {ldelim} ajaxURL:"{crmURL p='civicrm/ajax/rest' h=0}"
       ,closetxt:'<div class="icon close-icon"></div>'
      {rdelim} 
entityID={$entityID};
entityTable='{$entityTable}';
{literal}
function hideStatus( ) {
    cj( '#restmsg' ).hide( );
}
cj(document).ready(function(){initTagTree()});

function initTagTree() {
    //unobsctructive elements are there to provide the function to those not having javascript, no need for the others
    cj(".unobstructive").hide();
    cj("#tagtree").treeview({
        animated: "fast",
        collapsed: true,
        unique: true
    });
    cj("#tagtree ul input:checked").each (function(){
        cj(this).parents("li").children(".hit").addClass('highlighted');
    });
    cj("#tagtree input").change(function(){
        tagid = this.id.replace("check_", "");

        //get current tags from Summary and convert to array
        var tagLabels = cj.trim( cj("#tags").text( ) );
        if ( tagLabels ) {
            var tagsArray = tagLabels.split(',');
        } else{
            var tagsArray = new Array();
        }

        //get current tag label
        var currentTagLabel = cj("#tagLabel_" + tagid ).text( );
        if (this.checked) {
            //civiREST ('entity_tag','add',{entity_table:entityTable,entity_id:entityID,tag_id:tagid},image);
            cj().crmAPI ('entity_tag','add',{entity_table:entityTable,entity_id:entityID,tag_id:tagid},options);
            // add check to tab label array
            tagsArray.push( currentTagLabel );
        } else {
            cj().crmAPI ('entity_tag','remove',{entity_table:entityTable,entity_id:entityID,tag_id:tagid},options);
            // build array of tag labels
            tagsArray = cj.map(tagsArray, function (a) { 
                 if ( cj.trim( a ) != currentTagLabel ) {
                     return cj.trim( a );
                 }
             });
        }
		//showing count of tags in summary tab
		cj( '.ui-tabs-nav #tab_tag a' ).html( 'Tags <em>' + cj("#tagtree input:checkbox:checked").length + '</em>');
        //update summary tab 
        tagLabels = tagsArray.join(', ');
        cj("#tags").html( tagLabels );
        ( tagLabels ) ? cj("#tagLink,#tags").show( ) : cj("#tagLink,#tags").hide( );
    });
    
    {/literal}
    {if $permission neq 'edit'}
    {literal}
        cj("#tagtree input").attr('disabled', true);
    {/literal}
    {/if}
    {literal}
    
};
{/literal}
</script>
<a name="#saved"></a>
<span id="restmsg" style="display:none"></span>
<div id="Tag" class="view-content">
<h3>{if !$hideContext}{ts}Tags{/ts}{/if}</h3>
    <p>
    {if $action eq 16}
        {if $permission EQ 'edit'}
            {capture assign=crmURL}{crmURL p='civicrm/contact/view/tag' q='action=update'}{/capture}
            <span class="unobstructive">{ts 1=$displayName 2=$crmURL}Current tags for <strong>%1</strong> are highlighted. You can add or remove tags from <a href='%2'>Edit Tags</a>.{/ts}</span>
        {else}
            {ts}Current tags are highlighted.{/ts}
        {/if}
    {else}
        {if !$hideContext} 
        {ts}Mark or unmark the checkboxes, <span class="unobstructive">and click 'Update Tags' to modify tags.</span>{/ts}
	{/if}
    {/if}
    </p>

<!--<pre>{$tree|@print_r}</pre>-->
    <ul id="tagtree" class="tree">
        {foreach from=$tree item="node" key="id"}
        <li id="tag_{$id}" class="tagset">
            {if ! $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
            {if $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
            <label for="check_{$id}x" {if $node.children}{*class="hit"*}{/if} id="tagLabel_{$id}">{$node.name}</label>
            {if $node.children}
            <ul>
                {foreach from=$node.children item="subnode" key="subid"}
                    <li id="tag_{$subid}">
                        <input id="check_{$subid}" name="tagList[{$subid}]" type="checkbox" {if $tagged[$subid]}checked="checked"{/if}/>
                        <label for="check_{$subid}" {if $subnode.children}class="hit"{/if} id="tagLabel_{$subid}">{$subnode.name}</label>
                        {if $subnode.children}
                        <ul>
                            {foreach from=$subnode.children item="subsubnode" key="subsubid"}
                                <li id="tag_{$subsubid}">
                                    <input id="check_{$subsubid}" name="tagList[{$subsubid}]" type="checkbox" {if $tagged[$subsubid]}checked="checked"{/if}/>
                                    <label for="check_{$subsubid}" id="tagLabel_{$subsubid}">{$subsubnode.name}</label>
                                    
                                    {*LCD Extend to level 4*}
                                    {if $subsubnode.children}
                        			<ul>
                            			{foreach from=$subsubnode.children item="subsubsubnode" key="subsubsubid"}
                                		<li id="tag_{$subsubsubid}">value
                                    	<input id="check_{$subsubsubid}" name="tagList[{$subsubsubid}]" type="checkbox" {if $tagged[$subsubsubid]}checked="checked"{/if}/>
                                    	<label for="check_{$subsubsubid}" id="tagLabel_{$subsubsubid}">{$subsubsubnode.name}</label>
                                		</li>
                            			{/foreach} 
                        			</ul>
                        			{/if}
                                    {*LCD end*}
                                    
                                </li>
                            {/foreach} 
                        </ul>
                        {/if}
                    </li>	 
                {/foreach} 
            </ul>
            {/if}
        </li>	 
        {/foreach}
        <li>
        <div class="action-link">
    <a href="#saved" class="button" id="tag-refresh-button">
        <span>
            <div class="icon refresh-icon"></div>
            Refresh Tags
            </span>
    </a>
</div>
        </li> 
    </ul>
   
      {*foreach from=$tag item="row" key="id"}

        <div class="form-item" id="rowidtag_{$id}">
         {$form.tagList[$id].html} &nbsp;<label for="tag_{$id}">{$row}</label>
        </div>

      {/foreach*}

    {* Show Edit Tags link if in View mode *}
    {if $permission EQ 'edit' AND $action eq 16}
        </fieldset>
        <div class="action-link unobstructive">
          <a accesskey="N" href="{crmURL p='civicrm/contact/view/tag' q='action=update'}" class="button"><span><div class="icon edit-icon"></div>{ts}Edit Tags{/ts}</span></a>
        </div>
    {else}
       <div class="form-item unobstructive">{$form.buttons.html}</div>
       </fieldset>
    {/if}

    {include file="CRM/common/Tag.tpl"}
</div>

{if $action eq 1 or $action eq 2 }
 <script type="text/javascript">
 {* this function is called to change the color of selected row(s) *}
    var fname = "{$form.formName}";	
    on_load_init_check(fname);
 </script>
{/if}

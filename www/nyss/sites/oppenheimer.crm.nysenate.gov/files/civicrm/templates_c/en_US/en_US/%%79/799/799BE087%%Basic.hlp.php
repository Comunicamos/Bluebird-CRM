<?php /* Smarty version 2.6.26, created on 2010-08-16 12:12:46
         compiled from CRM/Contact/Form/Search/Basic.hlp */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'htxt', 'CRM/Contact/Form/Search/Basic.hlp', 26, false),array('block', 'ts', 'CRM/Contact/Form/Search/Basic.hlp', 28, false),array('modifier', 'crmMoney', 'CRM/Contact/Form/Search/Basic.hlp', 27, false),array('function', 'crmURL', 'CRM/Contact/Form/Search/Basic.hlp', 35, false),)), $this); ?>
<?php $this->_tag_stack[] = array('htxt', array('id' => "id-basic-criteria")); $_block_repeat=true;smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
    <?php ob_start(); ?><?php echo ((is_array($_tmp=500)) ? $this->_run_mod_handler('crmMoney', true, $_tmp) : smarty_modifier_crmMoney($_tmp)); ?>
<?php $this->_smarty_vars['capture']['default'] = ob_get_contents();  $this->assign('amt', ob_get_contents());ob_end_clean(); ?>
    <p><?php $this->_tag_stack[] = array('ts', array()); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Find contacts by partial or full name or email, type of contact, group, and/or tag. You can then view or edit contact details, print a contact list, or use the <strong>- more actions -</strong> drop down list to assign tags, add contacts to groups, create smart groups, export contact data to a spreadsheet, and more.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></p>
    <p><?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['amt'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Click <strong>Advanced Search</strong> to search by address, demographics, custom fields, notes, and / or relationships. <?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></p>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>

<?php $this->_tag_stack[] = array('htxt', array('id' => "id-smog-criteria")); $_block_repeat=true;smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
    <p><?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['group']['title'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Contacts in the <strong>%1</strong> group are listed below. Use 'Find Contacts within this Group' to search for specific contacts. Use the 'Group Status...' checkboxes to view contacts with 'Pending' status and/or contacts who have been 'Removed' from this group.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></p>
    <?php if ($this->_tpl_vars['permissionedForGroup']): ?>
        <?php ob_start(); ?><?php echo CRM_Utils_System::crmURL(array('q' => "context=amtg&amtgID=".($this->_tpl_vars['group']['id'])."&reset=1"), $this);?>
<?php $this->_smarty_vars['capture']['default'] = ob_get_contents();  $this->assign('addMembersURL', ob_get_contents());ob_end_clean(); ?>
        <p><?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['addMembersURL'],'2' => $this->_tpl_vars['group']['title'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Click <a href='%1'>Add Contacts to %2</a> if you want to add contacts to this group.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        <?php if ($this->_tpl_vars['ssID']): ?>
            <?php if ($this->_tpl_vars['ssMappingID']): ?>
                <?php ob_start(); ?><?php echo CRM_Utils_System::crmURL(array('p' => "civicrm/contact/search/builder",'q' => "reset=1&force=1&ssID=".($this->_tpl_vars['ssID'])), $this);?>
<?php $this->_smarty_vars['capture']['default'] = ob_get_contents();  $this->assign('editSmartGroupURL', ob_get_contents());ob_end_clean(); ?>
            <?php else: ?>
                <?php ob_start(); ?><?php echo CRM_Utils_System::crmURL(array('p' => "civicrm/contact/search/advanced",'q' => "reset=1&force=1&ssID=".($this->_tpl_vars['ssID'])), $this);?>
<?php $this->_smarty_vars['capture']['default'] = ob_get_contents();  $this->assign('editSmartGroupURL', ob_get_contents());ob_end_clean(); ?>
            <?php endif; ?> 
            <?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['editSmartGroupURL'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Click <a href='%1'>Edit Smart Group Search Criteria...</a> to change the search query used for this 'smart' group.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
        <?php endif; ?>
        </p>
    <?php endif; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>

<?php $this->_tag_stack[] = array('htxt', array('id' => "id-amtg-criteria")); $_block_repeat=true;smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
    <?php $this->_tag_stack[] = array('ts', array('1' => $this->_tpl_vars['group']['title'])); $_block_repeat=true;smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Use this Search form to find contacts. Mark the contacts you want to add to this group. Then click 'Add Contacts to %1'.<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_ts($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_htxt($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php /* Smarty version 2.6.18, created on 2008-07-09 11:09:54
         compiled from register.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'default', 'register.tpl', 14, false),array('modifier', 'escape', 'register.tpl', 14, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'inc/header.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<h1>Register server</h1>

<p>Register a server which is gonna act as an identity client.</p>

<form method="post">

    <fieldset>
	<legend>About You</legend>
	
	<p>
	    <label for="requester_name">Your name</label><br/>
	    <input class="text" id="requester_name"  name="requester_name" type="text" value="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['consumer']['requester_name'])) ? $this->_run_mod_handler('default', true, $_tmp, @$_REQUEST['requester_name']) : smarty_modifier_default($_tmp, @$_REQUEST['requester_name'])))) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" />
	</p>
	
	<p>
	    <label for="requester_email">Your email address</label><br/>
	    <input class="text" id="requester_email"  name="requester_email" type="text" value="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['consumer']['requester_email'])) ? $this->_run_mod_handler('default', true, $_tmp, @$_REQUEST['requester_email']) : smarty_modifier_default($_tmp, @$_REQUEST['requester_email'])))) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" />
	</p>
    </fieldset>
    
    <fieldset>
	<legend>Location Of Your Application Or Site</legend>
	
	<p>
	    <label for="application_uri">URL of your application or site</label><br/>
	    <input id="application_uri" class="text" name="application_uri" type="text" value="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['consumer']['application_uri'])) ? $this->_run_mod_handler('default', true, $_tmp, @$_REQUEST['application_uri']) : smarty_modifier_default($_tmp, @$_REQUEST['application_uri'])))) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" />
	</p>
	
	<p>
	    <label for="callback_uri">Callback URL</label><br/>
	    <input id="callback_uri" class="text" name="callback_uri" type="text" value="<?php echo ((is_array($_tmp=((is_array($_tmp=@$this->_tpl_vars['consumer']['callback_uri'])) ? $this->_run_mod_handler('default', true, $_tmp, @$_REQUEST['callback_uri']) : smarty_modifier_default($_tmp, @$_REQUEST['callback_uri'])))) ? $this->_run_mod_handler('escape', true, $_tmp) : smarty_modifier_escape($_tmp)); ?>
" />
	</p>
    </fieldset>

    <br />
    <input type="submit" value="Register server" />
</form>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'inc/footer.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
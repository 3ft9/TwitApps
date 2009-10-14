<?php /* Smarty version 2.6.18, created on 2008-07-09 11:09:41
         compiled from index.tpl */ ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'inc/header.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>

<h1>OAuth server</h1>
Go to:

<ul>
  <li><a href="/logon">Logon</a></li>
  <li><a href="/register">Register your consumer</a></li>
</ul>

Afterwards, make an OAuth test request to <strong>http://<?php echo $_SERVER['name']; ?>
/hello</strong> to test your connection.</p>

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => 'inc/footer.tpl', 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
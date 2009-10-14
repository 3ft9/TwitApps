<?php if (!empty($user)) { ?>
<div style="float:right;">
	@<a href="/account/"><?php echo htmlentities($user['screen_name']); ?></a>
	<strong>&middot;</strong>
	<a href="/account/signin">Sign out</a>
</div>
<?php } ?>

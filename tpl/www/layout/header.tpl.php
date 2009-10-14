<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php if (!empty($title)) { echo $title.' | '; } ?>TwitApps</title>
		<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.5.2/build/reset-fonts-grids/reset-fonts-grids.css" />
		<link rel="stylesheet" href="http://static.twitapps.com/style.css" type="text/css" />
		<style type="text/css">@import url('http://s3.amazonaws.com/getsatisfaction.com/feedback/feedback.css');</style>
	</head>
	<body><div id="mydoc">
		<div id="h">
			<div id="menu">
				<a href="/" class="nb<?php if ($section == 'home') { echo ' current'; } ?>">home</a>
				<a href="/replies/" class="<?php if ($section == 'replies') { echo 'current'; } ?>">replies</a>
				<a href="/follows/" class="<?php if ($section == 'follows') { echo 'current'; } ?>">follows</a>
				<a href="/account/" class="<?php if ($section == 'account') { echo 'current'; } ?>">account</a>
			</div>
			<div id="sitename"><a href="http://twitapps.com/"><span style="color:#51cafb;">Twit</span>Apps</a></div>
		</div>
		<div id="b">

#!/usr/local/bin/php
<?php
	ob_start('EmailOutput');
	function EmailOutput($str)
	{
		if (strlen(trim($str)) > 0)
		{
			mail('stuart@stut.net', 'Output from incomingmail.php', $str, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
		}
	}

	$data = file_get_contents('php://stdin');
	
	$msg = mailparse_msg_create();
	if (!mailparse_msg_parse($msg, $data))
	{
		mail('contact@twitapps.com', 'TwitApps incoming mail: Parse failed', $data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
	}
	else
	{
		$message = mailparse_msg_get_part($msg, 1);
		$info = mailparse_msg_get_part_data($message);
		
		if (!$message or !$info)
		{
			mail('contact@twitapps.com', 'TwitApps incoming mail: Failed to get message or info', $data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
		}
		else
		{
			ob_start();
			mailparse_msg_extract_part($message, $data);
			$body = ob_get_clean();
	        $body = urldecode($body);
	        $body = iconv($info['charset'], 'UTF-8', $body);
	        $body = html_entity_decode($body, ENT_NOQUOTES, 'UTF-8');
	
			//mail('contact@twitapps.com', $info['headers']['subject'], $body."\n\n========================================\n\n".$data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');

			switch (@$info['headers']['x-twitterrecipientscreenname'])
			{
				case 'ta_follows':
					require dirname(__FILE__).'/../follows/cli/incoming_mail.php';
					break;
	
				case 'ta_replies':
					require dirname(__FILE__).'/../replies/cli/incoming_mail.php';
					break;
	
				// Unhandled, forward the email on so it doesn't get lost
				default:
					mail('contact@twitapps.com', $info['headers']['subject'], $body."\n\n========================================\n\n".$data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
					break;
			}
		}
	}
	mailparse_msg_free($msg);

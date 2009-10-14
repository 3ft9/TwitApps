-- MySQL dump 10.11
--
-- Host: localhost    Database: twitapps
-- ------------------------------------------------------
-- Server version	5.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `service` varchar(20) NOT NULL,
  `user_id` int(20) unsigned NOT NULL,
  `stamp` int(10) unsigned NOT NULL,
  `type` varchar(20) NOT NULL default 'notice',
  `message` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `byservice` (`service`),
  KEY `byserviceanduser` (`service`,`user_id`),
  KEY `byuser` (`user_id`),
  KEY `serviceandtype` (`service`,`type`),
  KEY `userandtype` (`user_id`,`type`),
  KEY `serviceuserandtype` (`service`,`user_id`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=13541 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `follows_followers`
--

DROP TABLE IF EXISTS `follows_followers`;
CREATE TABLE `follows_followers` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `user_id` int(20) unsigned NOT NULL,
  `follower_id` int(20) unsigned NOT NULL,
  `started_at` int(10) unsigned NOT NULL,
  `stopped_at` int(10) unsigned NOT NULL,
  `last_seen_at` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `user_follower` (`user_id`,`follower_id`),
  KEY `byuser` (`user_id`),
  KEY `user_started` (`user_id`,`started_at`),
  KEY `user_stopped` (`user_id`,`stopped_at`),
  KEY `user_started_stopped` (`user_id`,`started_at`,`stopped_at`),
  KEY `user_last_seen` (`user_id`,`last_seen_at`)
) ENGINE=InnoDB AUTO_INCREMENT=82278 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `follows_users`
--

DROP TABLE IF EXISTS `follows_users`;
CREATE TABLE `follows_users` (
  `id` int(20) NOT NULL,
  `email` varchar(250) NOT NULL,
  `status` varchar(20) default NULL,
  `last_run_at` int(11) unsigned NOT NULL default '0',
  `next_run_at` int(11) unsigned NOT NULL default '0',
  `frequency` varchar(20) NOT NULL default 'daily',
  `hour` tinyint(3) unsigned NOT NULL,
  `when` varchar(20) NOT NULL,
  `last_email_at` int(11) unsigned NOT NULL,
  `next_email_at` int(11) unsigned NOT NULL default '0',
  `registered_at` int(11) unsigned NOT NULL default '0',
  `post_url` text NOT NULL,
  `post_format` varchar(20) NOT NULL default 'json',
  `processor_pid` int(11) unsigned NOT NULL default '0',
  `updated_at` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `replies_queue`
--

DROP TABLE IF EXISTS `replies_queue`;
CREATE TABLE `replies_queue` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `recipient_id` int(20) unsigned NOT NULL,
  `tweet_id` int(20) unsigned NOT NULL,
  `sender_id` int(20) unsigned NOT NULL,
  `received_at` int(11) unsigned NOT NULL,
  `data` text NOT NULL,
  `emailed_at` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `tweet_to_user` (`recipient_id`,`tweet_id`),
  KEY `recipient` (`recipient_id`),
  KEY `recipient_due` (`recipient_id`,`emailed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11659 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `replies_users`
--

DROP TABLE IF EXISTS `replies_users`;
CREATE TABLE `replies_users` (
  `id` int(20) NOT NULL,
  `email` varchar(250) NOT NULL,
  `status` varchar(20) NOT NULL default 'inactive',
  `last_run_at` int(11) unsigned NOT NULL default '0',
  `last_email_at` int(11) unsigned NOT NULL default '0',
  `next_email_at` int(11) unsigned NOT NULL default '0',
  `last_id` int(20) unsigned NOT NULL default '0',
  `registered_at` int(11) unsigned NOT NULL default '0',
  `processor_pid` int(11) unsigned NOT NULL default '0',
  `ignore_self` tinyint(3) unsigned NOT NULL default '0',
  `replies_only` tinyint(3) unsigned NOT NULL default '0',
  `min_interval` int(10) unsigned NOT NULL default '3600',
  `max_queued` int(10) unsigned NOT NULL default '25',
  `updated_at` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `processor_pid` (`processor_pid`),
  KEY `get_next` (`status`,`last_run_at`,`last_email_at`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(20) unsigned NOT NULL,
  `screen_name` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` varchar(200) NOT NULL,
  `location` varchar(200) NOT NULL,
  `url` text NOT NULL,
  `utc_offset` tinyint(4) NOT NULL default '0',
  `time_zone` varchar(50) NOT NULL,
  `statuses_count` int(11) unsigned NOT NULL default '0',
  `followers_count` int(11) unsigned NOT NULL default '0',
  `friends_count` int(11) unsigned NOT NULL default '0',
  `favourites_count` int(11) unsigned NOT NULL default '0',
  `created_at` int(11) unsigned NOT NULL default '0',
  `protected` tinyint(4) NOT NULL default '0',
  `status` text NOT NULL,
  `profile_image_url` text NOT NULL,
  `profile_sidebar_fill_color` varchar(20) NOT NULL,
  `profile_sidebar_border_color` varchar(20) NOT NULL,
  `profile_background_tile` tinyint(4) NOT NULL default '0',
  `profile_background_color` varchar(20) NOT NULL,
  `profile_text_color` varchar(20) NOT NULL,
  `profile_background_image_url` text NOT NULL,
  `profile_link_color` varchar(20) NOT NULL,
  `updated_at` int(11) unsigned NOT NULL,
  `registered_at` int(11) unsigned NOT NULL,
  `oauth_token` varchar(100) default NULL,
  `oauth_token_secret` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2009-10-14 18:58:11

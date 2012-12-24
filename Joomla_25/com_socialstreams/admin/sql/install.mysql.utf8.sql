-- ----------------------------------------
-- Create the Tables for com_socialstreams
-- ----------------------------------------

DROP TABLE IF EXISTS `#__ss_auth`;
-- ----------------------------------------
-- Holds the OAuth access data for each network
-- network: the name of the network
-- profile_id: FK to the profile associated with this Auth
-- access_token: serialized OAuth access token data
-- message: last message from the OAuth server
-- state: preserved random string state variable
-- ----------------------------------------
CREATE TABLE `#__ss_auth`(
    `id` int(11) unsigned NOT NULL auto_increment,
    `network` VARCHAR(64) NOT NULL ,
    `clientid` VARCHAR(64) NOT NULL DEFAULT '',
    `profile_id` INT(11) NOT NULL DEFAULT '0',
    `access_token` VARCHAR(255) default '',
    `access_token_secret` VARCHAR(255) default '',
    `message` VARCHAR(255) default '',
    `state` TINYINT(3) NOT NULL DEFAULT '0',
    `expires` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `params` VARCHAR(1024) NULL ,
    `checked_out` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `checked_out_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',	
    `created` datetime NOT NULL default '0000-00-00 00:00:00',
    `created_by` int(10) unsigned NOT NULL default '0',
    `created_by_alias` varchar(255) NOT NULL default '',
    `modified` datetime NOT NULL default '0000-00-00 00:00:00',
    `modified_by` int(10) unsigned NOT NULL default '0',
    PRIMARY KEY  (`id`),
    INDEX `idx_auth_clientid` (`clientid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__ss_profiles`;
 
CREATE TABLE `#__ss_profiles` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `network` varchar(64) NOT NULL,
    `networkid` VARCHAR(255) NOT NULL DEFAULT '',
    `client_id` INT(11) NOT NULL,
    `user` varchar(64) NOT NULL,
    `name` varchar(256) NOT NULL,
    `image` varchar(256) NOT NULL,
    `url` varchar(256) NOT NULL,
    `profile` TEXT NOT NULL,
    `expires` datetime NOT NULL default '0000-00-00 00:00:00',
    `created` datetime NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY  (`id`),
    INDEX `idx_profile_network` (`network`),
    INDEX `idx_profile_user` (`user`),
    INDEX `idx_profile_networkid` (`networkid`),
    INDEX `idx_profile_client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__ss_items`;
 
CREATE TABLE `#__ss_items` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `network` varchar(64) NOT NULL,
    `profile_id` INT(11) NOT NULL,
    `networkid` VARCHAR(255) NOT NULL DEFAULT '',
    `client_id` INT(11) NOT NULL,
    `published` datetime NOT NULL default '0000-00-00 00:00:00',
    `modified` datetime NOT NULL default '0000-00-00 00:00:00',
    `item` TEXT NOT NULL,
    `expires` datetime NOT NULL default '0000-00-00 00:00:00',
    `created` datetime NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY  (`id`),
    INDEX `idx_item_network` (`network`),
    INDEX `idx_item_profileid` (`profile_id`),
    INDEX `idx_item_networkid` (`networkid`),
    INDEX `idx_item_client_id` (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__ss_item_meta`;
 
CREATE TABLE `#__ss_item_meta` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `type` varchar(256) NOT NULL,
    `network` varchar(64) NOT NULL,
    `networkid` varchar(64) DEFAULT NULL,
    `published` datetime default '0000-00-00 00:00:00',
    `meta` TEXT NOT NULL,
    `created` datetime NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY  (`id`),
    INDEX `idx_meta_type` (`type`),
    INDEX `idx_meta_network` (`network`),
    INDEX `idx_meta_networkid` (`networkid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__ss_connections`;
 
CREATE TABLE `#__ss_connections` (
    `id` int(11) unsigned NOT NULL auto_increment,
    `item_id` INT(11) DEFAULT NULL,
    `meta_id` INT(11) DEFAULT NULL,
    `user_id` INT(11) DEFAULT NULL,
    `connection` TEXT DEFAULT NULL,
    `created` datetime NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY  (`id`),
    INDEX `idx_connect_itemid` (`item_id`),
    INDEX `idx_connect_userid` (`user_id`),
    INDEX `idx_connect_metaid` (`meta_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
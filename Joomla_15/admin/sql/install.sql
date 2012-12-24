DROP TABLE IF EXISTS `#__socialstreammentions`;
 
CREATE TABLE `#__socialstreammentions` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `network` varchar(25) NOT NULL,
  `articleid` INT NULL,
  `url` varchar(256) NOT NULL,
  `count` INT NULL,
  `date` INT NOT NULL,
  `lastupdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__socialstreamprofiles`;
 
CREATE TABLE `#__socialstreamprofiles` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `network` varchar(64) NOT NULL,
  `user` varchar(64) NOT NULL,
  `profile` TEXT NOT NULL,
  `expires` INT NULL,
  `lastupdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `#__socialstreamitems`;
 
CREATE TABLE `#__socialstreamitems` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `network` varchar(64) NOT NULL,
  `networkid` varchar(64) NOT NULL,
  `user` varchar(64) NOT NULL,
  `date` INT NOT NULL,
  `item` TEXT NOT NULL,
  `expires` INT NULL,
  `lastupdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
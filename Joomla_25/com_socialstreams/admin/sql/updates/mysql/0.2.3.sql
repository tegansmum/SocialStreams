-- -----------------------------------------------------
-- Version 0.2.3 updates
-- -----------------------------------------------------
ALTER TABLE `#__ss_auth` ADD COLUMN `expires` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ;

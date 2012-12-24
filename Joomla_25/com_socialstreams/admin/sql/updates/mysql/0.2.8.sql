-- -----------------------------------------------------
-- Version 0.2.8 updates
-- -----------------------------------------------------
-- Add column to record the social networks internal id for the profile
ALTER TABLE `#__ss_profiles` ADD `networkid` VARCHAR(255) NOT NULL DEFAULT '';
CREATE INDEX `idx_networkid` ON `#__ss_profiles`(`networkid`);
-- Change the name of the userid column to user
ALTER TABLE `#__ss_profiles` CHANGE COLUMN `userid` `user` VARCHAR(64) NOT NULL DEFAULT '';
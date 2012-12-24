-- -----------------------------------------------------
-- Version 0.3.0 updates
-- -----------------------------------------------------
-- Add column to record the social networks authenticated client id for the profile
ALTER TABLE `#__ss_profiles` ADD `client_id` INT(11) NOT NULL;
CREATE INDEX `idx_clientid` ON `#__ss_profiles`(`client_id`);
-- Add column to record the social networks authenticated client id for the profile
ALTER TABLE `#__ss_items` ADD `client_id` INT(11) NOT NULL;
CREATE INDEX `idx_clientid` ON `#__ss_items`(`client_id`);
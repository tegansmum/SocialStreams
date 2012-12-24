-- -----------------------------------------------------
-- Version 0.2.9 updates
-- -----------------------------------------------------
-- Change the name of the itemid column to networkid
ALTER TABLE `#__ss_items` CHANGE COLUMN `itemid` `networkid` VARCHAR(255) NOT NULL DEFAULT '';
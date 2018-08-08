ALTER TABLE `icargo_chargebee_subscription` ADD `plan_type` ENUM('SAMEDAY','LAST_MILE') NOT NULL AFTER `update_date`, ADD `allowed_shipment` BIGINT NOT NULL DEFAULT '0' AFTER `plan_type`;


ALTER TABLE `icargo_chargebee_subscription` CHANGE `auto_collection` `auto_collection` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

ALTER TABLE `icargo_chargebee_subscription` CHANGE `invoice_immediately` `invoice_immediately` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'false';

ALTER TABLE `icargo_chargebee_subscription` CHANGE `payment_status` `payment_status` VARCHAR(35) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `icargo_billing_addresses` ( `id` INT NOT NULL AUTO_INCREMENT , `user_id` INT NOT NULL , `name` VARCHAR(250) NOT NULL , `company` VARCHAR(250) NOT NULL , `address_one` VARCHAR(250) NOT NULL , `address_two` VARCHAR(250) NOT NULL , `city` VARCHAR(50) NOT NULL , `state` VARCHAR(50) NOT NULL , `zip` VARCHAR(20) NOT NULL , `country` VARCHAR(5) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `icargo_user_cards` ( `id` INT NOT NULL AUTO_INCREMENT , `user_id` INT NOT NULL , `holder_name` VARCHAR(250) NOT NULL , `card_number` VARCHAR(20) NOT NULL , `expiry_month` VARCHAR(2) NOT NULL , `expiry_year` VARCHAR(4) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;


ALTER TABLE `icargo_chargebee_plan` ADD `plan_type` ENUM('SAME_DAY','LAST_MILE') NOT NULL AFTER `update_date`, ADD `shipment_limit` BIGINT NOT NULL AFTER `plan_type`;

ALTER TABLE `icargo_chargebee_plan` DROP `cahrgebee_subscription_id`;

ALTER TABLE `icargo_chargebee_plan`
  DROP `controller_count`,
  DROP `driver_count`,
  DROP `warehouse_count`;

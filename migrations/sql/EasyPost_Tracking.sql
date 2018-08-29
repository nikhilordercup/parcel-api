CREATE TABLE `icargo_tracking_detail` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `object` VARCHAR(100) NULL DEFAULT NULL,
  `message` VARCHAR(255) NULL DEFAULT NULL,
  `description` VARCHAR(45) NULL DEFAULT NULL,
  `status` VARCHAR(55) NULL DEFAULT NULL,
  `carrier_code` VARCHAR(45) NOT NULL,
  `city` VARCHAR(45) NULL DEFAULT NULL,
  `state` VARCHAR(45) NULL DEFAULT NULL,
  `country` VARCHAR(45) NULL DEFAULT NULL,
  `zip` VARCHAR(45) NULL DEFAULT NULL,
  `tracking_id` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`));


CREATE TABLE `icargo_tracking_carrier_detail` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `object` VARCHAR(100) NULL DEFAULT NULL,
  `service` VARCHAR(100) NULL DEFAULT NULL,
  `container_type` VARCHAR(100) NULL DEFAULT NULL,
  `est_delivery_date_local` DATE NULL DEFAULT '1970-01-01',
  `est_delivery_time_local` TIME NULL DEFAULT '00:00:00',
  `origin_location` VARCHAR(100) NULL DEFAULT NULL,
  `origin_tracking_location_city` VARCHAR(100) NULL DEFAULT NULL,
  `origin_tracking_location_state` VARCHAR(100) NULL DEFAULT NULL,
  `origin_tracking_location_country` VARCHAR(55) NULL DEFAULT NULL,
  `origin_tracking_location_zip` VARCHAR(45) NULL DEFAULT NULL,
  `destination_location` VARCHAR(100) NULL DEFAULT NULL,
  `destination_tracking_location_city` VARCHAR(100) NULL DEFAULT NULL,
  `destination_tracking_location_state` VARCHAR(100) NULL DEFAULT NULL,
  `destination_tracking_location_country` VARCHAR(55) NULL DEFAULT NULL,
  `destination_tracking_location_zip` VARCHAR(45) NULL DEFAULT NULL,
  `guaranteed_delivery_date` DATETIME NULL DEFAULT '1970-01-01 00:00:00',
  `alternate_identifier` VARCHAR(100) NULL DEFAULT NULL,
  `initial_delivery_attempt` DATETIME NULL DEFAULT '1970-01-01 00:00:00',
  `tracking_id` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`));

ALTER TABLE `icargo_tracking_detail` 
ADD COLUMN `status_detail` TEXT NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `icargo_tracking_detail` 
ADD COLUMN `datetime` VARCHAR(45) NULL DEFAULT NULL AFTER `tracking_id`;

ALTER TABLE `icargo_tracking_detail` 
ADD COLUMN `source` VARCHAR(45) NULL DEFAULT NULL AFTER `datetime`;

ALTER TABLE `icargo_tracking_detail` 
ADD COLUMN `origin` VARCHAR(45) NULL DEFAULT NULL AFTER `source`;

ALTER TABLE `icargo_tracking_carrier_detail` 
CHANGE COLUMN `origin_tracking_location_city` `origin_location_city` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `origin_tracking_location_state` `origin_location_state` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `origin_tracking_location_country` `origin_location_country` VARCHAR(55) NULL DEFAULT NULL ,
CHANGE COLUMN `origin_tracking_location_zip` `origin_location_zip` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `destination_tracking_location_city` `destination_location_city` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `destination_tracking_location_state` `destination_location_state` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `destination_tracking_location_country` `destination_location_country` VARCHAR(55) NULL DEFAULT NULL ,
CHANGE COLUMN `destination_tracking_location_zip` `destination_location_zip` VARCHAR(45) NULL DEFAULT NULL ;

ALTER TABLE `icargo_tracking_carrier_detail` 
CHANGE COLUMN `est_delivery_date_local` `est_delivery_date_local` VARCHAR(100) NULL DEFAULT NULL ,
CHANGE COLUMN `est_delivery_time_local` `est_delivery_time_local` VARCHAR(100) NULL DEFAULT NULL ;

ALTER TABLE `icargo_tracking_carrier_detail` 
CHANGE COLUMN `guaranteed_delivery_date` `guaranteed_delivery_date` VARCHAR(100) NULL DEFAULT NULL ;

ALTER TABLE `icargo_tracking_carrier_detail` 
CHANGE COLUMN `initial_delivery_attempt` `initial_delivery_attempt` VARCHAR(100) NULL DEFAULT NULL ;

ALTER TABLE `icargo_tracking_carrier_detail` 
ADD COLUMN `origin` VARCHAR(45) NULL DEFAULT NULL AFTER `tracking_id`;

ALTER TABLE `icargo_shipment_tracking` 
ADD COLUMN `tracking_id` VARCHAR(100) NULL DEFAULT NULL AFTER `create_date`,
ADD COLUMN `object` VARCHAR(100) NULL DEFAULT NULL AFTER `tracking_id`,
ADD COLUMN `mode` VARCHAR(45) NULL DEFAULT NULL AFTER `object`,
ADD COLUMN `tracking_code` VARCHAR(100) NULL DEFAULT NULL AFTER `mode`,
ADD COLUMN `status_detail` VARCHAR(100) NULL DEFAULT NULL AFTER `tracking_code`,
ADD COLUMN `created_at` VARCHAR(100) NULL DEFAULT NULL AFTER `status_detail`,
ADD COLUMN `updated_at` VARCHAR(100) NULL DEFAULT NULL AFTER `created_at`,
ADD COLUMN `signed_by` VARCHAR(100) NULL DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `weight` VARCHAR(45) NULL DEFAULT NULL AFTER `signed_by`,
ADD COLUMN `est_delivery_date` VARCHAR(100) NULL DEFAULT NULL AFTER `weight`,
ADD COLUMN `carrier` VARCHAR(100) NULL DEFAULT NULL AFTER `est_delivery_date`,
ADD COLUMN `finalized` TINYINT(1) NULL DEFAULT 0 AFTER `carrier`,
ADD COLUMN `is_return` VARCHAR(45) NULL DEFAULT NULL AFTER `finalized`,
ADD COLUMN `public_url` VARCHAR(100) NULL DEFAULT NULL AFTER `is_return`,
ADD COLUMN `user_id` VARCHAR(100) NULL DEFAULT NULL AFTER `public_url`,
ADD COLUMN `event_id` VARCHAR(100) NULL DEFAULT NULL AFTER `user_id`;

ALTER TABLE `icargo_shipment_tracking` 
ADD COLUMN `origin` VARCHAR(45) NULL DEFAULT NULL AFTER `event_id`;

ALTER TABLE `icargo_shipment_tracking` 
ADD COLUMN `api_string` TEXT NULL DEFAULT NULL AFTER `origin`;

ALTER TABLE `icargo_shipment_tracking` 
CHANGE COLUMN `origin` `origin` VARCHAR(45) NULL DEFAULT 'local' ;

INSERT INTO `icargo_tracking_code` (`tracking_id`, `tracking_code`) VALUES ('IN_TRANSIT', 'IN_TRANSIT');

INSERT INTO `icargo_shipments_master` (`name`, `code`, `icon`, `description`, `is_used_for_tracking`) VALUES ('In Transit', 'IN_TRANSIT', 'IN_TRANSIT', 'INFO', 'YES');

ALTER TABLE `icargo_release1_11`.`icargo_tracking_code` 
ADD COLUMN `is_cancel` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO' AFTER `tracking_code`;
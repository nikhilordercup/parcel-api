CREATE TABLE IF NOT EXISTS `icargo_service_options`
( `id` INT NOT NULL AUTO_INCREMENT , `service_id` INT NOT NULL , `residential` BOOLEAN NOT NULL ,
 `am_delivery` BOOLEAN NOT NULL , `saturday_delivery` BOOLEAN NOT NULL , `duitable` BOOLEAN NOT NULL ,
 `hold_at_location` BOOLEAN NOT NULL , `holiday_delivery` BOOLEAN NOT NULL , `length` VARCHAR(20) NOT NULL ,
 `width` VARCHAR(20) NOT NULL , `height` VARCHAR(20) NOT NULL , `dimension_unit` VARCHAR(20) NOT NULL ,
  `girth` BOOLEAN NOT NULL , `service_type` VARCHAR(250) NOT NULL , `service_level` VARCHAR(250) NOT NULL ,
  `barcode_value` VARCHAR(250) NOT NULL , `max_waiting_time` VARCHAR(250) NOT NULL ,
  `time_unit` VARCHAR(20) NOT NULL , `change_from_base` BOOLEAN NOT NULL , `min_weigth` VARCHAR(20) NOT NULL ,
  `max_weight` VARCHAR(20) NOT NULL , `min_box_weight` VARCHAR(20) NOT NULL ,
  `max_box_weight` VARCHAR(20) NOT NULL , `weight_unit` VARCHAR(20) NOT NULL ,
  `max_box_count` VARCHAR(20) NOT NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  `updated_at` TIMESTAMP NOT NULL , `status` BOOLEAN NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;

  ALTER TABLE `icargo_service_options` ADD `min_transit_days`
  INT NOT NULL AFTER `max_box_count`,
  ADD `max_transit_days` INT NOT NULL AFTER `min_transit_days`,
  ADD `service_time` VARCHAR(50) NOT NULL AFTER `max_transit_days`;

  ALTER TABLE `icargo_service_options` ADD `weight_per` VARCHAR(15) NOT NULL AFTER `service_time`;

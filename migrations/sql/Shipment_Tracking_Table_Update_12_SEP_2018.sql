ALTER TABLE `icargo_shipment_tracking` 
ADD COLUMN `load_type` VARCHAR(45) NULL DEFAULT NULL AFTER `api_string`,
ADD COLUMN `service_type` VARCHAR(15) NULL DEFAULT NULL AFTER `load_type`;

ALTER TABLE `icargo_shipment_tracking` ADD `custom_tracking` TINYINT(1) NOT NULL DEFAULT '0' AFTER `service_type`;
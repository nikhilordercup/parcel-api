ALTER TABLE `icargo_shipment_service` ADD `tracking_code` VARCHAR(45) NULL DEFAULT NULL AFTER `status`;
ALTER TABLE `icargo_shipment_service` CHANGE `tracking_code` `tracking_code` VARCHAR(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'INFO_RECEIVED';

ALTER TABLE `icargo_quote_service`
ADD COLUMN `email` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Quotation email id' AFTER `expiry_date`;

ALTER TABLE `icargo_quote_service`
ADD COLUMN `booking_type` VARCHAR(20) NOT NULL DEFAULT 'sameday' AFTER `email`;

ALTER TABLE `icargo_quote_shipment`
ADD COLUMN `json_string` TEXT NULL DEFAULT NULL AFTER `warehouse_id`;

ALTER TABLE `icargo_quote_service`
ADD COLUMN `warehouse_id` INT NULL DEFAULT 0 AFTER `email`,
ADD COLUMN `company_id` INT NULL DEFAULT 0 AFTER `warehouse_id`;

ALTER TABLE `icargo_quote_service` CHANGE `service_opted` `service_opted` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
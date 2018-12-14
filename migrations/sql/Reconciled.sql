CREATE TABLE `icargo_reconciled_reports` (
`id` INT NOT NULL,
`processed_date` DATE NULL DEFAULT '1970-01-01',
`total_requested_shipment` INT(11) NULL DEFAULT 0,
`total_eligible_shipment` INT(11) NULL DEFAULT 0,
  `apply_with_account` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO',
  `requested_csv_path` VARCHAR(255) NULL DEFAULT NULL,
  `responded_csv_path` VARCHAR(255) NULL DEFAULT NULL,
  `carrier` INT(11) NULL DEFAULT 0,
  `status` INT(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`));
  
ALTER TABLE `icargo_courier_vs_surcharge` 
ADD COLUMN `reconciled_code` VARCHAR(255) NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `icargo_courier_vs_services` 
ADD COLUMN `reconciled_code` VARCHAR(255) NULL DEFAULT NULL AFTER `flow_type`;

ALTER TABLE `icargo_reconciled_reports` 
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT ;

ALTER TABLE `icargo_shipment_price` 
ADD COLUMN `reconciled_code` VARCHAR(255) NULL DEFAULT NULL AFTER `inputjson`;

ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `reconciled_code` VARCHAR(255) NULL DEFAULT NULL AFTER `create_date`;

ALTER TABLE `icargo_shipment_price` 
CHANGE COLUMN `version_reason` `version_reason` ENUM('NA', 'CARRIER_PRICE_UPDATE', 'CUSTOMER_PRICE_UPDATE', 'RECONCILED') NOT NULL DEFAULT 'NA' ;

ALTER TABLE `icargo_shipment_service` 
CHANGE COLUMN `version_reason` `version_reason` ENUM('NA', 'CARRIER_PRICE_UPDATE', 'CUSTOMER_PRICE_UPDATE', 'RECONCILED') NOT NULL DEFAULT 'NA' ;

ALTER TABLE `icargo_courier_vs_company` 
ADD COLUMN `fual_surcharge` FLOAT(10,2) NULL DEFAULT 0.00 AFTER `cancelation_charge`;

ALTER TABLE `icargo_reconciled_reports` 
ADD COLUMN `company_id` INT(11) NULL DEFAULT 0 AFTER `status`;

ALTER TABLE `icargo_configuration` 
ADD COLUMN `reconciled_buffer_amt` FLOAT(10,2) NULL DEFAULT 0.00 AFTER `url`;

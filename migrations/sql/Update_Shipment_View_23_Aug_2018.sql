ALTER TABLE `icargo_shipment_service` 
CHANGE COLUMN `label_tracking_number` `label_tracking_number` BIGINT(20) NULL DEFAULT 00000 ;

ALTER TABLE `icargo_shipment_service` 
CHANGE COLUMN `label_files_png` `label_files_png` VARCHAR(255) NULL DEFAULT NULL, CHANGE COLUMN `label_file_pdf` `label_file_pdf` VARCHAR(255) NULL DEFAULT NULL, CHANGE COLUMN `label_json` `label_json` TEXT NULL DEFAULT NULL COMMENT 'label json response string from carrier', CHANGE COLUMN `is_label_printed` `is_label_printed` VARCHAR(1) NULL DEFAULT NULL COMMENT 'Y for yes,N for No';

ALTER TABLE `icargo_shipment_service` CHANGE `status` `status` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL COMMENT'initially next day booking status is pending. After successful response from core-prime-label it will be confirm.';
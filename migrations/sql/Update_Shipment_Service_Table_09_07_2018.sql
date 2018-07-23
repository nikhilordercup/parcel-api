ALTER TABLE `icargo_shipment_service` ADD `label_json` TEXT NOT NULL COMMENT 'label json response string from carrier' AFTER `label_file_pdf`;

ALTER TABLE `icargo_shipment_service` ADD `label_tracking_number` INT NOT NULL AFTER `status`, ADD `label_files_png` VARCHAR(255) NOT NULL AFTER `label_tracking_number`, ADD `label_file_pdf` VARCHAR(255) NOT NULL AFTER `label_files_png`, ADD `is_label_printed` VARCHAR(1) NOT NULL COMMENT 'Y for yes,N for No'AFTER `label_file_pdf`; 

ALTER TABLE `icargo_shipment_service` CHANGE `label_tracking_number` `label_tracking_number` BIGINT(20) NOT NULL; 

ALTER TABLE `icargo_shipment_service` ADD `is_insured` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 for yes 0 for no' AFTER `is_label_printed`; 

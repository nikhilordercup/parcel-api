ALTER TABLE `icargo_customer_info` ADD `auto_label_print` VARCHAR(1) NOT NULL DEFAULT 'Y' COMMENT 'Y for yes,N for No' AFTER `charge_from_base`; 

ALTER TABLE `icargo_customer_info` CHANGE `auto_label_print` `auto_label_print` VARCHAR(3) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'YES'; 
ALTER TABLE `icargo_package_service` ADD `company_id` INT NOT NULL AFTER `id`; 

ALTER TABLE `icargo_package_service` ADD `carrier_code` VARCHAR(55) NOT NULL AFTER `company_id`; 
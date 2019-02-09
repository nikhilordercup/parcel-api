ALTER TABLE `icargo_package_type` ADD `customer_user_id` INT(11) NOT NULL AFTER `created_by`, ADD `allowed_user` INT(5) NOT NULL AFTER `customer_user_id`; 

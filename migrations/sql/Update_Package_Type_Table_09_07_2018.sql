ALTER TABLE `icargo_package_type` CHANGE `carrier` `carrier` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `icargo_package_type` ADD `carrier` VARCHAR(25) NOT NULL AFTER `is_internal`; 
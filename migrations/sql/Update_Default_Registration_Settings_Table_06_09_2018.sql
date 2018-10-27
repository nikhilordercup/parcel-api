UPDATE `icargo_default_registration_settings` SET `setup_order` = '4' WHERE `icargo_default_registration_settings`.`id` = 5;
UPDATE `icargo_default_registration_settings` SET `setup_order` = '5' WHERE `icargo_default_registration_settings`.`id` = 2;
UPDATE `icargo_default_registration_settings` SET `setup_order` = '2' WHERE `icargo_default_registration_settings`.`id` = 4;
UPDATE `icargo_default_registration_settings` SET `setup_order` = '3' WHERE `icargo_default_registration_settings`.`id` = 3;
UPDATE `icargo_default_registration_settings` SET `status` = '0' WHERE `icargo_default_registration_settings`.`id` = 2;
UPDATE `icargo_default_registration_settings` SET `description` = 'Please initiate the setup by creating a new warehouse for the company, you need to enter a unique email address.' WHERE `icargo_default_registration_settings`.`id` = 1;
UPDATE `icargo_default_registration_settings` SET `description` = 'Please create a driver, assign vehicle to the driver. Select warehouse from the dropdown. You need to enter an unique email address.' WHERE `icargo_default_registration_settings`.`id` = 3;
UPDATE `icargo_default_registration_settings` SET `description` = 'Please create a vehicle that need to be assign to a driver.' WHERE `icargo_default_registration_settings`.`id` = 4;
UPDATE `icargo_default_registration_settings` SET `description` = 'Create your first custom route, you can add a combination of cities, localities or postcodes to create your route.' WHERE `icargo_default_registration_settings`.`id` = 5;
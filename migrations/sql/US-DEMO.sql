ALTER TABLE `icargo_countries` 
ADD COLUMN `regex` VARCHAR(255) NULL DEFAULT NULL AFTER `job_type`;
UPDATE `icargo_countries` SET `regex`='/^[0-9]{5}(?:-[0-9]{4})?$/i'  WHERE `alpha2_code` = 'US'
UPDATE  `icargo_countries` SET `regex`='/^[a-z]{1,2}[0-9][a-z0-9]?\\s?[0-9][a-z]{2}$/i' WHERE `alpha2_code`='GB';

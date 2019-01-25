ALTER TABLE `icargo_countries` ADD `google_api_country_code` VARCHAR(2) NULL DEFAULT NULL AFTER `job_type`;

/*
run following query when google_api_country_code has no value for any country, if there is data in the google_api_country_code column do not execute following query.
UPDATE icargo_countries SET google_api_country_code = alpha2_code
*/
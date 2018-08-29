/*Country related changes*/

ALTER TABLE  `icargo_countries` ADD  `weight_dutiable_limit` DOUBLE NOT NULL DEFAULT  '0' AFTER  `currency_code` ,
ADD  `paperless_trade` TINYINT NOT NULL DEFAULT  '0' COMMENT  '1= paper less trade support, 0=non support' AFTER  `weight_dutiable_limit` ,
ADD  `postal_type` TINYINT NOT NULL DEFAULT  '1' COMMENT  '1=Postal Type, 0 = City' AFTER  `paperless_trade` ;

ALTER TABLE  `icargo_countries` ADD  `job_type` TINYINT( 4 ) NOT NULL DEFAULT  '1' COMMENT  '1 = Collection & Delivery, 2 = Collection, 3 = Delivery' AFTER  `postal_type` ;

/******** Icargo Non-Dutiable country **********/

CREATE TABLE `icargo_country_non_duitable` (
  `id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  `nonduty_id` int(11) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `updated` DATETIME NULL 
);

ALTER TABLE `icargo_country_non_duitable`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `icargo_country_non_duitable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE icargo_country_non_duitable ADD CONSTRAINT nonduty_country_id UNIQUE (country_id, nonduty_id);

ALTER TABLE `icargo_shipment_service` ADD `reason_for_export` VARCHAR(100) NULL DEFAULT NULL COMMENT 'used only for dutiable shipments' AFTER `is_insured`, ADD `tax_status` VARCHAR(100) NULL DEFAULT NULL COMMENT 'used only for dutiable shipments' AFTER `reason_for_export`, ADD `terms_of_trade` VARCHAR(20) NULL DEFAULT NULL COMMENT 'used only for dutiable shipments' AFTER `tax_status`;

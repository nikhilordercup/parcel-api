CREATE TABLE `icargo_tax_details` ( `id` INT NOT NULL AUTO_INCREMENT ,
`country_id` INT NOT NULL , `tax_type` VARCHAR(20) NOT NULL ,
 `tax_factor` VARCHAR(20) NOT NULL , `tax_factor_value` VARCHAR(20) NOT NULL ,
  `create_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
   `updated_at` TIMESTAMP NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;
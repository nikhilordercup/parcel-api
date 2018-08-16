CREATE TABLE IF NOT EXISTS  `icargo_dev`.`icargo_company_carrier_config` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`company_id` INT NOT NULL ,
 `carrier_id` INT NOT NULL , 
`configuration` TEXT NOT NULL ,
 `status` BIT(1) NOT NULL ,
 `last_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
 PRIMARY KEY (`id`))
 ENGINE = InnoDB;
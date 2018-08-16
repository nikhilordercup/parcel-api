CREATE TABLE IF NOT EXISTS `icargo_carrier_service` 
( `id` INT NOT NULL AUTO_INCREMENT ,
 `carrier_id` INT NOT NULL , 
`provider_id` INT NOT NULL , 
PRIMARY KEY (`id`))
 ENGINE = InnoDB;
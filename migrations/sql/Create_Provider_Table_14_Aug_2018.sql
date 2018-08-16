CREATE TABLE IF NOT EXISTS `icargo_provider_services` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`provider_name` VARCHAR(250) NOT NULL ,
 `provider_logo` VARCHAR(250) NULL ,
 `status` BIT(1) NOT NULL DEFAULT b'0' , 
`provider_configuration` TEXT NOT NULL , 
PRIMARY KEY (`id`)) 
ENGINE = InnoDB;
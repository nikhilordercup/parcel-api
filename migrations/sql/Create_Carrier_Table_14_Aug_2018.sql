CREATE TABLE IF NOT EXISTS `icargo_carriers` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`carrier_name` VARCHAR(250) NOT NULL ,
 `carrier_logo` VARCHAR(250) NULL ,
 `status` BIT(1) NOT NULL DEFAULT b'0' , 
`carrier_configuration` TEXT NOT NULL , 
PRIMARY KEY (`id`)) 
ENGINE = InnoDB;
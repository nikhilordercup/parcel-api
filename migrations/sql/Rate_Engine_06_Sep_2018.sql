CREATE TABLE `icargo_rate_types` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`name` VARCHAR(250) NOT NULL , 
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
 `modifed_at` TIMESTAMP NULL ,
 PRIMARY KEY (`id`)) ENGINE = MyISAM;


CREATE TABLE `icargo_currency` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`name` VARCHAR(250) NOT NULL , 
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`modifed_at` TIMESTAMP NULL ,
`company_id` INT NOT NULL,
 PRIMARY KEY (`id`)) ENGINE = MyISAM;


CREATE TABLE `icargo_zone_info` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`name` VARCHAR(250) NOT NULL , 
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`modifed_at` TIMESTAMP NULL ,
`carrier_id` INT NOT NULL,
`company_id` INT NOT NULL,
 PRIMARY KEY (`id`)) ENGINE = MyISAM;


CREATE TABLE `icargo_rate_info` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`carrier_id` INT NOT NULL , 
`service_id` INT NOT NULL , 
`rate_type_id` INT NOT NULL , 
`from_zone_id` INT NOT NULL , 
`to_zone_id` INT NOT NULL ,
`start_unit` VARCHAR(250) NOT NULL , 
`end_unit` VARCHAR(250) NOT NULL ,
`rate` VARCHAR(20) NOT NULL, 
`additional_cost` VARCHAR(250) NOT NULL , 
`additional_base_unit` VARCHAR(250) NOT NULL , 
`rate_unit_id` INT NOT NULL,
`company_id` INT NOT NULL,
PRIMARY KEY (`id`)) ENGINE = MyISAM;


CREATE TABLE `icargo_zone_details` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`zone_id` INT NOT NULL ,
`company_id` INT NOT NULL, 
`city` VARCHAR(250) NOT NULL , 
`post_code` VARCHAR(250) NOT NULL , 
`country` VARCHAR(250) NOT NULL , 
`level` ENUM('Post Code','City','Country') NULL , 
`flow_type` ENUM('Domastic','International') NULL , 
`volume_base` VARCHAR(250) NOT NULL , 
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
`modified_at` TIMESTAMP NULL , 
PRIMARY KEY (`id`)) ENGINE = MyISAM;


CREATE TABLE `icargo_rate_units` 
( `id` INT NOT NULL AUTO_INCREMENT , 
`name` VARCHAR(250) NOT NULL ,
`abb` VARCHAR(20) NOT NULL , 
`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
 `modifed_at` TIMESTAMP NULL ,
 PRIMARY KEY (`id`)) ENGINE = MyISAM;




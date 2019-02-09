CREATE TABLE `icargo_email_config`
( `id` INT NOT NULL AUTO_INCREMENT , `mail_type` VARCHAR(250) NOT NULL ,
 `company_id` INT NOT NULL DEFAULT '0' , `is_smtp` ENUM('Yes','No') NOT NULL ,
  `host` TEXT NULL , `username` VARCHAR(250) NULL , `password` VARCHAR(250) NULL ,
  `ssl_type` ENUM('SSL','TLS') NULL , `port` INT NULL , `from_name` TEXT NOT NULL ,
  `from_email` TEXT NOT NULL , `mail_content_type` ENUM('Html','Text') NOT NULL ,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active' , `description` TEXT NULL ,
   PRIMARY KEY (`id`)) ENGINE = MyISAM;
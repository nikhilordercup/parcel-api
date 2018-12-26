ALTER TABLE `icargo_quote_service` 
CHANGE COLUMN `service_opted` `service_opted` LONGTEXT NOT NULL ,
CHANGE COLUMN `service_request_string` `service_request_string` LONGTEXT NOT NULL ,
CHANGE COLUMN `service_response_string` `service_response_string` LONGTEXT NOT NULL ;

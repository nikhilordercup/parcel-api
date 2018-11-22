CREATE TABLE `icargo_accountbalancehistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT '0',
  `customer_type` enum('PREPAID','POSTPAID','NONE') NOT NULL DEFAULT 'NONE',
  `company_id` int(11) DEFAULT '0',
  `payment_type` enum('CREDIT','DEBIT','NONE') DEFAULT 'NONE',
  `pre_balance` float(10,2) NOT NULL DEFAULT '0.00',
  `amount` float(10,2) NOT NULL DEFAULT '0.00',
  `balance` float(10,2) NOT NULL DEFAULT '0.00',
  `create_date` datetime DEFAULT '1970-01-01 00:00:00',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_desc` varchar(255) DEFAULT NULL,
  `payment_for` enum('RECHARGE','BOOKSHIP','PAYINVOICE','VOUCHER','PRICECHANGE','CANCELSHIP','NONE') NOT NULL DEFAULT 'NONE',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=latin1;

ALTER TABLE `icargo_customer_info` 
ADD COLUMN `tax_exempt` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO' AFTER `charge_from_base`;

ALTER TABLE `icargo_customer_info` 
CHANGE COLUMN `charge_from_base` `charge_from_base` ENUM('YES', 'NO') NOT NULL DEFAULT 'YES' ;


ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `is_hold` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO' AFTER `status`;

ALTER TABLE `icargo_configuration` 
ADD COLUMN `logo` TEXT NULL DEFAULT NULL AFTER `voucher_end_number`;


ALTER TABLE `icargo_vouchers` 
CHANGE COLUMN `amount` `total` FLOAT(10,2) NOT NULL DEFAULT '0.00' ,
ADD COLUMN `base_amount` FLOAT(10,2) NOT NULL DEFAULT 0.00 AFTER `invoice_reference`,
ADD COLUMN `surcharge_total` FLOAT(10,2) NOT NULL DEFAULT 0.00 AFTER `base_amount`,
ADD COLUMN `fual_surcharge` FLOAT(10,2) NOT NULL DEFAULT 0.00 AFTER `surcharge_total`,
ADD COLUMN `tax` FLOAT(10,2) NOT NULL DEFAULT 0.00 AFTER `fual_surcharge`;



ALTER TABLE `icargo_vouchers` 
CHANGE COLUMN `base_amount` `base_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `surcharge_total` `surcharge_total` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `fual_surcharge` `fual_surcharge` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `tax` `tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ;


ALTER TABLE `icargo_invoice_vs_docket` 
CHANGE COLUMN `base_amount` `base_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `surcharge_total` `surcharge_total` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `fual_surcharge` `fual_surcharge` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `total` `total` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ,
CHANGE COLUMN `tax` `tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' ;

ALTER TABLE `icargo_invoices` 
ADD COLUMN `voucher_data` TEXT NULL DEFAULT NULL AFTER `invoice_status`;

ALTER TABLE `icargo_invoices` 
ADD COLUMN `incoice_pdf` VARCHAR(255) NULL AFTER `voucher_data`;

ALTER TABLE `icargo_courier_vs_company` 
ADD COLUMN `cancelation_charge` FLOAT(10,2) NOT NULL DEFAULT 0.00 AFTER `is_master`;

CREATE TABLE `icargo_recurring_jobs` (
  `job_id` INT NOT NULL AUTO_INCREMENT,
  `load_identity` VARCHAR(255) NOT NULL,
  `company_id` INT(11) NOT NULL DEFAULT 0,
  `customer_id` INT(11) NOT NULL DEFAULT 0,
  `load_type` ENUM('SAME', 'NEXT', 'NONE') NOT NULL DEFAULT 'NONE',
  `company_carrier_id` INT(11) NOT NULL DEFAULT 0,
  `company_service_id` INT(11) NOT NULL DEFAULT 0,
  `last_booking_date` DATE NOT NULL DEFAULT '1970-01-01',
  `last_booking_time` TIME NOT NULL DEFAULT '00:00:00',
  `last_booking_reference` VARCHAR(255) NOT NULL,
  `recurring_type` ENUM('DAILY', 'ONCE', 'WEEKLY', 'MONTHLY', 'NONE') NOT NULL DEFAULT 'NONE',
  `recurring_date` DATE GENERATED ALWAYS AS ('1970-01-01'),
  `recurring_day` ENUM('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'NONE') NOT NULL DEFAULT 'NONE',
  `recurring_time` TIME NOT NULL DEFAULT '00:00:00',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`job_id`));

ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `is_recurring` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO' AFTER `is_hold`;


ALTER TABLE `icargo_customer_info` 
ADD COLUMN `webapi_token` LONGTEXT NULL DEFAULT NULL AFTER `tax_exempt`;


ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `booked_service_id` INT(11) NOT NULL DEFAULT 0 AFTER `is_recurring`;

ALTER TABLE `icargo_recurring_jobs` 
ADD COLUMN `recurring_month_date` INT(2) NOT NULL DEFAULT 00 AFTER `status`;


ALTER TABLE `icargo_recurring_jobs` 
CHANGE COLUMN `last_booking_date` `last_booking_date` DATE NULL DEFAULT '1970-01-01' ,
CHANGE COLUMN `last_booking_time` `last_booking_time` TIME NULL DEFAULT '00:00:00' ,
CHANGE COLUMN `last_booking_reference` `last_booking_reference` VARCHAR(255) NULL ;


ALTER TABLE `icargo_recurring_jobs` 
CHANGE COLUMN `status` `status` ENUM('true', 'false') NOT NULL DEFAULT 'true' ;

ALTER TABLE `icargo_recurring_jobs` 
CHANGE COLUMN `recurring_month_date` `recurring_month_date` INT(2) ZEROFILL NOT NULL DEFAULT '0' ;

CREATE TABLE `icargo_customer_tokens` (
  `token_id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `create_date` VARCHAR(45) NULL,
  `status` TINYINT(2) NOT NULL DEFAULT 1,
  `token` TEXT NOT NULL,
  PRIMARY KEY (`token_id`));


ALTER TABLE `icargo_customer_tokens` 
ADD COLUMN `url` VARCHAR(255) NULL DEFAULT NULL AFTER `token`;


CREATE TABLE `icargo_webapi_request_response` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(100) NOT NULL,
  `request` TEXT NULL,
  `response` TEXT NULL,
  `status` TINYINT(2) NULL DEFAULT 1,
  `create_date` DATE NOT NULL DEFAULT '1970-01-01',
  `create_time` TIME NULL DEFAULT '00:0000',
  PRIMARY KEY (`id`));


ALTER TABLE `icargo_webapi_request_response` 
ADD COLUMN `request_status` VARCHAR(45) NOT NULL DEFAULT 'NC' AFTER `create_time`,
ADD COLUMN `end_point` VARCHAR(255) NULL DEFAULT NULL AFTER `request_status`;

ALTER TABLE `icargo_webapi_request_response` 
ADD COLUMN `webservice_req` TEXT NULL AFTER `end_point`;

ALTER TABLE `icargo_webapi_request_response` 
ADD COLUMN `webservice_res` TEXT NULL DEFAULT NULL AFTER `webservice_req`;


ALTER TABLE `icargo_recurring_jobs` 
CHANGE COLUMN `recurring_day` `recurring_day` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'NONE') NOT NULL DEFAULT 'NONE' ;

ALTER TABLE `icargo_recurring_jobs` 
CHANGE COLUMN `recurring_day` `recurring_day` ENUM('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN', 'NONE') NOT NULL DEFAULT 'NONE' ;

ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `accountkey` VARCHAR(255) NOT NULL AFTER `booked_service_id`;

ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `booked_by_recurring` ENUM('NO', 'YES') NOT NULL DEFAULT 'NO' AFTER `accountkey`;

ALTER TABLE `icargo_shipment` 
CHANGE COLUMN `current_status` `current_status` ENUM('C', 'O', 'H', 'S', 'D', 'Ca', 'Dis', 'Rit', 'Deleted', 'Cancel') NOT NULL DEFAULT 'C' COMMENT 'C = Current O = Operational H = History S = Saved D = Delivered,Ca= Carded,Dis = Disputed,Cancel=Cancel' ;

CREATE TABLE `icargo_api_request_response` (
  `id` INT NOT NULL DEFAULT 11,
  `token_id` INT(11) NOT NULL DEFAULT 0,
  `web_request` TEXT NULL,
  `web_responce` TEXT NULL,
  `create_date` DATE NULL,
  `status` INT(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`));

ALTER TABLE `icargo_api_request_response` 
CHANGE COLUMN `create_date` `create_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ;

ALTER TABLE `icargo_api_request_response` 
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT ;


ALTER TABLE `icargo_api_request_response` 
ADD COLUMN `requested_url` VARCHAR(255) NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `icargo_customer_tokens` 
CHANGE COLUMN `status` `status` VARCHAR(5) NOT NULL DEFAULT 'true' ;

ALTER TABLE `icargo_customer_tokens` 
CHANGE COLUMN `status` `status` INT(1) NOT NULL DEFAULT 1 ;

CREATE TABLE `icargo_invoice_payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `invoice_reference` VARCHAR(255) NOT NULL,
  `invoice_amt` FLOAT(10,2) NOT NULL DEFAULT 0.00,
  `paid_amt` FLOAT(10,2) NOT NULL DEFAULT 0.00,
  `paydate` DATE NOT NULL DEFAULT '1970-01-01',
  `customer_account` VARCHAR(255) NOT NULL,
  `last_invoice_status` VARCHAR(25) NOT NULL,
  `paymode` VARCHAR(25) NOT NULL,
  `payment_reference` VARCHAR(255) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`));

ALTER TABLE `icargo_shipment_service` 
CHANGE COLUMN `service_response_string` `service_response_string` TEXT NULL ,
ADD COLUMN `customer_type` ENUM('PREPAID', 'POSTPAID', 'NONE') NOT NULL DEFAULT 'NONE' AFTER `booked_by_recurring`,
ADD COLUMN `booked_api_token_id` INT(11) NULL DEFAULT 0 AFTER `customer_type`;

ALTER TABLE `icargo_shipments_master` 
ADD COLUMN `is_used_for_cancel` ENUM('YES', 'NO') NOT NULL DEFAULT 'NO' AFTER `tracking_internal_code`;

ALTER TABLE `icargo_shipment_service` 
ADD COLUMN `booked_quotation_ref` VARCHAR(255) NULL DEFAULT NULL AFTER `booked_api_token_id`,
ADD COLUMN `tracking_callbackurl` VARCHAR(255) NULL DEFAULT NULL AFTER `booked_quotation_ref`;

ALTER TABLE `icargo_accountbalancehistory` ADD `payment_provider` VARCHAR(255) NOT NULL AFTER `payment_for`;

ALTER TABLE `icargo_recurring_jobs` CHANGE `status` `status` ENUM('true','false','fail') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'true';


ALTER
 ALGORITHM = UNDEFINED
DEFINER=`app_stable`@`localhost` 
 SQL SECURITY DEFINER
 VIEW `icargo_shipments_view`
 AS select `S`.`warehouse_id` AS `warehouse_id`,`S`.`company_id` AS `company_id`,`S`.`instaDispatch_loadIdentity` AS `instaDispatch_loadIdentity`,`S`.`customer_id` AS `customer_id`,`SST`.`carrier` AS `carrier`,`SST`.`service_name` AS `service_name`,`S`.`instaDispatch_loadGroupTypeCode` AS `shipment_type`,`S`.`shipment_create_date` AS `booking_date`,`S`.`booked_by` AS `booked_by`,`SST`.`grand_total` AS `amount`,`SST`.`isInvoiced` AS `isInvoiced`,`SST`.`tracking_code` AS `tracking_code` from (`app_stable`.`icargo_shipment` `S` left join `app_stable`.`icargo_shipment_service` `SST` on((`SST`.`load_identity` = `S`.`instaDispatch_loadIdentity`))) where (((`S`.`current_status` = 'C') or (`S`.`current_status` = 'O') or (`S`.`current_status` = 'S') or (`S`.`current_status` = 'D') or (`S`.`current_status` = 'Ca') or (`S`.`current_status` = 'Cancel')) and ((`S`.`instaDispatch_loadGroupTypeCode` = 'SAME') or (`S`.`instaDispatch_loadGroupTypeCode` = 'NEXT'))) group by `S`.`instaDispatch_loadIdentity`;
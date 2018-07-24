
--
-- Table structure for table `icargo_pickups`
--

CREATE TABLE `icargo_pickups` (
  `id` int(11) NOT NULL,
  `carrier_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `company_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `address_line1` text NOT NULL,
  `address_line2` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `postal_code` varchar(50) NOT NULL,
  `address_type` varchar(10) NOT NULL,
  `package_quantity` int(5) NOT NULL,
  `package_type` varchar(20) NOT NULL COMMENT 'Package or Letter',
  `is_overweight` varchar(5) NOT NULL COMMENT 'Yes or No',
  `package_location` varchar(200) NOT NULL,
  `pickup_date` text NOT NULL,
  `earliest_pickup_time` varchar(20) NOT NULL,
  `latest_pickup_time` varchar(20) NOT NULL,
  `pickup_reference` varchar(200) NOT NULL,
  `instruction_todriver` text NOT NULL,
  `confirmation_number` varchar(20) NOT NULL COMMENT 'Pickup Confirmation Number coming from service',
  `charge` varchar(50) DEFAULT NULL COMMENT 'Charge coming from service',
  `currency_code` varchar(10) NOT NULL COMMENT 'Origin Service Area coming from service',
  `origin_service_area` varchar(50) DEFAULT NULL COMMENT 'Pickup Confirmation Number coming from service',
  `ready_time` varchar(20) NOT NULL COMMENT 'Ready Time coming from service',
  `next_date` varchar(50) DEFAULT NULL COMMENT 'Next Date coming from service',
  `second_time` varchar(50) DEFAULT NULL COMMENT 'Second Time coming from service',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Successfull, 0 = Pending',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_pickups`
--
ALTER TABLE `icargo_pickups`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_pickups`
--
ALTER TABLE `icargo_pickups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- Pickup Id for shipment
--
ALTER TABLE `icargo_shipment` ADD `pickup_id` INT(11) NULL DEFAULT '0' AFTER `is_internal`;
CREATE TABLE `icargo_customer_courier_child_accont` (
  `id` int(11) NOT NULL,
  `courier_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `parent_account_number` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `coreprime_token` varchar(255) DEFAULT NULL,
  `authentiaction_token` varchar(255) DEFAULT NULL,
  `authentication_token_created_at` varchar(255) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `collection_start_at` datetime NOT NULL,
  `collection_end_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL,
  `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_courier_child_accont`
--
ALTER TABLE `icargo_courier_child_accont`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_courier_child_accont`
--
ALTER TABLE `icargo_courier_child_accont`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
  
ALTER TABLE `icargo_customer_courier_child_accont` CHANGE `coreprime_token` `token` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;  

ALTER TABLE `icargo_customer_courier_child_accont` CHANGE `authentiaction_token` `authentication_token` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;  

ALTER TABLE `icargon`.`icargo_customer_courier_child_accont` ADD UNIQUE`unique` (`parent_account_number`, `customer_id`); 

ALTER TABLE `icargo_customer_courier_child_accont` CHANGE `currency` `currency` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'GBP'; 

ALTER TABLE `icargo_customer_courier_child_accont` CHANGE `status` `status` TINYINT(1) NOT NULL DEFAULT '1';   
DROP TABLE `icargo_shipment_tracking`;
CREATE TABLE `icargo_shipment_tracking` (
  `id` int(11) NOT NULL,
  `shipment_ticket` varchar(100) DEFAULT NULL,
  `load_identity` varchar(100) NOT NULL,
  `code` varchar(45) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tracking_id` varchar(100) DEFAULT NULL,
  `object` varchar(100) DEFAULT NULL,
  `mode` varchar(45) DEFAULT NULL,
  `tracking_code` varchar(100) DEFAULT NULL,
  `status_detail` varchar(100) DEFAULT NULL,
  `created_at` varchar(100) DEFAULT NULL,
  `updated_at` varchar(100) DEFAULT NULL,
  `signed_by` varchar(100) DEFAULT NULL,
  `weight` varchar(45) DEFAULT NULL,
  `est_delivery_date` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `finalized` tinyint(1) DEFAULT '0',
  `is_return` varchar(45) DEFAULT NULL,
  `public_url` varchar(100) DEFAULT NULL,
  `user_id` varchar(100) DEFAULT NULL,
  `event_id` varchar(100) DEFAULT NULL,
  `origin` varchar(45) DEFAULT 'local',
  `api_string` text,
  `load_type` varchar(45) DEFAULT NULL,
  `service_type` varchar(15) DEFAULT NULL,
  `custom_tracking` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_shipment_tracking`
--
ALTER TABLE `icargo_shipment_tracking`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_shipment_tracking`
--
ALTER TABLE `icargo_shipment_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
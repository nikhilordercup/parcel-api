--
-- Table structure for table `icargo_shipment_items`
--

CREATE TABLE `icargo_shipment_items` (
  `id` int(11) NOT NULL,
  `load_identity` varchar(50) NOT NULL COMMENT 'Foreign key, reference to the master table.',
  `item_description` varchar(50) NOT NULL,
  `item_quantity` int(5) NOT NULL DEFAULT '0',
  `country_of_origin` varchar(50) NOT NULL,
  `item_value` int(5) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `item_weight` FLOAT(6,2) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_shipment_items`
--
ALTER TABLE `icargo_shipment_items`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_shipment_items`
--
ALTER TABLE `icargo_shipment_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


--
-- Table structure for table `icargo_address_carrier_time`
--

CREATE TABLE `icargo_address_carrier_time` (
  `id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `carrier_code` varchar(35) NOT NULL,
  `booking_start_time` time NOT NULL,
  `booking_end_time` time NOT NULL,
  `collection_start_time` time NOT NULL,
  `collection_end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_address_carrier_time`
--

INSERT INTO `icargo_address_carrier_time` (`id`, `address_id`, `customer_id`, `carrier_code`, `booking_start_time`, `booking_end_time`, `collection_start_time`, `collection_end_time`) VALUES
(1, 376, 179, 'UKMAIL', '10:00:00', '23:00:00', '11:00:00', '12:00:00'),
(2, 376, 179, 'PNP', '10:00:00', '23:00:00', '11:00:00', '12:00:00'),
(3, 376, 179, 'DHL', '10:00:00', '23:00:00', '11:00:00', '12:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_address_carrier_time`
--
ALTER TABLE `icargo_address_carrier_time`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`address_id`,`customer_id`,`carrier_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_address_carrier_time`
--
ALTER TABLE `icargo_address_carrier_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
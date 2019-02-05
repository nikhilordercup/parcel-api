CREATE TABLE `icargo_customer_grid` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `grid_state` text NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_customer_grid`
--

INSERT INTO `icargo_customer_grid` (`id`, `user_id`, `grid_state`, `company_id`, `created_date`) VALUES
(1, 511, '[{\"visible\":\"true\",\"columnname\":\"Job Identity\",\"target\":\"1\"},{\"visible\":\"true\",\"columnname\":\"Job Type\",\"target\":\"2\"},{\"visible\":\"true\",\"columnname\":\"Collection\",\"target\":\"3\"},{\"visible\":\"true\",\"columnname\":\"Pickup Date\",\"target\":\"4\"},{\"visible\":\"true\",\"columnname\":\"Delivery\",\"target\":\"5\"},{\"visible\":\"true\",\"columnname\":\"Service\",\"target\":\"6\"},{\"visible\":\"true\",\"columnname\":\"Carrier\",\"target\":\"7\"},{\"visible\":\"true\",\"columnname\":\"Amount\",\"target\":\"8\"},{\"visible\":\"true\",\"columnname\":\"Is Invoiced\",\"target\":\"9\"},{\"visible\":\"true\",\"columnname\":\"Shipment Status\",\"target\":\"10\"},{\"visible\":\"true\",\"columnname\":\"Collection Reference\",\"target\":\"11\"},{\"visible\":\"true\",\"columnname\":\"Tracking Number\",\"target\":\"12\"},{\"visible\":\"true\",\"columnname\":\"Booking Date\",\"target\":\"13\"},{\"visible\":\"true\",\"columnname\":\"Collection Date\",\"target\":\"14\"},{\"visible\":\"true\",\"columnname\":\"Total Item\",\"target\":\"15\"},{\"visible\":\"true\",\"columnname\":\"Total Weight\",\"target\":\"16\"},{\"visible\":\"false\",\"columnname\":\"Customer Refrence1\",\"target\":\"17\"},{\"visible\":\"false\",\"columnname\":\"Customer Refrence2\",\"target\":\"18\"}]', 10, '2019-01-29 12:01:22'),
(2, 562, '[{\"visible\":\"true\",\"columnname\":\"Job Identity\",\"target\":\"1\"},{\"visible\":\"true\",\"columnname\":\"Job Type\",\"target\":\"2\"},{\"visible\":\"true\",\"columnname\":\"Collection\",\"target\":\"3\"},{\"visible\":\"true\",\"columnname\":\"Pickup Date\",\"target\":\"4\"},{\"visible\":\"true\",\"columnname\":\"Delivery\",\"target\":\"5\"},{\"visible\":\"true\",\"columnname\":\"Service\",\"target\":\"6\"},{\"visible\":\"true\",\"columnname\":\"Carrier\",\"target\":\"7\"},{\"visible\":\"true\",\"columnname\":\"Amount\",\"target\":\"8\"},{\"visible\":\"true\",\"columnname\":\"Is Invoiced\",\"target\":\"9\"},{\"visible\":\"true\",\"columnname\":\"Shipment Status\",\"target\":\"10\"},{\"visible\":\"true\",\"columnname\":\"Collection Reference\",\"target\":\"11\"},{\"visible\":\"true\",\"columnname\":\"Tracking Number\",\"target\":\"12\"},{\"visible\":\"true\",\"columnname\":\"Booking Date\",\"target\":\"13\"},{\"visible\":\"true\",\"columnname\":\"Collection Date\",\"target\":\"14\"},{\"visible\":\"true\",\"columnname\":\"Total Item\",\"target\":\"15\"},{\"visible\":\"true\",\"columnname\":\"Total Weight\",\"target\":\"16\"},{\"visible\":\"false\",\"columnname\":\"Customer Refrence1\",\"target\":\"17\"},{\"visible\":\"false\",\"columnname\":\"Customer Refrence2\",\"target\":\"18\"}]', 10, '2019-01-29 13:17:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_customer_grid`
--
ALTER TABLE `icargo_customer_grid`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_customer_grid`
--
ALTER TABLE `icargo_customer_grid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;


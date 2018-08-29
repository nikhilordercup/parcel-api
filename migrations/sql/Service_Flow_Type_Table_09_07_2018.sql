--
-- Table structure for table `icargo_service_flow_type`
--

CREATE TABLE `icargo_service_flow_type` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `flow_type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_service_flow_type`
--
ALTER TABLE `icargo_service_flow_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`service_id`,`flow_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_service_flow_type`
--
ALTER TABLE `icargo_service_flow_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
ALTER TABLE `icargo_dev`.`icargo_service_flow_type` ADD UNIQUE `unique`(`service_id`, `flow_type`);   
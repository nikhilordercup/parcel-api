DROP TABLE `icargo_shipments_master`;

CREATE TABLE `icargo_shipments_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `description` varchar(255) DEFAULT NULL,
  `is_used_for_invoice` enum('YES','NO') NOT NULL DEFAULT 'NO',
  `is_used_for_tracking` enum('YES','NO') NOT NULL DEFAULT 'YES',
  `tracking_internal_code` varchar(45) DEFAULT NULL,
  `is_used_for_cancel` enum('YES','NO') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_shipments_master`
--

INSERT INTO `icargo_shipments_master` (`id`, `name`, `code`, `icon`, `status`, `description`, `is_used_for_invoice`, `is_used_for_tracking`, `tracking_internal_code`, `is_used_for_cancel`) VALUES
(2, 'Undefined', 'UNDEFINED', 'UNDEFINED', 1, 'INFO', 'NO', 'NO', 'CARDED', 'NO'),
(3, 'On Hold', 'COLLECTION_ON_HOLD', 'ON_HOLD', 1, 'INFO', 'NO', 'NO', 'NOTRECEIVEDINWAREHOUSE', 'NO'),
(4, 'Waiting for Acceptance', 'UNAPPROVED', 'UNAPPROVED', 1, 'INFO', 'NO', 'NO', 'ASSIGNTODRIVER', 'NO'),
(5, 'Awaiting Collection', 'ASSIGNED', 'ASSIGNED', 1, 'INFO', 'NO', 'NO', 'ASSIGNTODRIVER,ACCEPTBYDRIVER,ROUTESTART', 'NO'),
(6, 'On Transit', 'TRANSIT', 'TRANSIT', 1, 'INFO', 'NO', 'NO', 'DRIVERACCEPTED', 'NO'),
(7, 'On Return Path', 'RETURNING', 'RETURNING', 1, 'INFO', 'NO', 'NO', '', 'NO'),
(8, 'Late Delivery', 'LATE_DELIVERY', 'LATE_DELIVERY', 1, 'INFO', 'NO', 'NO', NULL, 'NO'),
(9, 'Delivered', 'DELIVERYSUCCESS', 'DELIVERED', 1, 'INFO', 'NO', 'YES', 'DELIVEREDBYDRIVER', 'NO'),
(10, 'Cancelled', 'CANCELLED', 'CANCELLED', 1, 'INFO', 'NO', 'NO', 'COLLECTIONSUCCESS', 'NO'),
(11, 'Attention Required', 'ATTENTION', 'ATTENTION', 1, 'INFO', 'NO', 'NO', NULL, 'NO'),
(12, 'Partly Delivered', 'PARTLYDELIVERED', 'PART_DELIVERY', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(13, 'Delayed ', 'DELAYED', 'DELAYED', 1, 'INFO', 'NO', 'NO', NULL, 'NO'),
(14, 'Out For Delivery', 'OUTFORDELIVERY', 'OUT_FOR_DELIVERY', 1, 'INFO', 'NO', 'YES', 'OUTFORDELIVERY', 'NO'),
(15, 'Collected', 'COLLECTIONSUCCESS', 'COLLECTED', 1, 'INFO', 'NO', 'YES', 'COLLECTIONSUCCESS', 'NO'),
(16, 'Intervene Requested', 'AGENT_ATTENTION', 'AGENT_ATTENTION', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(17, 'At Delivery Location', 'AT_DELIVERY_LOCATION', 'AT_DELIVERY_LOCATION', 1, 'INFO', 'NO', 'NO', '', 'NO'),
(18, 'Delivery Attempted', 'DELIVERY_ATTEMPTED', 'ATTEMPTED', 1, 'INFO', 'NO', 'NO', 'CARDED', 'NO'),
(19, 'Invoice Disputed', 'DISPUTED', 'DISPUTED', 1, 'INFO', 'NO', 'NO', '', 'NO'),
(20, 'Out for Delivery(Shortage)', 'DELIVERYSHORTAGE', 'DELIVERYSHORTAGE', 1, 'INFO', 'NO', 'NO', 'ROUTESTART', 'NO'),
(21, 'Collection Attempted', 'COLLECTION_ATTEMPTED', 'COLLECTION_ATTEMPTED', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(22, 'Partly Collected', 'PARTLYCOLLECTED', 'PART_COLLECTED', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(23, 'On Hold', 'DELIVERY_ON_HOLD', 'DELIVERY_ON_HOLD', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(24, 'Scanned In Warehouse', 'WAREHOUSE_SCAN', 'WAREHOUSE_SCAN', 1, NULL, 'NO', 'YES', NULL, 'NO'),
(25, 'Booking Confirm', 'INFO_RECEIVED', 'INFO_RECEIVED', 1, 'booking confirm', 'NO', 'YES', NULL, 'YES'),
(26, 'Awaiting Collection', 'COLLECTIONAWAITED', 'COLLECTION_AWAITED', 1, 'test description', 'NO', 'YES', 'COLLECTION_AWAITED', 'NO'),
(27, 'Return In Warehouse', 'RETURNINWAREHOUSE', 'RETURN_IN_WAREHOUSE', 1, 'return in warehouse', 'NO', 'YES', 'RETURNINWAREHOUSE', 'NO'),
(28, 'In Transit', 'IN_TRANSIT', 'IN_TRANSIT', 1, 'INFO', 'NO', 'YES', NULL, 'NO'),
(29, 'Delivery Carded', 'DELIVERY_CARDED', 'DELIVERY_CARDED', 1, NULL, 'NO', 'YES', NULL, 'NO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_shipments_master`
--
ALTER TABLE `icargo_shipments_master`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_shipments_master`
--
ALTER TABLE `icargo_shipments_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
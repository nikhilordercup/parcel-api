CREATE TABLE  IF  NOT  EXISTS  `icargo_service_code_mapping` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_code` varchar(100) NOT NULL,
  `service_type` varchar(100) NOT NULL
);

ALTER TABLE `icargo_service_code_mapping`
  ADD PRIMARY KEY (`id`);

INSERT INTO `icargo_service_code_mapping` (`id`, `service_id`, `provider_id`, `service_name`, `service_code`, `service_type`) VALUES
(1, 24, 7, 'dhl_domestic_express', 'dhl_domestic_express', 'service'),
(2, 33, 7, 'dhl_domestic_express_1200', 'dhl_domestic_express_1200', 'service'),
(3, 27, 7, 'dhl_domestic_express_0900', 'dhl_domestic_express_0900', 'service'),
(4, 28, 7, 'dhl_medical_express', 'dhl_medical_express', 'service'),
(5, 30, 7, 'dhl_economy_select_eu', 'dhl_economy_select_eu', 'service'),
(6, 34, 7, 'dhl_express_worldwide', 'dhl_express_worldwide', 'service'),
(7, 34, 7, 'dhl_express_worldwide_eu', 'dhl_express_worldwide_eu', 'service');


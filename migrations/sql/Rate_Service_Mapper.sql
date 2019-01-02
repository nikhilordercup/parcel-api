CREATE TABLE IF NOT EXISTS `icargo_carrier_service_provider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_id` int(11) NOT NULL,
  `request_type` enum('RATE','LABEL') NOT NULL,
  `provider_id` int(11) NOT NULL,
  `provider_endpoint_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_carrier_service_provider`
--

INSERT INTO `icargo_carrier_service_provider` (`id`, `carrier_id`, `request_type`, `provider_id`, `provider_endpoint_id`) VALUES
(1, 1, 'RATE', 3, 3),
(2, 1, 'LABEL', 1, 3),
(3, 2, 'RATE', 2, 2),
(4, 2, 'LABEL', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `icargo_service_providers`
--

CREATE TABLE IF NOT EXISTS `icargo_service_providers` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `rate_endpoint` text NOT NULL,
  `label_endpoint` text NOT NULL,
  `auth_config` text NOT NULL,
  `provider_type` enum('ENDPOINT','PROVIDER') NOT NULL,
  `app_env` enum('DEV','PROD') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_service_providers`
--

INSERT INTO `icargo_service_providers` (`id`, `provider`, `rate_endpoint`, `label_endpoint`, `auth_config`, `provider_type`, `app_env`) VALUES
(1, 'Easypost', 'http://occore.ordercup.com/api/v1/rate', 'http://occore.ordercup.com/api/v1/rate', '', 'PROVIDER', 'DEV'),
(2, 'Coreprime', 'http://occore.ordercup1.com/api/v1/rate', 'http://occore.ordercup1.com/api/v1/rate', '', 'ENDPOINT', 'DEV'),
(3, 'Local', 'http://api.icargo.in/v1/RateEngine/getRate', 'http://api.icargo.in/v1/RateEngine/getRate', '', 'ENDPOINT', 'DEV'),
(4, 'Coreprime', 'http://occore.ordercup.com/api/v1/rate', 'http://occore.ordercup.com/api/v1/rate', '', 'ENDPOINT', 'PROD'),
(5, 'Local', 'http://api.icargo.in/v1/RateEngine/getRate', 'http://api.icargo.in/v1/RateEngine/getRate', '', 'ENDPOINT', 'PROD');
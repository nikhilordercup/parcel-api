CREATE TABLE IF NOT EXISTS `icargo_custom_filter_config` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `filter_name` varchar(250) NOT NULL,
  `filter_slug` varchar(250) NOT NULL,
  `filter_config` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
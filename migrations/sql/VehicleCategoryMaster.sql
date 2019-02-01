CREATE TABLE IF NOT EXISTS `icargo_vehicle_category_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_type` varchar(250) NOT NULL,
  `max_weight` float NOT NULL DEFAULT '0',
  `max_width` float NOT NULL DEFAULT '0',
  `max_height` float NOT NULL DEFAULT '0',
  `max_length` float NOT NULL DEFAULT '0',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `icargo_vehicle_category_master`
ADD `max_volume` INT NOT NULL DEFAULT '0' AFTER `max_length`;
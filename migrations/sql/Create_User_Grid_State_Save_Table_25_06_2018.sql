CREATE TABLE IF NOT EXISTS `icargo_user_grid_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `grid_slug` varchar(250) NOT NULL,
  `grid_state` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
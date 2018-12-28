CREATE TABLE IF NOT EXISTS `icargo_carrier_user_token` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `carrier` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `authentication_token` varchar(255) NOT NULL,
  `authentication_token_created_at` datetime NOT NULL,
  `authentication_token_expire_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
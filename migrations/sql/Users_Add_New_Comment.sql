ALTER TABLE `icargo_users` CHANGE COLUMN `status` `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '0 for inactive, 1 for active, 2 for deleted/disabled' ;

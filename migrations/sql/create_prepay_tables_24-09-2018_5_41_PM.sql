CREATE TABLE `icargo_customer_card_details` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sp_id` int(11) NOT NULL,
  `sp_customer_id` varchar(200) NOT NULL,
  `sp_token_id` varchar(200) NOT NULL COMMENT 'token id is used as a source in making charges',
  `sp_card_id` varchar(200) NOT NULL,
  `card_last_four` int(5) NOT NULL,
  `exp_month` int(3) NOT NULL,
  `exp_year` int(5) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `address_line1` text NOT NULL,
  `address_line2` text NOT NULL,
  `card_type` varchar(50) NOT NULL,
  `token_added` int(6) NOT NULL,
  `json_data` text NOT NULL,
  `status` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `icargo_customer_payment_details` (
  `id` int(11) NOT NULL,
  `service_provider_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sp_customer_id` varchar(200) NOT NULL,
  `customer_created` int(6) NOT NULL,
  `json_data` text NOT NULL,
  `status` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `icargo_customer_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sp_id` int(11) NOT NULL,
  `sp_customer_id` varchar(200) NOT NULL COMMENT 'Service Provider Customer Id',
  `card_id` varchar(200) NOT NULL COMMENT 'card id',
  `charge_id` varchar(200) NOT NULL,
  `transaction_id` varchar(200) DEFAULT NULL,
  `amount` double NOT NULL,
  `currency` varchar(20) NOT NULL,
  `transaction_created` int(10) DEFAULT NULL COMMENT 'timestamp transaction created',
  `sp_order_id` int(5) DEFAULT NULL,
  `json_data` text,
  `card_last_four` int(5) NOT NULL,
  `card_type` varchar(20) NOT NULL,
  `exp_month` int(3) NOT NULL,
  `exp_year` int(5) NOT NULL,
  `status` tinyint(3) NOT NULL DEFAULT '1' COMMENT '1=Transaction, 2=Transaction Complete, 3=Transaction Complete, 0=Transaction Failed',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `icargo_payment_provider` (
  `id` int(11) NOT NULL,
  `sp_name` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sk_test_key` varchar(200) NOT NULL,
  `pk_test_key` varchar(200) NOT NULL,
  `sk_live_key` varchar(200) NOT NULL,
  `pk_live_key` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


INSERT INTO `icargo_payment_provider` (`id`, `sp_name`, `user_id`, `sk_test_key`, `pk_test_key`, `sk_live_key`, `pk_live_key`, `email`, `status`, `created`, `updated`) VALUES
(1, 'Stripe', 10, 'sk_test_nVJKKmc70NljkP0gbCuz3a5u', 'pk_test_YxiVUdOAvhKjSqezES6FZFdh', 'sk_test_nVJKKmc70NljkP0gbCuz3a5u', 'pk_test_YxiVUdOAvhKjSqezES6FZFdh', 'test@gmail.com', 1, '2018-09-17 07:18:24', '2018-09-17 07:18:24');


ALTER TABLE `icargo_customer_card_details`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `icargo_customer_payment_details`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `icargo_customer_transactions`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `icargo_payment_provider`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `icargo_customer_card_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `icargo_customer_payment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;


ALTER TABLE `icargo_customer_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `icargo_payment_provider`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
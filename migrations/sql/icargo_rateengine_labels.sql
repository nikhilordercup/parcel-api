-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2018 at 07:12 AM
-- Server version: 10.1.30-MariaDB
-- PHP Version: 7.2.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `icargo_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `icargo_rateengine_labels`
--

CREATE TABLE `icargo_rateengine_labels` (
  `label_id` bigint(20) NOT NULL,
  `credential_info` text NOT NULL,
  `collection_info` text NOT NULL,
  `delivery_info` text NOT NULL,
  `package_info` text NOT NULL,
  `extra_info` text NOT NULL,
  `insurance_info` text NOT NULL,
  `constants_info` text NOT NULL,
  `billing_coounts` varchar(255) NOT NULL,
  `dispatch_date` date NOT NULL,
  `currency` varchar(50) NOT NULL,
  `carrier` varchar(50) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `labels` varchar(100) NOT NULL,
  `custom` varchar(100) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `reference_id` varchar(100) NOT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_rateengine_labels`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_rateengine_labels`
--
ALTER TABLE `icargo_rateengine_labels`
  ADD PRIMARY KEY (`label_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_rateengine_labels`
--
ALTER TABLE `icargo_rateengine_labels`
  MODIFY `label_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2019 at 02:00 PM
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
-- Database: `appstb`
--

-- --------------------------------------------------------

--
-- Table structure for table `icargo_user_notes`
--

CREATE TABLE `icargo_user_notes` (
  `note_id` int(11) NOT NULL,
  `user_notes` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `job_identity` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `icargo_user_notes`
--

INSERT INTO `icargo_user_notes` (`note_id`, `user_notes`, `created_by`, `job_identity`, `created_date`) VALUES
(1, 'Test', 420, 'ICARGOS1899104', '2019-01-09 13:45:38'),
(2, 'Test 1', 420, 'ICARGOS1899104', '2019-01-09 14:28:19'),
(3, 'Test 1 Test 1 Test 1 Test 1 Test 1 Test 1 Test 1', 420, 'ICARGOS1899104', '2019-01-09 14:29:11'),
(4, 'Test 2', 420, 'ICARGOS1899104', '2019-01-09 14:59:16'),
(5, 'Next day User Notes', 420, 'ICARGOS189908', '2019-01-09 15:16:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `icargo_user_notes`
--
ALTER TABLE `icargo_user_notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `job_identity` (`job_identity`),
  ADD KEY `created_date` (`created_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `icargo_user_notes`
--
ALTER TABLE `icargo_user_notes`
  MODIFY `note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

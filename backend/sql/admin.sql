-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 25, 2025 at 06:49 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_user`
--

CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL,
  `username` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp(),
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_user`
--

INSERT INTO `admin_user` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`, `name`) VALUES
(1, 'ADMIN', 'ADMIN@GMAIL.COM', '123', '2025-08-22 11:40:10', '2025-08-22 11:40:10', 'John Doe');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `text` text DEFAULT NULL,
  `hasTermination` tinyint(1) DEFAULT NULL,
  `hasLiability` tinyint(1) DEFAULT NULL,
  `lengthScore` float DEFAULT NULL,
  `riskLevel` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `text`, `hasTermination`, `hasLiability`, `lengthScore`, `riskLevel`) VALUES
(1, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'LowRisk'),
(2, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(3, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(4, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(5, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(6, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(7, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(8, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(9, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(10, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(11, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(12, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(13, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(14, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(15, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(16, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(17, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(18, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(19, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(20, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk'),
(21, 'This contract includes termination but no liability clause.', 1, 1, 0.059, 'HighRisk');

-- --------------------------------------------------------

--
-- Table structure for table `contract_new`
--

CREATE TABLE `contract_new` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `hasTermination` tinyint(1) NOT NULL,
  `hasLiability` tinyint(1) NOT NULL,
  `lengthScore` float NOT NULL,
  `riskLevel` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `team_leader_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `name`, `email`, `contact_number`, `team_leader_id`, `department`, `password`) VALUES
(1, 'linbil celestre', 'linbilcelestre3@gmial.com', '09234256', NULL, 'Administration', ''),
(2, 'Chares', 'chares@gmail.com', '09234256', NULL, 'Human Resources 3', '$2y$10$I6NNQsihwf12qfvjbeUic.dXFt3.Y52sdGvyJ2otVDaWeOqISCJiq');

-- --------------------------------------------------------

--
-- Table structure for table `risks`
--

CREATE TABLE `risks` (
  `id` int(11) NOT NULL,
  `risk_id` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `level` varchar(10) NOT NULL,
  `status` varchar(20) NOT NULL,
  `mitigation` text DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `systemadmin`
--

CREATE TABLE `systemadmin` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_user`
--
ALTER TABLE `admin_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`) USING HASH,
  ADD UNIQUE KEY `email` (`email`) USING HASH;

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contract_new`
--
ALTER TABLE `contract_new`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `team_leader_id` (`team_leader_id`);

--
-- Indexes for table `risks`
--
ALTER TABLE `risks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `systemadmin`
--
ALTER TABLE `systemadmin`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_user`
--
ALTER TABLE `admin_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `contract_new`
--
ALTER TABLE `contract_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `risks`
--
ALTER TABLE `risks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `systemadmin`
--
ALTER TABLE `systemadmin`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`team_leader_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `systemadmin`
--
ALTER TABLE `systemadmin`
  ADD CONSTRAINT `systemadmin_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

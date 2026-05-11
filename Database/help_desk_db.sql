-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql301.infinityfree.com
-- Generation Time: May 10, 2026 at 09:57 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41694135_help_desk_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `internal_messages`
--

CREATE TABLE `internal_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `receiver_role` varchar(20) NOT NULL,
  `receiver_email` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internal_messages`
--

INSERT INTO `internal_messages` (`id`, `sender_email`, `sender_role`, `receiver_role`, `receiver_email`, `message`, `is_read`, `created_at`) VALUES
(15, 'admin@klovrbank.com', 'admin', 'support', 'khristiantiu@support.com', 'NOTICE: We are making some changes to the way the database works to ensure it asynchronously works', 0, '2026-05-04 07:53:08'),
(14, 'admin@klovrbank.com', 'admin', 'support', 'ivannerodriguez@support.com', 'Test Message to Support', 1, '2026-04-29 04:10:48'),
(13, 'ivannerodriguez@support.com', 'support', 'admin', 'admin@klovrbank.com', 'Test Message to Admin', 1, '2026-04-29 04:10:35'),
(5, 'admin@klovrbank.com', 'admin', 'support', 'khristiantiu@support.com', 'Test message', 1, '2026-04-25 15:06:24'),
(6, 'admin@klovrbank.com', 'admin', 'support', 'amiramontalban@support.com', 'Test All', 1, '2026-04-25 15:17:21'),
(7, 'admin@klovrbank.com', 'admin', 'support', 'khristiantiu@support.com', 'Test All', 1, '2026-04-25 15:17:21'),
(8, 'admin@klovrbank.com', 'admin', 'support', 'rheanneplacio@support.com', 'Test All', 0, '2026-04-25 15:17:22'),
(9, 'admin@klovrbank.com', 'admin', 'support', 'ivannerodriguez@support.com', 'Test All', 1, '2026-04-25 15:17:22'),
(10, 'admin@klovrbank.com', 'admin', 'support', 'jcbautista@support.com', 'Test All', 1, '2026-04-25 15:17:22'),
(16, 'admin@klovrbank.com', 'admin', 'support', 'amiramontalban@support.com', 'NOTICE: We are making some changes to the way the database works to ensure it asynchronously works', 1, '2026-05-04 07:53:08'),
(17, 'admin@klovrbank.com', 'admin', 'support', 'rheanneplacio@support.com', 'NOTICE: We are making some changes to the way the database works to ensure it asynchronously works', 0, '2026-05-04 07:53:08'),
(18, 'admin@klovrbank.com', 'admin', 'support', 'ivannerodriguez@support.com', 'NOTICE: We are making some changes to the way the database works to ensure it asynchronously works', 1, '2026-05-04 07:53:08'),
(19, 'admin@klovrbank.com', 'admin', 'support', 'jcbautista@support.com', 'NOTICE: We are making some changes to the way the database works to ensure it asynchronously works', 0, '2026-05-04 07:53:08'),
(20, 'ivannerodriguez@support.com', 'support', 'admin', 'admin@klovrbank.com', 'Ok sir', 1, '2026-05-04 07:53:17'),
(21, 'admin@klovrbank.com', 'admin', 'support', 'ivannerodriguez@support.com', 'test', 1, '2026-05-07 03:53:52'),
(22, 'admin@klovrbank.com', 'admin', 'support', 'ivannerodriguez@support.com', 'test 1', 1, '2026-05-07 04:19:33'),
(23, 'ivannerodriguez@support.com', 'support', 'admin', 'admin@klovrbank.com', 'hi', 1, '2026-05-07 04:23:59');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`) VALUES
(1, 'kevinlumabi@gmail.com', '5cbbbbf12f688c06155ecdcb09b552ae4750c26e887137f657fe48ece1ddff31', '2026-04-24 12:16:31', 0),
(20, '27khristiangabrieltiu@gmail.com', '78117c29ddc7b26c1cda7633fce486f51b38cdc8c4b4382b91b140d507a4507f', '2026-04-25 03:52:24', 0),
(32, 'ivannejoshuarodriguez06@gmail.com', '988d0a2b445b814c7b50b4b77d48b553595579f88aa04bc576e78cff0e6b71ea', '2026-04-25 05:01:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `display_id` varchar(10) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `status` enum('Open','Under Review','On-Going','Resolved') NOT NULL DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `assigned_support_email` varchar(255) DEFAULT NULL,
  `is_escalated` tinyint(1) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `display_id`, `user_email`, `subject`, `category`, `content`, `status`, `created_at`, `assigned_support_email`, `is_escalated`, `image_path`) VALUES
(1, 'TK26AT1N', 'kevinlumabi@gmail.com', 'Testing', 'Account', 'Image Test', 'Under Review', '2026-04-21 09:03:00', 'khristiantiu@support.com', 0, 'uploads/img_69e73d44653ba9.35403029.png'),
(2, 'TK26WX4Z', 'kevinlumabi@gmail.com', 'Test 2', 'Billing', 'Yes', 'Under Review', '2026-04-21 09:05:17', 'jcbautista@support.com', 0, NULL),
(6, 'TK26YWPT', 'testingacc@gmail.com', 'Transaction Error', 'Billing', 'My transaction did not go through the merchant website but still gave me the product. The transaction ID is 000100012ADF.', 'Under Review', '2026-04-25 14:56:50', 'jcbautista@support.com', 1, 'uploads/img_69ecd631c49c61.80476971.jpg'),
(7, 'TK260W2A', 'kevinlumabi@gmail.com', 'Test 1', 'General', 'Test Message', 'Under Review', '2026-04-26 12:50:55', 'amiramontalban@support.com', 0, NULL),
(8, 'TK26DMQQ', 'kevinlumabi@gmail.com', 'Image Test 1', 'General', 'Image Testing Message', 'Under Review', '2026-04-26 12:55:52', 'amiramontalban@support.com', 0, 'uploads/img_69ee0b583e1ab3.61406648.jpg'),
(9, 'TK26VAP1', 'kevinlumabi@gmail.com', 'Image Test 1', 'General', 'Image Testing Message', 'On-Going', '2026-04-26 12:55:54', 'jcbautista@support.com', 0, 'uploads/img_69ee0b5a7dba50.60792227.jpg'),
(10, 'TK260Z5E', 'kevinlumabi@gmail.com', 'Testing 2', 'General', 'Repetition Test Message', 'Resolved', '2026-04-26 13:04:38', 'jcbautista@support.com', 0, NULL),
(11, 'TK26HZ41', 'kevinlumabi@gmail.com', 'Testing 2', 'General', 'Repetition Test Message', 'Under Review', '2026-04-26 13:04:39', 'khristiantiu@support.com', 0, NULL),
(12, 'TK26J7HO', 'kevinlumabi@gmail.com', 'Testing 2', 'General', 'Repetition Test Message', 'On-Going', '2026-04-26 13:04:39', 'jcbautista@support.com', 0, NULL),
(13, 'TK26DBWP', 'kevinlumabi@gmail.com', 'Account Ticket Testing', 'Account', 'Account Ticket Message', 'Under Review', '2026-04-26 13:45:49', 'jcbautista@support.com', 0, NULL),
(14, 'TK26ACJJ', 'kevinlumabi@gmail.com', 'Technical Ticket Testing', 'Technical', 'Technical Ticket Testing Message', 'Under Review', '2026-04-26 13:46:06', 'jcbautista@support.com', 0, NULL),
(15, 'TK263GBX', 'kevinlumabi@gmail.com', 'Limit Message Testing', 'General', 'Test Case - Past The Limit of the database for the message of ticket\r\n\r\naaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa aaaaa', 'Under Review', '2026-04-26 13:51:09', 'khristiantiu@support.com', 0, NULL),
(18, 'TK26DFCB', 'kevinlumabi@gmail.com', 'Testing 1 ', 'Account', 'Test Message', 'On-Going', '2026-04-29 12:06:14', 'khristiantiu@support.com', 0, NULL),
(26, 'TK26MY98', 'kevinlumabi@gmail.com', 'Message Live Test', 'Account', 'TEST', 'Resolved', '2026-05-04 08:53:54', 'ivannerodriguez@support.com', 0, NULL),
(27, 'TK26TEDA', 'kevsistired@gmail.com', 'My bank has been hacked', 'Account', 'Someone named Ivannescammer123 hacked my account. Can you recover my account??', 'Open', '2026-05-04 09:13:50', NULL, 0, 'uploads/img_69f8634e65e289.06189288.jpg'),
(28, 'TK26VSGD', 'rolarodriguez1018@gmail.com', 'Detailed report regarding statement error', 'Billing', 'Hi, I am writing to report a discrepancy on my latest billing statement. On February 6th, there is a charge for Php. 1,500 that I do not recognize. I have already checked with my family members, and no one made this purchase. The merchant listed is \"MirlaGo Online.\" I would like to dispute this charge and request a new card if my current one has been compromised. Please let me know what the next steps are. Thank you.', 'Open', '2026-05-04 14:05:17', NULL, 0, NULL),
(29, 'TK260PN8', 'horariojb27@gmail.com', 'Lost Money', 'Account', 'My account balance was deducted', 'On-Going', '2026-05-07 01:28:12', 'ivannerodriguez@support.com', 0, NULL),
(30, 'TK266U8F', 'crmnlysbll@gmail.com', 'Lost account', 'Account', 'I can\'t find my account and can\'t log in', 'On-Going', '2026-05-07 01:30:41', 'ivannerodriguez@support.com', 0, NULL),
(31, 'TK26SIKI', 'ralphcomeso@gmail.com', 'card number', 'Account', 'possible to change card number?', 'On-Going', '2026-05-07 01:54:37', 'ivannerodriguez@support.com', 0, NULL),
(32, 'TK26MCQW', 'janella.valencia11@gmail.com', 'Account', 'General', 'How to change my password?', 'Under Review', '2026-05-07 02:43:00', 'ivannerodriguez@support.com', 0, NULL),
(33, 'TK26XESZ', 'rangasajocrestellaerikal@gmail.com', 'Account', 'Billing', 'How to change my password?', 'Open', '2026-05-07 02:43:01', NULL, 0, NULL),
(34, 'TK260K83', 'kevinlumabi@gmail.com', 'My account is not working', 'Billing', 'how do I fix this, please help me', 'On-Going', '2026-05-07 02:43:15', 'ivannerodriguez@support.com', 0, NULL),
(35, 'TK26DQY1', 'kevinlumabi@gmail.com', 'My account got hacked', 'Technical', 'Test Message', 'On-Going', '2026-05-07 04:53:25', 'ivannerodriguez@support.com', 0, NULL),
(36, 'TK26HS3Z', 'kevinlumabi@gmail.com', 'Invalid pin number', 'Account', 'I have never changed my PIN number since the creation of my account, but i can\'t access it now.', 'On-Going', '2026-05-07 09:21:09', 'amiramontalban@support.com', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_replies`
--

CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL,
  `display_id` varchar(20) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `sender_role` enum('user','support','admin') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_replies`
--

INSERT INTO `ticket_replies` (`id`, `display_id`, `sender_email`, `sender_role`, `message`, `created_at`) VALUES
(1, 'TK26AT1N', 'kevinlumabi@gmail.com', 'user', 'hello, reply ASAP', '2026-04-21 09:05:54'),
(2, 'TK26VAP1', 'kevinlumabi@gmail.com', 'user', 'Reply Test', '2026-04-26 14:12:07'),
(3, 'TK26VAP1', 'jcbautista@support.com', 'support', 'hii', '2026-04-26 14:12:47'),
(13, 'TK26MY98', 'ivannerodriguez@support.com', 'support', 'Test', '2026-05-04 08:54:26'),
(5, 'TK264YPM', 'ivannerodriguez@support.com', 'support', 'hello', '2026-04-29 03:38:58'),
(7, 'TK26DFCB', 'khristiantiu@support.com', 'support', 'reply test', '2026-04-29 12:09:29'),
(17, 'TK26VSGD', 'rolarodriguez1018@gmail.com', 'user', 'if this could be fixed ASAP, please do.', '2026-05-04 14:06:10'),
(16, 'TK26MY98', 'ivannerodriguez@support.com', 'support', 'Thank you for choosing KlovrBank!', '2026-05-04 08:55:53'),
(15, 'TK26MY98', 'ivannerodriguez@support.com', 'support', 'Has the issue been resolved?', '2026-05-04 08:55:48'),
(14, 'TK26MY98', 'kevinlumabi@gmail.com', 'user', 'Nice', '2026-05-04 08:54:32'),
(18, 'TK260PN8', 'ivannerodriguez@support.com', 'support', 'Can you tell me more?', '2026-05-07 01:29:13'),
(19, 'TK266U8F', 'ivannerodriguez@support.com', 'support', 'Can you tell me more?', '2026-05-07 01:30:56'),
(20, 'TK266U8F', 'ivannerodriguez@support.com', 'support', 'Test', '2026-05-07 01:31:27'),
(21, 'TK266U8F', 'crmnlysbll@gmail.com', 'user', 'No ðŸ˜­', '2026-05-07 01:31:46'),
(22, 'TK26SIKI', 'ivannerodriguez@support.com', 'support', 'Can you tell me more?', '2026-05-07 01:56:24'),
(23, 'TK260K83', 'ivannerodriguez@support.com', 'support', 'Please wait a moment', '2026-05-07 03:57:18'),
(24, 'TK260K83', 'kevinlumabi@gmail.com', 'user', 'ayoko nga maghintay tulungan mo na agad ako', '2026-05-07 03:59:13'),
(25, 'TK26DQY1', 'ivannerodriguez@support.com', 'support', 'Can you tell me more?', '2026-05-07 04:54:04'),
(26, 'TK26DQY1', 'kevinlumabi@gmail.com', 'user', 'Yes', '2026-05-07 04:54:19'),
(27, 'TK26HS3Z', 'amiramontalban@support.com', 'support', 'hello, i am trying to work on it. please wait for a few days.', '2026-05-07 09:23:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `bank_account` varchar(16) NOT NULL,
  `bank_account_4` varchar(4) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `phone` varchar(11) DEFAULT NULL,
  `display_name` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `house_no` varchar(20) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `subdivision` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` char(4) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `profile_picture_offset` varchar(50) DEFAULT '50% 50%'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `bank_account`, `bank_account_4`, `role`, `is_active`, `is_verified`, `status`, `phone`, `display_name`, `created_at`, `house_no`, `street`, `subdivision`, `municipality`, `region`, `country`, `postal_code`, `profile_picture`, `profile_picture_offset`) VALUES
(1, 'admin@klovrbank.com', '$2y$10$MFzge7daD6zvSeJDZiMdU.4GKUuRiTSDQj5YRm1YNklNoR/gdhdGa', '1111111111111111', '1111', 'admin', 1, 1, 'Active', NULL, NULL, '2026-04-21 08:48:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(2, 'kevinlumabi@gmail.com', '$2y$10$4H3zOCeg2C25wzdZYB/K/e358JuSuyxrDxD6xmltIer4oKv6GQe7C', '1010101010101010', '1010', 'user', 1, 1, 'Active', '09123456789', 'Kevin Lumabi', '2026-04-21 08:51:06', '100', 'Rizal Ave.', 'Sta. Cruz', 'Manila', 'NCR', 'Philippines', '1800', NULL, '48.5% 0.0%'),
(3, 'khristiantiu@support.com', '$2y$10$nvQUXpJY1HoBcLLkdB2MJeuMdZ7WwGoL/wYgO2i/HS/UVYVCqLWLu', '0000000000000000', '0000', 'support', 1, 1, 'Active', NULL, NULL, '2026-04-21 08:53:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_e0ef2a901985ccdd3aef4a0cc00e0c64.png', '50.0% 50.0%'),
(4, 'amiramontalban@support.com', '$2y$10$UCgSLrFfdTgP2Pgu9s1am.955Y37buhwLo1qJaHOYaRH94yMbOU8.', '0000000000000000', '0000', 'support', 1, 1, 'Active', NULL, NULL, '2026-04-21 08:54:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_b0aec2a42ee788b565c889c9c9821b5b.jpg', '47.6% 25.0%'),
(5, 'liansalguero@gmail.com', '$2y$10$MkKEKM5XisnYikIhYPue9uUB2BTqL0quFmy9xQtBI1yI9ogm08soq', '2020202020202020', '2020', 'user', 1, 1, 'Active', NULL, NULL, '2026-04-21 09:35:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(9, 'rheanneplacio@support.com', '$2y$10$m2HqrR1tDvglDG5vL3d6IOLwebe2nyJjx4r95zIpEURcideZiqCCW', '0000000000000000', '0000', 'support', 1, 1, 'Active', NULL, NULL, '2026-04-23 04:19:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_f3de312a7f7c0c8fccbde8fc6fbcee0e.png', '50.0% 50.0%'),
(10, 'ivannerodriguez@support.com', '$2y$10$2kHAADJXuGQVA6TAGXtC0urDAO8Hl0aK5M.ROpvSJmd.FC6sX/y8y', '0000000000000000', '0000', 'support', 1, 1, 'Active', NULL, NULL, '2026-04-23 04:20:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_cd6d74e709ab8c86b39e195dfe4d753e.jpg', '50.0% 50.0%'),
(20, 'jcbautista@support.com', '$2y$10$3IEhMcPA.zHao9YqQ4n7BuyzkfZgMqgNmeJzDyTHQHG1qRGhDHYQG', '0000000000000000', '0000', 'support', 1, 1, 'Active', NULL, NULL, '2026-04-25 14:13:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_de65830d2c890aeef19137a7fc2e7bad.jpg', '52.9% 0.0%'),
(21, 'testingacc@gmail.com', '$2y$10$.edEkqM9jGmHMVBi5zl7cO8CYlrF/Y1BKW154RVmre/ajrJezfLEG', '6767123467671234', '1234', 'user', 1, 1, 'Active', NULL, NULL, '2026-04-25 14:19:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/avatars/avatar_76d3bc45a552f4052e383dcac002d10b.png', '50.0% 50.0%'),
(22, 'testingacc2@gmail.com', '$2y$10$7QWdrwB59t8Qa4XowEa2e.b6k4PjKWWKGbmyKH8Khpi.mEuOhcM9q', '1234676712346767', '6767', 'user', 1, 0, 'Active', NULL, NULL, '2026-04-25 14:37:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(23, 'testingaccbrave@gmail.com', '$2y$10$GCtCRcxFuTMle7//1KBkmOT0Da2IYzvLPvhkfB5MYxy046rS8jrIy', '6769123467694321', '4321', 'user', 1, 1, 'Active', NULL, NULL, '2026-04-25 17:09:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(33, 'kevsistired@gmail.com', '$2y$10$lnjrm38V9jIpyd5IMGVn8.BKMCXB/g6nq4bL1hiUdsW7uGx/kH7ri', '0000000000001111', '1111', 'user', 1, 1, 'Active', '09999999999', 'jevs', '2026-05-04 09:08:20', '1234', '1234', 'barangay san jose', 'laguna city', 'NCR', 'Canada', NULL, 'uploads/avatars/avatar_da02574c6916359fbec3e2d48855c390.jpg', '57.9% 0.8%'),
(34, 'rolarodriguez1018@gmail.com', '$2y$10$UU7czLVVU2cKbh4NPSOGm.r5LgXpX9O9X.Q/Qs3NWrG8irwq0dl.q', '1234567891011121', '1121', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-04 13:54:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(35, 'gallerynimimei@gmail.com', '$2y$10$Xm/QwzFaB6vLXQ1O21Q5IO7HXHW8xViyhdqMK8m1dbumR4BQdjiO6', '6767676767676767', '6767', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:09:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(36, 'frances.escoto25@gmail.com', '$2y$10$3/ZUCA0kjvvAUHAfdCzym./bVjFZIPXQ3SFGJNCmmW2giRe3.YoXW', '2154604527316437', '6437', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:21:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(37, 'crmnlysbll@gmail.com', '$2y$10$d86Hsu5rtdRlUZDvOD.3H.0LO3HxbUuM.VY/SBnGACQa6v0g5Tkp2', '7777777777777777', '7777', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:26:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(38, 'horariojb27@gmail.com', '$2y$10$rMGiLpHJdF0Xg7NcWqWXHO56v04.uIVXlbHb9SUqZWOqEiK8qK2a6', '2006200620062006', '2006', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:27:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(39, 'cndcgbngc@gmail.com', '$2y$10$20C99xKxleB4pQHGkFIqpODwlErDgjNQD.lMwvRd5oR2PM/V3rxrK', '1234432123149180', '9180', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:27:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(40, 'sarmientorvin@gmail.com', '$2y$10$a3ouXqJ3l8qun9CBl2oz1.c89mRx3fFDbzgpFtkQ2sLBQOAETdCRi', '6454386184381234', '1234', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:35:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(41, 'mjdc2706@gmail.com', '$2y$10$VBe.jv0r488siu/T5KkbaeJ6EZBHwFzhHRuRjzxg4X8PIFDUaAe9a', '5407568125805120', '5120', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:46:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(42, 'ralphcomeso@gmail.com', '$2y$10$vJ6RldaT3MHUxo/FSywTQ.M0TtTORt5mWc6EpcW4SadA60Tp6AHZK', '1231231231216767', '6767', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 01:53:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(43, 'iluvgmpls@gmail.com', '$2y$10$GehU5xcthBXfRo25mURVlOQuPaha8lL2r5kIKBvS7vpdjJ77ipwpy', '3191953864319999', '9999', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:01:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(44, 'kmarianu@gmail.com', '$2y$10$BLe76xHHEvjLDl9.Q7IEQu5Tk/pG6MJbuIVfkh6x8gqdGzNCr8BXq', '1234123412341235', '1235', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:07:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(45, 'janella.valencia11@gmail.com', '$2y$10$UDH3kcNvqr/3dOnvnyKkseQYJqiTaswTgqImeMPTNm5j//7/s/Fli', '7405854016461234', '1234', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:40:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(46, 'marygoldcentino@gmail.com', '$2y$10$fmz3Z1DLG.1cvmyB53eZcuIRoRBEATkWfo4jjKuDVwFmt8YicghAq', '1234567890103062', '3062', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:40:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(47, 'rangasajocrestellaerikal@gmail.com', '$2y$10$agb4XTfwJaApX8lX1/zFNeauSlq.g6D1mSwx38jqnX3e3x40MlSa2', '1235362365561234', '1234', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:41:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(48, 'adriansc0416@gmail.com', '$2y$10$9q6SZoMAmCAEhVTnIllkT.pU1h/vNqfVuJdaZaZB/P73oBCTugSee', '1545850673135648', '5648', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 02:44:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(49, 'samanthaaniag@gmail.com', '$2y$10$4SV4R4KIHHIiwMI/WHaHx.SkC9sqQJQlk3X2dbPNQFvqdNeUypayi', '0553865428130722', '0722', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 03:06:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(50, 'ulysesendraca@gmail.com', '$2y$10$z.cxRlqrvz/7jobylsZGA.1IEek.Fzzmp1mF.8z55BOhDayd.H.la', '2222222233331111', '1111', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 03:32:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%'),
(51, 'endracaulyses@gmail.com', '$2y$10$NFFjdgUZUVYuJJCpC8houeu4iVNSNp0hla4xiA4RJMxANKPKhm1kK', '1111222233334444', '4444', 'user', 1, 1, 'Active', NULL, NULL, '2026-05-07 03:34:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% 50%');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `internal_messages`
--
ALTER TABLE `internal_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `email` (`email`(250));

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD UNIQUE KEY `display_id` (`display_id`),
  ADD KEY `fk_ticket_user_email` (`user_email`);

--
-- Indexes for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `internal_messages`
--
ALTER TABLE `internal_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `ticket_replies`
--
ALTER TABLE `ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_user_email` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

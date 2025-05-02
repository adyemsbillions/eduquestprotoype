-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2025 at 12:15 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unimaidconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'adyems', '$2y$10$O74CO2jnEELCjlxL5ZA54OO9smRWBhp9r7EblavG0WkFMl39pTMdC');

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ads`
--

INSERT INTO `ads` (`id`, `image_url`, `link_url`, `status`) VALUES
(0, 'uploads/67b7096c4e320-images.jpg', 'https://amazon.com', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `anonymous_posts`
--

CREATE TABLE `anonymous_posts` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anonymous_posts`
--

INSERT INTO `anonymous_posts` (`id`, `content`, `created_at`, `image_path`) VALUES
(1, 'pooos', '2025-02-19 09:47:19', NULL),
(2, 'this is an anoyimous', '2025-02-19 09:49:44', 'uploads/67b5a9387de3f.jpg'),
(3, 'this is an anoyimous', '2025-02-19 09:54:31', 'uploads/67b5aa57a0801.jpg'),
(4, 'this is an anoyimous', '2025-02-19 09:54:57', 'uploads/67b5aa71b74e2.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `chat_users`
--

CREATE TABLE `chat_users` (
  `user_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `user_id`, `comment_text`, `created_at`) VALUES
(1, 8, 1, 'hello', '2025-01-21 09:27:28'),
(2, 8, 1, 'hi', '2025-01-21 09:27:46'),
(3, 8, 1, 'hi', '2025-01-21 09:47:42'),
(4, 8, 1, 'hi', '2025-01-21 09:48:40'),
(5, 8, 1, 'hi', '2025-01-21 09:49:12'),
(6, 8, 1, 'hi', '2025-01-21 10:07:57'),
(7, 8, 1, 'hi', '2025-01-21 10:09:11'),
(8, 8, 4, 'hi', '2025-01-21 10:17:41'),
(9, 8, 4, 'hi', '2025-01-21 10:18:11'),
(10, 8, 4, 'hi', '2025-01-21 10:19:43'),
(11, 8, 1, 'hi', '2025-01-21 10:20:35'),
(12, 9, 1, 'hh', '2025-01-21 10:20:43'),
(13, 8, 1, 'hello', '2025-01-21 10:26:41'),
(14, 8, 1, 'hello', '2025-01-21 10:32:08'),
(15, 8, 1, 'wowo', '2025-01-21 15:50:39'),
(16, 8, 1, 'haba', '2025-01-21 15:53:57'),
(17, 8, 1, 'hello', '2025-01-21 16:03:48'),
(18, 7, 1, 'this is not nice na', '2025-01-21 16:05:38'),
(19, 9, 1, 'skala this is you', '2025-01-21 16:06:52'),
(20, 10, 1, 'hello', '2025-01-21 17:18:28'),
(21, 10, 1, 'hello', '2025-01-21 17:18:28'),
(22, 5, 1, 'hello nigga', '2025-01-23 12:29:57'),
(23, 5, 1, 'hello nigga', '2025-01-23 12:29:57'),
(28, 19, 1, 'hello', '2025-01-23 19:35:07'),
(29, 19, 1, 'hello', '2025-01-23 19:35:08'),
(34, 19, 1, 'nothing here', '2025-01-23 23:21:33'),
(35, 19, 1, 'nothing here', '2025-01-23 23:21:34'),
(36, 18, 1, 'this is check', '2025-01-23 23:35:17'),
(37, 18, 1, 'this is check', '2025-01-23 23:35:17'),
(38, 15, 1, 'nice one', '2025-01-23 23:37:46'),
(39, 15, 1, 'nice one', '2025-01-23 23:37:46'),
(40, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(41, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(42, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(43, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(44, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(45, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(46, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(47, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(48, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(49, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(50, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(51, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(52, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(53, 19, 9, 'commn add', '2025-01-23 23:43:20'),
(54, 18, 1, 'noooooooooo', '2025-01-23 23:44:58'),
(55, 18, 1, 'noooooooooo', '2025-01-23 23:44:58'),
(56, 18, 9, 'noeee', '2025-01-23 23:48:00'),
(57, 18, 9, 'the 6th', '2025-01-23 23:51:36'),
(58, 18, 1, 'helllllllllllllllllllo', '2025-01-24 00:03:16'),
(59, 18, 4, 'helllllllllllllllllllo', '2025-01-24 00:03:16'),
(60, 18, 9, 'the 9th', '2025-01-24 00:05:44'),
(61, 18, 9, '10th', '2025-01-24 00:06:35'),
(62, 18, 9, '1th', '2025-01-24 00:07:59'),
(63, 15, 9, 'now nah', '2025-01-24 00:08:23'),
(64, 10, 9, 'only two comments', '2025-01-24 00:08:45'),
(65, 19, 3, 'now nah', '2025-01-24 08:18:35'),
(66, 19, 1, 'this is a commment to admin from simon', '2025-01-24 12:57:09'),
(67, 19, 9, 'okay na', '2025-01-25 21:16:04'),
(68, 19, 1, 'now', '2025-01-25 21:42:06'),
(69, 19, 9, 'now', '2025-01-25 21:42:06'),
(70, 19, 1, 'haba', '2025-01-25 21:42:16'),
(71, 19, 9, 'haba', '2025-01-25 21:42:16'),
(72, 19, 1, 'naaaaaaaaaaa', '2025-01-25 21:43:24'),
(73, 19, 9, 'naaaaaaaaaaa', '2025-01-25 21:43:24'),
(74, 19, 1, 'no', '2025-01-25 21:44:48'),
(75, 19, 9, 'no', '2025-01-25 21:44:49'),
(76, 18, 1, 'theh', '2025-01-25 21:46:51'),
(77, 18, 9, 'theh', '2025-01-25 21:46:51'),
(78, 15, 1, '4th', '2025-01-25 21:50:45'),
(79, 15, 9, '4th', '2025-01-25 21:50:45'),
(80, 15, 1, 'no', '2025-01-25 21:52:01'),
(81, 15, 9, 'no', '2025-01-25 21:52:01'),
(82, 14, 1, 'no', '2025-01-25 21:57:58'),
(83, 14, 9, 'no', '2025-01-25 21:57:58'),
(84, 9, 1, 'noe', '2025-01-25 21:59:05'),
(85, 9, 3, 'noe', '2025-01-25 21:59:05'),
(86, 19, 1, 'henenenen', '2025-01-26 11:25:27'),
(87, 19, 3, 'henenenen', '2025-01-26 11:25:27'),
(88, 15, 1, '8th none', '2025-01-26 11:31:10'),
(89, 15, 1, '8th none', '2025-01-26 11:31:10'),
(90, 14, 1, 'nooooooooooooo', '2025-01-26 11:32:46'),
(91, 14, 1, 'nooooooooooooo', '2025-01-26 11:32:46'),
(92, 9, 1, 'hooo', '2025-01-26 11:38:50'),
(93, 15, 1, 'no na', '2025-01-26 11:40:45'),
(94, 15, 1, 'no na', '2025-01-26 11:40:45'),
(95, 15, 1, 'no na', '2025-01-26 11:41:06'),
(96, 15, 1, 'no na', '2025-01-26 11:41:06'),
(97, 15, 1, 'no na', '2025-01-26 11:41:15'),
(98, 15, 1, 'no na', '2025-01-26 11:41:15'),
(99, 19, 1, 'let go', '2025-01-26 14:30:46'),
(100, 19, 1, 'let go', '2025-01-26 14:30:46'),
(101, 19, 1, 'no', '2025-01-26 14:34:56'),
(102, 19, 1, 'no', '2025-01-26 14:34:57'),
(103, 18, 1, 'haabababab', '2025-01-26 14:37:30'),
(104, 18, 1, 'haabababab', '2025-01-26 14:37:31'),
(105, 19, 1, '36th', '2025-01-26 14:38:48'),
(106, 19, 1, '36th', '2025-01-26 14:38:49'),
(107, 18, 1, 'haabababab', '2025-01-26 14:39:17'),
(108, 18, 1, 'haabababab', '2025-01-26 14:39:17'),
(109, 19, 1, '36th', '2025-01-26 14:40:12'),
(110, 19, 1, '36th', '2025-01-26 14:40:12'),
(111, 19, 1, '36th', '2025-01-26 14:43:07'),
(112, 19, 1, '36th', '2025-01-26 14:43:08'),
(113, 18, 1, 'ne', '2025-01-26 14:44:01'),
(114, 19, 1, '19th', '2025-01-26 14:44:09'),
(115, 15, 1, 'no', '2025-01-26 14:44:43'),
(116, 15, 1, 'no', '2025-01-26 14:44:43'),
(117, 19, 1, '43', '2025-01-26 14:45:32'),
(118, 19, 1, '44', '2025-01-26 14:45:42'),
(119, 18, 1, 'no', '2025-01-26 15:02:40'),
(120, 18, 1, 'no', '2025-01-26 15:02:40'),
(121, 18, 1, 'haabababab', '2025-01-26 15:10:36'),
(122, 18, 1, 'haabababab', '2025-01-26 15:10:36'),
(123, 19, 1, 'haba', '2025-01-26 15:11:25'),
(124, 19, 1, 'haba', '2025-01-26 15:11:25'),
(125, 20, 1, 'helooo', '2025-01-26 15:15:23'),
(126, 20, 7, 'helooo', '2025-01-26 15:15:24'),
(127, 20, 1, 'hello', '2025-01-26 15:20:10'),
(128, 20, 7, 'hello', '2025-01-26 15:20:10'),
(129, 20, 1, 'hababababab', '2025-01-26 15:22:25'),
(130, 20, 7, 'hababababab', '2025-01-26 15:22:25'),
(131, 20, 1, 'last', '2025-01-26 15:25:13'),
(132, 20, 7, 'last', '2025-01-26 15:25:13'),
(133, 20, 1, 'noooo', '2025-01-26 15:30:59'),
(134, 19, 1, 'the 47th', '2025-01-26 15:31:16'),
(135, 15, 1, '18th', '2025-01-26 15:36:19'),
(136, 15, 7, '18th', '2025-01-26 15:36:19'),
(137, 15, 1, '20th', '2025-01-26 15:37:29'),
(138, 13, 1, 'firest', '2025-01-26 15:38:02'),
(139, 20, 1, 'my 10th comment was sent', '2025-01-26 15:47:32'),
(140, 18, 1, '23rd', '2025-01-29 15:36:26'),
(141, 21, 1, 'hello', '2025-02-12 00:38:55'),
(142, 22, 1, 'yo', '2025-02-12 14:52:58'),
(143, 23, 1, 'oga shift', '2025-02-16 15:01:14'),
(144, 23, 1, 'this is ellies comment', '2025-02-17 09:00:36'),
(145, 24, 1, 'this s billions', '2025-02-18 16:16:23'),
(146, 25, 1, 'thats great', '2025-02-18 16:18:42');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_description` text NOT NULL,
  `event_date` datetime NOT NULL,
  `event_location` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_description`, `event_date`, `event_location`, `created_at`, `updated_at`, `event_image`) VALUES
(5, 'adyems', 'Expedita quas repell', '2003-06-02 22:52:00', 'Laudantium nesciunt', '2025-01-24 09:47:48', '2025-01-24 10:13:08', 'uploads/event_images/images (1) - Copy.jpg'),
(6, 'Jennifer Trevino', 'Sunt quia rem culpa', '2001-04-15 03:58:00', 'Libero rerum delectu', '2025-01-24 09:48:35', '2025-01-24 09:48:35', NULL),
(7, 'Eleanor Brewer', 'Sed natus consectetu', '1988-11-06 11:16:00', 'Optio perferendis n', '2025-01-24 09:48:59', '2025-01-24 09:48:59', NULL),
(9, '', '', '0000-00-00 00:00:00', '', '2025-01-24 09:51:30', '2025-01-24 09:53:11', ''),
(10, 'valantine blast', 'come find babe', '2025-02-16 17:00:00', 'lfc bama road', '2025-02-16 15:04:27', '2025-02-16 15:04:27', 'uploads/event_images/papa.jfif');

-- --------------------------------------------------------

--
-- Table structure for table `fcomment`
--

CREATE TABLE `fcomment` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `comments_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fcomment`
--

INSERT INTO `fcomment` (`id`, `post_id`, `user_id`, `content`, `timestamp`, `comments_id`, `image_path`, `parent_comment_id`, `parent_id`) VALUES
(1, 1, 13, 'hrhrhr', '2025-02-15 14:15:21', NULL, NULL, NULL, NULL),
(2, 1, 13, 'nennnd', '2025-02-15 14:15:33', NULL, NULL, NULL, NULL),
(3, 3, 13, 'haba', '2025-02-15 21:17:51', NULL, NULL, NULL, NULL),
(4, 3, 13, 'hhh', '2025-02-15 21:18:06', NULL, NULL, NULL, NULL),
(5, 3, 13, 'hhh', '2025-02-15 21:20:30', NULL, NULL, NULL, NULL),
(6, 3, 13, 'hhh', '2025-02-15 21:24:35', NULL, NULL, NULL, NULL),
(7, 3, 13, 'hhh', '2025-02-15 21:27:17', NULL, NULL, NULL, NULL),
(8, 3, 13, 'hhh', '2025-02-15 21:28:02', NULL, NULL, NULL, NULL),
(9, 3, 13, 'hhh', '2025-02-15 21:28:52', NULL, NULL, NULL, NULL),
(10, 1, 13, 'u dey mad', '2025-02-16 13:11:26', NULL, NULL, NULL, NULL),
(11, 3, 13, 'shshs', '2025-02-16 13:46:45', NULL, 'uploads/comments/67b1ec45dedf1.jpeg', NULL, NULL),
(12, 3, 13, 'shshs', '2025-02-16 13:50:08', NULL, 'uploads/comments/67b1ed104c822.jpeg', NULL, NULL),
(13, 3, 13, 'shshs', '2025-02-16 13:50:45', NULL, 'uploads/comments/67b1ed35c4d2c.jpeg', NULL, NULL),
(14, 3, 13, 'shshs', '2025-02-16 14:00:48', NULL, 'uploads/comments/67b1ef90409dd.jpeg', NULL, NULL),
(15, 3, 13, 'shshs', '2025-02-16 14:01:49', NULL, 'uploads/comments/67b1efcda5a5c.jpeg', NULL, NULL),
(16, 3, 13, 'thats a lie', '2025-02-16 14:02:00', NULL, NULL, NULL, 14),
(17, 3, 13, 'thats a lie', '2025-02-16 14:04:35', NULL, NULL, NULL, 14),
(18, 3, 13, 'thats a lie', '2025-02-16 14:06:21', NULL, NULL, NULL, 14),
(19, 6, 9, 'oga its a lie', '2025-02-16 14:57:25', NULL, NULL, NULL, NULL),
(20, 6, 13, 'nah lie', '2025-02-17 09:18:40', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feed`
--

CREATE TABLE `feed` (
  `feed_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `feed_content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `link_url` varchar(255) DEFAULT NULL,
  `image_paths` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed`
--

INSERT INTO `feed` (`feed_id`, `user_id`, `feed_content`, `created_at`, `link_url`, `image_paths`, `is_read`) VALUES
(1, 7, 'Est pariatur Vitae \r\n<a href=\"https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603\" target=\"_blank\">https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603</a>', '2025-01-26 20:19:53', NULL, NULL, 0),
(2, 7, 'Est pariatur Vitae \r\n<a href=\"https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603\" target=\"_blank\">https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603</a>', '2025-01-26 20:21:20', NULL, NULL, 0),
(3, 7, 'Est pariatur Vitae \r\n<a href=\"https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603\" target=\"_blank\">https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603</a>', '2025-01-26 20:22:06', NULL, NULL, 0),
(4, 7, 'Est pariatur Vitae \r\n<a href=\"https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603\" target=\"_blank\">https://www.vectorstock.com/royalty-free-vector/connect-logo-design-template-elements-o-signal-vector-33287603</a>', '2025-01-26 20:23:36', NULL, NULL, 0),
(5, 7, 'hhh', '2025-01-26 20:23:51', NULL, NULL, 0),
(6, 7, 'Tempora tempor beata', '2025-01-26 20:28:10', NULL, NULL, 0),
(7, 7, 'Sed consequatur Min', '2025-01-26 20:28:57', NULL, NULL, 0),
(8, 9, 'Nulla excepturi natu', '2025-01-26 20:39:36', NULL, NULL, 1),
(9, 9, 'Nemo libero hic et i', '2025-01-26 20:45:36', 'https://www.nevimeroheqyrav.ws', 'uploads/hello - Copy - Copy.jpg,uploads/hello - Copy (2).jpg,uploads/images (1) - Copy.jpg', 1),
(10, 9, 'Et molestiae facilis', '2025-01-26 20:57:56', 'https://www.rurarosaho.me.uk', 'uploads/hello - Copy - Copy.jpg,uploads/hello - Copy (2).jpg,uploads/images (1) - Copy.jpg,uploads/images (1).jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `feed_links`
--

CREATE TABLE `feed_links` (
  `link_id` int(11) NOT NULL,
  `feed_id` int(11) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed_links`
--

INSERT INTO `feed_links` (`link_id`, `feed_id`, `link_url`) VALUES
(1, 6, 'https://www.dihi.org'),
(2, 7, 'https://www.tucemymarepy.cm'),
(3, 8, 'https://www.foxofujy.co.uk');

-- --------------------------------------------------------

--
-- Table structure for table `feed_media`
--

CREATE TABLE `feed_media` (
  `media_id` int(11) NOT NULL,
  `feed_id` int(11) DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed_media`
--

INSERT INTO `feed_media` (`media_id`, `feed_id`, `media_url`, `media_type`) VALUES
(1, 4, 'uploads/images (1) - Copy.jpg', 'image/jpeg'),
(2, 5, 'uploads/hello.jpg', 'image/jpeg'),
(3, 6, 'uploads/67969adab1d43.jpg', 'image'),
(4, 7, 'uploads/67969b09b22bb.jpg', 'image'),
(5, 7, 'uploads/67969b09bf989.jpg', 'image'),
(6, 7, 'uploads/67969b09d8320.jpg', 'image'),
(7, 8, 'uploads/67969d88ee366.jpg', 'image'),
(8, 8, 'uploads/67969d892da26.jpg', 'image'),
(9, 8, 'uploads/67969d893b0e6.jpg', 'image');

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `followers`
--

INSERT INTO `followers` (`follower_id`, `following_id`) VALUES
(1, 1),
(4, 4),
(6, 1),
(6, 3),
(6, 4),
(6, 5),
(6, 6),
(7, 1),
(7, 4),
(9, 1),
(9, 3),
(9, 4),
(9, 5),
(9, 6),
(9, 7),
(10, 1),
(10, 9),
(13, 1),
(13, 11);

-- --------------------------------------------------------

--
-- Table structure for table `fpost`
--

CREATE TABLE `fpost` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fpost`
--

INSERT INTO `fpost` (`id`, `user_id`, `content`, `timestamp`, `image_path`) VALUES
(1, 13, 'hththth', '2025-02-15 12:37:04', NULL),
(2, 13, 'hskhhhhhhhhh', '2025-02-15 14:15:56', NULL),
(3, 13, 'hskhhhhhhhhh', '2025-02-15 20:02:44', NULL),
(4, 13, 'see werey', '2025-02-16 13:37:31', 'uploads/67b1ea1baf3dc.jpeg'),
(5, 13, 'see werey', '2025-02-16 13:44:55', 'uploads/67b1ebd7d298d.jpeg'),
(6, 9, 'i loe real madrid', '2025-02-16 14:56:49', 'uploads/67b1fcb11cba1.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `freplies`
--

CREATE TABLE `freplies` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `reply_to_reply_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `freplies`
--

INSERT INTO `freplies` (`id`, `comment_id`, `user_id`, `content`, `timestamp`, `reply_to_reply_id`) VALUES
(1, 2, 13, 'hshsh', '2025-02-15 14:15:47', NULL),
(2, 2, 13, 'oga', '2025-02-15 20:03:03', NULL),
(3, 2, 13, 'oga', '2025-02-15 20:10:31', NULL),
(4, 2, 13, 'oga', '2025-02-15 20:13:06', NULL),
(5, 2, 13, 'oga', '2025-02-15 20:14:28', NULL),
(6, 2, 13, 'oga', '2025-02-15 20:14:35', NULL),
(7, 2, 13, 'oga', '2025-02-15 21:16:23', NULL),
(8, 2, 13, 'oga', '2025-02-15 21:17:23', NULL),
(9, 2, 13, 'hhh', '2025-02-15 21:29:00', NULL),
(10, 1, 13, 'noooooooooooo', '2025-02-16 13:11:38', NULL),
(11, 1, 13, 'noooooooooooo', '2025-02-16 13:21:28', NULL),
(12, 1, 13, 'noooooooooooo', '2025-02-16 13:25:51', NULL),
(13, 1, 13, 'noooooooooooo', '2025-02-16 13:26:22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `creator_id` int(11) NOT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `can_post` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `creator_id`, `is_public`, `can_post`) VALUES
(2, 'Isadora Velasquez', 'Ut corrupti corpori', 9, 1, 1),
(3, 'computer engineering', 'this is only for computer engineers', 1, 1, 1),
(4, 'library science departement ', 'this is a group for only lib students', 1, 1, 1),
(5, 'political science departement group', 'erdtfvgybuhnji', 9, 1, 1),
(6, 'civil engr group', 'this is only for civl students', 13, 1, 1),
(7, 'MEE discusssion forum', 'gbgkkkkkkkkkkkkkkkkk', 13, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`group_id`, `user_id`, `joined_at`) VALUES
(2, 1, '2025-01-24 13:11:39'),
(2, 13, '2025-02-15 13:25:08'),
(3, 3, '2025-02-18 17:14:44'),
(3, 9, '2025-01-25 21:18:45'),
(3, 13, '2025-02-12 15:44:40'),
(4, 1, '2025-01-24 14:22:05'),
(5, 9, '2025-02-16 16:17:36'),
(6, 13, '2025-02-17 10:30:02'),
(7, 13, '2025-02-20 12:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `group_notifications`
--

CREATE TABLE `group_notifications` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sender_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_posts`
--

CREATE TABLE `group_posts` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_text` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_posts`
--

INSERT INTO `group_posts` (`id`, `group_id`, `user_id`, `post_text`, `image_url`, `created_at`) VALUES
(1, 2, 9, 'Maxime sit repellend', 'uploads/679382f21996e-hello.jpg', '2025-01-24 13:09:22'),
(2, 4, 1, 'Nulla non Nam laboru', 'uploads/67939429d7767-images (1) - Copy.jpg', '2025-01-24 14:22:49'),
(3, 3, 13, 'yooooo', 'uploads/67acb3eaccbfa-download (3).jpeg', '2025-02-12 15:44:58'),
(4, 5, 9, 'nah me be the first', 'uploads/67b201b8b1ccf-images (1) - Copy.jpg', '2025-02-16 16:18:16'),
(5, 3, 3, 'i am here', 'uploads/67b4b20ee973b-hello - Copy - Copy.jpg', '2025-02-18 17:15:10'),
(6, 7, 13, 'am i welcome', '', '2025-02-20 12:01:55');

-- --------------------------------------------------------

--
-- Table structure for table `handouts`
--

CREATE TABLE `handouts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `download_link` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `type` enum('handout','pastQ','summary') NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'past_questions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `handouts`
--

INSERT INTO `handouts` (`id`, `title`, `image`, `download_link`, `note`, `type`, `uploaded_at`, `category`) VALUES
(1, 'Consequatur Volupta', 'uploads/download (4).jpeg', 'https://www.kagavymewowofu.ca', 'Proident quod omnis', 'handout', '2025-02-14 11:29:33', 'past_questions'),
(2, 'Accusamus doloribus ', 'uploads/download (4).jpeg', 'https://www.vomo.mobi', 'Ad maiores in aut na', 'handout', '2025-02-14 12:03:42', 'handouts');

-- --------------------------------------------------------

--
-- Table structure for table `loan_requests`
--

CREATE TABLE `loan_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `loan_amount` float NOT NULL,
  `status` varchar(20) NOT NULL,
  `next_payment_date` datetime NOT NULL,
  `amount_to_pay` float NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_requests`
--

INSERT INTO `loan_requests` (`id`, `user_id`, `loan_amount`, `status`, `next_payment_date`, `amount_to_pay`, `request_date`) VALUES
(1, 9, 0, 'Pending', '0000-00-00 00:00:00', 0, '2025-01-25 08:58:25'),
(2, 9, 0, 'Approved', '0000-00-00 00:00:00', 0, '2025-01-25 08:58:34'),
(3, 9, 1000, 'Rejected', '2025-02-01 10:01:28', 1400, '2025-01-25 09:01:28'),
(4, 9, 10000, 'Pending', '2025-02-01 10:03:31', 14000, '2025-01-25 09:03:31'),
(5, 9, 10000, 'Approved', '2025-02-23 15:45:35', 14000, '2025-02-16 14:45:35');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_posts`
--

CREATE TABLE `marketplace_posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`images`)),
  `status` enum('pending','approved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marketplace_posts`
--

INSERT INTO `marketplace_posts` (`post_id`, `user_id`, `item_name`, `description`, `price`, `images`, `status`, `created_at`) VALUES
(1, 9, 'Dexter Bradley', 'Sed unde quia aut ea', 920.00, '[]', 'approved', '2025-01-24 08:33:09'),
(2, 9, 'Xavier Morton', 'Labore magni minus a', 332.00, '[]', 'approved', '2025-01-24 08:44:46'),
(3, 9, 'Xavier Morton', 'Labore magni minus a', 332.00, '[]', 'pending', '2025-01-24 08:44:50'),
(4, 9, 'Xavier Morton', 'Labore magni minus a', 332.00, '[\"uploads\\/marketplace_images\\/hello - Copy (2).jpg\",\"uploads\\/marketplace_images\\/images (1) - Copy.jpg\"]', 'approved', '2025-01-24 08:46:09'),
(5, 9, 'Brenna Mckay', 'Optio duis ipsam ut', 522.00, '[\"uploads\\/marketplace_images\\/IMG_20241222_101120_203.jpg\",\"uploads\\/marketplace_images\\/IMG_20241222_101224_864.jpg\",\"uploads\\/marketplace_images\\/office-buildings-with-modern-architecture_107420-95734.jpg\",\"uploads\\/marketplace_images\\/ru4.jpg\"]', 'approved', '2025-01-24 08:53:18'),
(6, 9, 'Abdul Sutton', 'Laboris dolor quae v', 665.00, '[\"uploads\\/marketplace_images\\/hello - Copy (2).jpg\",\"uploads\\/marketplace_images\\/hello.jpg\",\"uploads\\/marketplace_images\\/images (1) - Copy.jpg\",\"uploads\\/marketplace_images\\/images (1).jpg\"]', 'approved', '2025-01-24 09:09:43'),
(7, 9, 'Axel Barber', 'Exercitationem dolor', 403.00, '[]', 'pending', '2025-01-24 09:31:33'),
(8, 1, 'iphone 15 pro max', 'very new 2 weeks used contact me on 09061512740', 400000.00, '[\"uploads\\/marketplace_images\\/IMG_20241222_101120_203.jpg\",\"uploads\\/marketplace_images\\/IMG_20241225_111843_788.jpg\",\"uploads\\/marketplace_images\\/IMG_20241225_111906_251.jpg\",\"uploads\\/marketplace_images\\/papa.jfif\"]', 'approved', '2025-01-24 13:16:17'),
(9, 9, 'best', 'Pariatur Voluptatem', 655.00, '[\"uploads\\/marketplace_images\\/IMG_20241225_111845_479.jpg\"]', 'approved', '2025-02-16 15:10:10'),
(10, 13, 'ellie phone', 'Officia qui laudanti', 99999999.99, '[\"uploads\\/marketplace_images\\/hello - Copy (2).jpg\",\"uploads\\/marketplace_images\\/hello - Copy.jpg\",\"uploads\\/marketplace_images\\/hello.jpg\",\"uploads\\/marketplace_images\\/images (1) - Copy.jpg\",\"uploads\\/marketplace_images\\/images (1).jpg\"]', 'approved', '2025-02-17 09:20:01');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','read') DEFAULT 'pending',
  `image_path` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `created_at`, `timestamp`, `status`, `image_path`, `image_url`) VALUES
(1, 6, 5, 'hello', '2025-01-21 22:52:27', '2025-01-21 22:58:39', 'pending', NULL, NULL),
(2, 6, 1, 'adyems how fr', '2025-01-21 22:54:20', '2025-01-21 22:58:39', 'read', NULL, NULL),
(3, 6, 5, 'hey', '2025-01-21 23:00:37', '2025-01-21 23:00:37', 'pending', NULL, NULL),
(4, 6, 5, 'heloooo', '2025-01-21 23:05:41', '2025-01-21 23:05:41', 'pending', NULL, NULL),
(5, 6, 5, 'hey', '2025-01-21 23:08:56', '2025-01-21 23:08:56', 'pending', NULL, NULL),
(6, 6, 5, 'hey', '2025-01-21 23:09:35', '2025-01-21 23:09:35', 'pending', NULL, NULL),
(7, 6, 5, 'hey', '2025-01-21 23:11:03', '2025-01-21 23:11:03', 'pending', NULL, NULL),
(8, 6, 5, 'hey', '2025-01-21 23:14:09', '2025-01-21 23:14:09', 'pending', NULL, NULL),
(9, 6, 4, 'hello', '2025-01-21 23:14:25', '2025-01-21 23:14:25', 'pending', NULL, NULL),
(10, 6, 4, 'how was your da buddy', '2025-01-21 23:18:12', '2025-01-21 23:18:12', '', NULL, NULL),
(11, 1, 6, 'am good sir this is adyems confriming message send', '2025-01-21 23:20:30', '2025-01-21 23:20:30', '', NULL, NULL),
(12, 6, 1, 'oga your name is billion, or better still adyems billy', '2025-01-21 23:28:27', '2025-01-21 23:28:27', '', NULL, NULL),
(13, 6, 1, 'i wan check the notification', '2025-01-21 23:39:19', '2025-01-21 23:39:19', '', NULL, NULL),
(14, 1, 6, 'okay sir', '2025-01-21 23:42:55', '2025-01-21 23:42:55', '', NULL, NULL),
(15, 6, 1, 'check again', '2025-01-21 23:45:24', '2025-01-21 23:45:24', '', NULL, NULL),
(16, 1, 6, 'ok', '2025-01-21 23:49:13', '2025-01-21 23:49:13', '', NULL, NULL),
(17, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:54:13', '2025-01-23 18:54:13', '', NULL, NULL),
(18, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:55:10', '2025-01-23 18:55:10', '', NULL, NULL),
(19, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:55:24', '2025-01-23 18:55:24', '', NULL, NULL),
(20, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:55:35', '2025-01-23 18:55:35', '', NULL, NULL),
(21, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:56:43', '2025-01-23 18:56:43', '', NULL, NULL),
(22, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:56:53', '2025-01-23 18:56:53', '', NULL, NULL),
(23, 9, 1, 'this is a welcome message from adyems charity giy how fr\r\n', '2025-01-23 18:57:02', '2025-01-23 18:57:02', '', NULL, NULL),
(24, 1, 9, 'okay this is adyems billions i have recieved your message', '2025-01-23 19:02:41', '2025-01-23 19:02:41', '', NULL, NULL),
(25, 10, 9, 'this is adyems', '2025-01-23 19:32:48', '2025-01-23 19:32:48', '', NULL, NULL),
(26, 9, 10, 'good this is charity i have received your message', '2025-01-23 19:36:43', '2025-01-23 19:36:43', '', NULL, NULL),
(27, 13, 5, 'yooo', '2025-02-12 14:48:15', '2025-02-12 14:48:15', '', NULL, NULL),
(28, 13, 4, 'hello brother this is adyems i just logged in oo', '2025-02-16 15:33:25', '2025-02-16 15:33:25', '', NULL, NULL),
(29, 13, 9, 'helo charity this is adyems\r\n', '2025-02-16 15:33:57', '2025-02-16 15:33:57', '', NULL, NULL),
(30, 9, 13, 'okay i have seen it thank you', '2025-02-16 15:34:35', '2025-02-16 15:34:35', '', NULL, NULL),
(31, 13, 3, 'hdhdhd', '2025-02-16 19:49:23', '2025-02-16 19:49:23', '', NULL, NULL),
(32, 13, 3, 'hdhdhd', '2025-02-16 19:50:17', '2025-02-16 19:50:17', '', 'uploads/images/67b24179ceb33_images (1) - Copy.jpg', NULL),
(33, 13, 3, 'hdhdhd', '2025-02-16 19:54:04', '2025-02-16 19:54:04', '', 'uploads/images/67b2425c69b30_images (1) - Copy.jpg', NULL),
(34, 13, 3, 'hon', '2025-02-16 20:02:15', '2025-02-16 20:02:15', '', NULL, NULL),
(35, 13, 3, 'hon', '2025-02-16 20:04:09', '2025-02-16 20:04:09', '', NULL, NULL),
(36, 13, 3, 'hon', '2025-02-16 20:04:09', '2025-02-16 20:04:09', '', NULL, 'uploads/67b244b9c4c540.16069781.jpg'),
(37, 13, 3, 'hon', '2025-02-16 20:04:43', '2025-02-16 20:04:43', '', NULL, NULL),
(38, 13, 3, 'hon', '2025-02-16 20:04:43', '2025-02-16 20:04:43', '', NULL, 'uploads/67b244dbb53c45.48616195.jpg'),
(39, 13, 5, 'hhh', '2025-02-16 20:05:27', '2025-02-16 20:05:27', '', NULL, NULL),
(40, 13, 5, 'hhh', '2025-02-16 20:05:38', '2025-02-16 20:05:38', '', NULL, NULL),
(41, 13, 5, 'hhh', '2025-02-16 20:05:50', '2025-02-16 20:05:50', '', NULL, NULL),
(42, 13, 5, 'shhhhhhhh', '2025-02-16 20:06:04', '2025-02-16 20:06:04', '', 'uploads/images/67b2452ce72ee_images (1) - Copy.jpg', NULL),
(43, 13, 5, 'shhhhhhhh', '2025-02-16 20:06:20', '2025-02-16 20:06:20', '', 'uploads/images/67b2453c30eee_images (1) - Copy.jpg', NULL),
(44, 13, 5, 'shhhhhhhh', '2025-02-16 20:09:43', '2025-02-16 20:09:43', '', 'uploads/images/67b246073b3d8_images (1) - Copy.jpg', NULL),
(45, 13, 5, 'helo', '2025-02-16 20:10:06', '2025-02-16 20:10:06', '', NULL, NULL),
(46, 13, 5, 'hhhhh', '2025-02-16 20:10:14', '2025-02-16 20:10:14', '', NULL, NULL),
(47, 13, 5, 'hhhhh', '2025-02-16 20:10:22', '2025-02-16 20:10:22', '', NULL, NULL),
(48, 13, 5, 'hhhhh', '2025-02-16 20:13:07', '2025-02-16 20:13:07', '', NULL, NULL),
(49, 13, 5, 'hello', '2025-02-16 20:13:18', '2025-02-16 20:13:18', '', NULL, NULL),
(50, 13, 3, 'Quis veniam sint co', '2025-02-16 20:14:05', '2025-02-16 20:14:05', '', NULL, NULL),
(51, 13, 3, 'Quis veniam sint co', '2025-02-16 20:15:15', '2025-02-16 20:15:15', '', NULL, NULL),
(52, 13, 3, 'Quos Nam fugit mini', '2025-02-16 20:15:31', '2025-02-16 20:15:31', '', 'uploads/images/67b24763e7752_images (1) - Copy.jpg', NULL),
(53, 13, 3, '\r\nArchitecto quibusdam\r\nArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdam', '2025-02-16 20:15:54', '2025-02-16 20:15:54', '', NULL, NULL),
(54, 13, 3, '\r\nArchitecto quibusdam\r\nArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdam', '2025-02-16 20:16:11', '2025-02-16 20:16:11', '', NULL, NULL),
(55, 13, 3, '\r\nArchitecto quibusdam\r\nArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdamArchitecto quibusdam', '2025-02-16 20:17:30', '2025-02-16 20:17:30', '', NULL, NULL),
(56, 13, 6, 'how far', '2025-02-16 20:22:13', '2025-02-16 20:22:13', '', NULL, NULL),
(57, 13, 10, 'this is ellie', '2025-02-17 09:05:32', '2025-02-17 09:05:32', '', NULL, NULL),
(58, 13, 10, 'guy i want buy this house\r\n', '2025-02-17 09:05:48', '2025-02-17 09:05:48', '', 'uploads/images/67b2fbec8d6a7_images (1) - Copy.jpg', NULL),
(59, 13, 10, 'this is ellie', '2025-02-17 09:06:02', '2025-02-17 09:06:02', '', NULL, NULL),
(60, 13, 9, 'helloi want gy', '2025-02-17 09:06:34', '2025-02-17 09:06:34', '', 'uploads/images/67b2fc1a7eeda_images (1) - Copy.jpg', NULL),
(61, 13, 9, 'how e go be', '2025-02-17 09:06:41', '2025-02-17 09:06:41', '', NULL, NULL),
(62, 3, 13, 'good', '2025-02-18 16:14:06', '2025-02-18 16:14:06', '', NULL, NULL),
(63, 9, 13, 'THIS IS GANYU', '2025-02-20 10:48:44', '2025-02-20 10:48:44', '', NULL, NULL),
(64, 13, 10, 'ghhhhhhhh', '2025-02-20 10:50:52', '2025-02-20 10:50:52', '', 'uploads/images/67b7090c2d332_hello - Copy.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `post_id` int(11) NOT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `reply_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `post_id`, `comment_id`, `reply_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, '', 9, NULL, NULL, 'New reply to your comment on post #9', 1, '2025-01-21 16:16:49'),
(2, 4, '', 9, NULL, NULL, 'New reply to your comment on post #9', 0, '2025-01-21 16:18:50'),
(3, 6, 'comment', 10, NULL, NULL, 'New comment on your post.', 1, '2025-01-21 17:18:28'),
(4, 7, '', 8, NULL, NULL, 'New reply to your comment on post #8', 1, '2025-01-22 15:13:06'),
(5, 4, 'comment', 5, NULL, NULL, 'New comment on your post.', 0, '2025-01-23 12:29:57'),
(6, 9, '', 8, NULL, NULL, 'New reply to your comment on post #8', 1, '2025-01-23 18:49:27'),
(7, 9, 'comment', 15, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 18:52:38'),
(8, 9, 'comment', 15, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 19:33:41'),
(9, 10, 'comment', 19, NULL, NULL, 'New comment on your post.', 0, '2025-01-23 19:35:08'),
(10, 9, 'comment', 18, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 23:14:13'),
(11, 9, 'comment', 18, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 23:21:07'),
(12, 10, 'comment', 19, NULL, NULL, 'New comment on your post.', 0, '2025-01-23 23:21:34'),
(13, 9, 'comment', 18, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 23:35:17'),
(14, 9, 'comment', 15, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 23:37:46'),
(15, 9, 'comment', 18, NULL, NULL, 'New comment on your post.', 1, '2025-01-23 23:44:59'),
(16, 1, '', 19, NULL, NULL, 'New reply to your comment on post #19', 1, '2025-01-26 14:53:25'),
(17, 7, '', 13, NULL, NULL, 'New reply to your comment on post #13', 1, '2025-01-26 15:40:12');

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE `page_views` (
  `id` int(11) NOT NULL,
  `page` varchar(255) NOT NULL,
  `views` int(11) DEFAULT 0,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_views`
--

INSERT INTO `page_views` (`id`, `page`, `views`, `timestamp`) VALUES
(1, 'dashboard.php', 29, '2025-01-27 17:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `past_questions`
--

CREATE TABLE `past_questions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `download_link` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `type` enum('handout','pastQ','summary') NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'past_questions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `past_questions`
--

INSERT INTO `past_questions` (`id`, `title`, `image`, `download_link`, `note`, `type`, `uploaded_at`, `category`) VALUES
(1, 'hthththt', 'uploads/hello - Copy.jpg', 'https://www.gucemiwyqopy.ca', 'jhhh', 'handout', '2025-02-16 15:15:58', 'past_questions');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_content` text DEFAULT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video','none') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `post_content`, `media_url`, `media_type`, `created_at`, `updated_at`, `view_count`) VALUES
(4, 4, 'Debitis ullamco quia', NULL, 'none', '2025-01-19 23:41:48', '2025-01-19 23:41:48', 0),
(5, 4, 'Laborum Aut aliquid', '678d6088a8cc3_user-10', 'image', '2025-01-19 23:43:12', '2025-01-21 08:51:28', 0),
(6, 4, 'Quos iusto tenetur a', '678d6088a8cc3_user-10', 'image', '2025-01-21 08:44:11', '2025-01-21 08:51:04', 0),
(7, 4, 'Aspernatur praesenti', 'uploads/user-1.jpg', 'image', '2025-01-21 08:56:47', '2025-01-21 08:56:47', 0),
(8, 4, 'Beatae ex praesentiu', 'uploads/user-13.png', 'image', '2025-01-21 08:58:08', '2025-01-21 08:58:08', 0),
(9, 4, 'i just feel great today', 'uploads/user-2.jpg', 'image', '2025-01-21 09:15:02', '2025-01-21 09:15:02', 0),
(10, 6, 'this is a post done by adyems using a vojaja', 'uploads/images__1_.jpg', 'image', '2025-01-21 17:18:13', '2025-01-21 17:18:13', 0),
(11, 8, 'Excepteur fugiat per', 'a:3:{i:0;s:20:\"uploads/banner-3.jpg\";i:1;s:18:\"uploads/blog-1.jpg\";i:2;s:18:\"uploads/blog-2.jpg\";}', '', '2025-01-23 13:12:54', '2025-01-23 13:12:54', 0),
(12, 8, '', NULL, 'none', '2025-01-23 13:18:24', '2025-01-23 13:18:24', 0),
(13, 8, 'Praesentium aliquam ', NULL, 'none', '2025-01-23 13:18:56', '2025-01-23 13:18:56', 0),
(14, 8, '', 'uploads/blog-1.jpg', 'image', '2025-01-23 13:20:03', '2025-01-23 13:20:03', 0),
(15, 9, 'this is a post mad by adyems charity this evening i am feeling very good', 'uploads/IMG_20241225_111845_479.jpg', 'image', '2025-01-23 18:52:11', '2025-01-23 18:52:11', 0),
(18, 9, 'Dolor ut odit laboru', 'uploads/images__1_.jpg', 'image', '2025-01-23 19:22:19', '2025-01-23 19:22:19', 0),
(19, 10, 'this is a post to confirm to admola and simon', 'uploads/IMG_20241222_101224_864.jpg', 'image', '2025-01-23 19:27:48', '2025-01-23 19:27:48', 0),
(20, 7, 'Adipisci suscipit du', NULL, 'none', '2025-01-26 15:15:06', '2025-01-26 15:15:06', 0),
(21, 1, 'these are beaut', 'uploads/download__5_.jpeg', 'image', '2025-02-12 00:35:49', '2025-02-12 00:35:49', 0),
(22, 13, 'ljcjgggggggggggg', 'uploads/download__5_.jpeg', 'image', '2025-02-12 14:52:43', '2025-02-12 14:52:43', 0),
(23, 9, 'this is best trying to check somehting', 'uploads/IMG_20241225_111902_502.jpg', 'image', '2025-02-16 14:59:44', '2025-02-16 14:59:44', 0),
(24, 13, 'ghghkvjhvjh', 'uploads/hello_-_Copy_-_Copy.jpg', 'image', '2025-02-17 09:01:29', '2025-02-17 09:01:29', 0),
(25, 3, 'this is oyedepo', 'uploads/images.jpg', 'image', '2025-02-18 16:18:16', '2025-02-18 16:18:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`like_id`, `post_id`, `user_id`, `created_at`) VALUES
(1, 8, 1, '2025-01-21 09:19:49'),
(2, 7, 1, '2025-01-21 09:20:37'),
(3, 6, 1, '2025-01-21 09:23:34'),
(4, 9, 1, '2025-01-21 10:20:49'),
(5, 10, 1, '2025-01-22 07:29:42'),
(6, 5, 1, '2025-01-22 07:33:58'),
(7, 4, 1, '2025-01-22 07:36:18'),
(8, 10, 6, '2025-01-22 07:39:00'),
(9, 8, 6, '2025-01-22 07:41:57'),
(10, 7, 6, '2025-01-22 07:42:05'),
(11, 4, 6, '2025-01-22 07:42:42'),
(12, 5, 6, '2025-01-22 07:45:07'),
(13, 6, 6, '2025-01-22 07:46:47'),
(14, 9, 6, '2025-01-22 07:49:54'),
(15, 9, 7, '2025-01-22 07:53:11'),
(16, 7, 7, '2025-01-22 07:53:41'),
(17, 6, 7, '2025-01-22 07:53:48'),
(18, 5, 7, '2025-01-22 07:53:55'),
(19, 4, 7, '2025-01-22 07:54:25'),
(20, 10, 7, '2025-01-22 07:54:58'),
(21, 8, 7, '2025-01-22 07:58:01'),
(22, 4, 8, '2025-01-23 12:29:42'),
(23, 10, 8, '2025-01-23 12:30:26'),
(24, 9, 8, '2025-01-23 12:45:12'),
(25, 11, 8, '2025-01-23 13:18:00'),
(26, 10, 9, '2025-01-23 18:48:16'),
(27, 11, 9, '2025-01-23 18:48:21'),
(28, 9, 9, '2025-01-23 18:48:48'),
(29, 15, 9, '2025-01-23 18:52:26'),
(30, 19, 9, '2025-01-23 23:40:36'),
(31, 18, 9, '2025-01-25 21:19:27'),
(32, 14, 9, '2025-01-25 21:19:48'),
(33, 8, 9, '2025-01-25 21:25:18'),
(34, 7, 9, '2025-01-25 21:25:27'),
(35, 5, 9, '2025-01-25 21:29:36'),
(36, 4, 9, '2025-01-25 21:31:15'),
(37, 6, 9, '2025-01-25 21:31:45'),
(38, 19, 1, '2025-01-25 21:38:07'),
(39, 19, 11, '2025-01-25 21:38:08'),
(40, 18, 1, '2025-01-25 21:38:16'),
(41, 18, 11, '2025-01-25 21:38:16'),
(42, 15, 1, '2025-01-25 21:38:23'),
(43, 15, 11, '2025-01-25 21:38:24'),
(44, 14, 1, '2025-01-25 21:38:32'),
(45, 14, 11, '2025-01-25 21:38:32'),
(46, 8, 11, '2025-01-25 21:39:32'),
(47, 7, 11, '2025-01-25 21:39:41'),
(48, 19, 3, '2025-01-25 21:58:42'),
(49, 20, 1, '2025-01-26 15:16:05'),
(50, 20, 7, '2025-01-26 15:16:05'),
(51, 15, 7, '2025-01-26 15:16:26'),
(52, 13, 1, '2025-01-26 15:37:50'),
(53, 21, 1, '2025-02-12 00:39:03'),
(54, 22, 1, '2025-02-12 14:52:54'),
(55, 23, 1, '2025-02-16 15:01:28'),
(56, 24, 1, '2025-02-18 14:15:25'),
(57, 25, 1, '2025-02-18 16:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `reply_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`reply_id`, `comment_id`, `user_id`, `reply_text`, `created_at`, `post_id`) VALUES
(20, 19, 4, 'ha', '2025-01-21 16:16:49', 9),
(21, 19, 4, 'no na', '2025-01-21 16:18:50', 9),
(22, 10, 7, 'habana', '2025-01-22 15:13:06', 8),
(23, 11, 9, 'this is m reply', '2025-01-23 18:49:27', 8),
(24, 109, 1, 'hola', '2025-01-26 14:53:25', 19),
(25, 138, 7, 'thats nie', '2025-01-26 15:40:12', 13),
(26, 138, 7, 'okea', '2025-01-26 15:43:50', 13),
(27, 138, 7, 'oga', '2025-01-26 15:44:02', 13),
(28, 138, 7, 'ogg', '2025-01-26 15:44:13', 13),
(29, 138, 7, 'its nice oo', '2025-01-26 15:46:24', 13),
(30, 138, 7, 'all working', '2025-01-26 15:46:33', 13),
(31, 141, 1, 'good', '2025-02-12 00:39:17', 21),
(32, 143, 9, 'hhh', '2025-02-16 15:02:00', 23),
(33, 143, 9, 'hhh', '2025-02-16 15:02:05', 23),
(34, 144, 13, 'gggghh', '2025-02-17 09:01:03', 23),
(35, 145, 3, 'me again', '2025-02-18 16:16:39', 24);

-- --------------------------------------------------------

--
-- Table structure for table `summaries`
--

CREATE TABLE `summaries` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `download_link` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `type` enum('handout','pastQ','summary') NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT 'past_questions'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `summaries`
--

INSERT INTO `summaries` (`id`, `title`, `image`, `download_link`, `note`, `type`, `uploaded_at`, `category`) VALUES
(1, 'Laudantium ut quae ', 'uploads/download (2).jpeg', 'https://www.fupahygelepel.info', 'Nihil odit dolorem d', 'handout', '2025-02-14 12:18:12', 'summaries'),
(2, 'Cumque fugiat dolore', 'uploads/download (4).jpeg', 'https://drive.google.com/uc?export=download&id=1qQIQhUrBqPbw40QUyz3rvi78re5UG-sr', 'Do nesciunt ipsa e', 'handout', '2025-02-14 12:20:34', 'summaries'),
(3, 'Bio 101 summaries', 'uploads/download (5).jpeg', 'https://drive.google.com/uc?export=download&id=1-KimyD2dCoBCCGsZOHr2YrlGBMVqJgUs', 'Ipsam sunt sint con', 'handout', '2025-02-14 12:44:26', 'summaries');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `faculty` varchar(255) DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `stays_in_hostel` enum('Yes','No') DEFAULT NULL,
  `about_me` text DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `relationship_status` enum('Single','Married','In a relationship','It''s complicated','Divorced','Widowed') DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `club` varchar(255) NOT NULL,
  `first_time` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `created_at`, `address`, `department`, `faculty`, `level`, `id_number`, `phone_number`, `stays_in_hostel`, `about_me`, `full_name`, `interests`, `relationship_status`, `gender`, `account_name`, `account_number`, `bank_name`, `verified`, `club`, `first_time`) VALUES
(1, 'billions', 'adyemsgodlove6@gmail.com', '$2y$10$eOv83/p2RAxTONNyBDLKs.wNIeRL/NSyonGiWThNfPQqyBTFIDhI2', 'uploads/profile_pictures/images (1) - Copy.jpg', '2024-12-04 22:55:42', 'Eligendi cumque unde', 'Voluptate anim lorem', 'Dolorum culpa cillum', 'Magni veniam totam ', '877', '+1 (956) 342-9519', 'No', 'Est autem nostrum t', 'Rajah Walter', 'Tenetur consequuntur', 'Married', NULL, '', '', '', 0, '', 1),
(2, 'info.earnesly@gmail.com', 'Info.earnesly@gmail.com ', '$2y$10$MiX/a3yAFSnfwCgtNYzTDeCaXFt4JldP75GACvLB5fzq4esOZI1U.', 'uploads/profile_pictures/67540975b2c2f_hello.jpg', '2024-12-07 08:38:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(3, 'Billions001', 'billions@gmail.com', '$2y$10$8zrwaW.9GS5p8r4.fNuL2uzz3IfN3tfl3Plh1OYSSLv7OwkzxWyqO', 'uploads/profile_pictures/images (1) - Copy.jpg', '2025-01-19 20:20:07', 'Aute saepe aperiam i', 'Sint et cupidatat se', 'Laboriosam tempore', 'Elit odio nulla off', '125', '+1 (848) 483-5374', 'Yes', 'Velit unde quia ill', 'Yetta Alvarado', 'Modi delectus sapie', 'Widowed', 'Female', '', '', '', 1, '', 1),
(4, 'simon', 'simon@gmal.com', '$2y$10$A9aKXXGXPTMfHcx6N1CoNOFH3JA2bfDn/EucSu2NFnnLyu8utJfr6', 'uploads/profile_pictures/hello - Copy - Copy.jpg', '2025-01-19 20:28:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(5, 'simon', '', '', '', '2025-01-21 16:50:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(6, 'vohajad', 'dakyralid@mailinator.com', '$2y$10$NqgAMedQ.0UMM3u0uj691OagL3w57cpU4spNKpiO5uUiQPN8275tm', 'uploads/profile_pictures/ru4.jpg', '2025-01-21 17:00:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(7, 'kehajelof', 'adyemschris001@gmail.com', '$2y$10$KtVv3B7nyKLbIKH0CJGU2e.BMx7UOpXqFrp4.hc3ObsHZjGsxeW3e', 'uploads/profile_pictures/blog-1.jpg', '2025-01-22 07:52:56', 'Fuga Aut vero ad fu', 'Quam doloremque veni', 'Repellendus Quo odi', 'Et ratione nihil sun', '404', '+1 (725) 267-9309', 'No', 'Nisi eiusmod Nam eos', 'September Lynch', 'Cillum ullamco adipi', 'Married', 'Male', '', '', '', 0, '', 1),
(8, 'kizibi', 'gebybi@mailinator.com', '$2y$10$EgDQACU0IpPrPxqqEFerte1DYFz6kHXL53EvVkcBYVIYLzD3BoQdq', 'uploads/profile_pictures/blog-1.jpg', '2025-01-23 12:28:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(9, 'adyemscharity', 'adyemscharity@gmail.com', '$2y$10$r.1mTOo9nPpuG/LH/aHCUOszObwgWfyvZHhnC3SiegoF2nr5FifHS', 'uploads/profile_pictures/IMG_20241222_101120_203.jpg', '2025-01-23 13:23:47', 'kyado', 'computer engineering', 'engineering', '400L', '20/05/04/010', '+1 (693) 123-8171', 'Yes', 'Consequat Itaque co', 'adyems charity ngoyima', 'Dolorem commodo fuga', 'In a relationship', 'Female', 'Maite Carver', '991', 'Bank B', 1, 'Real Madrid', 0),
(10, 'ademola', 'ademolaeyac@gmail.com', '$2y$10$srWYn0jnWghVA5GLa8L0yuunawKSHON6heJ0/bhVrD7HSbmwtcXuu', 'uploads/profile_pictures/IMG_20241222_101120_203.jpg', '2025-01-23 19:25:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 2, '', 1),
(11, 'mowujyb', 'makowopid@mailinator.com', '$2y$10$LJNS8hgh4.J8kNbhmvN/FONOXAYdi8ynJ6Gq9RSIiWvKaWGkQZb9G', '', '2025-01-25 21:34:31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(12, 'fivihi', 'kevivu@mailinator.com', '$2y$10$BojWDEihnoj.X/QHpeUb7ul35aCZ59tMtaCZAJLvcUsjbw3QGJqmq', '', '2025-01-26 21:25:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', 0, '', 1),
(13, 'saviour', 'saviourwerey@gmail.com', '$2y$10$J5Gi6eqBZLB0X1zF6DQrKOxwjxYfkB0xjBcfSXcJOc60/xgvFbF.y', 'uploads/profile_pictures/images (1) - Copy.jpg', '2025-02-12 14:42:42', 'Fugit eligendi quas', 'Est aut soluta vel e', 'Amet aut ipsum debi', '300L', '215', '+1 (135) 237-7146', 'No', 'Nulla delectus labo', 'Keiko Slater', 'Nulla ipsum sint vol', 'Widowed', 'Female', 'Boris Blackwell', '0774110871', 'Bank B', 2, 'Chelsea', 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_requests`
--

INSERT INTO `verification_requests` (`id`, `user_id`, `request_date`, `status`) VALUES
(1, 3, '2025-01-29 17:17:36', 'pending'),
(2, 3, '2025-01-29 17:17:37', 'pending'),
(3, 3, '2025-01-29 17:17:39', 'pending'),
(4, 3, '2025-01-29 17:17:39', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_id` int(11) NOT NULL,
  `vote_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `user_id`, `image_id`, `vote_time`) VALUES
(1, 3, 1, '2025-02-19 08:48:17'),
(2, 3, 3, '2025-02-19 09:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `wcw_images`
--

CREATE TABLE `wcw_images` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `votes` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wcw_images`
--

INSERT INTO `wcw_images` (`id`, `name`, `image_path`, `approved`, `votes`, `user_id`) VALUES
(1, 'Merrill Herrera', 'uploads/67b4fff7b1d6b3.39072319.jpg', 1, 8, NULL),
(2, 'Merrill Herrera', 'uploads/67b5007078af65.54598096.jpg', 0, 0, NULL),
(3, NULL, 'uploads/67b59d114ce6e6.24277509.jpg', 1, 1, 3),
(4, NULL, 'uploads/67b59d2dc1b791.28428366.jpg', 0, 0, 3),
(5, NULL, 'uploads/67b59d3032b9a1.53383603.jpg', 1, 1, 3),
(6, 'Yetta Alvarado', 'uploads/67b59d34a4ecf3.61963359.jpg', 1, 0, 3),
(7, 'Yetta Alvarado', 'uploads/67b59d6ddfaf73.07385041.jpg', 0, 0, 3),
(8, 'Yetta Alvarado', 'uploads/67b59d7b779800.32908359.jpg', 0, 0, 3),
(9, 'Yetta Alvarado', 'uploads/67b59e56ceae06.38134394.jpg', 0, 0, 3),
(10, 'Keiko Slater', 'uploads/67b70b768e66a4.52255423.jpg', 0, 0, 13);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `anonymous_posts`
--
ALTER TABLE `anonymous_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_users`
--
ALTER TABLE `chat_users`
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `fcomment`
--
ALTER TABLE `fcomment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feed`
--
ALTER TABLE `feed`
  ADD PRIMARY KEY (`feed_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feed_links`
--
ALTER TABLE `feed_links`
  ADD PRIMARY KEY (`link_id`),
  ADD KEY `feed_id` (`feed_id`);

--
-- Indexes for table `feed_media`
--
ALTER TABLE `feed_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `feed_id` (`feed_id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- Indexes for table `fpost`
--
ALTER TABLE `fpost`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `freplies`
--
ALTER TABLE `freplies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_notifications`
--
ALTER TABLE `group_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `group_posts`
--
ALTER TABLE `group_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `handouts`
--
ALTER TABLE `handouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_requests`
--
ALTER TABLE `loan_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marketplace_posts`
--
ALTER TABLE `marketplace_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `past_questions`
--
ALTER TABLE `past_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_post_id` (`post_id`);

--
-- Indexes for table `summaries`
--
ALTER TABLE `summaries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`image_id`),
  ADD KEY `image_id` (`image_id`);

--
-- Indexes for table `wcw_images`
--
ALTER TABLE `wcw_images`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `anonymous_posts`
--
ALTER TABLE `anonymous_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fcomment`
--
ALTER TABLE `fcomment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `feed`
--
ALTER TABLE `feed`
  MODIFY `feed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feed_links`
--
ALTER TABLE `feed_links`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feed_media`
--
ALTER TABLE `feed_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `fpost`
--
ALTER TABLE `fpost`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `freplies`
--
ALTER TABLE `freplies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `group_notifications`
--
ALTER TABLE `group_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_posts`
--
ALTER TABLE `group_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `handouts`
--
ALTER TABLE `handouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loan_requests`
--
ALTER TABLE `loan_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `marketplace_posts`
--
ALTER TABLE `marketplace_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `past_questions`
--
ALTER TABLE `past_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `summaries`
--
ALTER TABLE `summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wcw_images`
--
ALTER TABLE `wcw_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_users`
--
ALTER TABLE `chat_users`
  ADD CONSTRAINT `chat_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fcomment`
--
ALTER TABLE `fcomment`
  ADD CONSTRAINT `fcomment_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `fpost` (`id`),
  ADD CONSTRAINT `fcomment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `feed`
--
ALTER TABLE `feed`
  ADD CONSTRAINT `feed_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `feed_links`
--
ALTER TABLE `feed_links`
  ADD CONSTRAINT `feed_links_ibfk_1` FOREIGN KEY (`feed_id`) REFERENCES `feed` (`feed_id`) ON DELETE CASCADE;

--
-- Constraints for table `feed_media`
--
ALTER TABLE `feed_media`
  ADD CONSTRAINT `feed_media_ibfk_1` FOREIGN KEY (`feed_id`) REFERENCES `feed` (`feed_id`) ON DELETE CASCADE;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `fpost`
--
ALTER TABLE `fpost`
  ADD CONSTRAINT `fpost_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `freplies`
--
ALTER TABLE `freplies`
  ADD CONSTRAINT `freplies_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `fcomment` (`id`),
  ADD CONSTRAINT `freplies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_notifications`
--
ALTER TABLE `group_notifications`
  ADD CONSTRAINT `group_notifications_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_posts`
--
ALTER TABLE `group_posts`
  ADD CONSTRAINT `group_posts_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `group_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `marketplace_posts`
--
ALTER TABLE `marketplace_posts`
  ADD CONSTRAINT `marketplace_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `fk_post_id` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`),
  ADD CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD CONSTRAINT `verification_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `wcw_images` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

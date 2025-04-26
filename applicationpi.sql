-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 25, 2025 at 02:30 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `applicationpi`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `activity_destination` varchar(255) NOT NULL,
  `activity_duration` varchar(100) DEFAULT NULL,
  `activity_price` decimal(10,2) DEFAULT NULL,
  `activity_genre` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `max_number` int(11) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `activity_name`, `activity_description`, `activity_destination`, `activity_duration`, `activity_price`, `activity_genre`, `user_id`, `activity_date`, `created_at`, `max_number`, `latitude`, `longitude`) VALUES
(68, 'hooking', 'dkjsbfkjqsbfkbj', 'aaaa', '2 days', 200.00, 'Beach', 28, '2025-04-10', '2025-04-03 23:21:05', 50, NULL, NULL),
(69, 'Hollaaaa', 'ahahhhahah ty brcha jaw', 'aaaa', '2 days', 100.00, 'Cultural', 28, '2025-04-23', '2025-04-07 08:36:36', 100, NULL, NULL),
(70, 'hahahahah', 'aaaaaaaaaaaaaaaaaaaaa', 'aaaa', '2 days', 200.00, 'Cultural', 28, '2025-04-18', '2025-04-15 11:20:51', 100, NULL, NULL),
(76, 'aqssqs', 'aaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaa', '2 days', 400.00, 'Relaxation', 28, '2060-01-11', '2025-04-22 01:03:05', 100, 37.15254900, 9.49925400);

-- --------------------------------------------------------

--
-- Table structure for table `billet`
--

CREATE TABLE `billet` (
  `id` int(11) NOT NULL,
  `prix` double NOT NULL,
  `numero` varchar(50) NOT NULL,
  `activiteId` int(11) NOT NULL,
  `nb` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billet`
--

INSERT INTO `billet` (`id`, `prix`, `numero`, `activiteId`, `nb`) VALUES
(120, 100, 'TICKET-6802ad539232f', 69, 1),
(121, 100, 'TICKET-6802ad5ee198f', 69, 1),
(122, 100, 'TICKET-6802af1806366', 69, 15),
(123, 200, 'TICKET-6802c1bf3822c', 70, 6),
(124, 200, 'TICKET-6803b54f9b63f', 70, 94),
(125, 100, 'TICKET-6803b5f23a484', 69, 1),
(126, 200, 'TICKET-6803b6bd40fc5', 68, 50),
(127, 100, 'TICKET-6803b73b51a40', 69, 1),
(128, 400, 'TICKET-6807a27c90f0e', 76, 1);

-- --------------------------------------------------------

--
-- Table structure for table `blogs_rating`
--

CREATE TABLE `blogs_rating` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `is_like` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blogs_rating`
--

INSERT INTO `blogs_rating` (`id`, `user_id`, `post_id`, `is_like`, `created_at`, `updated_at`) VALUES
(1, 37, 6, 0, '2025-04-21 18:47:49', '2025-04-21 19:40:30'),
(5, 40, 6, 1, '2025-04-22 14:11:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `date`, `comment`) VALUES
(6, 6, 28, '2025-04-15 13:00:09', 'bbbb'),
(7, 6, 28, '2025-04-15 13:08:47', 'aaaaaa');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `location`, `description`, `image_path`, `user_id`, `created_at`) VALUES
(18, 'kkkkkkkkk', 'Ariana', 'Hannibal country', 'uploads/destinations/destination-67e9980293168-1743362050.png', 28, '2025-03-30 20:14:10'),
(20, 'aaaa', 'blabla', 'qshdbkqsbdkjqsckjbqskjckqjsb', 'uploads/destinations/destination-67ef09a496d7e-1743718820.png', 28, '2025-04-03 23:20:20'),
(23, 'gggggg', 'daaa', 'aaaaaaaaaaaa', 'uploads/destinations/destination-67fe2c9138517-1744710801.png', 28, '2025-04-15 10:53:21'),
(24, 'aaaaaaaaaaaa', 'qeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'uploads/destinations/destination-6806b97e424b1-1745271166.jpg', 28, '2025-04-21 22:32:46'),
(25, 'Bali', 'ASIA', 'Enjoy your trippppppppp', 'uploads/destinations/destination-68079b483e86f-1745328968.jpg', 28, '2025-04-22 12:36:08');

-- --------------------------------------------------------

--
-- Table structure for table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250402Add_IsBanned', '2025-04-03 00:03:49', 290);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `activity_id`, `title`, `description`, `picture`, `date`) VALUES
(6, 28, 68, 'Malla jaw w malla hiya', 'brhca jaw fel hooking hhahha', 'WhatsApp-Image-2025-03-02-at-12-27-03-AM-67fe37564a070.jpg', '2025-04-15 12:39:18'),
(7, 37, 68, 'Malla jaw w malla hiya', '**Hooked on Hiking in aaaa: An Adventure You Won\'t Forget!**\r\n\r\nThe wind whipped around us as we scrambled over the rocky terrain, the turquoise glacial lake shimmering below like a mirage. aaaa had already stolen my heart, but this hike? This was next level. We were aiming for the perfect vantage point, a spot where we could truly soak in the majesty of the snow-capped peaks and the vast expanse of the Patagonian landscape. The air was crisp and clean, biting at our cheeks, but the adrenaline kept us warm. Every step was a challenge, boots finding purchase on the uneven stone, but with each upward climb, the view became more breathtaking. I could feel the burn in my legs, the steady rhythm of my breath, and the sheer exhilaration of being completely immersed in nature\'s grandeur.\r\n\r\nReaching the summit felt like an accomplishment of epic proportions. We threw our arms up in victory, hooting and hollering into the wind. The panorama was simply unreal. The glacier, a river of ice snaking down from the mountains, fed into the milky blue lake. The jagged peaks, dusted with snow, pierced the sky. We were standing on top of the world, or at least it felt that way. We hugged, laughed, and snapped photos, trying to capture the moment, knowing full well that no image could ever truly do it justice. The feeling of accomplishment, the shared joy, the sheer awe of the landscape – it was a high I knew I\'d be chasing for a long time.\r\n\r\nIf you\'re looking for an adventure that will challenge you, reward you, and leave you breathless (literally and figuratively!), then you absolutely HAVE to experience hiking in aaaa. Trust me, you\'ll be hooked!', 'IMG-8684-68068422d11a2.jpg', '2025-04-21 19:45:06');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id` int(11) NOT NULL,
  `dateAchat` varchar(255) NOT NULL,
  `userId` int(11) NOT NULL,
  `billetId` int(11) NOT NULL,
  `nombre` int(11) NOT NULL,
  `prixTotal` double NOT NULL,
  `prixUnite` double NOT NULL,
  `statuts` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`id`, `dateAchat`, `userId`, `billetId`, `nombre`, `prixTotal`, `prixUnite`, `statuts`) VALUES
(1, '2025-04-18 21:51:47', 28, 120, 1, 100, 100, 'confirmed'),
(2, '2025-04-18 21:51:59', 28, 121, 1, 100, 100, 'confirmed'),
(3, '2025-04-18 21:59:20', 28, 122, 15, 1500, 100, 'confirmed'),
(4, '2025-04-18 23:18:55', 28, 123, 6, 1200, 200, 'confirmed'),
(5, '2025-04-19 16:38:07', 39, 124, 94, 18800, 200, 'confirmed'),
(6, '2025-04-19 16:40:50', 39, 125, 1, 100, 100, 'confirmed'),
(7, '2025-04-19 16:44:13', 39, 126, 50, 10000, 200, 'confirmed'),
(8, '2025-04-19 16:46:19', 39, 127, 1, 100, 100, 'confirmed'),
(9, '2025-04-22 14:06:52', 40, 128, 1, 400, 400, 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `activity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `path`, `activity_id`) VALUES
(50, '/uploads/activities/activity-67ef09d1cb61c-1743718865.webp', 68),
(51, '/uploads/activities/activity-67ef09d1e1c7b-1743718865.jpg', 68),
(52, '/uploads/activities/activity-67ef09d1e2d0c-1743718865.jpg', 68),
(53, '/uploads/activities/activity-67ef09d1e3c28-1743718865.jpg', 68),
(54, '/uploads/activities/activity-67f3808526b36-1744011397.webp', 69),
(55, '/uploads/activities/activity-67fe3303accb2-1744712451.png', 70),
(56, '/uploads/activities/activity-67fe3303adecb-1744712451.png', 70),
(57, '/uploads/activities/activity-67fe3303ae816-1744712451.jpg', 70),
(69, '/uploads/activities/activity-6806dcbc30169-1745280188.jpg', 76);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `nom`) VALUES
(2, 'admin'),
(1, 'client'),
(3, 'Publicitaire');

-- --------------------------------------------------------

--
-- Table structure for table `upgrade_requests`
--

CREATE TABLE `upgrade_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `processed_date` timestamp NULL DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upgrade_requests`
--

INSERT INTO `upgrade_requests` (`id`, `user_id`, `request_date`, `status`, `processed_date`, `message`) VALUES
(12, 28, '2025-04-07 09:29:09', 'approved', '2025-04-15 13:19:20', 'i wanna be hahaha'),
(13, 40, '2025-04-22 10:24:26', 'pending', NULL, 'i would like to be a Publicator');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT 0,
  `totp_secret` varchar(255) DEFAULT NULL,
  `totp_enabled` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `email`, `password`, `gender`, `role`, `phone`, `birthday`, `verification_code`, `enabled`, `created_at`, `image`, `is_banned`, `totp_secret`, `totp_enabled`) VALUES
(1, 'SiAiimir', 'Othman', 'tayebgod@gmail.com', 'qqqqqqx', 'Male', 'Publicitaire', '048294234', '2000-02-16', '5443', 1, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(15, 'mihan', 'iihcsa', 'ahmsm3eed@gmail.com', 'qqqqqqx', 'Male', 'user', '06324234234', '1998-02-12', NULL, 1, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(16, 'aaaamir', 'othman', 'amir.othman@esprit.tn', 'Amyr2001@', 'Female', 'Publicitaire', '0648324423', '2002-02-14', '1973', 0, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(18, 'si amir', 'safadas', 'amir.othaman@esprit.tn', 'aaaaaa', 'Female', 'user', 'Amyr2001@', '2001-02-02', NULL, 1, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(19, 'amiro', 'oothmsan', 'therealamirothman@gmail.com', 'qqqqqq', 'Male', 'Publicitaire', '0642834234', '1998-02-05', '8064', 1, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(20, 'amir', 'thmsn', 'amyyr.othman@gmail.com', 'aaaaaa', 'Female', 'user', '04723942', '1998-02-05', '1465', 1, '2025-02-23 17:18:28', NULL, 0, NULL, 0),
(25, 'amid', 'sdfsf', 'aundrea6263@edny.net', 'qwertyu', 'Male', 'user', '075832534', '1997-02-06', NULL, 1, '2025-02-24 18:40:52', NULL, 0, NULL, 0),
(27, 'aami', 'zzzzz', 'amaltr21@gmail.com', 'Amyr2001@', 'Male', 'user', '0575835345', '1998-02-05', NULL, 1, '2025-02-25 14:17:08', NULL, 0, NULL, 0),
(28, 'Amir', 'Othman', 'amirothmaneee@gmail.com', '$2y$13$fasEV95xWkDMUknjeKKYPeO3gyV76Y2yM5689p3HFUggd/SGSjm3m', NULL, 'Publicitaire', '27856958', '2025-03-30', NULL, 1, '2025-03-30 18:03:46', 'WhatsApp-Image-2025-03-02-at-12-27-03-AM-67e987716365c.jpg', 0, NULL, 0),
(37, 'l 3araf', 'karim', 'nihedabdworks@gmail.com', '$2y$13$rGhg7gTOcj5mddXTAk9EVeCseAlGyJDXt6CDW2iiXCjGHJHddN2kO', 'Male', 'Publicitaire', 'nihedabdworks@gmail.', '2002-02-11', '33dcb5346c7c117661cbe7dfe98fc35b', 1, '2025-04-19 12:25:07', NULL, 0, '45YKIDMOHGXOENT5WVMXTABBJXCLJ52A3JNZKZ46ZMXDLFWY4KYQ====', 1),
(38, 'l 3araf', 'karim', 'crackxtn07@gmail.com', '$2y$13$tb/nc4sH7GzDkeStdlFHx.V07ld2q4a2Zan2dqBvIENCw95U3gpO2', 'Male', 'user', '27582038', '2002-01-11', '9952a39bc1f72a8d5ed4e2d07bf48f5c', 1, '2025-04-19 14:05:23', NULL, 0, 'YO7EA4E6HQLFHWUV4RPV4NMEBA4V2XSG62WLGNJ6CJPOKHRYUD5Q====', 1),
(39, 'l 3araf', 'karim', 'hello@gmail.com', '$2y$13$F5DGL9D5ojvVVL9uJC94E.t0hloejBccI10pav8GjQnOQ1yJQ7v2i', 'Male', 'user', '27582038', '2002-01-11', 'fff5347f92b1601d01e1cba5590d62c8', 1, '2025-04-19 15:31:49', NULL, 0, 'MTRAJB72FJFCOFVZKT3LSP5BIUMSE5PABH755N4ZTVRHUPLWXYRQ====', 1),
(40, 'amir', 'othman', 'aronxothman@gmail.com', '$2y$13$WfkJdMDtC5sbdWNHuLuDnOFo098gqyQ1rsNoA83CxoPS1.ejrC5wi', 'Male', 'admin', '+21655590348', '2025-04-18', '6054e431c235d58711b589baf9f3f183', 1, '2025-04-22 10:20:58', NULL, 0, 'X6G5YMJRMM4T7VZUMWT6MXP46KAHLEHBDWLMKSTPZFOMTUYMLCAA====', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `billet`
--
ALTER TABLE `billet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs_rating`
--
ALTER TABLE `blogs_rating`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_post` (`user_id`,`post_id`),
  ADD KEY `idx_user_post` (`user_id`,`post_id`),
  ADD KEY `idx_post` (`post_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_activity` (`activity_id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_ibfk_1` (`userId`),
  ADD KEY `reservation_ibfk_2` (`billetId`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `billet`
--
ALTER TABLE `billet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `blogs_rating`
--
ALTER TABLE `blogs_rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `blogs_rating`
--
ALTER TABLE `blogs_rating`
  ADD CONSTRAINT `fk_blogs_rating_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_blogs_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `destinations`
--
ALTER TABLE `destinations`
  ADD CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD CONSTRAINT `upgrade_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

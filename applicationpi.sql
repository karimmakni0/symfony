-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 10, 2025 at 05:32 PM
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
(100, 'Colosseum Guided Tour', 'Discover the secrets of the Colosseum with an expert historian.', 'Rome', 'Half Day', 150.00, 'Cultural', 4, '2025-06-10', '2025-04-25 10:53:22', 30, 41.90280000, 12.49640000),
(101, 'Vatican Museums Visit', 'Explore the treasures of the Vatican including the Sistine Chapel.', 'Rome', 'Half Day', 120.00, 'Cultural', 4, '2025-06-11', '2025-04-25 10:53:22', 25, 41.90280000, 12.49640000),
(102, 'Statue of Liberty Cruise', 'Sail around Manhattan and admire the Statue of Liberty.', 'New York', '2 Hours', 70.00, 'Cultural', 4, '2025-06-12', '2025-04-25 10:53:22', 80, 40.71280000, -74.00600000),
(103, 'Broadway Show Night', 'Enjoy a classic Broadway musical in Times Square.', 'New York', 'Evening', 180.00, 'Cultural', 4, '2025-06-13', '2025-04-25 10:53:22', 50, 40.71280000, -74.00600000),
(104, 'Gaudi Architecture Tour', 'Visit the magnificent Sagrada Familia and Park Güell.', 'Barcelona', 'Full Day', 160.00, 'Cultural', 4, '2025-06-14', '2025-04-25 10:53:22', 40, 41.38510000, 2.17340000),
(105, 'Barcelona Beach Chill', 'Relax at Barceloneta Beach with cocktails and sunshine.', 'Barcelona', 'Half Day', 90.00, 'Beach', 4, '2025-06-15', '2025-04-25 10:53:22', 60, 41.38510000, 2.17340000),
(106, 'Desert Safari', 'Thrilling 4x4 ride through Dubai’s golden sand dunes.', 'Dubai', '1 Day', 200.00, 'Adventure', 4, '2025-06-16', '2025-04-25 10:53:22', 50, 25.20480000, 55.27080000),
(107, 'Burj Khalifa VIP Tour', 'Ascend to the top of the tallest building in the world.', 'Dubai', '2 Hours', 110.00, 'Cultural', 4, '2025-06-17', '2025-04-25 10:53:22', 20, 25.20480000, 55.27080000),
(108, 'Bosphorus Dinner Cruise', 'Enjoy a luxurious dinner while cruising Istanbul’s Bosphorus.', 'Istanbul', 'Evening', 130.00, '', 4, '2025-06-18', '2025-04-25 10:53:22', 70, 41.00820000, 28.97840000),
(109, 'Grand Bazaar Shopping Tour', 'Shop at one of the world’s oldest and largest covered markets.', 'Istanbul', 'Half Day', 80.00, 'Cultural', 4, '2025-06-19', '2025-04-25 10:53:22', 50, 41.00820000, 28.97840000),
(110, 'Blue Mountains Adventure', 'Hike and explore the Blue Mountains outside Sydney.', 'Sydney', 'Full Day', 220.00, 'Nature', 4, '2025-06-20', '2025-04-25 10:53:22', 20, -33.86880000, 151.20930000),
(111, 'Sydney Opera House Tour', 'Behind-the-scenes tour of Australia’s most famous icon.', 'Sydney', '2 Hours', 95.00, 'Cultural', 4, '2025-06-21', '2025-04-25 10:53:22', 40, -33.86880000, 151.20930000),
(112, 'Table Mountain Hike', 'Climb or cable-car to Cape Town’s famous flat mountain.', 'Cape Town', 'Half Day', 150.00, 'Adventure', 4, '2025-06-22', '2025-04-25 10:53:22', 30, -33.92490000, 18.42410000),
(113, 'Cape Winelands Tour', 'Taste world-class wines in the Cape countryside.', 'Cape Town', '1 Day', 170.00, 'Food', 4, '2025-06-23', '2025-04-25 10:53:22', 20, -33.92490000, 18.42410000),
(114, 'Canal Biking Tour', 'Bike alongside the beautiful canals of Amsterdam.', 'Amsterdam', 'Half Day', 60.00, 'Cultural', 4, '2025-06-24', '2025-04-25 10:53:22', 50, 52.36760000, 4.90410000),
(115, 'Tulip Gardens Visit', 'Explore the colorful Keukenhof gardens in spring.', 'Amsterdam', 'Half Day', 100.00, 'Nature', 4, '2025-06-25', '2025-04-25 10:53:22', 40, 52.36760000, 4.90410000),
(116, 'Pyramids Camel Ride', 'Ride camels around the majestic pyramids of Giza.', 'Cairo', '2 Hours', 90.00, 'Adventure', 4, '2025-06-26', '2025-04-25 10:53:22', 30, 30.04440000, 31.23570000),
(117, 'Egyptian Museum Tour', 'See ancient artifacts including Tutankhamun’s treasures.', 'Cairo', 'Half Day', 110.00, 'Cultural', 4, '2025-06-27', '2025-04-25 10:53:22', 30, 30.04440000, 31.23570000),
(118, 'Old Town Walking Tour', 'Explore Prague’s historic Old Town and Charles Bridge.', 'Prague', 'Half Day', 85.00, 'Cultural', 4, '2025-06-28', '2025-04-25 10:53:22', 40, 50.07550000, 14.43780000),
(122, 'Lisbon Tram 28 Ride', 'Take a scenic historic tram ride through Lisbon’s neighborhoods.', 'Lisbon', '2 Hours', 40.00, 'Cultural', 4, '2025-07-02', '2025-04-25 10:53:22', 70, 38.71690000, -9.13990000),
(123, 'Fado Music Dinner', 'Enjoy a traditional Portuguese dinner with live Fado music.', 'Lisbon', 'Evening', 100.00, 'Cultural', 4, '2025-07-03', '2025-04-25 10:53:22', 50, 38.71690000, -9.13990000),
(124, 'Bangkok Floating Market Tour', 'Shop at lively markets floating along Bangkok\'s canals.', 'Bangkok', 'Half Day', 60.00, 'Cultural', 4, '2025-07-04', '2025-04-25 10:53:22', 50, 13.75630000, 100.50180000),
(125, 'Thai Cooking Class', 'Learn to prepare authentic Thai dishes with a local chef.', 'Bangkok', 'Half Day', 90.00, 'Food', 4, '2025-07-05', '2025-04-25 10:53:22', 20, 13.75630000, 100.50180000),
(126, 'Hollywood Sign Hike', 'Get up close to LA’s iconic Hollywood sign.', 'Los Angeles', 'Half Day', 70.00, 'Adventure', 4, '2025-07-06', '2025-04-25 10:53:22', 50, 34.05220000, -118.24370000),
(127, 'Venice Beach Bike Ride', 'Cycle along the colorful Venice Beach boardwalk.', 'Los Angeles', 'Half Day', 65.00, 'Beach', 4, '2025-07-07', '2025-04-25 10:53:22', 40, 34.05220000, -118.24370000),
(128, 'Northern Lights Hunt', 'Go chasing the Aurora Borealis outside Reykjavik.', 'Reykjavik', 'Night', 300.00, 'Nature', 4, '2025-07-08', '2025-04-25 10:53:22', 25, 64.13550000, -21.89540000),
(129, 'Blue Lagoon Spa Day', 'Relax in Iceland’s famous geothermal spa.', 'Reykjavik', 'Half Day', 250.00, '', 4, '2025-07-09', '2025-04-25 10:53:22', 30, 64.13550000, -21.89540000),
(130, 'Edinburgh Castle Tour', 'Explore the royal history of Edinburgh Castle.', 'Edinburgh', 'Half Day', 80.00, 'Cultural', 4, '2025-07-10', '2025-04-25 10:53:22', 40, 55.95330000, -3.18830000),
(131, 'Scottish Highlands Day Trip', 'Visit the stunning Highlands scenery from Edinburgh.', 'Edinburgh', '1 Day', 220.00, 'Nature', 4, '2025-07-11', '2025-04-25 10:53:22', 20, 55.95330000, -3.18830000),
(132, 'Petra Night Experience', 'See the ancient Petra lit by thousands of candles.', 'Petra', 'Evening', 110.00, 'Cultural', 4, '2025-07-12', '2025-04-25 10:53:22', 60, 30.32850000, 35.44440000),
(133, 'Treasury Guided Tour', 'Discover the mysteries behind Petra’s famous Treasury.', 'Petra', 'Half Day', 140.00, 'Cultural', 4, '2025-07-13', '2025-04-25 10:53:22', 30, 30.32850000, 35.44440000),
(134, 'Seoul Palace Tour', 'Visit the grand palaces of the Joseon Dynasty.', 'Seoul', 'Half Day', 80.00, 'Cultural', 4, '2025-07-14', '2025-04-25 10:53:22', 50, 37.56650000, 126.97800000),
(135, 'K-Pop Experience', 'Dive into the exciting world of K-pop in Seoul.', 'Seoul', 'Half Day', 150.00, 'Cultural', 4, '2025-07-15', '2025-04-25 10:53:22', 30, 37.56650000, 126.97800000),
(136, 'Gondola Ride', 'Romantic gondola ride through Venice’s canals.', 'Venice', '1 Hour', 100.00, '', 4, '2025-07-16', '2025-04-25 10:53:22', 30, 45.44080000, 12.31550000),
(137, 'Venetian Mask Workshop', 'Learn the craft of traditional Venetian masks.', 'Venice', 'Half Day', 120.00, 'Cultural', 4, '2025-07-17', '2025-04-25 10:53:22', 20, 45.44080000, 12.31550000),
(138, 'Classic Car Tour Havana', 'Cruise through Havana in a vintage car.', 'Havana', '2 Hours', 80.00, 'Cultural', 4, '2025-07-18', '2025-04-25 10:53:22', 50, 23.11360000, -82.36660000),
(139, 'Cuban Salsa Lessons', 'Learn salsa from the best in Havana.', 'Havana', 'Half Day', 90.00, 'Cultural', 4, '2025-07-19', '2025-04-25 10:53:22', 30, 23.11360000, -82.36660000);

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
(129, 250, 'TICKET-63BA57DC224', 135, 2),
(130, 90, 'TICKET-6427DB9C01C', 133, 1),
(131, 50, 'TICKET-63D28FD23CA', 122, 3),
(132, 200, 'TICKET-63C0E4F1970', 119, 2),
(133, 250, 'TICKET-6402F86011C', 135, 5),
(134, 150, 'TICKET-631D7ED5E8B', 109, 3),
(135, 300, 'TICKET-632C0C5E259', 130, 1),
(136, 90, 'TICKET-644B06C5F04', 123, 5),
(137, 90, 'TICKET-64374F12FE2', 107, 5),
(138, 200, 'TICKET-633CD04CFD5', 118, 2),
(139, 150, 'TICKET-63A6CC59635', 112, 1),
(140, 120, 'TICKET-6334E59B777', 107, 1),
(141, 90, 'TICKET-63AC8E822A6', 119, 4),
(142, 300, 'TICKET-63235975100', 119, 4),
(143, 70, 'TICKET-634A5506CDD', 132, 4),
(144, 200, 'TICKET-63961552A57', 109, 3),
(145, 50, 'TICKET-63141911588', 138, 4),
(146, 300, 'TICKET-631B0419C36', 115, 3),
(147, 200, 'TICKET-6320CC2A099', 115, 3),
(148, 70, 'TICKET-6339E4D0771', 111, 5),
(149, 100, 'TICKET-63CD966DB3F', 100, 4),
(150, 100, 'TICKET-63318C34664', 132, 2),
(151, 70, 'TICKET-63F88A5DC03', 113, 4),
(152, 300, 'TICKET-6306A4CD964', 113, 5),
(153, 150, 'TICKET-63575AB4363', 115, 5),
(154, 70, 'TICKET-63F837370AA', 101, 2),
(155, 100, 'TICKET-63F4A263F11', 127, 5),
(156, 90, 'TICKET-62F773ED20E', 103, 4),
(157, 300, 'TICKET-62FD4E273A2', 129, 1),
(158, 250, 'TICKET-644778E50B5', 122, 3),
(159, 90, 'TICKET-680b9cd3a46f8', 139, 1),
(160, 80, 'TICKET-680bb9e427917', 138, 1),
(161, 80, 'TICKET-680bbe113329a', 138, 1),
(162, 80, 'TICKET-680bbe33629ef', 138, 2),
(163, 120, 'TICKET-680bf381d4539', 101, 2),
(164, 80, 'TICKET-680bfe669e8bd', 138, 1),
(165, 80, 'TICKET-680bfe67e9432', 138, 1),
(166, 90, 'TICKET-680c019907f29', 139, 3),
(167, 90, 'TICKET-680c01a79b746', 139, 3),
(168, 409, 'TICKET-680e088496e9a', 145, 2),
(169, 409, 'TICKET-680f5fc3a4eed', 145, 1),
(170, 120, 'TICKET-681043f428fb0', 137, 1),
(173, 80, 'TICKET-6810c2e29fe41', 138, 1),
(174, 150, 'TICKET-6810c4690985c', 135, 1),
(175, 409, 'TICKET-6810c9be6df9f', 155, 1),
(176, 120, 'TICKET-6810cfca89f92', 137, 1),
(177, 150, 'TICKET-6810d36228698', 100, 1);

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
(44, 47, 30, 1, '2025-04-29 11:53:50', NULL),
(45, 47, 27, 0, '2025-04-29 12:24:47', '2025-04-29 12:24:50'),
(46, 47, 37, 1, '2025-04-29 12:26:47', NULL),
(47, 45, 37, 1, '2025-04-29 13:15:31', NULL);

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
(8, 29, 28, '2025-04-22 14:00:00', 'Absolutely loved this!'),
(9, 22, 28, '2025-04-22 14:02:00', 'What a view 😍'),
(10, 13, 28, '2025-04-22 14:04:00', 'Can’t wait to go there myself!'),
(11, 26, 28, '2025-04-22 14:06:00', 'This looks magical ✨'),
(12, 30, 28, '2025-04-22 14:08:00', 'Pure vibes 🙌'),
(13, 30, 28, '2025-04-22 14:10:00', 'I felt the same way when I visited!'),
(14, 26, 28, '2025-04-22 14:12:00', 'Thanks for sharing this moment!'),
(15, 13, 28, '2025-04-22 14:14:00', 'On my bucket list now!'),
(16, 30, 28, '2025-04-22 14:16:00', 'Love the energy here.'),
(17, 16, 28, '2025-04-22 14:18:00', 'So well written. I could picture it.'),
(18, 10, 28, '2025-04-22 14:20:00', 'Did you try the local food too?'),
(19, 26, 28, '2025-04-22 14:22:00', 'Top experience!'),
(20, 14, 28, '2025-04-22 14:24:00', 'Nature at its best!'),
(21, 25, 28, '2025-04-22 14:26:00', 'This made me smile 😊'),
(22, 16, 28, '2025-04-22 14:28:00', 'Looks unforgettable.'),
(23, 35, 28, '2025-04-22 14:30:00', 'That\'s the spirit!'),
(24, 27, 28, '2025-04-22 14:32:00', 'So many memories in one shot.'),
(25, 12, 28, '2025-04-22 14:34:00', 'What a journey!'),
(26, 15, 28, '2025-04-22 14:36:00', 'Hope to see it myself one day!'),
(27, 8, 28, '2025-04-22 14:38:00', 'Iconic spot!'),
(28, 19, 28, '2025-04-22 14:40:00', 'Your post inspired me!'),
(29, 16, 28, '2025-04-22 14:42:00', 'That place is dreamlike.'),
(30, 14, 28, '2025-04-22 14:44:00', 'Totally agree with this!'),
(31, 26, 28, '2025-04-22 14:46:00', 'Best post I’ve seen today.'),
(32, 32, 28, '2025-04-22 14:48:00', 'Chills. Just chills.'),
(33, 27, 28, '2025-04-22 14:50:00', 'How long was the trip?'),
(34, 28, 28, '2025-04-22 14:52:00', 'Looks like a scene from a movie!'),
(35, 24, 28, '2025-04-22 14:54:00', 'Feels peaceful just looking at this.'),
(36, 33, 28, '2025-04-22 14:56:00', 'Amazing capture!'),
(37, 21, 28, '2025-04-22 14:58:00', 'Love this perspective.'),
(38, 36, 40, '2025-04-25 12:54:18', 'merciiis'),
(39, 37, 40, '2025-04-25 21:46:58', 'asdasdad'),
(40, 37, 40, '2025-04-25 21:48:28', 'aaaaassewfe');

-- --------------------------------------------------------

--
-- Table structure for table `coupon`
--

CREATE TABLE `coupon` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `discount` double NOT NULL,
  `is_percentage` tinyint(1) NOT NULL DEFAULT 1,
  `usage_limit` int(11) NOT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupon`
--

INSERT INTO `coupon` (`id`, `code`, `discount`, `is_percentage`, `usage_limit`, `usage_count`, `expires_at`, `is_active`, `created_at`) VALUES
(1, 'SUMMER2025', 50, 1, 1, 1, '2025-05-01 00:00:00', 1, '2025-04-29 12:14:09'),
(3, 'SUMMER303', 50, 1, 1, 1, '2025-05-01 00:00:00', 1, '2025-04-29 12:17:50'),
(4, 'SUMMER2021', 1, 1, 1, 1, '2025-04-30 00:00:00', 1, '2025-04-29 12:21:18');

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
(26, 'Rome', 'Italy', 'Walk through ancient history with the Colosseum, Roman Forum, and the timeless beauty of the Vatican.', 'uploads/destinations/destination-680fd1550a82c-1745867093.jpg', 4, '2025-04-23 09:00:00'),
(27, 'New York', 'USA', 'Experience the bustling energy of Times Square, Central Park, and the skyline of Manhattan.', 'uploads/destinations/destination-680fd12a3b49a-1745867050.jpg', 4, '2025-04-23 10:00:00'),
(28, 'Barcelona', 'Spain', 'Admire Gaudi\'s architecture, relax on Mediterranean beaches, and explore the vibrant Catalan culture.', 'uploads/destinations/destination-680fd0fe69264-1745867006.jpg', 4, '2025-04-23 11:00:00'),
(29, 'Dubai', 'United Arab Emirates', 'Marvel at futuristic skyscrapers, luxurious shopping malls, and desert adventures.', 'uploads/destinations/destination-680fd0d30fb9d-1745866963.jpg', 4, '2025-04-23 12:00:00'),
(30, 'Istanbul', 'Turkey', 'Where East meets West: discover historic mosques, bazaars, and rich cultural traditions.', 'uploads/destinations/destination-680fd0ab7e876-1745866923.jpg', 4, '2025-04-23 13:00:00'),
(31, 'Sydney', 'Australia', 'Famous for its harbor, beaches, and iconic Opera House — a must-visit Down Under.', 'uploads/destinations/destination-680fd06bdc83f-1745866859.jpg', 4, '2025-04-23 14:00:00'),
(32, 'Cape Town', 'South Africa', 'Enjoy stunning coastal views, Table Mountain hikes, and vibrant city life.', 'uploads/destinations/destination-680fd0436ac57-1745866819.jpg', 4, '2025-04-23 15:00:00'),
(33, 'Amsterdam', 'Netherlands', 'Cruise through canals, visit the Anne Frank House, and enjoy beautiful tulip fields.', 'uploads/destinations/destination-680fcff04dd42-1745866736.jpg', 4, '2025-04-23 16:00:00'),
(34, 'Cairo', 'Egypt', 'Visit the awe-inspiring Pyramids of Giza, Sphinx, and bustling souks.', 'uploads/destinations/destination-680fcfb295178-1745866674.jpg', 4, '2025-04-23 17:00:00'),
(35, 'Prague', 'Czech Republic', 'Explore the fairy-tale old town, castles, and charming medieval streets.', 'uploads/destinations/destination-680fcf80c1f9b-1745866624.jpg', 4, '2025-04-23 18:00:00'),
(36, 'Rio de Janeiro', 'Brazil', 'Celebrate life with vibrant festivals, Sugarloaf Mountain, and Copacabana beach.', 'uploads/destinations/destination-680fcf4a7bfc2-1745866570.jpg', 4, '2025-04-23 19:00:00'),
(37, 'Lisbon', 'Portugal', 'Colorful streets, historic trams, and Atlantic coastlines await you in Lisbon.', 'uploads/destinations/destination-680fcf1400537-1745866516.jpg', 4, '2025-04-23 20:00:00'),
(38, 'Bangkok', 'Thailand', 'Dive into street food, golden temples, and a vibrant nightlife.', 'uploads/destinations/destination-680fce689cf9e-1745866344.jpg', 4, '2025-04-23 21:00:00'),
(39, 'Los Angeles', 'USA', 'Hollywood, beaches, and endless sunshine — the dream city of stars.', 'uploads/destinations/destination-680fce38ae8d0-1745866296.jpg', 4, '2025-04-23 22:00:00'),
(40, 'Reykjavik', 'Iceland', 'Gateway to the Northern Lights, geysers, and stunning ice landscapes.', 'uploads/destinations/destination-680fce055a7f9-1745866245.jpg', 4, '2025-04-24 09:00:00'),
(41, 'Edinburgh', 'Scotland', 'Explore medieval castles, vibrant festivals, and rolling green hills.', 'uploads/destinations/destination-680fcdcc06085-1745866188.jpg', 4, '2025-04-24 10:00:00'),
(42, 'Petra', 'Jordan', 'Wander through the rose-red ancient city carved into the cliffs.', 'uploads/destinations/destination-680fcda807757-1745866152.jpg', 4, '2025-04-24 11:00:00'),
(43, 'Seoul', 'South Korea', 'Modern skyscrapers meet centuries-old palaces and street markets.', 'uploads/destinations/destination-680fcd808a2de-1745866112.jpg', 4, '2025-04-24 12:00:00'),
(44, 'Venice', 'Italy', 'Romantic gondola rides, charming canals, and timeless architecture.', 'uploads/destinations/destination-680fcd6123193-1745866081.jpg', 4, '2025-04-24 13:00:00'),
(45, 'Havana', 'Cuba', 'Colorful streets, salsa rhythms, and a nostalgic trip back in time.', 'uploads/destinations/destination-680fcce54d4be-1745865957.jpg', 4, '2025-04-24 14:00:00'),
(46, 'Zurich', 'Switzerland', 'Discover scenic lakes, luxury shopping, and stunning Alpine views.', 'uploads/destinations/destination-680fcca1f3bf6-1745865889.jpg', 4, '2025-04-24 15:00:00'),
(47, 'Cusco', 'Peru', 'The ancient capital of the Inca Empire, gateway to Machu Picchu.', 'uploads/destinations/destination-680fcc6e31e09-1745865838.jpg', 4, '2025-04-24 16:00:00'),
(48, 'Auckland', 'New Zealand', 'Adventures in nature, Maori culture, and spectacular coastlines.', 'uploads/destinations/destination-680fcc0d54e68-1745865741.jpg', 4, '2025-04-24 17:00:00'),
(49, 'Budapest', 'Hungary', 'Relax in thermal baths and admire grand architecture by the Danube River.', 'uploads/destinations/destination-680fcbc677234-1745865670.jpg', 4, '2025-04-24 18:00:00'),
(50, 'Vienna', 'Austria', 'City of music, art, imperial palaces, and delightful coffee houses.', 'uploads/destinations/destination-680fcb8107ff7-1745865601.jpg', 4, '2025-04-24 19:00:00'),
(53, 'Moscow', 'Russia', 'Explore Red Square, the Kremlin, and onion-domed cathedrals.', 'uploads/destinations/destination-680fe5ead2680-1745872362.jpg', 4, '2025-04-24 22:00:00'),
(54, 'Athens', 'Greece', 'Discover the ancient wonders of the Acropolis and Greek mythology.', 'uploads/destinations/destination-680fe408ae28f-1745871880.jpg', 4, '2025-04-25 09:00:00'),
(55, 'Hoi An', 'Vietnam', 'Charming lantern-lit streets, ancient temples, and riverside cafes.', 'uploads/destinations/destination-680fe5cd7edf3-1745872333.jpg', 4, '2025-04-25 10:00:00'),
(56, 'Vancouver', 'Canada', 'Nature meets city life: mountains, beaches, and a vibrant downtown.', 'uploads/destinations/destination-680fe5adcbd0c-1745872301.jpg', 4, '2025-04-25 11:00:00'),
(57, 'Florence', 'Italy', 'Birthplace of the Renaissance — art, architecture, and Tuscan charm.', 'uploads/destinations/destination-680fe5822542a-1745872258.jpg', 4, '2025-04-25 12:00:00'),
(58, 'Amman', 'Jordan', 'Modern city blended with historic ruins and Middle Eastern culture.', 'uploads/destinations/destination-680fe564ad1a5-1745872228.jpg', 4, '2025-04-25 13:00:00'),
(59, 'Lima', 'Peru', 'A vibrant coastal city known for its colonial architecture and cuisine.', 'uploads/destinations/destination-680fe545aeead-1745872197.jpg', 4, '2025-04-25 14:00:00'),
(60, 'Madrid', 'Spain', 'Lively plazas, royal palaces, world-famous museums and tapas.', 'uploads/destinations/destination-680fe52449c42-1745872164.jpg', 4, '2025-04-25 15:00:00'),
(61, 'Phuket', 'Thailand', 'Tropical beaches, luxury resorts, and lively night markets.', 'uploads/destinations/destination-680fe5062aa11-1745872134.jpg', 4, '2025-04-25 16:00:00'),
(62, 'Luxor', 'Egypt', 'The world’s greatest open-air museum of ancient temples and tombs.', 'uploads/destinations/destination-680fe4db57cbd-1745872091.png', 4, '2025-04-25 17:00:00'),
(63, 'Montreal', 'Canada', 'Blend of European charm and vibrant festivals in every season.', 'uploads/destinations/destination-680fe4b07eb98-1745872048.jpg', 4, '2025-04-25 18:00:00'),
(64, 'Jerusalem', 'Palestine', 'A holy city rich in spiritual significance and historic landmarks.', 'uploads/destinations/destination-680fe47aa7c2d-1745871994.jpg', 4, '2025-04-25 19:00:00'),
(65, 'Siem Reap', 'Cambodia', 'Gateway to the incredible Angkor Wat temple complex.', 'uploads/destinations/destination-680fe454a3158-1745871956.jpg', 4, '2025-04-25 20:00:00'),
(67, 'Baliafii', 'Armenia', 'Destination deleted successfully!', 'uploads/destinations/destination-680fe42edcc17-1745871918.jpg', 4, '2025-04-26 00:15:19'),
(69, 'Bali', 'Angola', 'enjoyyyyy our trippp', 'uploads/destinations/destination-6810d48fdd660-1745933455.png', 47, '2025-04-29 12:30:55');

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
('DoctrineMigrations\\Version20250402Add_IsBanned', '2025-04-03 00:03:49', 290),
('DoctrineMigrations\\Version20250510SustainabilityFields', '2025-05-10 14:06:05', 39);

-- --------------------------------------------------------

--
-- Table structure for table `faceid_sessions`
--

CREATE TABLE `faceid_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 day),
  `device_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faceid_sessions`
--

INSERT INTO `faceid_sessions` (`id`, `user_id`, `token`, `created_at`, `expires_at`, `device_info`) VALUES
(33, 40, '191d723a472c40f0f6d8e0f1cfe8b18bea96cca5abd4a3d888902458f961ae10', '2025-04-28 10:46:01', '2025-04-29 10:46:01', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(34, 40, '0f6bedc225b9315e30f08ab4e6146b2d92d6aa2024e6961bc250805b1b0d40f1', '2025-04-29 02:39:48', '2025-04-30 02:39:48', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'),
(35, 40, '1cf776b5c86374d06410cf228689ba4508b97372b81761db8b2be5b8cbbe607e', '2025-04-29 09:35:46', '2025-04-30 09:35:46', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(9, 4, 101, 'Cultural Immersion in the City', 'Nothing beats the thrill of exploring new places and cultures. This one hit all the right notes.', 'images-10-6810b96a3b84c.jpg', '2025-04-20 11:00:00'),
(11, 4, 103, 'From Peak to Paradise', 'Views that took our breath away and memories that will last a lifetime.', '501453311-6810b98d301d4.jpg', '2025-04-20 13:00:00'),
(13, 4, 105, 'Sky High and Smiling', 'Adrenaline, awe, and a bit of altitude! What an epic day out.', 'hq720-6810b91ed5d18.jpg', '2025-04-20 15:00:00'),
(14, 4, 106, 'Tastes of Tradition', 'We laughed, we learned, and we feasted. A perfect cultural dive.', 'images-9-6810b8e29b844.jpg', '2025-04-20 16:00:00'),
(15, 4, 107, 'Dancing With Locals', 'Music, movement, and magic – a celebration of local life and energy.', '46f17b9ef62f42ee9a96e050b36dc20f-6810b8c648484.jpg', '2025-04-20 17:00:00'),
(16, 4, 108, 'Nature’s Symphony', 'Surrounded by nature’s finest melodies and colors, I found peace.', 'images-8-6810b87fd3740.jpg', '2025-04-20 18:00:00'),
(17, 4, 109, 'Calm Before the Climb', 'Challenging yet rewarding. Every step brought us closer to the sky.', 'images-7-6810b860c4d71.jpg', '2025-04-20 19:00:00'),
(18, 4, 137, 'Lost in the Moment', 'Every moment felt like a dream frozen in time. Pure magic.', 'ab67616d0000b273c1df1a17390d131c354ff85f-6810b8339bcf8.jpg', '2025-04-21 10:00:00'),
(19, 4, 102, 'Views Worth the Climb', 'The higher we climbed, the more stunning the view became.', 'The-View-Is-Worth-the-Climb-6810b8145d8ea.jpg', '2025-04-21 11:00:00'),
(20, 4, 113, 'Colors of the City', 'Bright colors, friendly faces, and stories at every turn.', 'City-Lights-Color-image-PowerPoint-6810b7f5dabff.jpg', '2025-04-21 12:00:00'),
(21, 4, 133, 'Under the Stars', 'That night under the stars will live forever in my memory.', 'stars-feature-6810b7cf65cad.jpg', '2025-04-21 13:00:00'),
(22, 4, 118, 'Seashell Stories', 'Collected stories like seashells – one for every smile.', 'images-6-6810b7ab56c01.jpg', '2025-04-21 14:00:00'),
(23, 4, 124, 'A Day to Remember', 'Perfect day. Perfect people. Perfect memories.', 'images-5-6810b78a9759e.jpg', '2025-04-21 15:00:00'),
(24, 4, 136, 'Flavors of the Market', 'Tasted, touched, and treasured every second of it.', 'natural-food-flavors-market-2023-2032-6810b75cbbeb3.jpg', '2025-04-21 16:00:00'),
(25, 4, 111, 'Steps Through Time', 'History whispered through the walls as we wandered.', '082512-0029-1-6810b73454d7e.png', '2025-04-21 17:00:00'),
(26, 4, 104, 'Chasing Sunsets', 'Golden hour cast everything in soft wonder.', '1587128557290-6810b6d77650f.jpg', '2025-04-21 18:00:00'),
(27, 4, 100, 'Between Sky and Sea', 'We found peace where the sky met the sea.', 'Between-Sea-And-Sky-34335644-6810b6b5e844a.jpg', '2025-04-21 19:00:00'),
(28, 4, 117, 'Sands and Stories', 'Warm sand, cool breeze, and laughter in the air.', 'images-4-6810b4761dc6a.jpg', '2025-04-21 20:00:00'),
(29, 4, 104, 'Echoes of History', 'Ancient ruins with timeless tales.', '1715601123546-436f709a81a446bccaae37a60e471645-6810b44f1beb2.jpg', '2025-04-21 21:00:00'),
(30, 4, 122, 'Into the Wild', 'We followed the wild path and found ourselves.', 'intothewildcouv1-1030x689-6810b42c65abb.jpg', '2025-04-21 22:00:00'),
(31, 4, 101, 'Calm Currents', 'Water so calm it mirrored our joy.', 'Screenshot-2023-04-23-at-2-06-36-AM-6810b3dde1f5a.jpg', '2025-04-21 23:00:00'),
(32, 4, 106, 'Rooftop Rhythms', 'Music and movement under the open sky.', 'images-3-6810b3a963863.jpg', '2025-04-22 00:00:00'),
(33, 4, 115, 'Beneath Ancient Walls', 'Stone walls and soul-stirring silence.', 'excavation-under-temple-mount-6810b38c64fce.jpg', '2025-04-22 01:00:00'),
(34, 4, 123, 'Desert Dreams', 'Endless dunes and endless dreams.', 'desert-dreams-6810b3632e9e0.jpg', '2025-04-22 02:00:00'),
(35, 4, 110, 'Lantern Light Adventures', 'Paper lanterns guiding our journey.', 'images-2-6810b3403921a.jpg', '2025-04-22 03:00:00'),
(36, 4, 135, 'Market Maze', 'Winding alleys full of scents and sounds.', 'Corn-Maze-Pingles-Farm-Market-6810b30f48e38.jpg', '2025-04-22 04:00:00'),
(37, 4, 126, 'Cityscape Serenity', 'City views that take your breath away.', '630355376-6810b2ef8479a.jpg', '2025-04-22 05:00:00');

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
(10, '2025-04-25 12:00:00', 28, 129, 2, 500, 250, 'confirmed'),
(11, '2025-04-25 12:01:00', 28, 130, 1, 90, 90, 'confirmed'),
(12, '2025-04-25 12:02:00', 28, 131, 3, 150, 50, 'confirmed'),
(13, '2025-04-25 12:03:00', 28, 132, 2, 400, 200, 'confirmed'),
(14, '2025-04-25 12:04:00', 28, 133, 5, 1250, 250, 'confirmed'),
(15, '2025-04-25 12:05:00', 28, 134, 3, 450, 150, 'confirmed'),
(16, '2025-04-25 12:06:00', 28, 135, 1, 300, 300, 'confirmed'),
(17, '2025-04-25 12:07:00', 28, 136, 5, 450, 90, 'confirmed'),
(18, '2025-04-25 12:08:00', 28, 137, 5, 450, 90, 'confirmed'),
(19, '2025-04-25 12:09:00', 28, 138, 2, 400, 200, 'confirmed'),
(20, '2025-04-25 12:10:00', 28, 139, 1, 150, 150, 'confirmed'),
(21, '2025-04-25 12:11:00', 28, 140, 1, 120, 120, 'confirmed'),
(22, '2025-04-25 12:12:00', 28, 141, 4, 360, 90, 'confirmed'),
(23, '2025-04-25 12:13:00', 28, 142, 4, 1200, 300, 'confirmed'),
(24, '2025-04-25 12:14:00', 28, 143, 4, 280, 70, 'confirmed'),
(25, '2025-04-25 12:15:00', 28, 144, 3, 600, 200, 'confirmed'),
(26, '2025-04-25 12:16:00', 28, 145, 4, 200, 50, 'confirmed'),
(27, '2025-04-25 12:17:00', 28, 146, 3, 900, 300, 'confirmed'),
(28, '2025-04-25 12:18:00', 28, 147, 3, 600, 200, 'confirmed'),
(29, '2025-04-25 12:19:00', 28, 148, 5, 350, 70, 'confirmed'),
(30, '2025-04-25 12:20:00', 28, 149, 4, 400, 100, 'confirmed'),
(31, '2025-04-25 12:21:00', 28, 150, 2, 200, 100, 'confirmed'),
(32, '2025-04-25 12:22:00', 28, 151, 4, 280, 70, 'confirmed'),
(33, '2025-04-25 12:23:00', 28, 152, 5, 1500, 300, 'confirmed'),
(34, '2025-04-25 12:24:00', 28, 153, 5, 750, 150, 'confirmed'),
(35, '2025-04-25 12:25:00', 28, 154, 2, 140, 70, 'confirmed'),
(36, '2025-04-25 12:26:00', 28, 155, 5, 500, 100, 'confirmed'),
(37, '2025-04-25 12:27:00', 28, 156, 4, 360, 90, 'confirmed'),
(38, '2025-04-25 12:28:00', 28, 157, 1, 300, 300, 'confirmed'),
(39, '2025-04-25 12:29:00', 28, 158, 3, 750, 250, 'confirmed'),
(40, '2025-04-25 14:31:47', 40, 159, 1, 90, 90, 'confirmed'),
(41, '2025-04-25 16:35:48', 40, 160, 1, 80, 80, 'confirmed'),
(42, '2025-04-25 16:53:37', 40, 161, 1, 80, 80, 'confirmed'),
(43, '2025-04-25 16:54:11', 40, 162, 1, 80, 80, 'Cancelled'),
(44, '2025-04-25 20:41:37', 40, 163, 2, 240, 120, 'confirmed'),
(45, '2025-04-25 21:28:06', 40, 164, 1, 80, 80, 'confirmed'),
(46, '2025-04-25 21:28:07', 40, 165, 1, 80, 80, 'confirmed'),
(47, '2025-04-25 21:41:45', 40, 166, 3, 270, 90, 'confirmed'),
(48, '2025-04-25 21:41:59', 40, 167, 3, 270, 90, 'confirmed'),
(49, '2025-04-27 10:35:48', 40, 168, 2, 818, 409, 'confirmed'),
(50, '2025-04-28 11:00:19', 42, 169, 1, 409, 409, 'confirmed'),
(51, '2025-04-29 03:13:56', 45, 170, 1, 120, 120, 'confirmed'),
(55, '2025-04-29 13:10:34', 42, 176, 1, 120, 120, 'confirmed'),
(56, '2025-04-29 13:25:54', 45, 177, 1, 75, 150, 'confirmed');

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
(101, '/uploads/activities/activity-680f992447724-1745852708.jpg', 100),
(102, '/uploads/activities/activity-680f9947326b1-1745852743.jpg', 101),
(103, '/uploads/activities/activity-680f998659da6-1745852806.jpg', 102),
(104, '/uploads/activities/activity-680f99ab83f10-1745852843.jpg', 103),
(105, '/uploads/activities/activity-680f99d5d77fa-1745852885.jpg', 104),
(106, '/uploads/activities/activity-680f9a06ed709-1745852934.jpg', 105),
(107, '/uploads/activities/activity-680f9a52055ea-1745853010.jpg', 106),
(108, '/uploads/activities/activity-680f9aa10737b-1745853089.jpg', 107),
(109, '/uploads/activities/activity-680f9b73f2061-1745853299.png', 108),
(110, '/uploads/activities/activity-680f9bc8d890e-1745853384.jpg', 109),
(111, '/uploads/activities/activity-680f9c2688a11-1745853478.jpg', 110),
(112, '/uploads/activities/activity-680f9c7767a4d-1745853559.jpg', 111),
(113, '/uploads/activities/activity-680f9d0c1f283-1745853708.jpg', 112),
(114, '/uploads/activities/activity-680f9d4fa3fa1-1745853775.jpg', 113),
(115, '/uploads/activities/activity-680f9ddc92a22-1745853916.jpg', 114),
(116, '/uploads/activities/activity-680f9e470db2d-1745854023.jpg', 115),
(117, '/uploads/activities/activity-680f9e9795859-1745854103.jpg', 116),
(118, '/uploads/activities/activity-680f9ecbea540-1745854155.jpg', 117),
(119, '/uploads/activities/activity-680f9f2362c59-1745854243.jpg', 118),
(120, '/uploads/activities/activity-680f9f67bf1bf-1745854311.jpg', 122),
(121, '/uploads/activities/activity-680f9fa41b78c-1745854372.jpg', 123),
(122, '/uploads/activities/activity-680f9fd99c37c-1745854425.jpg', 124),
(124, '/uploads/activities/activity-680fe09bd3a93-1745871003.jpg', 139),
(125, '/uploads/activities/activity-680fe0c91e439-1745871049.jpg', 138),
(126, '/uploads/activities/activity-680fe0fa1281b-1745871098.jpg', 137),
(127, '/uploads/activities/activity-680fe1736bc3c-1745871219.jpg', 136),
(128, '/uploads/activities/activity-680fe1ab8b83d-1745871275.jpg', 135),
(129, '/uploads/activities/activity-680fe1cb38ae1-1745871307.jpg', 134),
(130, '/uploads/activities/activity-680fe1f75b188-1745871351.jpg', 133),
(131, '/uploads/activities/activity-680fe226b2090-1745871398.jpg', 132),
(132, '/uploads/activities/activity-680fe24821382-1745871432.jpg', 131),
(133, '/uploads/activities/activity-680fe266e530d-1745871462.jpg', 130),
(134, '/uploads/activities/activity-680fe2853547a-1745871493.jpg', 129),
(135, '/uploads/activities/activity-680fe2a8d4ebc-1745871528.jpg', 128),
(136, '/uploads/activities/activity-680fe2ccec19d-1745871564.jpg', 127),
(137, '/uploads/activities/activity-680fe2f1826da-1745871601.jpg', 126),
(138, '/uploads/activities/activity-680fe3374b30b-1745871671.jpg', 125),
(139, '/uploads/activities/activity-680fe362e51c9-1745871714.jpg', 124);

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
(13, 40, '2025-04-22 10:24:26', 'approved', '2025-04-24 11:16:34', 'i would like to be a Publicator'),
(14, 47, '2025-04-29 11:22:34', 'approved', '2025-04-29 11:28:51', 'i wannt to be an admin');

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
  `totp_enabled` tinyint(4) NOT NULL DEFAULT 1,
  `faceid_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `faceid_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `email`, `password`, `gender`, `role`, `phone`, `birthday`, `verification_code`, `enabled`, `created_at`, `image`, `is_banned`, `totp_secret`, `totp_enabled`, `faceid_enabled`, `faceid_data`) VALUES
(4, 'Karim', 'Makni', 'usualmuire@indigobook.com', '$2y$13$nIZzjsmGwdgZtxK3C964B.PhPKOPTrQFXUf5wdKjEhpr1OUENikXe', 'Male', 'Publicitaire', '+21650034045', '1999-06-11', 'd755b87a0bd8e261bc663445c33b4bb8', 1, '2025-04-25 18:07:47', NULL, 0, 'WRGTGCEHPTSED4ZQPNYK4CZ3N4N35SHHEZO42PFRMBEJIYXCV3UA====', 1, 0, NULL),
(28, 'Amir', 'Othman', 'amirothmaneee@gmail.com', '$2y$13$fasEV95xWkDMUknjeKKYPeO3gyV76Y2yM5689p3HFUggd/SGSjm3m', 'Male', 'Publicitaire', '+21656080708', '2025-03-30', NULL, 1, '2025-03-30 18:03:46', 'WhatsApp-Image-2025-03-02-at-12-27-03-AM-67e987716365c.jpg', 0, NULL, 0, 0, NULL),
(40, 'Amir', 'Jabeur', 'aronxothman@gmail.com', '$2y$13$SgH.DPj1F3HOZkKEgijeCe.QgykSRH/D39NIhZEnDXsU6X30elfcu', 'Male', 'Publicitaire', '+21699091975', '2025-04-18', '6054e431c235d58711b589baf9f3f183', 1, '2025-04-22 10:20:58', NULL, 0, 'X6G5YMJRMM4T7VZUMWT6MXP46KAHLEHBDWLMKSTPZFOMTUYMLCAA====', 1, 1, '[-0.09101969003677368,0.05621180310845375,0.06823620945215225,-0.00796710979193449,0.029068470001220703,-0.0671747624874115,0.0380515530705452,-0.03139021620154381,0.19187438488006592,-0.014750045724213123,0.21530748903751373,-0.04585587605834007,-0.2251916080713272,-0.013716773129999638,-0.012400914914906025,0.09665724635124207,-0.16648182272911072,-0.0466231033205986,-0.16958202421665192,-0.13869428634643555,0.039498016238212585,0.004601642023772001,0.027451537549495697,0.06841817498207092,-0.11838299036026001,-0.3278138041496277,-0.03149542585015297,-0.18195486068725586,0.08959534764289856,-0.1428821086883545,0.009174403734505177,-0.02022998221218586,-0.17064210772514343,-0.05691646412014961,-0.044760555028915405,-0.0347156822681427,0.009606297127902508,-0.02870282344520092,0.17581139504909515,0.04638194292783737,-0.1428762823343277,0.07047270238399506,0.014346876181662083,0.2928623855113983,0.13948601484298706,0.13543131947517395,0.01581786572933197,-0.03318842872977257,0.10836894810199738,-0.2521018981933594,0.09867920726537704,0.08591792732477188,0.09168725460767746,0.022156883031129837,0.12599150836467743,-0.18979491293430328,-0.03903734311461449,0.03492341190576553,-0.24141258001327515,0.15042153000831604,0.07395657151937485,0.03471061587333679,-0.08909610658884048,-0.05665525048971176,0.2305142879486084,0.12612025439739227,-0.18356572091579437,-0.05951378121972084,0.0800490602850914,-0.1618833839893341,0.027553923428058624,0.06812775135040283,-0.08558066934347153,-0.15613290667533875,-0.25899645686149597,0.11747337877750397,0.4662856161594391,0.10993050038814545,-0.16597433388233185,-0.01224405039101839,-0.1165815070271492,-0.015079188160598278,0.07426777482032776,0.07675547152757645,-0.11098774522542953,0.02029147744178772,-0.03919195756316185,0.03041800670325756,0.06206792965531349,0.05173986032605171,-0.07462269812822342,0.20900622010231018,-0.04798086732625961,-0.005631290841847658,-0.07565127313137054,0.025548599660396576,-0.1902490258216858,-0.030694512650370598,-0.06222335621714592,-0.045650627464056015,0.06211089715361595,0.005202838685363531,0.016680415719747543,0.03738303482532501,-0.15919694304466248,0.14513981342315674,0.002218682551756501,-0.022758523002266884,0.031797174364328384,0.12384363263845444,-0.1062551736831665,-0.04078623279929161,0.1025049239397049,-0.2976419925689697,0.1784587800502777,0.17565661668777466,0.05979923903942108,0.09383762627840042,0.014327965676784515,0.020642409101128578,0.03400006517767906,0.10321594029664993,-0.15449252724647522,-0.05228870362043381,0.0334889255464077,-0.04254143685102463,0.06097587198019028,0.004967527464032173]'),
(42, 'Amine', 'afat', 'jonisrose@chefalicious.com', '$2y$13$2nY2UWS.a9tAQM8CkFP2XuPI0BfB/teUsNXDmPLwceQDoSwq4nqkK', 'Male', 'user', '+21650034045', '1994-04-17', '984aeb7001a792d8b8ac4e70f0cd094d', 1, '2025-04-28 09:06:15', NULL, 0, 'L36P46AXY4DYW6ZSZY3YABQ244DIC5UOTRDODMOAXLMO7XPB5ILQ====', 1, 0, NULL),
(45, 'JUST', 'FOR LOCALHOST', 'justforlocalhost@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$sYXRYb8croL47vHV5T4mmA$2indGibyRORIxL6TFbzvD9/K6ZoqePI+4c5DfUtNKPg', NULL, 'admin', NULL, NULL, NULL, 1, '2025-04-29 00:54:37', 'google_profile:https://lh3.googleusercontent.com/a/ACg8ocKe2Z-adCjrenA-qUVyYZDFbgtxVi-_DML_g_N4doKypgsDaQ=s96-c', 0, NULL, 0, 0, NULL),
(46, 'Amir', 'Othmasn', 'cindyaqua@chefalicious.com', '$2y$13$4zf1YWoHAJKfjlUTcmAmpOQxUqACxEt.4EO3qROhhHtpG7/MfpmVG', 'Male', 'user', '+21650034045', '1998-07-30', '541daa80c5643268430d62fe3d2bd1a8', 0, '2025-04-29 09:12:04', NULL, 0, 'RWL6FR3D2VNCXCGERCCLZVDV7PJ3SCO374SJVZW5SYIKBLIERQBQ====', 0, 0, NULL),
(47, 'circulars', 'apphire', 'circularsapphire@chefalicious.com', '$2y$13$tO.O3cZTUEHABxcEAUXXBODkV0Q5dX3dofw9scJFS4yuIzG9sn/FO', 'Male', 'Publicitaire', '037875823', '2025-04-30', '0d41ab6ed7a1159a3dc6678de7e8c3e9', 1, '2025-04-29 10:16:16', NULL, 0, '4QDZJ6M4KJRBKLP7TIXDZFJGVNWE3SFWKH44S4GSINBQNVQ27EZA====', 1, 0, NULL),
(48, 'joseph', 'gatti', 'corresponding2399@chefalicious.com', '$2y$13$B31QoTq.bH9hAYj0d2VpG.GOoOVN9d5rP3PQMr5PRtANsNQ3nCrcG', 'Male', 'user', '037875823', '1996-06-07', '8dcf0fc6f97a17812f8112995a42cd38', 1, '2025-05-06 09:26:10', NULL, 0, '2FGCNZY2QYCKZ45QFSF33UX3ROIF35YG64IDGJ7QPQLRAJ4XUMJA====', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_login_history`
--

CREATE TABLE `user_login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `login_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `coupon`
--
ALTER TABLE `coupon`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_64BF3F0277153098` (`code`);

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
-- Indexes for table `faceid_sessions`
--
ALTER TABLE `faceid_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Indexes for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  ADD KEY `IDX_75EA56E016BA31DB` (`delivered_at`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`),
  ADD KEY `fk_activity` (`activity_id`);

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
-- Indexes for table `user_login_history`
--
ALTER TABLE `user_login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_LOGIN_USER` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `billet`
--
ALTER TABLE `billet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT for table `blogs_rating`
--
ALTER TABLE `blogs_rating`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `coupon`
--
ALTER TABLE `coupon`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `faceid_sessions`
--
ALTER TABLE `faceid_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user_login_history`
--
ALTER TABLE `user_login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `faceid_sessions`
--
ALTER TABLE `faceid_sessions`
  ADD CONSTRAINT `fk_faceid_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `user_login_history`
--
ALTER TABLE `user_login_history`
  ADD CONSTRAINT `FK_LOGIN_USER_ID` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

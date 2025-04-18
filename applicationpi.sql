-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 18 avr. 2025 à 21:30
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `applicationpi`
--

-- --------------------------------------------------------

--
-- Structure de la table `activities`
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
  `max_number` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `activities`
--

INSERT INTO `activities` (`id`, `activity_name`, `activity_description`, `activity_destination`, `activity_duration`, `activity_price`, `activity_genre`, `user_id`, `activity_date`, `created_at`, `max_number`) VALUES
(68, 'hooking', 'dkjsbfkjqsbfkbj', 'aaaa', '2 days', 200.00, 'Beach', 28, '2025-04-10', '2025-04-03 23:21:05', 50),
(69, 'Hollaaaa', 'ahahhhahah ty brcha jaw', 'aaaa', '2 days', 100.00, 'Cultural', 28, '2025-04-23', '2025-04-07 08:36:36', 100),
(70, 'hahahahah', 'aaaaaaaaaaaaaaaaaaaaa', 'aaaa', '2 days', 200.00, 'Cultural', 28, '2025-04-18', '2025-04-15 11:20:51', 100);

-- --------------------------------------------------------

--
-- Structure de la table `billet`
--

CREATE TABLE `billet` (
  `id` int(11) NOT NULL,
  `prix` double NOT NULL,
  `numero` varchar(50) NOT NULL,
  `activiteId` int(11) NOT NULL,
  `nb` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `billet`
--

INSERT INTO `billet` (`id`, `prix`, `numero`, `activiteId`, `nb`) VALUES
(112, 200, 'TICKET-68024646bd14a', 68, 1);

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `date`, `comment`) VALUES
(6, 6, 28, '2025-04-15 13:00:09', 'bbbb'),
(7, 6, 28, '2025-04-15 13:08:47', 'aaaaaa');

-- --------------------------------------------------------

--
-- Structure de la table `destinations`
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
-- Déchargement des données de la table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `location`, `description`, `image_path`, `user_id`, `created_at`) VALUES
(18, 'kkkkkkkkk', 'Ariana', 'Hannibal country', 'uploads/destinations/destination-67e9980293168-1743362050.png', 28, '2025-03-30 20:14:10'),
(20, 'aaaa', 'blabla', 'qshdbkqsbdkjqsckjbqskjckqjsb', 'uploads/destinations/destination-67ef09a496d7e-1743718820.png', 28, '2025-04-03 23:20:20'),
(23, 'gggggg', 'daaa', 'aaaaaaaaaaaa', 'uploads/destinations/destination-67fe2c9138517-1744710801.png', 28, '2025-04-15 10:53:21');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20250402Add_IsBanned', '2025-04-03 00:03:49', 290);

-- --------------------------------------------------------

--
-- Structure de la table `posts`
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
-- Déchargement des données de la table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `activity_id`, `title`, `description`, `picture`, `date`) VALUES
(6, 28, 68, 'Malla jaw w malla hiya', 'brhca jaw fel hooking hhahha', 'WhatsApp-Image-2025-03-02-at-12-27-03-AM-67fe37564a070.jpg', '2025-04-15 12:39:18');

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
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
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id`, `dateAchat`, `userId`, `billetId`, `nombre`, `prixTotal`, `prixUnite`, `statuts`) VALUES
(0, '2025-04-18 14:32:06', 28, 112, 1, 200, 200, 'confirmed');

-- --------------------------------------------------------

--
-- Structure de la table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `activity_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `resources`
--

INSERT INTO `resources` (`id`, `path`, `activity_id`) VALUES
(50, '/uploads/activities/activity-67ef09d1cb61c-1743718865.webp', 68),
(51, '/uploads/activities/activity-67ef09d1e1c7b-1743718865.jpg', 68),
(52, '/uploads/activities/activity-67ef09d1e2d0c-1743718865.jpg', 68),
(53, '/uploads/activities/activity-67ef09d1e3c28-1743718865.jpg', 68),
(54, '/uploads/activities/activity-67f3808526b36-1744011397.webp', 69),
(55, '/uploads/activities/activity-67fe3303accb2-1744712451.png', 70),
(56, '/uploads/activities/activity-67fe3303adecb-1744712451.png', 70),
(57, '/uploads/activities/activity-67fe3303ae816-1744712451.jpg', 70);

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `nom`) VALUES
(2, 'admin'),
(1, 'client'),
(3, 'Publicitaire');

-- --------------------------------------------------------

--
-- Structure de la table `upgrade_requests`
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
-- Déchargement des données de la table `upgrade_requests`
--

INSERT INTO `upgrade_requests` (`id`, `user_id`, `request_date`, `status`, `processed_date`, `message`) VALUES
(12, 28, '2025-04-07 09:29:09', 'approved', '2025-04-15 13:19:20', 'i wanna be hahaha');

-- --------------------------------------------------------

--
-- Structure de la table `users`
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
  `is_banned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `email`, `password`, `gender`, `role`, `phone`, `birthday`, `verification_code`, `enabled`, `created_at`, `image`, `is_banned`) VALUES
(1, 'SiAiimir', 'Othman', 'tayebgod@gmail.com', 'qqqqqqx', 'Male', 'Publicitaire', '048294234', '2000-02-16', '5443', 1, '2025-02-23 17:18:28', NULL, 0),
(15, 'mihan', 'iihcsa', 'ahmsm3eed@gmail.com', 'qqqqqqx', 'Male', 'Publicitaire', '06324234234', '1998-02-12', NULL, 1, '2025-02-23 17:18:28', NULL, 0),
(16, 'aaaamir', 'othman', 'amir.othman@esprit.tn', 'Amyr2001@', 'Female', 'Publicitaire', '0648324423', '2002-02-14', '1973', 0, '2025-02-23 17:18:28', NULL, 0),
(18, 'si amir', 'safadas', 'amir.othaman@esprit.tn', 'aaaaaa', 'Female', 'user', 'Amyr2001@', '2001-02-02', NULL, 1, '2025-02-23 17:18:28', NULL, 0),
(19, 'amiro', 'oothmsan', 'therealamirothman@gmail.com', 'qqqqqq', 'Male', 'Publicitaire', '0642834234', '1998-02-05', '8064', 1, '2025-02-23 17:18:28', NULL, 0),
(20, 'amir', 'thmsn', 'amyyr.othman@gmail.com', 'aaaaaa', 'Female', 'user', '04723942', '1998-02-05', '1465', 1, '2025-02-23 17:18:28', NULL, 0),
(25, 'amid', 'sdfsf', 'aundrea6263@edny.net', 'qwertyu', 'Male', 'user', '075832534', '1997-02-06', NULL, 1, '2025-02-24 18:40:52', NULL, 0),
(27, 'aami', 'zzzzz', 'amaltr21@gmail.com', 'Amyr2001@', 'Male', 'user', '0575835345', '1998-02-05', NULL, 1, '2025-02-25 14:17:08', NULL, 0),
(28, 'Amir', 'Othman', 'amirothmaneee@gmail.com', '$2y$13$fasEV95xWkDMUknjeKKYPeO3gyV76Y2yM5689p3HFUggd/SGSjm3m', NULL, 'Publicitaire', '27856958', '2025-03-30', NULL, 1, '2025-03-30 18:03:46', 'WhatsApp-Image-2025-03-02-at-12-27-03-AM-67e987716365c.jpg', 0),
(29, 'lemaalem', 'nihed', 'nihedabdennour7@gmail.com', '$2y$13$VygCpOtBLK15b3vDBFpxVeoBmBEprCjoqe9dUCO8mWbU3qD0s4QPO', 'Female', 'user', '27856958', '2025-01-11', NULL, 1, '2025-04-08 09:54:12', NULL, 0);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `billet`
--
ALTER TABLE `billet`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post` (`post_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Index pour la table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_activity` (`activity_id`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_ibfk_1` (`userId`),
  ADD KEY `reservation_ibfk_2` (`billetId`);

--
-- Index pour la table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `billet`
--
ALTER TABLE `billet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `destinations`
--
ALTER TABLE `destinations`
  ADD CONSTRAINT `destinations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `upgrade_requests`
--
ALTER TABLE `upgrade_requests`
  ADD CONSTRAINT `upgrade_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

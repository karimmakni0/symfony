-- Add Face ID support to users table
ALTER TABLE `users` 
ADD COLUMN `faceid_enabled` TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN `faceid_data` TEXT DEFAULT NULL;

-- Create a table to store Face ID authentication sessions
CREATE TABLE `faceid_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 DAY),
  `device_info` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_faceid_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

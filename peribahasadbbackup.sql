-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for peribahasa_db
CREATE DATABASE IF NOT EXISTS `kerteh_peribahasa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `kerteh_peribahasa`;

-- Dumping structure for table peribahasa_db.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peribahasa_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `peribahasa_id` (`peribahasa_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`peribahasa_id`) REFERENCES `peribahasa` (`id`),
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table peribahasa_db.comments: ~0 rows (approximately)
INSERT INTO `comments` (`id`, `peribahasa_id`, `user_id`, `comment`, `created_at`) VALUES
	(1, 2, 2, 'Terbaik', '2024-11-15 18:36:58'),
	(2, 3, 3, 'Sangat baik kawan Ikhwan', '2024-11-15 18:37:20');

-- Dumping structure for table peribahasa_db.daily_peribahasa
CREATE TABLE IF NOT EXISTS `daily_peribahasa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peribahasa_id` int NOT NULL,
  `display_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`display_date`),
  KEY `peribahasa_id` (`peribahasa_id`),
  CONSTRAINT `daily_peribahasa_ibfk_1` FOREIGN KEY (`peribahasa_id`) REFERENCES `peribahasa` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table peribahasa_db.daily_peribahasa: ~0 rows (approximately)
INSERT INTO `daily_peribahasa` (`id`, `peribahasa_id`, `display_date`, `created_at`) VALUES
	(1, 1, '2024-11-15', '2024-11-15 15:15:09');

-- Dumping structure for table peribahasa_db.peribahasa
CREATE TABLE IF NOT EXISTS `peribahasa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `meaning` text NOT NULL,
  `example_usage` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `contributor_id` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `contributor_id` (`contributor_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `peribahasa_ibfk_1` FOREIGN KEY (`contributor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `peribahasa_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table peribahasa_db.peribahasa: ~0 rows (approximately)
INSERT INTO `peribahasa` (`id`, `title`, `meaning`, `example_usage`, `status`, `contributor_id`, `approved_by`, `created_at`, `updated_at`) VALUES
	(1, 'Bagai aur dengan tebing', 'Hubungan yang rapat', 'Hubungan kami adik beradik bagai aur dengan tebing.', 'approved', 2, 2, '2024-11-15 15:08:42', '2024-11-15 17:34:25'),
	(2, 'Melentur buluh, biarlah dari rebungnya', 'Mendidik dari kecil', 'Anak2 perlu di didik dari kecil agar mereka berbudi bahasa pada masa akan datang.', 'approved', 3, 2, '2024-11-15 15:38:12', '2024-11-15 15:38:49'),
	(3, 'Bagai bulan jatuh ke riba', 'Rezeki yang tiba-tiba datang ke kita', 'Ikhwan sungguh bertuah untuk mendapat RM1000 dari kawan nya, seolah-olah bagai bulan jatuh ke riba.', 'approved', 2, 2, '2024-11-15 17:35:37', '2024-11-15 17:35:37');

-- Dumping structure for table peribahasa_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','moderator','contributor') NOT NULL DEFAULT 'contributor',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table peribahasa_db.users: ~1 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `updated_at`, `is_active`) VALUES
	(1, 'admin', 'admin@peribahasa.com', '$2y$10$8KyAH0jrYqXg3nGD6x5EE.6VqR0tR7W9M5V5D8jJzx5ZHwB5XvGPi', 'admin', '2024-11-15 12:42:45', '2024-11-15 12:42:45', 1),
	(2, 'Ikhwan', 'ikhwan@gmail.com', '$2y$10$sbiYsPrFMFKeRKdfjpqwO.v/pMghhASZeezVUK7IjfoTwe6IoUmoG', 'admin', '2024-11-15 14:57:49', '2024-11-15 15:01:58', 1),
	(3, 'Itqan Azis', 'itqan@itqan.com', '$2y$10$LBgSThDb43Jw.4KPYOaDG.5MyRxB9qH8n9R334Fh9sY6q/DuZ/tOC', 'moderator', '2024-11-15 15:37:18', '2024-11-15 17:24:40', 1),
	(4, 'Zahin', 'zahin@gam.com', '$2y$10$VmhzNQDaW.1o2cxYriGrhOYu/3sm3yfZGTUImO2nLqQsXtVhbv0mS', 'contributor', '2024-11-15 18:38:19', '2024-11-15 18:38:19', 1);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: localhost    Database: forum_db
-- ------------------------------------------------------
-- Server version	8.0.40
create database IF NOT EXISTS forum_db;
use forum_db;
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_login`
--

DROP TABLE IF EXISTS `admin_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_login` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_login`
--

LOCK TABLES `admin_login` WRITE;
/*!40000 ALTER TABLE `admin_login` DISABLE KEYS */;
INSERT INTO `admin_login` VALUES (1,'admin@example.com','admin123','2025-01-16 18:41:20');
/*!40000 ALTER TABLE `admin_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `comments_ibfk_3` (`parent_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (73,75,23,'eeee',NULL,'2025-02-28 18:53:49'),(74,77,23,'dw',NULL,'2025-02-28 20:59:43'),(75,180,27,'wedc',NULL,'2025-02-28 21:08:42');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usability` tinyint NOT NULL,
  `design` tinyint NOT NULL,
  `features` tinyint NOT NULL,
  `satisfaction` tinyint NOT NULL,
  `comments` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avg_rating` decimal(3,2) DEFAULT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `feedback_chk_1` CHECK (`usability` >= 1 AND `usability` <= 5),
  CONSTRAINT `feedback_chk_2` CHECK (`design` >= 1 AND `design` <= 5),
  CONSTRAINT `feedback_chk_3` CHECK (`features` >= 1 AND `features` <= 5),
  CONSTRAINT `feedback_chk_4` CHECK (`satisfaction` >= 1 AND `satisfaction` <= 5)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,3,3,2,1,'edew','2025-02-28 19:58:28',2.25,23),(2,1,2,3,4,'edwedwedw','2025-02-28 20:59:32',2.00,23),(3,3,3,4,3,'ok','2025-02-28 21:03:35',3.00,27);
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `privacy` enum('public','private') DEFAULT 'public',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (74,23,'sdsds','sdsdssdsdssdsds','2025-02-23 18:26:31',NULL,NULL,'Entertainment','public'),(75,23,'sdsds','sdsdssdsdssdsds','2025-02-23 18:26:35',NULL,NULL,'Entertainment','public'),(76,23,'sdsds','sdsdssdsdssdsds','2025-02-23 18:30:10',NULL,NULL,'Entertainment','public'),(77,25,'wifi problem','wifi problem wifi problemwifi problem wifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problem wifi problem wifi problemwifi problem wifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problem wifi problemwifi problem wifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problemwifi problem','2025-02-24 05:59:03','img_67bc0aa76005b.jpeg',NULL,'Technology','public'),(175,23,'eded','ewded','2025-02-28 19:06:50',NULL,NULL,'Nikhil Wagh','public'),(176,23,'sample1','sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1 sample1','2025-02-28 19:30:26','img_67c20ed2da53c.jpg','vid_67c20ed2db1ba.mp4','Lifestyle','private'),(177,23,'sample12','sample12 sample12sample12 sample12 sample12','2025-02-28 19:31:51',NULL,NULL,'Health','public'),(180,27,'edewdwe','edwedwe','2025-02-28 21:08:16',NULL,NULL,'Entertainment','public');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reactions`
--

DROP TABLE IF EXISTS `reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reactions` (
  `reaction_id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reaction` enum('like','dislike') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reaction_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reactions`
--

LOCK TABLES `reactions` WRITE;
/*!40000 ALTER TABLE `reactions` DISABLE KEYS */;
INSERT INTO `reactions` VALUES (1,60,14,'like','2025-01-19 05:21:22'),(2,53,14,'like','2025-01-19 05:21:29'),(3,61,15,'like','2025-01-19 05:39:08'),(4,61,12,'like','2025-01-19 05:23:01'),(5,53,15,'like','2025-01-19 05:39:25'),(6,53,12,'dislike','2025-01-19 05:28:34'),(7,64,19,'like','2025-01-19 05:46:48'),(8,70,20,'like','2025-01-19 08:39:04'),(9,71,20,'like','2025-01-24 16:46:42'),(10,63,20,'like','2025-01-24 16:47:02'),(11,72,21,'like','2025-02-17 04:15:43'),(12,73,22,'like','2025-02-17 07:50:04'),(13,87,26,'like','2025-02-24 15:05:23'),(14,98,26,'like','2025-02-24 15:05:54'),(15,117,26,'like','2025-02-28 13:33:24'),(16,174,26,'like','2025-02-28 18:42:07'),(17,173,26,'like','2025-02-28 18:42:11'),(18,172,26,'like','2025-02-28 18:42:16'),(19,161,26,'dislike','2025-02-28 18:42:40'),(20,74,26,'like','2025-02-28 18:50:05'),(21,75,26,'like','2025-02-28 18:50:41'),(22,167,26,'dislike','2025-02-28 18:51:09'),(23,170,23,'like','2025-02-28 18:53:21'),(24,75,23,'dislike','2025-02-28 18:53:44'),(25,180,27,'like','2025-02-28 21:08:28');
/*!40000 ALTER TABLE `reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `replies`
--

DROP TABLE IF EXISTS `replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `comment_id` (`comment_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `replies_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `replies`
--

LOCK TABLES `replies` WRITE;
/*!40000 ALTER TABLE `replies` DISABLE KEYS */;
INSERT INTO `replies` VALUES (12,75,27,'ds','2025-02-28 21:08:44');
/*!40000 ALTER TABLE `replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `login_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (21,'Shubham','shubham1','s@gmail.com','$2y$10$pQ89PYxBxm6BPQnraQzQve5o/.WtXiqfjCbOiKsuZbstOr0CxmfhG','2025-02-17 04:14:29',NULL),(22,'tushar','tushar121','tushar@gmail.com','$2y$10$5TAzBZ5QGU50kNeFrVeT4e49TH138fXJio3Rrjogjvk.HtzN8kF3W','2025-02-17 07:48:53',NULL),(23,'CM NIKHIL WAGH','nikhil_wagh101','nwagh008@gmail.com','$2y$10$e.07AAbd2jEDNMioUE8QQeI5KhbWJLtzyG08.99vsaSIJteF0tSZG','2025-02-23 18:24:15',NULL),(24,'Aryan Sable','aryan121','ars123@gmail.com','$2y$10$o.PSewSlicBy2ojK3AxGWuP/7BeNx/D37kgroJaHjbU0Rx7NAGQua','2025-02-24 04:32:00',NULL),(25,'Sujal Tawale','ssss@22','sss@gmail.com','$2y$10$XB./Z52XoEHPRDbLj00K1ucJYzHc2E6GTdCPgO3I.mBc2Y4kCTtVG','2025-02-24 05:56:23',NULL),(27,'madura Lute','madura Lute 121','madura@gmail.com','$2y$10$pQSKwUHQCFrutauM0aqfVuEIxCks0GLMF6trYFXK9MtoXMV6G6xPu','2025-02-28 21:01:06',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-01  4:14:09

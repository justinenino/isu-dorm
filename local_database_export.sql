-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: dormitory_management
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary table structure for view `active_announcements`
--

DROP TABLE IF EXISTS `active_announcements`;
/*!50001 DROP VIEW IF EXISTS `active_announcements`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `active_announcements` AS SELECT
 1 AS `id`,
  1 AS `title`,
  1 AS `content`,
  1 AS `category`,
  1 AS `priority`,
  1 AS `status`,
  1 AS `published_at`,
  1 AS `expires_at`,
  1 AS `is_pinned`,
  1 AS `is_archived`,
  1 AS `view_count`,
  1 AS `like_count`,
  1 AS `created_by`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Dorm_admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2025-07-31 17:41:45','2025-09-03 15:46:57');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_comment_likes`
--

DROP TABLE IF EXISTS `announcement_comment_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_comment_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`comment_id`,`student_id`,`admin_id`),
  KEY `student_id` (`student_id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_comment_id` (`comment_id`),
  CONSTRAINT `announcement_comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `announcement_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comment_likes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comment_likes_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_comment_likes`
--

LOCK TABLES `announcement_comment_likes` WRITE;
/*!40000 ALTER TABLE `announcement_comment_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_comment_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_comment_replies`
--

DROP TABLE IF EXISTS `announcement_comment_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_comment_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_comment_id` (`comment_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_is_deleted` (`is_deleted`),
  CONSTRAINT `announcement_comment_replies_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `announcement_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comment_replies_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comment_replies_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_comment_replies`
--

LOCK TABLES `announcement_comment_replies` WRITE;
/*!40000 ALTER TABLE `announcement_comment_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcement_comment_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_comments`
--

DROP TABLE IF EXISTS `announcement_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_announcement` (`announcement_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_student_id_negative` (`student_id`),
  KEY `idx_announcement_comments_lookup` (`announcement_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_comments`
--

LOCK TABLES `announcement_comments` WRITE;
/*!40000 ALTER TABLE `announcement_comments` DISABLE KEYS */;
INSERT INTO `announcement_comments` VALUES (2,14,16,NULL,'heyyyy','2025-09-14 13:10:11','2025-09-14 13:10:11',0),(3,16,NULL,1,'kindly check this annooncement\'','2025-09-14 13:31:52','2025-09-14 13:31:52',0),(8,12,-1,NULL,'kindly be polite','2025-09-14 14:32:32','2025-09-14 14:32:32',0),(19,14,-1,NULL,'eyy','2025-09-14 14:38:12','2025-09-14 14:38:12',0),(25,14,-1,NULL,'hello','2025-09-14 14:52:55','2025-09-14 14:52:55',0),(26,14,-1,NULL,'hello','2025-09-14 14:54:51','2025-09-14 14:54:51',0),(27,1,-1,NULL,'Test admin comment','2025-09-14 15:07:31','2025-09-14 15:07:31',0),(28,4,1,NULL,'fdfgdg','2025-09-20 15:39:23','2025-09-20 15:39:23',0),(29,4,1,NULL,'dfedf','2025-09-20 15:41:34','2025-09-20 15:41:34',0),(30,4,1,NULL,'hello','2025-09-20 15:45:20','2025-09-20 15:45:20',0),(31,4,NULL,1,'wassup','2025-09-20 15:54:13','2025-09-20 16:02:31',1),(32,4,NULL,1,'wassup','2025-09-20 15:54:14','2025-09-20 16:02:34',1),(33,4,NULL,1,'wassup','2025-09-20 15:54:17','2025-09-20 16:02:36',1),(34,4,NULL,1,'wassup','2025-09-20 15:54:17','2025-09-20 16:02:37',1),(35,4,NULL,1,'wassup','2025-09-20 15:54:18','2025-09-20 15:54:18',0),(36,4,NULL,1,'vbcvbv','2025-09-20 15:57:57','2025-09-20 15:57:57',0),(37,4,NULL,1,'hi guys','2025-09-20 16:02:45','2025-09-20 16:02:45',0),(38,4,NULL,1,'hello?','2025-09-20 16:09:31','2025-09-20 16:09:31',0),(39,4,1,NULL,'hiii','2025-09-20 16:11:07','2025-09-20 16:11:07',0),(40,4,1,NULL,'hii','2025-09-20 16:23:44','2025-09-20 16:23:44',0),(41,4,NULL,1,'its mee the admin','2025-09-20 16:28:24','2025-09-20 16:28:24',0);
/*!40000 ALTER TABLE `announcement_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `announcement_comments_with_author`
--

DROP TABLE IF EXISTS `announcement_comments_with_author`;
/*!50001 DROP VIEW IF EXISTS `announcement_comments_with_author`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `announcement_comments_with_author` AS SELECT
 1 AS `id`,
  1 AS `announcement_id`,
  1 AS `comment`,
  1 AS `created_at`,
  1 AS `updated_at`,
  1 AS `student_id`,
  1 AS `author_type`,
  1 AS `author_name`,
  1 AS `school_id` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `announcement_interactions`
--

DROP TABLE IF EXISTS `announcement_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `interaction_type` enum('like','acknowledge','view') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interaction` (`announcement_id`,`student_id`,`interaction_type`),
  KEY `idx_announcement_interactions_announcement` (`announcement_id`),
  KEY `idx_announcement_interactions_student` (`student_id`),
  KEY `idx_interactions_announcement` (`announcement_id`),
  KEY `idx_interactions_student` (`student_id`),
  KEY `idx_interactions_type` (`interaction_type`),
  CONSTRAINT `announcement_interactions_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_interactions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_interactions`
--

LOCK TABLES `announcement_interactions` WRITE;
/*!40000 ALTER TABLE `announcement_interactions` DISABLE KEYS */;
INSERT INTO `announcement_interactions` VALUES (1,12,1,'acknowledge','2025-09-06 15:51:30'),(2,4,1,'acknowledge','2025-09-07 02:35:58'),(3,4,2,'acknowledge','2025-09-07 13:57:08'),(4,12,2,'acknowledge','2025-09-07 13:57:09'),(5,12,16,'acknowledge','2025-09-14 12:39:46'),(7,16,1,'acknowledge','2025-09-14 13:32:26');
/*!40000 ALTER TABLE `announcement_interactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_likes`
--

DROP TABLE IF EXISTS `announcement_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `liked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`announcement_id`,`student_id`),
  KEY `idx_announcement_likes_announcement` (`announcement_id`),
  KEY `idx_announcement_likes_student` (`student_id`),
  KEY `idx_likes_announcement` (`announcement_id`),
  KEY `idx_likes_student` (`student_id`),
  CONSTRAINT `announcement_likes_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_likes_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_likes`
--

LOCK TABLES `announcement_likes` WRITE;
/*!40000 ALTER TABLE `announcement_likes` DISABLE KEYS */;
INSERT INTO `announcement_likes` VALUES (1,12,1,'2025-09-06 15:51:26'),(2,12,2,'2025-09-07 13:57:04'),(3,4,2,'2025-09-07 13:57:07'),(4,16,1,'2025-09-14 13:32:26'),(6,4,1,'2025-09-20 15:44:38');
/*!40000 ALTER TABLE `announcement_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcement_views`
--

DROP TABLE IF EXISTS `announcement_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_view` (`announcement_id`,`student_id`),
  KEY `idx_announcement_views_announcement` (`announcement_id`),
  KEY `idx_announcement_views_student` (`student_id`),
  KEY `idx_views_announcement` (`announcement_id`),
  KEY `idx_views_student` (`student_id`),
  CONSTRAINT `announcement_views_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_views_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcement_views`
--

LOCK TABLES `announcement_views` WRITE;
/*!40000 ALTER TABLE `announcement_views` DISABLE KEYS */;
INSERT INTO `announcement_views` VALUES (1,12,1,'2025-09-06 15:51:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'),(2,4,1,'2025-09-06 15:51:20','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'),(3,12,2,'2025-09-07 13:56:46','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),(4,4,2,'2025-09-07 13:56:46','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),(7,12,16,'2025-09-14 11:41:29','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),(8,4,16,'2025-09-14 11:41:29','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),(9,16,1,'2025-09-14 13:32:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'),(10,16,16,'2025-09-14 15:04:35','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `announcement_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('general','urgent','events','maintenance','academic','sports') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('draft','published') DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_announcements_status` (`status`),
  KEY `idx_announcements_published_at` (`published_at`),
  KEY `idx_announcements_expires_at` (`expires_at`),
  KEY `idx_announcements_is_pinned` (`is_pinned`),
  KEY `idx_announcements_is_archived` (`is_archived`),
  KEY `idx_announcements_category` (`category`),
  KEY `idx_announcements_priority` (`priority`),
  KEY `idx_announcements_published` (`published_at`),
  KEY `idx_announcements_pinned` (`is_pinned`),
  KEY `idx_announcements_archived` (`is_archived`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (4,'gfdgdf','gfdgdfg','general','medium','published','2025-09-03 15:35:20',NULL,0,0,3,2,1,'2025-09-03 15:35:20','2025-09-20 15:44:38'),(12,'yyyyyyyyyyyy','zzzzzzzzzzzzz','urgent','medium','published','2025-09-17 08:00:00','2025-09-17 08:00:00',0,0,3,2,1,'2025-09-04 16:01:01','2025-09-14 11:41:29'),(16,'test','tesitng','urgent','high','published','2025-09-14 13:31:00','2025-09-20 13:31:00',0,0,2,1,1,'2025-09-14 13:31:38','2025-09-14 15:04:35');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bed_spaces`
--

DROP TABLE IF EXISTS `bed_spaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bed_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) DEFAULT NULL,
  `bed_number` int(11) DEFAULT NULL,
  `is_occupied` tinyint(1) DEFAULT 0,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `bed_spaces_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=175 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bed_spaces`
--

LOCK TABLES `bed_spaces` WRITE;
/*!40000 ALTER TABLE `bed_spaces` DISABLE KEYS */;
INSERT INTO `bed_spaces` VALUES (37,10,1,1,4,'2025-09-06 15:15:37'),(38,10,2,1,3,'2025-09-06 15:15:37'),(39,10,3,1,5,'2025-09-06 15:15:37'),(40,10,4,0,NULL,'2025-09-06 15:15:37'),(41,11,1,1,6,'2025-09-06 15:15:37'),(42,11,2,1,11,'2025-09-06 15:15:37'),(43,11,3,1,13,'2025-09-06 15:15:37'),(44,11,4,1,15,'2025-09-06 15:15:37'),(45,12,1,0,NULL,'2025-09-06 15:15:37'),(46,12,2,0,NULL,'2025-09-06 15:15:37'),(47,12,3,0,NULL,'2025-09-06 15:15:37'),(48,12,4,0,NULL,'2025-09-06 15:15:37'),(49,13,1,0,NULL,'2025-09-06 15:15:37'),(50,13,2,0,NULL,'2025-09-06 15:15:37'),(51,13,3,0,NULL,'2025-09-06 15:15:37'),(52,13,4,0,NULL,'2025-09-06 15:15:37'),(53,14,1,0,NULL,'2025-09-06 15:15:37'),(54,14,2,0,NULL,'2025-09-06 15:15:37'),(55,14,3,0,NULL,'2025-09-06 15:15:37'),(56,14,4,0,NULL,'2025-09-06 15:15:37'),(57,15,1,0,NULL,'2025-09-06 15:15:37'),(58,15,2,0,NULL,'2025-09-06 15:15:37'),(59,15,3,0,NULL,'2025-09-06 15:15:37'),(60,15,4,0,NULL,'2025-09-06 15:15:37'),(61,16,1,0,NULL,'2025-09-06 15:15:37'),(62,16,2,0,NULL,'2025-09-06 15:15:37'),(63,16,3,0,NULL,'2025-09-06 15:15:37'),(64,16,4,0,NULL,'2025-09-06 15:15:37'),(65,17,1,0,NULL,'2025-09-06 15:15:37'),(66,17,2,0,NULL,'2025-09-06 15:15:37'),(67,17,3,0,NULL,'2025-09-06 15:15:37'),(68,17,4,0,NULL,'2025-09-06 15:15:37'),(69,18,1,0,NULL,'2025-09-06 15:15:37'),(70,18,2,0,NULL,'2025-09-06 15:15:37'),(71,18,3,0,NULL,'2025-09-06 15:15:37'),(72,18,4,0,NULL,'2025-09-06 15:15:37'),(73,19,1,0,NULL,'2025-09-06 15:15:37'),(74,19,2,0,NULL,'2025-09-06 15:15:37'),(75,19,3,0,NULL,'2025-09-06 15:15:37'),(76,19,4,0,NULL,'2025-09-06 15:15:37'),(77,20,1,0,NULL,'2025-09-06 15:15:37'),(78,20,2,0,NULL,'2025-09-06 15:15:37'),(79,20,3,0,NULL,'2025-09-06 15:15:37'),(80,20,4,0,NULL,'2025-09-06 15:15:37'),(81,21,1,0,NULL,'2025-09-06 15:15:37'),(82,21,2,0,NULL,'2025-09-06 15:15:37'),(83,21,3,0,NULL,'2025-09-06 15:15:37'),(84,21,4,0,NULL,'2025-09-06 15:15:37'),(85,22,1,1,7,'2025-09-07 17:15:24'),(86,22,2,1,8,'2025-09-07 17:15:24'),(87,22,3,1,10,'2025-09-07 17:15:24'),(88,22,4,1,12,'2025-09-07 17:15:24'),(89,23,1,1,14,'2025-09-07 17:15:24'),(90,23,2,0,NULL,'2025-09-07 17:15:24'),(93,24,1,0,NULL,'2025-09-07 17:15:24'),(94,24,2,0,NULL,'2025-09-07 17:15:24'),(95,24,3,0,NULL,'2025-09-07 17:15:24'),(96,24,4,0,NULL,'2025-09-07 17:15:24'),(97,25,1,0,NULL,'2025-09-07 17:15:24'),(98,25,2,1,16,'2025-09-07 17:15:24'),(99,25,3,0,NULL,'2025-09-07 17:15:24'),(100,25,4,0,NULL,'2025-09-07 17:15:24'),(101,26,1,0,NULL,'2025-09-07 17:15:24'),(102,26,2,0,NULL,'2025-09-07 17:15:24'),(103,26,3,0,NULL,'2025-09-07 17:15:24'),(104,26,4,0,NULL,'2025-09-07 17:15:24'),(105,27,1,0,NULL,'2025-09-07 17:15:24'),(106,27,2,0,NULL,'2025-09-07 17:15:24'),(107,27,3,0,NULL,'2025-09-07 17:15:24'),(108,27,4,0,NULL,'2025-09-07 17:15:24'),(109,28,1,0,NULL,'2025-09-07 17:15:24'),(110,28,2,0,NULL,'2025-09-07 17:15:24'),(111,28,3,0,NULL,'2025-09-07 17:15:24'),(112,28,4,0,NULL,'2025-09-07 17:15:24'),(113,29,1,0,NULL,'2025-09-07 17:15:24'),(114,29,2,0,NULL,'2025-09-07 17:15:24'),(115,29,3,0,NULL,'2025-09-07 17:15:24'),(116,29,4,0,NULL,'2025-09-07 17:15:24'),(117,30,1,0,NULL,'2025-09-07 17:15:24'),(118,30,2,0,NULL,'2025-09-07 17:15:24'),(119,30,3,0,NULL,'2025-09-07 17:15:24'),(120,30,4,0,NULL,'2025-09-07 17:15:24'),(121,31,1,0,NULL,'2025-09-07 17:15:24'),(122,31,2,0,NULL,'2025-09-07 17:15:24'),(123,31,3,0,NULL,'2025-09-07 17:15:24'),(124,31,4,0,NULL,'2025-09-07 17:15:24'),(125,32,1,0,NULL,'2025-09-07 17:15:24'),(126,32,2,0,NULL,'2025-09-07 17:15:24'),(127,32,3,0,NULL,'2025-09-07 17:15:24'),(128,32,4,0,NULL,'2025-09-07 17:15:24'),(129,33,1,0,NULL,'2025-09-07 17:15:24'),(130,33,2,0,NULL,'2025-09-07 17:15:24'),(131,33,3,0,NULL,'2025-09-07 17:15:24'),(132,33,4,0,NULL,'2025-09-07 17:15:24'),(133,23,3,0,NULL,'2025-09-08 02:42:25'),(134,23,4,0,NULL,'2025-09-08 02:42:25');
/*!40000 ALTER TABLE `bed_spaces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `biometric_files`
--

DROP TABLE IF EXISTS `biometric_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `biometric_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `upload_date` date NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `biometric_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `biometric_files`
--

LOCK TABLES `biometric_files` WRITE;
/*!40000 ALTER TABLE `biometric_files` DISABLE KEYS */;
INSERT INTO `biometric_files` VALUES (4,'2025-09-09_16-46-18_68c03dbab542f.xlsx','2025-09-06_17-13-56_68bc4fb40272f (1).xlsx','../uploads/biometric_files/2025-09-09_16-46-18_68c03dbab542f.xlsx','2025-09-09',NULL,'2025-09-09 14:46:18');
/*!40000 ALTER TABLE `biometric_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buildings`
--

DROP TABLE IF EXISTS `buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_floors` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buildings`
--

LOCK TABLES `buildings` WRITE;
/*!40000 ALTER TABLE `buildings` DISABLE KEYS */;
INSERT INTO `buildings` VALUES (5,'girls','girls dormitory',1,'2025-09-06 15:15:37'),(6,'Boys','for boys only',1,'2025-09-07 17:15:24');
/*!40000 ALTER TABLE `buildings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaints`
--

DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','investigating','resolved','closed') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (9,1,'girls - Room 101 - Noise Complaint (Regarding Justine Manuel)','111111','closed','12','2025-09-07 15:14:51','2025-09-07 10:34:41','2025-09-21 13:19:19','2025-09-21 13:19:19'),(10,1,'girls - Room 101 - Security Concern (Regarding Justine Manuel)','22222','closed','222\n\nConverted to offense log. Action taken: 2222','2025-09-07 15:15:21','2025-09-07 09:56:22','2025-09-21 13:19:19','2025-09-21 13:19:19'),(11,2,'girls - Room 104 - Roommate Issue (Regarding Justine Manuel)','33333','closed','on going\n\nConverted to offense log. Offense created for Justine Manuel. Action taken: 1111','2025-09-07 15:16:09','2025-09-07 09:29:37','2025-09-21 13:19:19','2025-09-21 13:19:19'),(12,1,'girls - Room 101 - Cleanliness Issue (Regarding Justine Manuel)','121212','pending','wewewe','2025-09-07 16:48:20',NULL,'2025-09-21 13:19:19','2025-09-21 13:19:19'),(13,1,'girls - Room 101 - Noise Complaint (Regarding Justine Manuel)','erer','closed','\n\nConverted to offense log. Offense created for Justine Manuel. Action taken: yeyeyeyeye','2025-09-08 03:38:23','2025-09-09 07:51:51','2025-09-21 13:19:19','2025-09-21 13:19:19'),(14,1,'girls - Room 101 - Noise Complaint (Regarding Lisa Davis)','maingay','closed','\n\nConverted to offense log. Offense created for Lisa Davis. Action taken: rerer','2025-09-09 13:49:24','2025-09-09 07:52:03','2025-09-21 13:19:19','2025-09-21 13:19:19'),(15,1,'girls - Room 101 - Maintenance Issue (Regarding Justine Manuel)','qwerty','pending',NULL,'2025-09-14 14:58:35',NULL,'2025-09-21 13:19:19','2025-09-21 13:19:19');
/*!40000 ALTER TABLE `complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_rate_limit`
--

DROP TABLE IF EXISTS `email_rate_limit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_rate_limit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_rate_limit`
--

LOCK TABLES `email_rate_limit` WRITE;
/*!40000 ALTER TABLE `email_rate_limit` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_rate_limit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_submissions_user_action` (`user_id`,`action`),
  KEY `idx_form_submissions_created_at` (`created_at`),
  CONSTRAINT `form_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
INSERT INTO `form_submissions` VALUES (6,2,'maintenance_request','2025-09-07 13:57:22',NULL,NULL),(7,2,'maintenance_request','2025-09-07 13:57:36',NULL,NULL),(8,2,'maintenance_request','2025-09-07 13:57:52',NULL,NULL),(9,2,'maintenance_request','2025-09-07 13:58:55',NULL,NULL),(10,2,'maintenance_request','2025-09-07 13:59:06',NULL,NULL),(11,2,'maintenance_request','2025-09-07 14:02:18',NULL,NULL),(28,2,'maintenance_request','2025-09-08 03:54:53',NULL,NULL),(29,2,'maintenance_request','2025-09-08 03:57:10',NULL,NULL),(30,2,'maintenance_request','2025-09-08 04:02:10',NULL,NULL),(31,2,'maintenance_request','2025-09-08 04:04:24',NULL,NULL),(33,2,'maintenance_request','2025-09-08 04:05:20',NULL,NULL),(34,2,'maintenance_request','2025-09-08 04:06:26',NULL,NULL),(35,2,'maintenance_request','2025-09-08 04:11:09',NULL,NULL),(36,2,'maintenance_request','2025-09-08 04:15:24',NULL,NULL),(37,2,'maintenance_request','2025-09-08 04:18:28',NULL,NULL),(44,1,'maintenance_request','2025-09-08 15:00:47',NULL,NULL),(45,1,'maintenance_request','2025-09-08 15:01:08',NULL,NULL),(46,1,'maintenance_request','2025-09-08 15:01:24',NULL,NULL),(47,1,'maintenance_request','2025-09-08 15:06:06',NULL,NULL),(48,1,'maintenance_request','2025-09-08 15:08:10',NULL,NULL),(49,1,'maintenance_request','2025-09-08 16:09:54',NULL,NULL),(50,2,'maintenance_request','2025-09-08 16:56:38',NULL,NULL),(51,1,'maintenance_request','2025-09-09 13:48:39',NULL,NULL),(52,1,'maintenance_request','2025-09-09 15:35:22',NULL,NULL);
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_notifications`
--

DROP TABLE IF EXISTS `maintenance_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_request_id` int(11) NOT NULL,
  `notification_type` enum('new_request','status_update','completion','feedback_request') NOT NULL,
  `recipient_type` enum('admin','student') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_notifications_request` (`maintenance_request_id`),
  KEY `idx_maintenance_notifications_recipient` (`recipient_type`,`recipient_id`),
  KEY `idx_maintenance_notifications_unread` (`is_read`),
  CONSTRAINT `maintenance_notifications_ibfk_1` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_notifications`
--

LOCK TABLES `maintenance_notifications` WRITE;
/*!40000 ALTER TABLE `maintenance_notifications` DISABLE KEYS */;
INSERT INTO `maintenance_notifications` VALUES (1,1,'new_request','admin',0,'New maintenance request submitted: qqqqqq',0,'2025-09-06 16:56:19',NULL),(2,1,'status_update','student',1,'Your maintenance request status has been updated to: Pending. Admin notes: qqq',0,'2025-09-06 16:56:59',NULL),(3,1,'status_update','student',1,'Your maintenance request status has been updated to: In progress. Admin notes: www',0,'2025-09-06 16:57:30',NULL),(4,1,'','admin',0,'Student marked maintenance request #1 as: Done',0,'2025-09-06 17:06:03',NULL),(5,1,'','admin',0,'Maintenance request #1 has been resubmitted by student',0,'2025-09-06 17:06:03',NULL),(6,2,'new_request','admin',0,'New maintenance request submitted: ppp',0,'2025-09-06 17:08:44',NULL),(7,1,'status_update','student',1,'Your maintenance request status has been updated to: In progress. Admin notes: oooo',0,'2025-09-06 17:09:17',NULL),(8,2,'status_update','student',1,'Your maintenance request status has been updated to: In progress. Admin notes: www',0,'2025-09-06 17:09:44',NULL),(9,1,'','admin',0,'Student marked maintenance request #1 as: Done',0,'2025-09-06 17:11:36',NULL),(10,1,'','admin',0,'Student marked maintenance request #1 as: Not Complete',0,'2025-09-06 17:11:36',NULL),(11,1,'','admin',0,'Maintenance request #1 has been resubmitted by student',0,'2025-09-06 17:11:36',NULL),(13,3,'new_request','admin',0,'New maintenance request submitted: 1',0,'2025-09-06 17:16:41',NULL),(14,1,'status_update','student',1,'Your maintenance request status has been updated to: In progress',0,'2025-09-06 17:38:53',NULL),(15,3,'status_update','student',1,'Your maintenance request status has been updated to: In progress',0,'2025-09-06 17:39:00',NULL);
/*!40000 ALTER TABLE `maintenance_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_requests`
--

DROP TABLE IF EXISTS `maintenance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` varchar(100) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `student_feedback` enum('satisfied','not_satisfied','pending') DEFAULT 'pending',
  `student_completion_status` enum('pending','done','not_complete') DEFAULT 'pending',
  `student_completion_notes` text DEFAULT NULL,
  `student_completion_date` timestamp NULL DEFAULT NULL,
  `resubmission_count` int(11) DEFAULT 0,
  `last_resubmitted_at` timestamp NULL DEFAULT NULL,
  `student_comments` text DEFAULT NULL,
  `feedback_submitted_at` timestamp NULL DEFAULT NULL,
  `admin_notified_at` timestamp NULL DEFAULT NULL,
  `student_notified_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_requests`
--

LOCK TABLES `maintenance_requests` WRITE;
/*!40000 ALTER TABLE `maintenance_requests` DISABLE KEYS */;
INSERT INTO `maintenance_requests` VALUES (1,1,NULL,'Resubmitted Test Request','This is a resubmitted request','high','completed','qwqwqwq','','2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending','Test not complete notes','2025-09-06 17:11:36',2,'2025-09-06 17:11:36',NULL,NULL,'2025-09-06 16:56:19',NULL,'2025-09-06 16:56:19','2025-09-07 01:58:03'),(2,1,NULL,'ppp','pppp','medium','completed','www','www','2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,'2025-09-06 17:08:44',NULL,'2025-09-06 17:08:44','2025-09-07 01:53:35'),(3,1,NULL,'1','1','low','completed','popopop','','2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,'2025-09-06 17:16:41',NULL,'2025-09-06 17:16:41','2025-09-07 02:30:07'),(4,1,NULL,'22222222','2222222222','urgent','completed','ghjg',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-07 02:33:19','2025-09-08 03:41:27'),(5,1,NULL,'3333333','3333333333333','high','completed','asdas',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-07 02:39:16','2025-09-07 21:59:43'),(6,2,13,'6666666','6666666666666','low','completed','asdas',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-07 13:57:52','2025-09-07 21:59:34'),(7,2,13,'77777777','77777777777','low','completed','asdas',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-07 14:02:18','2025-09-07 21:59:49'),(13,2,13,'Test Request','This is a test description for maintenance request','medium','completed',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 04:04:24','2025-09-08 04:16:30'),(15,2,13,'Test Request','This is a test description for maintenance request','medium','completed',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 04:05:20','2025-09-08 04:16:25'),(17,2,13,'Test Request','This is a test description for maintenance request','high','completed',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 04:06:26','2025-09-08 04:16:28'),(18,1,10,'Test Maintenance Requestsd','This is a test maintenance request for debugging purposes.','low','completed','asas',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:50:46','2025-09-08 08:53:10'),(19,1,10,'Test Maintenance Request - 2025-09-08 16:56:25','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:56:25',NULL),(20,1,10,'Test Maintenance Request - 2025-09-08 16:57:11','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:57:11',NULL),(21,1,10,'Test Maintenance Request - 2025-09-08 16:57:30','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:57:30',NULL),(22,1,10,'Test Maintenance Request - 2025-09-08 16:58:01','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:58:01',NULL),(23,1,10,'Test Maintenance Request - 2025-09-08 16:58:16','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:58:16',NULL),(24,1,10,'Test Maintenance Request - 2025-09-08 16:58:36','This is a test maintenance request to verify the form submission is working properly.','medium','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:58:36',NULL),(25,1,10,'Simple Test','Simple test description','low','in_progress','tytyty',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 14:59:06',NULL),(26,1,10,'Test Maintenance Request - 2025-09-08 17:00:47','This is a test maintenance request to verify the form submission is working properly.','medium','in_progress','wewewew',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 15:00:47',NULL),(27,1,10,'Test Maintenance Request - 2025-09-08 17:01:08','This is a test maintenance request to verify the form submission is working properly.','medium','in_progress','qqwqw',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 15:01:08',NULL),(28,1,10,'Test Maintenance Request - 2025-09-08 17:01:24','This is a test maintenance request to verify the form submission is working properly.','medium','in_progress','',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 15:01:24',NULL),(29,1,10,'ereererer','rererererererere','medium','in_progress','1qwqwq',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 15:08:10',NULL),(30,1,10,'wewewewewe','wewewewewew','high','in_progress','',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 16:09:54',NULL),(32,2,13,'bathroom','bathroom clog','high','pending',NULL,NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-08 16:56:38',NULL),(33,1,10,'electricfan','not working and need replacement','high','pending','qww',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-09 13:48:39',NULL),(34,1,10,'rtrtrtrtrtr','rtrtrtrtrtr','medium','in_progress','',NULL,'2025-09-21 13:10:40','2025-09-21 13:10:40','pending','pending',NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-09-09 15:35:22',NULL);
/*!40000 ALTER TABLE `maintenance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_status_history`
--

DROP TABLE IF EXISTS `maintenance_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maintenance_request_id` int(11) NOT NULL,
  `old_status` enum('pending','in_progress','completed','cancelled') DEFAULT NULL,
  `new_status` enum('pending','in_progress','completed','cancelled') NOT NULL,
  `changed_by` enum('admin','student') NOT NULL,
  `changed_by_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_status_history_request` (`maintenance_request_id`),
  KEY `idx_maintenance_status_history_date` (`created_at`),
  CONSTRAINT `maintenance_status_history_ibfk_1` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_status_history`
--

LOCK TABLES `maintenance_status_history` WRITE;
/*!40000 ALTER TABLE `maintenance_status_history` DISABLE KEYS */;
INSERT INTO `maintenance_status_history` VALUES (1,1,NULL,'pending','student',1,'Request submitted','2025-09-06 16:56:19'),(2,1,'pending','pending','admin',1,'qqq','2025-09-06 16:56:59'),(3,1,'pending','in_progress','admin',1,'www','2025-09-06 16:57:30'),(4,2,NULL,'pending','student',1,'Request submitted','2025-09-06 17:08:44'),(5,1,'pending','in_progress','admin',1,'oooo','2025-09-06 17:09:17'),(6,2,'pending','in_progress','admin',1,'www','2025-09-06 17:09:44'),(7,3,NULL,'pending','student',1,'Request submitted','2025-09-06 17:16:41'),(8,1,'pending','in_progress','admin',1,'','2025-09-06 17:38:53'),(9,3,'pending','in_progress','admin',1,'','2025-09-06 17:39:00');
/*!40000 ALTER TABLE `maintenance_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('application','room_request','maintenance','complaint','offense') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,'maintenance','New Maintenance Request','Student Justine Manuel has submitted a maintenance request for girls - Room 104 - Test Request',2,'Justine Manuel',13,'read','2025-09-08 04:04:24','2025-09-09 16:29:34'),(3,'','New Maintenance Request','Student Justine Manuel submitted a maintenance request for Room 10',1,'Justine Manuel',0,'unread','2025-09-08 04:04:54','2025-09-08 04:04:54'),(4,'maintenance','New Maintenance Request','Student Justine Manuel has submitted a maintenance request for girls - Room 104 - Test Request',2,'Justine Manuel',13,'unread','2025-09-08 04:05:20','2025-09-08 04:05:20'),(5,'','New Maintenance Request','Student Justine Manuel submitted a maintenance request for Room 10',1,'Justine Manuel',0,'read','2025-09-08 04:05:55','2025-09-09 16:29:26'),(6,'maintenance','New Maintenance Request','Student Justine Manuel has submitted a maintenance request for girls - Room 104 - Test Request',2,'Justine Manuel',13,'read','2025-09-08 04:06:26','2025-09-09 16:37:59');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offense_logs`
--

DROP TABLE IF EXISTS `offense_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offense_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `offense_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('minor','major','critical') DEFAULT 'minor',
  `action_taken` text DEFAULT NULL,
  `status` enum('pending','resolved','escalated') DEFAULT 'pending',
  `reported_by` varchar(100) DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `idx_offense_logs_complaint_id` (`complaint_id`),
  CONSTRAINT `offense_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offense_logs_ibfk_2` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offense_logs`
--

LOCK TABLES `offense_logs` WRITE;
/*!40000 ALTER TABLE `offense_logs` DISABLE KEYS */;
INSERT INTO `offense_logs` VALUES (9,1,'Curfew Violation','33333','major','1111\n\nAdmin Response: done','resolved','room 1','2025-09-07 15:29:37','2025-09-07 09:29:50',11),(10,1,'Curfew Violation','22222','minor','2222\n\nAdmin Response: for future update','escalated','student','2025-09-07 15:56:22',NULL,10),(11,1,'Curfew Violation','Returned after 10 PM without permission','minor',NULL,'pending','Security Guard','2025-09-08 16:32:13',NULL,NULL),(12,2,'Noise Violation','Playing loud music during quiet hours','minor',NULL,'pending','RA','2025-09-08 16:32:13',NULL,NULL),(13,3,'Property Damage','Damaged room furniture','major',NULL,'pending','Maintenance','2025-09-08 16:32:13',NULL,NULL),(14,4,'Unauthorized Guest','Had visitor without proper registration','minor',NULL,'pending','Security Guard','2025-09-08 16:32:13',NULL,NULL),(15,1,'Disruptive Behavior','erer','minor','yeyeyeyeye\n\nAdmin Response: gjgjggjgj','resolved','room 1','2025-09-09 13:51:51','2025-09-14 03:30:25',13),(16,15,'Noise Violation','maingay','minor','rerer\n\nAdmin Response: wewewewe','resolved','ererere','2025-09-09 13:52:03','2025-09-09 08:12:38',14);
/*!40000 ALTER TABLE `offense_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offenses`
--

DROP TABLE IF EXISTS `offenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `offense_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('minor','major','critical') NOT NULL,
  `status` enum('pending','resolved','escalated') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reported_by` varchar(100) NOT NULL,
  `complaint_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_offense_type` (`offense_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  KEY `idx_complaint_id` (`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offenses`
--

LOCK TABLES `offenses` WRITE;
/*!40000 ALTER TABLE `offenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `offenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `policies`
--

DROP TABLE IF EXISTS `policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `offense_descriptions` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `policies_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `policies`
--

LOCK TABLES `policies` WRITE;
/*!40000 ALTER TABLE `policies` DISABLE KEYS */;
INSERT INTO `policies` VALUES (1,'Dormitory Rules and Regulations','All students must follow the dormitory rules and regulations for a harmonious living environment.','Curfew violations: Being outside dormitory after 10 PM without permission. Property damage: Damaging dormitory property or facilities. Noise violations: Creating excessive noise during quiet hours.',1,'2025-07-31 17:41:46','2025-07-31 17:41:46'),(4,'qweq','qweqwe','qweqweqwe',NULL,'2025-09-07 17:08:34','2025-09-08 02:41:02');
/*!40000 ALTER TABLE `policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_change_requests`
--

DROP TABLE IF EXISTS `room_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `current_room_id` int(11) DEFAULT NULL,
  `requested_room_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `current_room_id` (`current_room_id`),
  KEY `requested_room_id` (`requested_room_id`),
  CONSTRAINT `room_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_change_requests_ibfk_2` FOREIGN KEY (`current_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `room_change_requests_ibfk_3` FOREIGN KEY (`requested_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_change_requests`
--

LOCK TABLES `room_change_requests` WRITE;
/*!40000 ALTER TABLE `room_change_requests` DISABLE KEYS */;
INSERT INTO `room_change_requests` VALUES (1,1,NULL,NULL,'rttr','approved','q','2025-09-04 16:10:41','2025-09-06 09:13:26','2025-09-21 13:19:19','2025-09-21 13:19:19'),(2,1,NULL,10,'1212','approved','1212','2025-09-07 02:40:17','2025-09-06 21:08:14','2025-09-21 13:19:19','2025-09-21 13:19:19'),(3,1,NULL,12,'11111111','rejected','1','2025-09-07 02:41:32','2025-09-06 21:08:07','2025-09-21 13:19:19','2025-09-21 13:19:19'),(4,2,NULL,13,'11','approved','11','2025-09-07 04:36:33','2025-09-06 22:36:55','2025-09-21 13:19:19','2025-09-21 13:19:19'),(5,1,10,23,'change room','rejected','not to take for the meantime\r\n','2025-09-08 03:37:21','2025-09-09 08:06:25','2025-09-21 13:19:19','2025-09-21 13:19:19'),(6,16,25,25,'11111111','pending',NULL,'2025-09-14 11:42:50',NULL,'2025-09-21 13:19:19','2025-09-21 13:19:19');
/*!40000 ALTER TABLE `room_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) DEFAULT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor_number` int(11) DEFAULT 1,
  `capacity` int(11) DEFAULT 4,
  `occupied` int(11) DEFAULT 0,
  `status` enum('available','full','maintenance') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `building_id` (`building_id`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rooms`
--

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (10,5,'101',1,4,4,'full','2025-09-06 15:15:37'),(11,5,'102',1,4,4,'full','2025-09-06 15:15:37'),(12,5,'103',1,4,0,'available','2025-09-06 15:15:37'),(13,5,'104',1,4,1,'available','2025-09-06 15:15:37'),(14,5,'105',1,4,0,'available','2025-09-06 15:15:37'),(15,5,'106',1,4,0,'available','2025-09-06 15:15:37'),(16,5,'107',1,4,0,'available','2025-09-06 15:15:37'),(17,5,'108',1,4,0,'available','2025-09-06 15:15:37'),(18,5,'109',1,4,0,'available','2025-09-06 15:15:37'),(19,5,'110',1,4,0,'available','2025-09-06 15:15:37'),(20,5,'111',1,4,0,'available','2025-09-06 15:15:37'),(21,5,'112',1,4,0,'available','2025-09-06 15:15:37'),(22,6,'101',1,4,4,'full','2025-09-07 17:15:24'),(23,6,'102',1,4,1,'available','2025-09-07 17:15:24'),(24,6,'103',1,4,0,'available','2025-09-07 17:15:24'),(25,6,'104',1,4,1,'available','2025-09-07 17:15:24'),(26,6,'105',1,4,0,'available','2025-09-07 17:15:24'),(27,6,'106',1,4,0,'available','2025-09-07 17:15:24'),(28,6,'107',1,4,0,'available','2025-09-07 17:15:24'),(29,6,'108',1,4,0,'available','2025-09-07 17:15:24'),(30,6,'109',1,4,0,'available','2025-09-07 17:15:24'),(31,6,'110',1,4,0,'available','2025-09-07 17:15:24'),(32,6,'111',1,4,0,'available','2025-09-07 17:15:24'),(33,6,'112',1,4,0,'available','2025-09-07 17:15:24');
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_location_logs`
--

DROP TABLE IF EXISTS `student_location_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_location_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `location_status` enum('inside_dormitory','outside_campus','in_class') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_location_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_location_logs`
--

LOCK TABLES `student_location_logs` WRITE;
/*!40000 ALTER TABLE `student_location_logs` DISABLE KEYS */;
INSERT INTO `student_location_logs` VALUES (10,16,'inside_dormitory','2025-09-14 11:42:35');
/*!40000 ALTER TABLE `student_location_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `province` varchar(100) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `street_purok` varchar(200) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `facebook_link` varchar(200) DEFAULT NULL,
  `school_id` varchar(6) NOT NULL,
  `learner_reference_number` varchar(12) NOT NULL,
  `attachment_file` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_mobile` varchar(15) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `application_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `room_id` int(11) DEFAULT NULL,
  `bed_space_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_id` (`school_id`),
  UNIQUE KEY `learner_reference_number` (`learner_reference_number`),
  KEY `room_id` (`room_id`),
  KEY `bed_space_id` (`bed_space_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`bed_space_id`) REFERENCES `bed_spaces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'Justine','thiam','Manuel','2004-05-27','Male','isabela','echague','fugu','purok 3','09661448338','manueljustine291@gmail.com','https://www.youtube.com/watch?v=Mp146CYrnCI&list=RDCz8l_cVmCa8&index=27','221755','123456789101','uploads/student_documents/221755_1753983938.pdf','ktyiytityi','09661448338','Guardian','approved',10,NULL,'2025-07-31 17:45:38','2025-09-07 03:08:14'),(2,'Justine','thiam','Manuel','2006-07-24','Male','isabela','echague','rerere','purok 3','09661448338','manueljustine291@gmail.com','https://www.youtube.com/watch?v=Mp146CYrnCI&list=RDCz8l_cVmCa8&index=27','221756','103331120738','uploads/student_documents/221756_1757002403.pdf','sdasdasd','09661448338','Guardian','approved',13,NULL,'2025-09-04 16:13:23','2025-09-07 04:36:55'),(3,'Justine','thiam','Manuel','2025-09-03','Male','isabela','echague','fugu','purok 3','09661448338','manueljustine291@gmail.com','https://www.youtube.com/watch?v=Mp146CYrnCI&list=RDCz8l_cVmCa8&index=27','222015','121212121212','uploads/student_documents/222015_1757219692.pdf','ktyiytityi','09661448338','Guardian','approved',10,38,'2025-09-07 04:34:52','2025-09-07 04:35:34'),(4,'Maria',NULL,'Santos','2000-05-15','Female','Metro Manila','Quezon City','Barangay 1','Street 1','09123456789','maria@email.com',NULL,'123456','123456789012',NULL,'Juan Santos','09123456788','Father','approved',10,37,'2025-09-08 16:32:13','2025-09-08 16:43:06'),(5,'Ana',NULL,'Garcia','2001-03-22','Female','Laguna','Calamba','Barangay 2','Street 2','09123456790','ana@email.com',NULL,'123457','123456789013',NULL,'Pedro Garcia','09123456791','Father','approved',10,39,'2025-09-08 16:32:13','2025-09-08 16:43:06'),(6,'Sofia',NULL,'Lopez','2000-11-08','Female','Cavite','Imus','Barangay 3','Street 3','09123456792','sofia@email.com',NULL,'123458','123456789014',NULL,'Carlos Lopez','09123456793','Father','approved',11,41,'2025-09-08 16:32:13','2025-09-08 16:43:06'),(7,'Jose',NULL,'Reyes','2000-07-12','Male','Bulacan','Malolos','Barangay 4','Street 4','09123456794','jose@email.com',NULL,'123459','123456789015',NULL,'Miguel Reyes','09123456795','Father','approved',22,85,'2025-09-08 16:32:13','2025-09-08 16:43:06'),(8,'Carlos',NULL,'Mendoza','2001-01-30','Male','Rizal','Antipolo','Barangay 5','Street 5','09123456796','carlos@email.com',NULL,'123460','123456789016',NULL,'Antonio Mendoza','09123456797','Father','approved',22,86,'2025-09-08 16:32:13','2025-09-08 16:43:06'),(10,'John',NULL,'Doe','2000-01-01','Male','Metro Manila','Manila','Barangay 1','Street 1','09123456789','john@email.com',NULL,'123461','123456789017',NULL,'Juan Doe','09123456788','Father','approved',22,87,'2025-07-27 16:00:00','2025-09-08 16:43:06'),(11,'Jane',NULL,'Smith','2000-02-01','Female','Laguna','Calamba','Barangay 2','Street 2','09123456790','jane@email.com',NULL,'123462','123456789018',NULL,'Maria Smith','09123456791','Mother','approved',11,42,'2025-08-03 16:00:00','2025-09-08 16:43:06'),(12,'Mike',NULL,'Johnson','2000-03-01','Male','Cavite','Imus','Barangay 3','Street 3','09123456792','mike@email.com',NULL,'123463','123456789019',NULL,'Carlos Johnson','09123456793','Father','approved',22,88,'2025-08-10 16:00:00','2025-09-08 16:43:06'),(13,'Sarah',NULL,'Wilson','2000-04-01','Female','Bulacan','Malolos','Barangay 4','Street 4','09123456794','sarah@email.com',NULL,'123464','123456789020',NULL,'Pedro Wilson','09123456795','Father','approved',11,43,'2025-08-17 16:00:00','2025-09-08 16:43:06'),(14,'David',NULL,'Brown','2000-05-01','Male','Rizal','Antipolo','Barangay 5','Street 5','09123456796','david@email.com',NULL,'123465','123456789021',NULL,'Antonio Brown','09123456797','Father','approved',23,89,'2025-08-24 16:00:00','2025-09-08 16:43:06'),(15,'Lisa',NULL,'Davis','2000-06-01','Female','Pampanga','San Fernando','Barangay 6','Street 6','09123456798','lisa@email.com',NULL,'123466','123456789022',NULL,'Miguel Davis','09123456799','Father','approved',11,44,'2025-08-31 16:00:00','2025-09-08 16:43:06'),(16,'eduardo','romano','kirk','2025-08-09','Male','isabela','echague','fugu','purok 3','09661448338','iambnx27@gmail.com','https://www.youtube.com/watch?v=Mp146CYrnCI&list=RDCz8l_cVmCa8&index=27','222025','777777777777','uploads/student_documents/222025_1757849812.pdf','sdasdasd','09661448338','Sibling','approved',25,98,'2025-09-14 11:36:52','2025-09-14 11:37:10');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitor_logs`
--

DROP TABLE IF EXISTS `visitor_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_age` int(11) NOT NULL,
  `visitor_address` text NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `reason_of_visit` varchar(50) NOT NULL DEFAULT 'Other',
  `time_in` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_out` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `visitor_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_logs`
--

LOCK TABLES `visitor_logs` WRITE;
/*!40000 ALTER TABLE `visitor_logs` DISABLE KEYS */;
INSERT INTO `visitor_logs` VALUES (1,1,'gdgfg',45,'gsfgsfgsg','4535345345','Other','2025-09-01 04:07:34','2025-08-31 22:18:43','2025-09-21 13:19:19'),(2,1,'hgj',45,'fgfdgdfg','4535345345','Other','2025-09-01 04:19:37','2025-09-03 10:35:09','2025-09-21 13:19:19'),(3,1,'22',22,'sadsadasda','22','Other','2025-09-07 03:29:23','2025-09-07 03:42:54','2025-09-21 13:19:19'),(4,1,'111111',11,'1111111','11111111','Other','2025-09-07 04:18:45','2025-09-06 22:19:17','2025-09-21 13:19:19'),(5,1,'111111',11,'1111111','11111111','Other','2025-09-07 04:19:06','2025-09-06 22:19:19','2025-09-21 13:19:19'),(6,1,'22222',22,'2222','222222','Other','2025-09-07 04:19:30','2025-09-07 21:37:55','2025-09-21 13:19:19'),(7,1,'33333',33,'3333','33333','Activities','2025-09-07 04:23:04','2025-09-07 21:37:57','2025-09-21 13:19:19'),(8,2,'gdgfg',55,'11111111111','1111111111','Activities','2025-09-07 14:03:15',NULL,'2025-09-21 13:19:19'),(9,2,'4444444',44,'4444444','4444444444','Project','2025-09-07 14:32:42',NULL,'2025-09-21 13:19:19'),(10,1,'erer',45,'erere','ererer','Meeting','2025-09-08 03:37:48','2025-09-21 07:21:23','2025-09-21 13:19:19');
/*!40000 ALTER TABLE `visitor_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'dormitory_management'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ArchiveOldAnnouncements` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `ArchiveOldAnnouncements`()
BEGIN
    UPDATE announcements 
    SET is_archived = TRUE 
    WHERE expires_at < NOW() 
    AND is_archived = FALSE;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `UpdateAnnouncementLikeCount` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateAnnouncementLikeCount`(IN announcement_id INT)
BEGIN
    UPDATE announcements 
    SET like_count = (
        SELECT COUNT(*) 
        FROM announcement_interactions 
        WHERE announcement_id = announcement_id 
        AND interaction_type = 'like'
    ) 
    WHERE id = announcement_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `UpdateAnnouncementViewCount` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateAnnouncementViewCount`(IN announcement_id INT)
BEGIN
    UPDATE announcements 
    SET view_count = view_count + 1 
    WHERE id = announcement_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `active_announcements`
--

/*!50001 DROP VIEW IF EXISTS `active_announcements`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_announcements` AS select `announcements`.`id` AS `id`,`announcements`.`title` AS `title`,`announcements`.`content` AS `content`,`announcements`.`category` AS `category`,`announcements`.`priority` AS `priority`,`announcements`.`status` AS `status`,`announcements`.`published_at` AS `published_at`,`announcements`.`expires_at` AS `expires_at`,`announcements`.`is_pinned` AS `is_pinned`,`announcements`.`is_archived` AS `is_archived`,`announcements`.`view_count` AS `view_count`,`announcements`.`like_count` AS `like_count`,`announcements`.`created_by` AS `created_by`,`announcements`.`created_at` AS `created_at`,`announcements`.`updated_at` AS `updated_at` from `announcements` where `announcements`.`is_archived` = 0 and (`announcements`.`expires_at` is null or `announcements`.`expires_at` > current_timestamp()) and `announcements`.`status` = 'published' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `announcement_comments_with_author`
--

/*!50001 DROP VIEW IF EXISTS `announcement_comments_with_author`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `announcement_comments_with_author` AS select `ac`.`id` AS `id`,`ac`.`announcement_id` AS `announcement_id`,`ac`.`comment` AS `comment`,`ac`.`created_at` AS `created_at`,`ac`.`updated_at` AS `updated_at`,`ac`.`student_id` AS `student_id`,case when `ac`.`student_id` > 0 then 'student' when `ac`.`student_id` < 0 then 'admin' else 'unknown' end AS `author_type`,case when `ac`.`student_id` > 0 then concat(`s`.`first_name`,' ',coalesce(`s`.`middle_name`,''),' ',`s`.`last_name`) when `ac`.`student_id` < 0 then `a`.`username` else 'Unknown' end AS `author_name`,case when `ac`.`student_id` > 0 then `s`.`school_id` else NULL end AS `school_id` from ((`announcement_comments` `ac` left join `students` `s` on(`ac`.`student_id` = `s`.`id` and `ac`.`student_id` > 0)) left join `admins` `a` on(`ac`.`student_id` = -`a`.`id` and `ac`.`student_id` < 0)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-21 22:28:52

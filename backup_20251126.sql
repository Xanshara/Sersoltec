-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: sersoltec_db
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','superadmin') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `active` tinyint DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `username_2` (`username`),
  KEY `email_2` (`email`),
  KEY `active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','admin@sersoltec.eu','$2y$10$nRBL6QnAY8K2lOVHmHT.D.tTYJU2K4/uYuJu3Cjhe.noT./Hjn1Da','superadmin',1,'2025-11-25 21:04:41','2025-11-20 07:53:57','2025-11-25 21:04:41'),(3,'Sinrac','bartek.rychel@outlook.com','$2y$10$m6/DbmtiWaxCrO5gbc/s9.kMJmPE2kscCizv6U5EU86TltYnkZNdi','superadmin',1,NULL,'2025-11-20 10:37:34','2025-11-20 10:37:34');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_comments`
--

DROP TABLE IF EXISTS `blog_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `author_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','spam') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blog_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_comments`
--

LOCK TABLES `blog_comments` WRITE;
/*!40000 ALTER TABLE `blog_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` int NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_published` (`published_at`),
  FULLTEXT KEY `idx_search` (`title`,`content`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `admin_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_pl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_es` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_pl` text COLLATE utf8mb4_unicode_ci,
  `description_en` text COLLATE utf8mb4_unicode_ci,
  `description_es` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int DEFAULT '0',
  `active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'okna-pvc','Okna PVC','PVC Windows','Ventanas PVC',NULL,NULL,NULL,'🪟',1,1,'2025-11-19 18:58:45'),(2,'okna-drewniane','Okna Drewniane','Wooden Windows','Ventanas de Madera',NULL,NULL,NULL,'🪟',2,1,'2025-11-19 18:58:45'),(3,'panele-grzewcze','Panele Grzewcze','Heating Panels','Paneles Calefactores',NULL,NULL,NULL,'🔥',3,1,'2025-11-19 18:58:45'),(4,'folie-grzewcze','Folie Grzewcze','Heating Films','Películas Calefactoras',NULL,NULL,NULL,'🔥',4,1,'2025-11-19 18:58:45'),(5,'profile-pvc','Profile PVC','PVC Profiles','Perfiles PVC',NULL,NULL,NULL,'📐',5,1,'2025-11-19 18:58:45'),(6,'drzwi-wewnetrzne','Drzwi Wewnętrzne','Interior Doors','Puertas Interiores',NULL,NULL,NULL,'🚪',6,1,'2025-11-19 18:58:45'),(7,'drzwi-zewnetrzne','Drzwi Zewnętrzne','Exterior Doors','Puertas Exteriores',NULL,NULL,NULL,'🚪',7,1,'2025-11-19 18:58:45'),(8,'akcesoria','Akcesoria','Accessories','Accesorios',NULL,NULL,NULL,'⚙️',8,1,'2025-11-19 18:58:45'),(9,'projektowanie','Projektowanie','Design Services','Servicios de Diseño',NULL,NULL,NULL,'🎨',9,1,'2025-11-19 18:58:45');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_history`
--

DROP TABLE IF EXISTS `chat_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_history`
--

LOCK TABLES `chat_history` WRITE;
/*!40000 ALTER TABLE `chat_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('new','read','replied') COLLATE utf8mb4_unicode_ci DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `created_at` (`created_at`),
  KEY `status` (`status`),
  CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inquiries`
--

LOCK TABLES `inquiries` WRITE;
/*!40000 ALTER TABLE `inquiries` DISABLE KEYS */;
INSERT INTO `inquiries` VALUES (1,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','produkty',NULL,'83.24.102.44','new','2025-11-20 06:48:10','2025-11-20 06:48:10'),(2,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','produkty',NULL,'83.24.102.44','new','2025-11-20 06:58:13','2025-11-20 06:58:13'),(3,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','dostawa',NULL,'83.24.102.44','new','2025-11-20 06:58:20','2025-11-20 06:58:20'),(4,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','test',NULL,'83.24.102.44','new','2025-11-20 06:58:28','2025-11-20 06:58:28'),(5,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','test',NULL,'83.24.102.44','new','2025-11-20 06:58:42','2025-11-20 06:58:42'),(6,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','test',NULL,'83.24.102.44','read','2025-11-20 07:15:42','2025-11-20 17:49:23'),(7,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','test',NULL,'83.24.102.44','read','2025-11-20 07:18:55','2025-11-20 09:44:35'),(8,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','produkty',NULL,'83.24.102.44','new','2025-11-23 18:23:46','2025-11-23 18:23:46'),(9,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','produkty',NULL,'83.24.102.44','new','2025-11-23 18:30:16','2025-11-23 18:30:16'),(10,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','okna',NULL,'83.24.102.44','new','2025-11-23 18:30:19','2025-11-23 18:30:19'),(11,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','produkty',NULL,'83.24.102.44','new','2025-11-23 18:34:21','2025-11-23 18:34:21'),(12,'Widget User','noemail@example.com',NULL,NULL,'Widget Message','products',NULL,'83.24.102.44','new','2025-11-23 18:34:31','2025-11-23 18:34:31');
/*!40000 ALTER TABLE `inquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_attempted` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price_per_unit` decimal(10,2) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,5,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:23:58'),(2,6,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:27:32'),(3,7,5,'Okno Drewniane Klejone Dąb 150x150',1,1200.00,NULL,NULL,'2025-11-22 10:29:08'),(4,8,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:29:39'),(5,9,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:32:16'),(6,10,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:41:50'),(7,11,10,'Folia Grzewcza 50m x 0.5m',1,150.00,NULL,NULL,'2025-11-22 10:45:19'),(8,12,6,'Okno Drewniane Laminowane 200x200',1,2000.00,NULL,NULL,'2025-11-22 11:05:44'),(9,13,5,'Glued Wooden Window Oak 150x150',1,1200.00,NULL,NULL,'2025-11-22 13:35:56'),(10,13,6,'Laminated Wooden Window 200x200',1,2000.00,NULL,NULL,'2025-11-22 13:35:56');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_tax_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` text COLLATE utf8mb4_unicode_ci,
  `message` text COLLATE utf8mb4_unicode_ci,
  `products` json DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_items` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','confirmed','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `lang` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pl',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `created_at` (`created_at`),
  KEY `status` (`status`),
  KEY `customer_email` (`customer_email`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (5,NULL,'kowal','bartek.rychel@outlook.com','+34643642837','234sdf2','ORD-20251122-5D6516',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'transfer',NULL,'',NULL,'pending','2025-11-22 10:23:58','2025-11-22 10:56:07','pl'),(6,NULL,'kowal','test@sersoltec.eu','+34 791 74 99 49','asdasd','ORD-20251122-DC6B1A',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'cash_on_delivery',NULL,'asdasda',NULL,'pending','2025-11-22 10:27:32','2025-11-22 10:56:07','pl'),(7,NULL,'kowal','bartek.rychel@outlook.com','+34643642837','asda','ORD-20251122-6EC421',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1200.00,'transfer',NULL,'adsad',NULL,'pending','2025-11-22 10:29:08','2025-11-22 10:56:07','pl'),(8,NULL,'kowal','bartek.rychel@outlook.com','1123123','123123','ORD-20251122-351EC8',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'transfer',NULL,'123123',NULL,'pending','2025-11-22 10:29:39','2025-11-22 10:56:07','pl'),(9,NULL,'kowal','test@sersoltec.eu','+34643642837','aSDA','ORD-20251122-448196',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'transfer',NULL,'ASD',NULL,'pending','2025-11-22 10:32:16','2025-11-22 10:56:07','pl'),(10,NULL,'kowal','asdad@02.pl','123123','123123','ORD-20251122-2BF5F0',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'cash_on_delivery',NULL,'12323',NULL,'pending','2025-11-22 10:41:50','2025-11-22 10:41:50','pl'),(11,NULL,'kowal','bartek.rychel@outlook.com','+34643642837','jbhj','ORD-20251122-AC4E7F',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,150.00,'transfer',NULL,'jbhhj',NULL,'pending','2025-11-22 10:45:19','2025-11-22 10:45:19','pl'),(12,1,'kowal','bartek.rychel@outlook.com','+34643642837','sprawdzam','ORD-20251122-B75542',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2000.00,'transfer',NULL,'te',NULL,'pending','2025-11-22 11:05:44','2025-11-22 13:31:51','pl'),(13,1,'kowal','bartek.rychel@outlook.com','123123123','123123123','ORD-20251122-73EE43',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3200.00,'transfer',NULL,'123123',NULL,'pending','2025-11-22 13:35:56','2025-11-22 13:35:56','en');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (23,'bartek.rychel96@gmail.com','d48f1f6336d1434a9ea135c1f55e8a39af967cdf0defce1364fd89f1e39d7091','2025-11-26 20:22:56',1,'2025-11-25 20:23:14','2025-11-25 19:22:56');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_comparisons`
--

DROP TABLE IF EXISTS `product_comparisons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_comparisons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` int NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `product_comparisons_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_comparisons`
--

LOCK TABLES `product_comparisons` WRITE;
/*!40000 ALTER TABLE `product_comparisons` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_comparisons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `author_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `verified_purchase` tinyint(1) DEFAULT '0',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_product_status` (`product_id`,`status`),
  KEY `idx_rating` (`rating`),
  KEY `idx_status` (`status`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_pl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_es` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_pl` text COLLATE utf8mb4_unicode_ci,
  `description_en` text COLLATE utf8mb4_unicode_ci,
  `description_es` text COLLATE utf8mb4_unicode_ci,
  `specifications` json DEFAULT NULL,
  `price_base` decimal(10,2) DEFAULT NULL,
  `price_min` decimal(10,2) DEFAULT NULL,
  `price_max` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` json DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT NULL,
  `active` tinyint DEFAULT '1',
  `b2b_only` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  KEY `active` (`active`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'PVC-WIN-001','Okno PVC 90x120 - Tilt & Turn','PVC Window 90x120 - Tilt & Turn','Ventana PVC 90x120 - Oscilobatiente','Nowoczesne okno PVC z funkcją uchylania i całkowitego otwarcia. Doskonała izolacja termiczna.','Modern PVC window with tilt and turn function. Excellent thermal insulation.','Moderna ventana PVC con función oscilobatiente. Excelente aislamiento térmico.','{\"glass\": \"Double\", \"width\": \"900mm\", \"frames\": 1, \"height\": \"1200mm\", \"u_value\": \"1.1\"}',450.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(2,1,'PVC-WIN-002','Okno PVC 150x150 - Tilt & Turn','PVC Window 150x150 - Tilt & Turn','Ventana PVC 150x150 - Oscilobatiente','Duże okno PVC do salonu. Profilownie z wzmocnieniami. Wysoka nośność szyb.','Large PVC window for living room. Reinforced frames. High glass load capacity.','Gran ventana PVC para sala de estar. Marcos reforzados. Alta capacidad de carga de vidrio.','{\"glass\": \"Triple\", \"width\": \"1500mm\", \"frames\": 1, \"height\": \"1500mm\", \"u_value\": \"0.9\"}',850.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(3,1,'PVC-WIN-003','Okno PVC Narożne 200x200','Corner PVC Window 200x200','Ventana PVC Esquina 200x200','Okno narożne do nowoczesnych wnętrz. Idealne do przestronnych przestrzeni. Minimalistyczne ramy.','Corner window for modern interiors. Ideal for spacious spaces. Minimalist frames.','Ventana esquinera para interiores modernos. Ideal para espacios amplios. Marcos minimalistas.','{\"glass\": \"Triple\", \"width\": \"2000mm\", \"frames\": 2, \"height\": \"2000mm\", \"u_value\": \"0.85\"}',1600.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(4,2,'WOOD-WIN-001','Okno Drewniane Sosnowe 90x120','Pine Wooden Window 90x120','Ventana de Madera de Pino 90x120','Tradycyjne okno drewniane z sosny. Naturalna izolacja. Piękny wygląd.','Traditional wooden window made of pine. Natural insulation. Beautiful appearance.','Ventana de madera tradicional de pino. Aislamiento natural. Hermosa apariencia.','{\"wood\": \"Pine\", \"glass\": \"Double\", \"width\": \"900mm\", \"height\": \"1200mm\"}',550.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(5,2,'WOOD-WIN-002','Okno Drewniane Klejone Dąb 150x150','Glued Wooden Window Oak 150x150','Ventana de Madera Encolada Roble 150x150','Ekskluzywne okno z naturalnego drewna dębu. Prestiżowy wygląd. Najwyższa jakość.','Exclusive window made from natural oak wood. Prestigious appearance. Highest quality.','Ventana exclusiva de madera de roble natural. Apariencia prestigiosa. Máxima calidad.','{\"wood\": \"Oak\", \"glass\": \"Triple\", \"width\": \"1500mm\", \"height\": \"1500mm\"}',1200.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(6,2,'WOOD-WIN-003','Okno Drewniane Laminowane 200x200','Laminated Wooden Window 200x200','Ventana de Madera Laminada 200x200','Nowoczesne okno drewniane z powłoką laminowaną. Łatwa konserwacja. Długowieczność.','Modern wooden window with laminated coating. Easy maintenance. Longevity.','Moderna ventana de madera con revestimiento laminado. Fácil mantenimiento. Longevidad.','{\"wood\": \"Laminated\", \"glass\": \"Triple\", \"width\": \"2000mm\", \"height\": \"2000mm\"}',2000.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(7,3,'HEAT-PANEL-001','Panel Grzewczy 600W','Heating Panel 600W','Panel Calefactor 600W','Nowoczesny panel grzewczy do pomieszczeń średnich. Efektywny, cichy, bezpieczny.','Modern heating panel for medium rooms. Efficient, quiet, safe.','Panel calefactor moderno para habitaciones medianas. Eficiente, silencioso, seguro.','{\"power\": \"600W\", \"weight\": \"3.5kg\", \"dimensions\": \"600x800mm\"}',200.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(8,3,'HEAT-PANEL-002','Panel Grzewczy 1000W','Heating Panel 1000W','Panel Calefactor 1000W','Panel grzewczy dla dużych pomieszczeń. Termostat wbudowany. Oszczędny w prądzie.','Heating panel for large rooms. Built-in thermostat. Power saving.','Panel calefactor para habitaciones grandes. Termostato incorporado. Ahorro de energía.','{\"power\": \"1000W\", \"weight\": \"5kg\", \"dimensions\": \"800x1000mm\"}',350.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(9,3,'HEAT-PANEL-003','Panel Grzewczy Sufitowy 2000W','Ceiling Heating Panel 2000W','Panel Calefactor de Techo 2000W','Zaawansowany panel sufitowy do pomieszczeń komercyjnych i dużych pomieszczeń mieszkalnych.','Advanced ceiling panel for commercial spaces and large residential rooms.','Panel de techo avanzado para espacios comerciales y grandes salas residenciales.','{\"power\": \"2000W\", \"weight\": \"8kg\", \"dimensions\": \"1200x1200mm\"}',650.00,NULL,NULL,'szt',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(10,4,'HEAT-FILM-001','Folia Grzewcza 50m x 0.5m','Heating Film 50m x 0.5m','Película Calefactora 50m x 0.5m','Elastyczna folia grzewcza do montażu pod podłogę. Efektywna, uniwersalna.','Flexible heating film for under-floor installation. Efficient, universal.','Película calefactora flexible para instalación bajo piso. Eficiente, universal.','{\"power\": \"100W/m2\", \"width\": \"0.5m\", \"length\": \"50m\"}',150.00,NULL,NULL,'rol',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-20 20:04:55'),(11,4,'HEAT-FILM-002','Folia Grzewcza 100m x 1m','Heating Film 100m x 1m','Película Calefactora 100m x 1m','Duża rola folii grzewczej. Idealna do gruntów dużych powierzchni. Równomierny rozkład ciepła.','Large roll of heating film. Ideal for large floor areas. Even heat distribution.','Gran rollo de película calefactora. Ideal para grandes áreas de piso. Distribución uniforme del calor.','{\"power\": \"120W/m2\", \"width\": \"1m\", \"length\": \"100m\"}',350.00,NULL,NULL,'rol',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45'),(12,4,'HEAT-FILM-003','Folia Grzewcza Sufitowa Premium 30m','Premium Ceiling Heating Film 30m','Película Calefactora de Techo Premium 30m','Profesjonalna folia do montażu na suficie. Wysoka moc. Bezpieczna technologia.','Professional film for ceiling installation. High power. Safe technology.','Película profesional para instalación de techo. Alta potencia. Tecnología segura.','{\"power\": \"200W/m2\", \"width\": \"1m\", \"length\": \"30m\"}',450.00,NULL,NULL,'rol',NULL,NULL,NULL,1,0,'2025-11-19 18:58:45','2025-11-19 18:58:45');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('company_address','Valencia, Spain','2025-11-19 18:58:45'),('company_email','info@sersoltec.eu','2025-11-19 18:58:45'),('company_name','Sersoltec','2025-11-19 18:58:45'),('company_phone','+34 XXX XXX XXX','2025-11-19 18:58:45'),('currency','EUR','2025-11-19 18:58:45'),('tax_rate','21','2025-11-19 18:58:45'),('vat_id','ES12345678A','2025-11-19 18:58:45');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`active`),
  KEY `idx_verification_token` (`verification_token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test','User','test@sersoltec.eu','$2y$10$xYiY0wUmopRfUfdL9Mr2Qe9RzT038zPQKCjcU66B6XpiVsPvRrHBC','1231231','123123123','user',1,'2025-11-20 13:03:36','2025-11-22 11:05:12',NULL,NULL,NULL),(2,'Test','Test2','test2@sersoltec.eu','$2y$10$mrugto.ShS1.YUNEUuxmB.7RG7Ge8aRHA66/xRrHh7RYLpYWzueem','123123123','','user',1,'2025-11-20 14:05:57','2025-11-21 20:27:38',NULL,NULL,NULL),(3,'Test','Test2','bartek.rychel96@gmail.com','$2y$10$RIW6YW9hZA4wZWSp8LNCe.UGUriiFzCuh/3eoPSbWhBUvKfuE8gaa',NULL,NULL,'user',1,'2025-11-25 14:48:01','2025-11-25 19:23:14',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `window_calculations`
--

DROP TABLE IF EXISTS `window_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `window_calculations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` decimal(10,2) DEFAULT NULL,
  `height` decimal(10,2) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `glass_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `estimated_price` decimal(10,2) DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `window_calculations`
--

LOCK TABLES `window_calculations` WRITE;
/*!40000 ALTER TABLE `window_calculations` DISABLE KEYS */;
INSERT INTO `window_calculations` VALUES (1,NULL,600.00,200.00,'double','pvc','double','fixed',1,10.08,'{\"type\": \"double\", \"glass\": \"double\", \"opening\": \"fixed\", \"material\": \"pvc\"}','2025-11-22 14:53:24'),(2,NULL,600.00,200.00,'double','pvc','double','fixed',1,10.08,'{\"type\": \"double\", \"glass\": \"double\", \"opening\": \"fixed\", \"material\": \"pvc\"}','2025-11-22 15:15:26'),(3,NULL,234.00,234.00,'double','pvc','double','fixed',1,4.60,'{\"type\": \"double\", \"glass\": \"double\", \"opening\": \"fixed\", \"material\": \"pvc\"}','2025-11-22 15:17:44'),(4,NULL,123.00,123.00,'double','wood','triple','sliding',1,4.58,'{\"type\": \"double\", \"glass\": \"triple\", \"opening\": \"sliding\", \"material\": \"wood\"}','2025-11-22 15:25:37');
/*!40000 ALTER TABLE `window_calculations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_unique` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-26 21:13:27

-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: grievease
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `branch_tb`
--

DROP TABLE IF EXISTS `branch_tb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_tb` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(255) NOT NULL,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_tb`
--

LOCK TABLES `branch_tb` WRITE;
/*!40000 ALTER TABLE `branch_tb` DISABLE KEYS */;
INSERT INTO `branch_tb` VALUES (1,'paete'),(2,'pila');
/*!40000 ALTER TABLE `branch_tb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `chatId` varchar(255) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `receiver` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','delivered','read') DEFAULT 'sent',
  `chatRoomId` varchar(255) NOT NULL,
  `messageType` enum('text','image','video') DEFAULT 'text',
  `attachmentUrl` text DEFAULT NULL,
  `receiver2` varchar(255) DEFAULT NULL,
  `receiver3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`chatId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES ('chat_67df99bd33643','4','8','Hello employee','2025-03-23 05:18:53','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67df99be44b69','bot','4','Hello! How can I help you today?','2025-03-23 05:18:54','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67df9b601e3eb4.06819516','22','4','yes here\'s the poging employee','2025-03-23 05:25:52','sent','room_4_o2x8l9dwa','text',NULL,NULL,NULL),('chat_67df9f3b834c07.64053341','8','4','hi im admin','2025-03-23 05:42:19','sent','room_4_o2x8l9dwa','text',NULL,NULL,NULL),('chat_67dfa0d1056ae','5','8','Hi','2025-03-23 05:49:05','read','room_5_5zrsc5d23','text',NULL,'24','25'),('chat_67dfa0d287579','bot','5','Hello! How can I help you today?','2025-03-23 05:49:06','sent','room_5_5zrsc5d23','text',NULL,'24','25'),('chat_67dfc32886bc1','4','8','HAHAHAHA','2025-03-23 08:15:36','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc32993e01','bot','4','Thank you for your message. One of our customer service representatives will assist you shortly.','2025-03-23 08:15:37','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc5fb3e9730.34973818','22','4','haha','2025-03-23 08:27:39','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc6144d1068.04140997','22','4','ahvshavh','2025-03-23 08:28:04','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc675d96cc8.42240199','22','4','agsahgas','2025-03-23 08:29:41','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc7194e0f9','4','8','TRYSE','2025-03-23 08:32:25','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc71a5a486','bot','4','Thank you for your message. One of our customer service representatives will assist you shortly.','2025-03-23 08:32:26','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfc7da412ab7.30654147','22','4','hahah','2025-03-23 08:35:38','read','room_4_o2x8l9dwa','text',NULL,'22','23'),('chat_67dfcc0ef1f67','4','8','to 3','2025-03-23 08:53:34','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67dfcc100de13','bot','4','Thank you for your message. One of our customer service representatives will assist you shortly.','2025-03-23 08:53:36','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67dfce5b81e8b','4','8','HAHAHAH','2025-03-23 09:03:23','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67dfce5c8838d','bot','4','Thank you for your message. One of our customer service representatives will assist you shortly.','2025-03-23 09:03:24','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67dfce97c4b4d5.12425062','22','4','HAHAHAHARYRUF','2025-03-23 09:04:23','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67dfceb478e4d7.05546009','8','4','TYR','2025-03-23 09:04:52','sent','room_4_xs09vii6b','text',NULL,NULL,NULL),('chat_67dfd58cf35183.29941210','22','4','asbgbas','2025-03-23 09:34:04','read','room_4_xs09vii6b','text',NULL,'22','23'),('chat_67e0ee704af988.03334329','8','8','test to employee','2025-03-24 05:32:32','read','room_4_xs09vii6b','text',NULL,NULL,NULL),('chat_67e2303f60a25','bot','41','Hello, welcome to GrievEase paete branch customer support. How can I assist you today?','2025-03-25 04:25:35','sent','room_41_2gnt1nw4s','text',NULL,'24','25'),('chat_67e25bab58a7c','bot','1','Hello, welcome to GrievEase pila branch customer support. How can I assist you today?','2025-03-25 07:30:51','sent','room_1_6soj2ghm0','text',NULL,'22','23');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_category`
--

DROP TABLE IF EXISTS `inventory_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_category`
--

LOCK TABLES `inventory_category` WRITE;
/*!40000 ALTER TABLE `inventory_category` DISABLE KEYS */;
INSERT INTO `inventory_category` VALUES (1,'cascet'),(2,'flower'),(3,'urns'),(4,'apparatus'),(5,'transportation');
/*!40000 ALTER TABLE `inventory_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_tb`
--

DROP TABLE IF EXISTS `inventory_tb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_tb` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_value` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `price`) STORED,
  `branch_id` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `inventory_img` longblob DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`inventory_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_tb`
--

LOCK TABLES `inventory_tb` WRITE;
/*!40000 ALTER TABLE `inventory_tb` DISABLE KEYS */;
INSERT INTO `inventory_tb` (`inventory_id`, `item_name`, `category_id`, `quantity`, `price`, `branch_id`, `updated_at`, `inventory_img`, `status`) VALUES (2,'item1',1,4,150000.00,1,'2025-03-21 06:40:09','',1),(3,'item2',3,5,12467.00,2,'2025-03-21 06:40:09',NULL,1),(4,'twolips',0,6,123567.00,2,'2025-03-21 06:40:18',NULL,0),(5,'item2',1,45,44332.00,1,'2025-03-21 06:44:25',NULL,0),(6,'item2',2,3,1234.00,2,'2025-03-21 06:40:09',NULL,1),(7,'try',1,5,50.00,0,'2025-03-21 06:40:09',NULL,1),(8,'item5',1,4,1244.00,2,'2025-03-21 06:40:09',NULL,1),(9,'item5',1,55,123.00,1,'2025-03-21 06:40:09',NULL,1),(10,'item6',2,5,3245.00,2,'2025-03-21 06:39:28',NULL,1),(11,'item7',1,5,7532.00,2,'2025-03-22 12:58:13',NULL,1);
/*!40000 ALTER TABLE `inventory_tb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_tb`
--

DROP TABLE IF EXISTS `sales_tb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_tb` (
  `sales_id` int(11) NOT NULL AUTO_INCREMENT,
  `customerID` int(11) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `fname_deceased` varchar(100) DEFAULT NULL,
  `mname_deceased` varchar(100) DEFAULT NULL,
  `lname_deceased` varchar(100) DEFAULT NULL,
  `suffix_deceased` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `date_of_death` date DEFAULT NULL,
  `date_of_burial` date DEFAULT NULL,
  `get_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sold_by` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `initial_price` decimal(10,2) DEFAULT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `payment_status` enum('With Balance','Fully Paid') DEFAULT NULL,
  `death_cert_image` blob DEFAULT NULL,
  `deceased_address` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`sales_id`),
  KEY `sold_by` (`sold_by`),
  KEY `branch_id` (`branch_id`),
  KEY `service_id` (`service_id`),
  KEY `customerID` (`customerID`),
  CONSTRAINT `sales_tb_ibfk_1` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_tb_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branch_tb` (`branch_id`),
  CONSTRAINT `sales_tb_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services_tb` (`service_id`),
  CONSTRAINT `sales_tb_ibfk_4` FOREIGN KEY (`customerID`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_tb`
--

LOCK TABLES `sales_tb` WRITE;
/*!40000 ALTER TABLE `sales_tb` DISABLE KEYS */;
INSERT INTO `sales_tb` VALUES (1,NULL,'customer','','ten','','09568426512','customer10@gmail.com','customer','','ten','','2002-02-02','2025-02-02','2025-02-07','2025-03-25 08:41:39',1,1,3,'Cash',574433.00,574433.00,77383882.00,-76809449.00,'Pending','Fully Paid',NULL,'havshjash');
/*!40000 ALTER TABLE `sales_tb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_category`
--

DROP TABLE IF EXISTS `service_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_category` (
  `service_categoryID` int(11) NOT NULL AUTO_INCREMENT,
  `service_category_name` varchar(255) NOT NULL,
  PRIMARY KEY (`service_categoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_category`
--

LOCK TABLES `service_category` WRITE;
/*!40000 ALTER TABLE `service_category` DISABLE KEYS */;
INSERT INTO `service_category` VALUES (1,'Funeral'),(2,'Cremation'),(3,'Life-Plan');
/*!40000 ALTER TABLE `service_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_tb`
--

DROP TABLE IF EXISTS `services_tb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services_tb` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) NOT NULL,
  `service_categoryID` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `casket_id` int(11) DEFAULT NULL,
  `urn_id` int(11) DEFAULT NULL,
  `flower_design` varchar(255) DEFAULT NULL,
  `inclusions` text DEFAULT NULL,
  `timestamp_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `capital_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_tb`
--

LOCK TABLES `services_tb` WRITE;
/*!40000 ALTER TABLE `services_tb` DISABLE KEYS */;
INSERT INTO `services_tb` VALUES (1,'PANG MAHIRAP',0,0,NULL,NULL,'Normal, Deluxe, Premium','Transportation, MemorialPrograms, GriefCounseling, MusicService','2025-03-23 03:43:29',45679.00,100000.00,NULL,'Active'),(2,'da',1,0,2,NULL,'Normal, Custom','Transportation, MemorialPrograms, GriefCounseling, MusicService','2025-03-23 03:44:42',234.00,6443.00,NULL,'Active'),(3,'PANG MAHIRAP',1,1,9,NULL,'Normal, Deluxe, Custom','Transportation, Embalming, MemorialPrograms, Videography','2025-03-23 07:54:20',672355.00,574433.00,NULL,'Active');
/*!40000 ALTER TABLE `services_tb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_type` int(11) DEFAULT 1,
  `otp` varchar(6) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT NULL,
  `branch_loc` varchar(255) DEFAULT 'unknown',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Wesley','Bibon','fuentes',NULL,'2002-10-02','','wesleyfuentes2k18@gmail.com','$2y$10$oe5/kwfaOW7TKlMTsUMVweqW43Eo/TCBNCqBrhlTB4Aq6B59YHh/W','0000-00-00 00:00:00','2025-03-25 15:30:51',3,NULL,1,'pila',NULL,NULL),(3,'customer','one','',NULL,'2002-01-01','','wesleyfuentes2k22@gmail.com','$2y$10$fNFz9/ZU2VC1lCRMD4Hj4..pH8lY3hr/E2fhmM8AkHp1QDHBvJiM.','0000-00-00 00:00:00','2025-03-25 15:20:12',3,NULL,1,'pila',NULL,NULL),(4,'Customer','two','',NULL,'2002-01-02','','customer2@gmail.com','$2y$10$JK7Y4He5QgI4o3aZqB4Pjeie5cxFxT4KNi0PvF4wnffMHA06JP7Qu','0000-00-00 00:00:00','2025-03-22 19:39:34',3,NULL,1,'pila',NULL,NULL),(5,'customer','three','',NULL,'2005-01-02','','customer3@gmail.com','$2y$10$ALUZWLT1iYrMua7eudrOqe2OZySidjcpVlQmc1UsqXpwNwR6QqgxK','0000-00-00 00:00:00','2025-03-23 11:24:52',3,NULL,1,'paete',NULL,NULL),(6,'customer','four','',NULL,'2002-10-02','','customer4@gmail.com','$2y$10$TCSgSclDeHWxQIzhUnjPiu56.pVguOARLmYfsZ2ecK.xG24/JYF0W','0000-00-00 00:00:00','2025-03-22 19:39:34',3,NULL,1,'paete',NULL,NULL),(8,'Admin','Vjay','',NULL,'2006-01-02','','admin1@gmail.com','$2y$10$JrZnlYJlSo5iDtu06CtiEO1q5Vd1Dgvz0hMgTEJsW2QRfk.ZPik/y','0000-00-00 00:00:00','2025-03-25 15:36:55',1,NULL,NULL,'unknown',NULL,NULL),(10,'customer','five','',NULL,'2005-02-02','','customer5@gmail.com','$2y$10$7RTE5zZa45XlZvMDOR/p9etrwF3w2Fr8gjW/gMqnHUfemr98/7daa','0000-00-00 00:00:00','2025-03-22 21:41:38',3,NULL,1,'pila',NULL,NULL),(22,'employee','pila','one',NULL,'2000-02-02','','employeepila#1@gmail.com','$2y$10$RM6sFJQAXgmqZfiHmmma1OeTUYoL8NagHym9.i7C107eDQKm12Wcm','0000-00-00 00:00:00','2025-03-22 19:24:52',2,NULL,NULL,'pila',NULL,NULL),(23,'employee','pila','two',NULL,'2000-05-02','','employeepila#2@gmail.com','$2y$10$oG60NyTJqLfEkOo/Ak/JQ.hGQnantRechiXRAv0lubAtI3Zc7f9.S','0000-00-00 00:00:00','2025-03-22 19:24:52',2,NULL,NULL,'pila',NULL,NULL),(24,'employee','paete','one',NULL,'2005-05-05','','employeepaete#1@gmail.com','$2y$10$zz4I0i9EpPw5isIrbq4/sOFOz2UCyJQcyqS2PE.Axn3JmAnLOUhH6','0000-00-00 00:00:00','2025-03-22 19:23:42',2,NULL,NULL,'paete',NULL,NULL),(25,'employee','paete','two',NULL,'2004-05-02','','employeepaete#2@gmail.com','$2y$10$Gr1.P.E.GQQDZbiplemoteG1W.p1WEa.YS8M89.LIHB3sYu5I7pz6','0000-00-00 00:00:00','2025-03-22 19:24:36',2,NULL,NULL,'paete',NULL,NULL),(41,'customer','ten','',NULL,'1999-09-14','','wesleybibon2k19@gmail.com','$2y$10$MWKHcnuqFQlcwJmKVdqANOJ7SjTh0TrkUj1qDEPDVyB.LupUvRLG.','2025-03-25 12:24:59','2025-03-25 12:25:35',3,NULL,1,'paete',NULL,NULL);
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

-- Dump completed on 2025-03-25 16:44:44

-- created manually from a similar table dump
--
-- Host: localhost    Database: web_demo
-- ------------------------------------------------------
-- Server version	5.5.41-0ubuntu0.12.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `username` varchar(255) NOT NULL,
  `pw_hash` varchar(10) DEFAULT 'sha256',
  `password` varchar(255) NOT NULL,
  `access_token` varchar(255),
  `ts_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked` BOOLEAN DEFAULT False,
  `ts_last_login` timestamp,
  PRIMARY KEY (`username`)
);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('Demo User',           'sha256','ff5bb3b85ea61d1b695fddb5f0124ce35786d64a435121c3e0b7c1a3434d2f15','','2022-06-01 09:00:00', False, '');  -- pw: none
INSERT INTO `users` VALUES ('Test',                'plain', 'test','','2022-06-01 10:00:00', False, '');
INSERT INTO `users` VALUES ('Demo User2',          'sha256','e645bfad3de5256e48df7da40f3f77500a2058f1f600e7d77db434710a9f4afa','','2022-06-01 11:00:00', False, '');  -- pw: none
-- INSERT INTO `users` VALUES ('O\'Brien',            'plain','none','','2022-06-01 12:00:00', False, '');
-- INSERT INTO `users` VALUES ('Harold &amp; Maude',  'plain','none','','2022-06-01 13:00:00', False, '');


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

-- Dump completed on 2015-01-31 15:50:13

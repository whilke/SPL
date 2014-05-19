-- MySQL dump 10.13  Distrib 5.5.28, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: spl
-- ------------------------------------------------------
-- Server version	5.5.28-0ubuntu0.12.10.2

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
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES (1,'admin','Administrator'),(2,'members','General User'),(3,'globman','Global Manager'),(4,'manager','Manager');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `heroes`
--

DROP TABLE IF EXISTS `heroes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heroes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `heroes`
--

LOCK TABLES `heroes` WRITE;
/*!40000 ALTER TABLE `heroes` DISABLE KEYS */;
INSERT INTO `heroes` VALUES (1,'Hero_Ray'),(2,'Hero_Fetterstone'),(3,'Hero_Claudessa'),(4,'Hero_Caprice'),(5,'Hero_Ladytinder'),(6,'Hero_Shank'),(7,'Hero_Vermillion'),(8,'Hero_Malady'),(9,'Hero_Bo'),(10,'Hero_Carter'),(11,'Hero_Ace');
/*!40000 ALTER TABLE `heroes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(15) NOT NULL,
  `login` varchar(100) NOT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matches`
--

DROP TABLE IF EXISTS `matches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `gamedate` datetime DEFAULT NULL,
  `proposeddate` datetime DEFAULT NULL,
  `home_team_id` int(11) NOT NULL,
  `away_team_id` int(11) NOT NULL,
  `home_team_state_id` int(11) DEFAULT NULL,
  `home_team_points` int(11) NOT NULL DEFAULT '0',
  `away_team_points` varchar(45) NOT NULL DEFAULT '0',
  `away_team_state_id` int(11) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '0',
  `week_id` int(11) NOT NULL,
  `strife_match_id` int(11) DEFAULT NULL,
  `home_team_ban_hero_id` int(11) DEFAULT NULL,
  `away_team_ban_hero_id` varchar(45) DEFAULT NULL,
  `server_region` varchar(45) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `replay_processed` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matches`
--

LOCK TABLES `matches` WRITE;
/*!40000 ALTER TABLE `matches` DISABLE KEYS */;
/*!40000 ALTER TABLE `matches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pets`
--

LOCK TABLES `pets` WRITE;
/*!40000 ALTER TABLE `pets` DISABLE KEYS */;
INSERT INTO `pets` VALUES (1,'Familiar_Mystik'),(2,'Familiar_Tortus'),(3,'Familiar_Bounder'),(4,'Familiar_Razer'),(5,'Familiar_Topps'),(6,'Familiar_Luster');
/*!40000 ALTER TABLE `pets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `strife_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  PRIMARY KEY (`strife_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `start` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(10) DEFAULT NULL,
  `current` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons`
--

LOCK TABLES `seasons` WRITE;
/*!40000 ALTER TABLE `seasons` DISABLE KEYS */;
INSERT INTO `seasons` VALUES (6,'Strife Beta Series 1','2014-05-12 00:00:00','2014-05-17 00:00:00',1,'SB1',1);
/*!40000 ALTER TABLE `seasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seasons_teams`
--

DROP TABLE IF EXISTS `seasons_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seasons_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `season_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seasons_teams`
--

LOCK TABLES `seasons_teams` WRITE;
/*!40000 ALTER TABLE `seasons_teams` DISABLE KEYS */;
INSERT INTO `seasons_teams` VALUES (10,6,16),(11,6,28),(12,6,33),(13,6,19),(14,6,22),(15,6,4),(18,6,38),(21,6,26),(22,6,2),(27,6,41),(29,6,34),(30,6,23),(39,6,30),(40,6,45),(43,6,43),(45,6,42),(46,6,37),(48,6,40),(50,6,12),(52,6,48),(53,6,14),(55,6,36),(57,6,27),(59,6,49),(61,6,51),(62,6,11);
/*!40000 ALTER TABLE `seasons_teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `desc` varchar(45) NOT NULL,
  `code` varchar(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
INSERT INTO `states` VALUES (0,'Open','O'),(1,'Win','W'),(2,'Loss','L'),(3,'Forfeit Loss','FL'),(4,'Forfeit Win','FW'),(5,'No show','NS');
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats`
--

DROP TABLE IF EXISTS `stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `hero_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `advanced` smallint(6) DEFAULT '0',
  `total_gold` int(11) DEFAULT NULL,
  `total_creep` int(11) DEFAULT NULL,
  `total_neut` int(11) DEFAULT NULL,
  `kills` int(11) DEFAULT NULL,
  `assists` int(11) DEFAULT NULL,
  `deaths` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats`
--

LOCK TABLES `stats` WRITE;
/*!40000 ALTER TABLE `stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `captain` varchar(100) DEFAULT NULL,
  `slot1` varchar(100) DEFAULT NULL,
  `slot2` varchar(100) DEFAULT NULL,
  `slot3` varchar(100) DEFAULT NULL,
  `slot4` varchar(100) DEFAULT NULL,
  `slot5` varchar(100) DEFAULT NULL,
  `slot6` varchar(100) DEFAULT NULL,
  `bio` text,
  `logo` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `region` varchar(15) DEFAULT NULL,
  `teamscol` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES (2,4,'Team Dynamix','TDx Lakota','TDx Lowjaxx','TDx Cobretti','TDx Lakez','TDx Glitch','Smd','Swan','0','http://www.strifeproleague.com/files/logo/T2.jpg','/u/thelakotasioux','USW',NULL),(4,6,'Team Kaizen','Gullivre','Ryzen','Arinnar','nyHGN','Mantequillas','Ghostly','Mobes','0','http://www.strifeproleague.com/files/logo/T4.png','In-Game: Gullivre, Ryzen','USW',NULL),(5,7,'CHAOS VANGUARD','Styxa','','','','','Superkge','Emperor','USE/EU \nPUG TEAM',NULL,'Styxa (Ingame)','USE',NULL),(6,8,'Super Natural','Wubby','Zhiku','Jejper','Wey','Lagg','','','Mucho grande, bad DAX?',NULL,'zHiKU','EU',NULL),(7,9,'NONAMEYET','linkens','Yellowjohn','Wolfjoe','Thatchy','Runkralf','Cabbage','Lagg','Team',NULL,'IGN: linkens','EU',NULL),(8,10,'BM','OGKush','SirLoseAlot','','','','','','0','','OGKush','USE',NULL),(9,11,'C6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,12,'Illuminati',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,13,'Domain of Pain','Emperor','Xevil','Parakletos','Kid Peculiar','Superkge','','','0','','Emperor in-game! Other methods can be used if needed.','USW',NULL),(12,14,'Nevermind HQ T1','NHQ T1 Kross','NHQ T1 Playa','NHQ T1 LarkhiLL','NHQ T1 unslash','NHQ T1 Flawxy','','','0','http://www.strifeproleague.com/files/logo/T12.jpg','NHQ T1 Kross','EU',NULL),(13,15,'Â°Cydia',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,16,'Team eShreKt','kittenlazer','teerav','wizkid1234','DarkEvanesser','ZeInsanity','chiefpete','Pandabro','0','http://www.strifeproleague.com/files/logo/T14.png','kittenlazer.prolol@gmail.com / u/kittenblazer','USE',NULL),(15,17,'Team Flaming Squirrelz','M3SSIAH','Koulu','BB33NN','CaptainZouLou','Bigerthanyou','Razeriot','Genraam','Small team from North Carolina, we have a League team as well.',NULL,'teamflamingsquirrelz@gmail.com','USE',NULL),(16,18,'The Chubby Ducks','Honigbaum','Nuja','Gavin','Curriboii','Fluffy','Caerisse','','',NULL,'iG:Honigbaum','EU',NULL),(19,21,'Team Diemen Zuid','Mithax','Kirbz','Zinnix','Xtatic','Banaan','Grbn','Dina','',NULL,'Mithax','EU',NULL),(20,22,'n/a',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,23,'wishyouwell',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,24,'valhalla','Tuttifrutti','Salty Donut','Barcode','Manschie','jUMP','Cuk','','0','','Tuttifrutti','EU',NULL),(23,25,'Ghoul','StormyGamess','Fyrno','Fanatick','TheChinesePanda ','TheRealSpoderman','lolitzrob','Chronic','0','','patsadejj@gmail.com','USE',NULL),(24,26,'Sun-Warriors',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(25,27,'Team LvL','Bambino','Swagilicious','','','','','','',NULL,'Bambino','USW',NULL),(26,29,'Reason Gaming','linkens','Yellowjohn','Thatchy','Runkralf','Wolfjoe','Cabbage','Lagg','0','http://www.strifeproleague.com/files/logo/T26.jpg','IGN: R linkens','EU',NULL),(27,30,'Too Funk To Dunk','Tfanxllak','Protagonist','Papersaver','Krone','Zoos','Ferret','k0wzking','0','http://www.strifeproleague.com/files/logo/T27.png','Hagbui@gmail.com (Tfanxllak\'s)','USW',NULL),(28,31,'FreniK Gaming','Bipolar FnK','Extreme FnK','Ínsolence FnK','Hikoboshi FnK','Ember FnK','Bhelome FnK','Ginacole FnK','Hi',NULL,'09056926273','SEA',NULL),(30,34,'Chillin n Killin','Cnk Inveinz','Cnk shinigami','spindeldiafragma','CnK Dobby','thefett','nightmare','CnK Icekobra','0','','sam.adam.pankhurst   skype','EU',NULL),(31,35,'Delightful Daisys',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(33,37,'Wargods','Toxic','Hikaru','Vinsil','Pain','Hover','Bummy','','Wargods Pro gaming Team',NULL,'09174219340','SEA',NULL),(34,38,'Shadow Gaming','SHG DI','SHG FLAMES','SHG Kaizukoo','SHG spratyon','SHG SWAN','SHG KEP','None Yet ','0','','In Game:SHG DI','EU',NULL),(36,40,'SenpaiPlsNoticeMe','Tangular','Kiwidude','ArcAngelArtemis','Satonaka','MrRemix','Yoshhk','Yesios','0','','Tangular in game','USE',NULL),(37,41,'Sometimes Gay','YoYo','Lokimor','Orctest','LemDog','KJSniper','','','0','','IGN:YoYo','USW',NULL),(38,42,'ezpk','ezpk Pesky','ezpk Faze','ezpk Penta','ezpk Zaxxle','ezpk Corruptus','','','0','','ezpk Pesky','USW',NULL),(39,43,'Payback Gaming',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(40,44,'KellerNation','Smallung','Widdlyscuds','Terricz','Moe Lester','Bryan','','','0','http://www.strifeproleague.com/files/logo/T40.jpg','Smallung@gmail.com','USE',NULL),(41,45,'Jambalaya','youngnpnoiaj','FGGOTClyde','MAGGOTMCFG','FGGOTMCFACE','therealdunny','germs122','GrahamCracker','0','http://www.strifeproleague.com/files/logo/T41.png','getchuckles@gmail.com','USW',NULL),(42,46,'Summit eSports','X','AllyMaster','Gull','Rippa','LazerLemon','RIP Dr X','Drunkelf','0','','skype: mwertenbroch (RIP Dr X our contact person)','EU',NULL),(43,47,'Five Kapparinos','Jejper the Korean','Bergo the Rice with Taco','Marleyy the Baguette in China','Lagg the Farmer in Hong Kong','Rapthera the Japan tourist','Köttbullar','Knäckebröd','0','http://www.strifeproleague.com/files/logo/T43.png','97jedu79@gmail.com','SEA',NULL),(44,48,'Can\'t Catch Lighting',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(45,49,'Clan uK','Spooky','Akilles','Porfer','Onyx','Mastervader','WalmartMeds','','0','http://www.strifeproleague.com/files/logo/T45.jpg','itmesnarfy@live.com','USE',NULL),(46,50,'-Domain of Pain-',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(47,51,'Scythe Gaming',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(48,52,'FGT','vanilla','Borromac','Korvin','Fade','kleinn','','','0','','skype: gmkleinn','EU',NULL),(49,53,'TFTDB','Shota','tomatoes','Feret','Deth','Katherine','k0wzking','Protagonist','0','','Protagonist ingame 2495','USW',NULL),(50,54,'Borromac',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(51,55,'SugarShack','Sugar','Dhacgaming','Radrussian2','ICEMANgogo','Tactix','Trideon','Phantomx123','0','http://www.strifeproleague.com/files/logo/T51.gif','dhacgaming@gmail.com','USE',NULL),(52,56,'Forlorn Hope',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(53,57,'Protohype',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(54,58,'ImbaLANCE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(15) NOT NULL,
  `teamname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `activation_code` varchar(40) DEFAULT NULL,
  `forgotten_password_code` varchar(40) DEFAULT NULL,
  `forgotten_password_time` int(11) unsigned DEFAULT NULL,
  `remember_code` varchar(40) DEFAULT NULL,
  `created_on` int(11) unsigned NOT NULL,
  `last_login` int(11) unsigned DEFAULT NULL,
  `active` tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'127.0.0.1','Admin','e7cc4799ef3cc1e281a4611bb95d4e7db421e334','','admin@strifeproleague.com',NULL,NULL,NULL,'1b2468cddb54bf7a28ebd85b23af4391d31f98eb',1268889823,1400468177,1),(4,'E&','Team Dynamix','260ae8e785907d90f03823fb8af65ce937009bf2',NULL,'udunson@hotmail.com',NULL,NULL,NULL,NULL,1398405586,1400429073,1),(6,'2','Team Kaizen','d7c63b1d7690750f427793a5af2c92936cea4875',NULL,'kznstrife@gmail.com',NULL,NULL,NULL,'037ed59a81ced62aba452b35d21965e3d6281925',1398406053,1400220090,1),(7,'','CHAOS VANGUARD','3975fbab16f727d7a45c7186aa8db4ff985138f8',NULL,'styxagamingstudios@gmail.com',NULL,NULL,NULL,NULL,1398410783,1398410888,1),(8,'','Super Natural','9f91a98285e54ffe087adbd2b942d82365283373',NULL,'zhikuu@gmail.com','03a484634be578f4cdf587182c72ecf47902aec3',NULL,NULL,NULL,1398412539,1398412624,0),(9,'N5b\"','NONAMEYET','f87460d6bf13bdee696b81a9e8322e8b77f9e0dc',NULL,'karim.louhechi@googlemail.com','947235781a0fe3b1b7d9ce1b169e74deb9839658',NULL,NULL,'1ba3ba3189eb4de9c987072895b55ebd756ab129',1398413638,1399951926,0),(10,'b','BM','3306e946ae71970836abcecf2d23eca29c31f3d0',NULL,'samuraidigitech@gmail.com','1a843cb20b02a6c2659726640c1cd2e0de26726c',NULL,NULL,NULL,1398432390,1399948930,0),(11,'bp','C6','4eda355c3ee39b70715eee89ecbd8e97a2986fa2',NULL,'ktoeiffel@hotmail.com',NULL,NULL,NULL,'545b54e54d9c9407c677c699b1a244629d242c98',1398800264,1399749476,1),(12,'Fb','Illuminati','5831215a2ae9c10ae601f229743f153ff075aa15',NULL,'alaarab@gmail.com',NULL,NULL,NULL,NULL,1398899728,1398899814,1),(13,'c','Domain of Pain','6e1c3aa01296e5b7c3b2689c0ed24177a71c2fd4',NULL,'colorado_4_10@hotmail.com',NULL,NULL,NULL,'ee0f6c26982150cb832981fac0fd9db211f4c35e',1398973648,1400433330,1),(14,'QAm','Nevermind HQ T1','49830881e135c82a3dd8a373be1e145eec706ffd',NULL,'ikross972@hotmail.fr',NULL,NULL,NULL,'e26e8af3ac245f0be7da7ab8dfbb48d970b81d4d',1398985656,1400436111,1),(15,'','Â°Cydia','1d637ca861f536a737f05dccfc99190857e32a9d',NULL,'bjjb0825@gmail.com',NULL,NULL,NULL,'e2299746368e1eaa27f588cda810f104a818612d',1399138388,1399138480,1),(16,'d','Team eShreKt','85f59a57e3b026d2b1493c322b34bac36c609209',NULL,'dave847@gmail.com',NULL,NULL,NULL,'dccf1f8b52bd9fde3198fdb9552200e640970095',1399153682,1400450363,1),(17,'','Team Flaming Squirrelz','8ebc15ee7971aba5b763370752dedf362b0b6821',NULL,'thatbiggamingnoob@gmail.com',NULL,NULL,NULL,NULL,1399325130,1400023809,1),(18,'_[','The Chubby Ducks','734c6e0b688ff936881c9a21ef5b5cf59a8f9456',NULL,'julian.wr@web.de',NULL,NULL,NULL,'296eb38dee2b1fcd0e5bf4e67120212d318e5dac',1399496905,1400454441,1),(21,'TP','Team Diemen Zuid','7c0bc90b52c27f46b8ba3bb7fbd3cd5b683a5d1d',NULL,'mithax@hotmail.com',NULL,NULL,NULL,NULL,1399671977,1400076505,1),(22,'G','n/a','427ac3675af8f6c811ee6f86c2781683d0c5070f',NULL,'william.woodside.88@gmail.com',NULL,NULL,NULL,'56b633b1b5edab165c0c0e330ed839ecb8113b5c',1399687838,1399687904,1),(23,'\\eM.','wishyouwell','ed0d405dfb745b760a3dbe0e0b044bf5ef0c2b91',NULL,'dreamfall13@mail.ru',NULL,NULL,NULL,NULL,1399719061,1399719061,1),(24,'N','valhalla','a582737fb1ab615c3be684ad72e5f4565e64361d',NULL,'jernej.koderman@gmail.com',NULL,'3ee51ba044afcd0bae4d807c660e75a3b1318554',1400021784,NULL,1399723485,1400089514,1),(25,'2','Ghoul','ed7f9f60e5a93cb9ba1d4ccc118f0f73c1be6a44',NULL,'patsadejj@gmail.com',NULL,NULL,NULL,'7866b54abbf15d4251c8a088bee8b124ec846a08',1399771453,1400277837,1),(26,'_','Sun-Warriors','2091753ac2c97451a34aa0e0740d0cb3b4bd9e28',NULL,'OsgamezDG@hotmail.com',NULL,NULL,NULL,NULL,1399774291,1399774334,1),(27,'k?','Team LvL','4acb10cfdbd79279af8e93f5d73079f1a438e6cc',NULL,'bguzman408@gmail.com',NULL,NULL,NULL,NULL,1399835805,1400182841,1),(28,'-','Admin','2a6c447f91d74c1bdcc2e92d1df9933fa42f78fe',NULL,'tulce@strifeproleague.com',NULL,NULL,NULL,'59e50d599b53981d3c2af1e05dc9f8dfa84b431b',0,1400451649,1),(29,'\\','Reason Gaming','fa980675622e0531ef9185692c808a95aae0917b',NULL,'heggy193@yahoo.de',NULL,NULL,NULL,'bfb8bfee6747aa451c6490b52076d564bf6a3625',1399952045,1400447444,1),(30,'l','Too Funk To Dunk','bea63fb6362ac54779646a21368efe6b0bc6ddec',NULL,'Hagbui@gmail.com',NULL,NULL,NULL,'dbc501d4f36280ef98427587fb797ca0304ec3c0',1399953668,1400461563,1),(31,'p','FreniK Gaming','65ddc399a33c61abc1e74b4c532fe83ba32db919',NULL,'john_malic@yahoo.com',NULL,NULL,NULL,NULL,1399986811,1399986905,1),(32,'-','Admin','cc5074e83d323f345ae38b1046b1947184ba3070',NULL,'andreulrich@live.com',NULL,NULL,NULL,NULL,0,1400175234,1),(34,'U','Chillin n Killin','0ec5cb3675f4f6b455c9a134dccd449bb1926fdd',NULL,'samadampankhurst@hotmail.com',NULL,NULL,NULL,NULL,1400005765,1400326282,1),(35,'H','Delightful Daisys','c8d0ab5790e42c404d6c0593b1c2b72445c04ee5',NULL,'elfeylol@hotmail.com','2178e0c79e741e9a2e395174eedc1a77ca9afa34',NULL,NULL,NULL,1400016154,1400017564,0),(37,'x','Wargods','24223ae3ba3a5e070d750d3db00ebbf4b987a463',NULL,'alvinprillera@gmail.com',NULL,NULL,NULL,NULL,1400027760,1400028065,1),(38,'U','Shadow Gaming','9b41cb2563e5a52d8e54daa4eda1d6ce95c6c948',NULL,'ajredin14@hotmail.com',NULL,NULL,NULL,'5e88ef798d6df6ce733c70e6bd6eb4366ab67d41',1400090076,1400428471,1),(40,'LE ','SenpaiPlsNoticeMe','f78996bc4bfd49e78d06af60ccff68c931b8e7df',NULL,'xi.tang.ix@gmail.com',NULL,NULL,NULL,'4f8d7a65fa0be97b5055f8cbd6321e06fe00904c',1400119553,1400473360,1),(41,'2.','Sometimes Gay','16f816cdac825a6d1a92a6ae263d184c32843dc2',NULL,'nomohoe09@yahoo.com',NULL,NULL,NULL,NULL,1400122663,1400347061,1),(42,'A','ezpk','6171393750a09e0872cecc6e22232982772e5b35',NULL,'alexzlaing@gmail.com',NULL,NULL,NULL,NULL,1400150431,1400150536,1),(43,'^','Payback Gaming','b6732b29ac37edab9a26cca544c5e9c6433ad78e',NULL,'mislavkaporkaporelo@gmail.com',NULL,NULL,NULL,'27173c653bf915bc697b2ed7b59b946251331624',1400160455,1400160537,1),(44,'','KellerNation','eab10a5f3b72b0ab6a5266843c1e6efeb0f508df',NULL,'Smallung@gmail.com',NULL,NULL,NULL,'ebe4375bbd50dc72636014415de8647063e73f94',1400188544,1400354230,1),(45,'c','Jambalaya','fcab1bd0b1318562fc77e8b734bffa8c17498f17',NULL,'gd.bngr@gmail.com',NULL,NULL,NULL,'8fbd9b415be1add47c6cd8e073afa90b4c0a238e',1400206086,1400456914,1),(46,'','Summit eSports','97e6e0fd362d89c9a9bc7b4c512104fa6378b6bf',NULL,'leorics@gmail.com',NULL,NULL,NULL,'d144d0f71fcf400383e6904f4545b6320b91a411',1400240464,1400415500,1),(47,'Q','Five Kapparinos','65629079ebc5a7932e1c31c5c96d2fa61b5d4b4c',NULL,'97jedu79@gmail.com',NULL,NULL,NULL,NULL,1400285739,1400435118,1),(48,'','Can\'t Catch Lighting','927e70f8a2e5dc369cf9de5f49c3de35302b5c00',NULL,'itmesnarfy@live.com',NULL,NULL,NULL,'89d165e052a7a54e835758ae19128c86bd3a5443',1400314520,1400391449,1),(49,'','Clan uK','65b76c826790abc0e03e5f88de3468896b28b6e1',NULL,'wumpscut08@gmail.com',NULL,NULL,NULL,'9debdafc9a4d36a0eac5d8990db396497397ce40',1400328375,1400391338,1),(50,'','-Domain of Pain-','98c9611207b8e60f19e3ebf995140f9f5526c933',NULL,'Andrew.Alavi@gmail.com',NULL,NULL,NULL,NULL,1400331442,1400331936,1),(51,'FA+','Scythe Gaming','16f53abaaac1ade94182462f4b82f1d706c3ee51',NULL,'Darius@catherall.ca',NULL,NULL,NULL,NULL,1400333242,1400333389,1),(52,'.\'','FGT','608650bd9883040389babbbd4ca7df34e0de965c',NULL,'gmkleinnn@gmail.com',NULL,NULL,NULL,NULL,1400357338,1400357368,1),(53,'','TFTDB','30944ad4e7803d7aac3bc1ad4b4004207662774f',NULL,'protagoniststream@gmail.com',NULL,NULL,NULL,NULL,1400360257,1400464537,1),(54,'Q','Borromac','ef8e887b5c8235be5699fc8e1eb35327d3ecb3aa',NULL,'nilsdonald@hotmail.com',NULL,NULL,NULL,NULL,1400366388,1400366439,1),(55,'F','SugarShack','846db1f2a3affdaf7c822b41123e2da9795f0ebf',NULL,'dhacgaming@gmail.com',NULL,NULL,NULL,NULL,1400367132,1400413258,1),(56,'M1y','Forlorn Hope','a3eda7c5bbea12fabd7da2978fca9afe49a19b51',NULL,'Darcioussssssssss@gmail.com','77cd9b3e07610837324afc045b5d456e66f94fef',NULL,NULL,NULL,1400427628,1400427628,0),(57,'L','Protohype','7857cdfd09dc02706681849d1da17e06eb14ed59',NULL,'coreymrussell@gmail.com',NULL,NULL,NULL,NULL,1400451311,1400451358,1),(58,'}','ImbaLANCE','f36f838a2ae1822604501d5f0a7cfd2b0ffff11f',NULL,'wxzy123@gmail.com','9711b2f011c7ce3fd5d8822e1d9f5fc57666090a',NULL,NULL,NULL,1400454553,1400454553,0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_groups`
--

DROP TABLE IF EXISTS `users_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_users_groups` (`user_id`,`group_id`),
  KEY `fk_users_groups_users1_idx` (`user_id`),
  KEY `fk_users_groups_groups1_idx` (`group_id`),
  CONSTRAINT `fk_users_groups_groups1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_users_groups_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_groups`
--

LOCK TABLES `users_groups` WRITE;
/*!40000 ALTER TABLE `users_groups` DISABLE KEYS */;
INSERT INTO `users_groups` VALUES (1,1,1),(2,1,2),(5,4,2),(7,6,2),(8,7,2),(9,8,2),(10,9,2),(11,10,2),(12,11,2),(13,12,2),(14,13,2),(15,14,2),(16,15,2),(17,16,2),(18,17,2),(19,18,2),(22,21,2),(23,22,2),(24,23,2),(25,24,2),(26,25,2),(27,26,2),(28,27,2),(30,28,2),(29,28,3),(31,29,2),(32,30,2),(33,31,2),(34,32,2),(35,32,4),(37,34,2),(38,35,2),(40,37,2),(41,38,2),(43,40,2),(44,41,2),(45,42,2),(46,43,2),(47,44,2),(48,45,2),(49,46,2),(50,47,2),(51,48,2),(52,49,2),(53,50,2),(54,51,2),(55,52,2),(56,53,2),(57,54,2),(58,55,2),(59,56,2),(60,57,2),(61,58,2);
/*!40000 ALTER TABLE `users_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weeks`
--

DROP TABLE IF EXISTS `weeks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weeks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(45) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `season_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weeks`
--

LOCK TABLES `weeks` WRITE;
/*!40000 ALTER TABLE `weeks` DISABLE KEYS */;
INSERT INTO `weeks` VALUES (3,'W1','2014-05-26 00:00:00','2014-06-01 00:00:00',6);
/*!40000 ALTER TABLE `weeks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'spl'
--

--
-- Dumping routines for database 'spl'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-05-18 21:31:18

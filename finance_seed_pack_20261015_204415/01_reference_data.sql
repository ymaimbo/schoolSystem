-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: vigurunganiSenior
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.3

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
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` (`id`, `name`, `slug`, `billing_cycle_amount_due`, `billing_cycle_amount_paid`, `billing_paid_percent`, `billing_access_stage`, `billing_grace_until`, `billing_access_override`, `billing_access_override_reason`, `billing_restricted_at`, `code`, `location`, `county`, `type`, `status`, `note`, `logo_path`, `hero_image_path`, `created_at`, `updated_at`) VALUES (1,'Vigurungani Senior School','vigurungani-senior-school',100000.00,50000.00,50.00,'half_only',NULL,0,NULL,'2026-09-30 13:44:38','2109104','Kinango Constituency, Kwale County, Kenya','Kwale County','Public Mixed Boarding School','Listed in the official register of secondary schools in Kwale County.','Specific contact details, postal addresses, and KCSE results are not explicitly provided in the current context.','/images/vigurungani-logo.png','/images/kinango-campus.jpg','2026-04-28 02:15:25','2026-09-30 06:58:09');
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `vote_heads`
--

LOCK TABLES `vote_heads` WRITE;
/*!40000 ALTER TABLE `vote_heads` DISABLE KEYS */;
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (2,1,'RMI','RMI / Remedial',1,'2026-08-15 10:32:08','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (3,1,'LTT','LT & T',1,'2026-08-15 10:32:08','2026-08-15 10:32:08');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (4,1,'ADM','Administration Costs',1,'2026-08-15 10:32:08','2026-08-15 10:32:08');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (5,1,'EWC','EWC',1,'2026-08-15 10:32:08','2026-08-15 10:32:08');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (9,1,'SMASSE','SMASSE',1,'2026-08-15 10:32:08','2026-08-15 10:32:08');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (10,1,'PTA','PTA',1,'2026-08-15 10:32:08','2026-08-15 10:32:08');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (13,1,'TEACHING','Teaching, Learning Materials and Exams',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (14,1,'ACTIVITY','Activity',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (15,1,'PERSONAL_EMOL','Personal Emoluments',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (16,1,'MEDICAL_INSUR','Medical & Insurance',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (17,1,'LUNCH_PROGRAMME','Lunch Programme',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
INSERT INTO `vote_heads` (`id`, `school_id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES (18,1,'BOARDING_MEALS','Boarding & Meals',1,'2026-08-22 04:38:15','2026-08-22 04:38:15');
/*!40000 ALTER TABLE `vote_heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `fee_profiles`
--

LOCK TABLES `fee_profiles` WRITE;
/*!40000 ALTER TABLE `fee_profiles` DISABLE KEYS */;
INSERT INTO `fee_profiles` (`id`, `school_id`, `code`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,'DAY_SCHOLAR','Day Scholar','Default day scholar billing profile',1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profiles` (`id`, `school_id`, `code`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (2,1,'BOARDER','Boarder','Default boarder billing profile',1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
/*!40000 ALTER TABLE `fee_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `fee_profile_votehead_priorities`
--

LOCK TABLES `fee_profile_votehead_priorities` WRITE;
/*!40000 ALTER TABLE `fee_profile_votehead_priorities` DISABLE KEYS */;
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,1,13,1,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (2,1,1,17,3,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (3,1,1,14,5,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (4,1,2,18,1,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (5,1,2,17,2,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (6,1,2,13,3,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (7,1,2,16,5,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
INSERT INTO `fee_profile_votehead_priorities` (`id`, `school_id`, `fee_profile_id`, `vote_head_id`, `priority_order`, `is_required`, `is_active`, `created_at`, `updated_at`) VALUES (8,1,2,14,7,1,1,'2026-10-05 11:00:02','2026-10-05 11:00:02');
/*!40000 ALTER TABLE `fee_profile_votehead_priorities` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-15 20:44:16

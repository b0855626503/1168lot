/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: 1168lot_wallet
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

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
-- Table structure for table `accounces`
--

DROP TABLE IF EXISTS `accounces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounces` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `new` enum('Y','N') NOT NULL DEFAULT 'N',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `all_log`
--

DROP TABLE IF EXISTS `all_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `all_log` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `member_user` varchar(100) NOT NULL DEFAULT '',
  `status_log` tinyint(1) NOT NULL,
  `before_credit` varchar(255) NOT NULL DEFAULT '',
  `amount` decimal(11,2) NOT NULL,
  `after_credit` varchar(255) NOT NULL DEFAULT '',
  `pro_id` int(11) NOT NULL,
  `pro_amount` decimal(11,2) NOT NULL,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `gamebalance` decimal(11,2) NOT NULL,
  `bank_payment_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `remark` varchar(200) NOT NULL,
  `bonus` varchar(100) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `type_record` int(11) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(200) NOT NULL,
  `user_update` varchar(200) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `all_log_bank_payment_id_index` (`bank_payment_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_payment`
--

DROP TABLE IF EXISTS `bank_payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_payment` (
  `code` bigint(20) NOT NULL AUTO_INCREMENT,
  `bank` varchar(20) NOT NULL DEFAULT '',
  `account_code` int(11) NOT NULL DEFAULT 0,
  `report_id` varchar(200) NOT NULL DEFAULT '',
  `bankstatus` int(1) NOT NULL DEFAULT 0 COMMENT '1=BankIn,2=BankOut',
  `bankname` varchar(200) NOT NULL DEFAULT '',
  `txid` varchar(40) NOT NULL DEFAULT '',
  `bank_time` timestamp NULL DEFAULT NULL,
  `time` varchar(200) NOT NULL DEFAULT '',
  `type` varchar(200) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `channel` varchar(1024) NOT NULL DEFAULT '',
  `value` decimal(11,2) NOT NULL DEFAULT 0.00,
  `comm_value` decimal(11,2) NOT NULL DEFAULT 0.00,
  `fee` double(20,2) NOT NULL DEFAULT 0.00,
  `detail` mediumtext NOT NULL,
  `checktime` varchar(200) NOT NULL DEFAULT '',
  `tx_hash` varchar(32) NOT NULL DEFAULT '',
  `status` int(5) NOT NULL DEFAULT 0 COMMENT '0=Wait,1=Accept,2=Reject,3=OutClear',
  `webcode` int(11) NOT NULL DEFAULT 0,
  `before_credit` decimal(11,2) NOT NULL DEFAULT 0.00,
  `tranferer` varchar(255) NOT NULL DEFAULT '' COMMENT 'User',
  `after_credit` decimal(11,2) NOT NULL DEFAULT 0.00,
  `webbefore` decimal(11,2) NOT NULL DEFAULT 0.00,
  `webafter` decimal(11,2) NOT NULL DEFAULT 0.00,
  `score` decimal(11,2) NOT NULL DEFAULT 0.00,
  `proclick` enum('Y','N') NOT NULL DEFAULT 'N',
  `pro_check` enum('Y','N') NOT NULL DEFAULT 'N',
  `pro_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Pro ID',
  `pro_amount` decimal(11,2) NOT NULL DEFAULT 0.00 COMMENT 'Pro Amount',
  `user_id` varchar(200) NOT NULL DEFAULT '' COMMENT 'Employee',
  `date_topup` timestamp NULL DEFAULT NULL,
  `codename` varchar(200) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `atranferer` varchar(15) NOT NULL DEFAULT '',
  `topupstatus` enum('N','Y') NOT NULL DEFAULT 'N',
  `bonus` decimal(11,2) NOT NULL DEFAULT 0.00,
  `user_get` varchar(255) NOT NULL DEFAULT '',
  `today_pro` int(11) NOT NULL DEFAULT 0,
  `prochek_date` timestamp NULL DEFAULT NULL,
  `procheck_user` varchar(255) NOT NULL DEFAULT '',
  `member_topup` int(11) NOT NULL DEFAULT 0,
  `emp_topup` int(11) NOT NULL DEFAULT 0,
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `date_approve` timestamp NULL DEFAULT NULL,
  `date_cancel` timestamp NULL DEFAULT NULL,
  `remark_admin` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `create_by` varchar(16) DEFAULT NULL,
  `autocheck` varchar(1) NOT NULL DEFAULT 'N',
  `amount` decimal(11,2) DEFAULT NULL,
  `topup_by` varchar(191) DEFAULT NULL,
  `ip_topup` varchar(100) NOT NULL,
  `deposit_status` varchar(20) NOT NULL DEFAULT 'NEW',
  `deposit_started_at` datetime DEFAULT NULL,
  `deposited_at` datetime DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `deposit_attempt` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_last_error` text DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `tx_hash` (`tx_hash`),
  UNIQUE KEY `bank_payment_account_code_tx_hash_index` (`account_code`,`tx_hash`) USING BTREE,
  KEY `bankcheck` (`value`,`date_create`) USING BTREE,
  KEY `txid` (`txid`),
  KEY `idx_bp_acc_month_time` (`account_code`,`bank_time`,`code`),
  KEY `idx_bp_date_create` (`date_create`),
  KEY `idx_bp_bank_acct_status_enable` (`bankname`(32),`account_code`,`status`,`enable`),
  KEY `idx_bp_channel` (`channel`(64)),
  KEY `bank_payment_deposit_status_index` (`deposit_status`),
  KEY `idx_bp_date_status_enable` (`date_create`,`status`,`enable`),
  KEY `idx_bp_member_status_enable_date` (`member_topup`,`status`,`enable`,`date_create`),
  KEY `idx_bp_status_enable_date` (`status`,`enable`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bankout_config`
--

DROP TABLE IF EXISTS `bankout_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bankout_config` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `acc_name` varchar(255) NOT NULL DEFAULT '',
  `acc_no` varchar(100) NOT NULL,
  `banks` int(11) NOT NULL,
  `sort` int(11) NOT NULL,
  `date_start` date NOT NULL DEFAULT '0000-00-00',
  `date_stop` date NOT NULL DEFAULT '0000-00-00',
  `enable` enum('Y','N') DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `shortcode` varchar(100) NOT NULL DEFAULT '',
  `bg_color` varchar(20) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `status_auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `website` mediumtext NOT NULL,
  `filepic` varchar(100) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `show_regis` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=314 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banks_account`
--

DROP TABLE IF EXISTS `banks_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks_account` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `date_start` date NOT NULL,
  `time_start` time NOT NULL,
  `date_end` date DEFAULT NULL,
  `time_end` time DEFAULT NULL,
  `start_at` datetime GENERATED ALWAYS AS (str_to_date(concat(`date_start`,' ',`time_start`),'%Y-%m-%d %H:%i:%s')) STORED,
  `end_at` datetime GENERATED ALWAYS AS (case when `date_end` is null or `time_end` is null then NULL else str_to_date(concat(`date_end`,' ',`time_end`),'%Y-%m-%d %H:%i:%s') end) STORED,
  `bank_code` int(10) unsigned DEFAULT NULL,
  `acc_name` varchar(255) NOT NULL DEFAULT '',
  `acc_no` varchar(100) NOT NULL DEFAULT '',
  `banks` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `user_pass` varchar(100) NOT NULL DEFAULT '',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status_auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `status_topup` enum('Y','N') NOT NULL DEFAULT 'N',
  `bank_type` int(11) NOT NULL DEFAULT 0 COMMENT '1=BankIn,2=BankOut',
  `smestatus` enum('N','Y') NOT NULL DEFAULT 'Y',
  `device_id` longtext DEFAULT NULL,
  `api_refresh` varchar(150) NOT NULL,
  `checktime` timestamp NULL DEFAULT NULL,
  `display_wallet` enum('Y','N') NOT NULL DEFAULT 'N',
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `local` enum('Y','N') NOT NULL DEFAULT 'Y',
  `pattern` enum('G','O') NOT NULL DEFAULT 'G',
  `website` varchar(191) NOT NULL DEFAULT 'http://sv2.168gametech.com',
  `webhook` enum('Y','N') NOT NULL DEFAULT 'N',
  `auto_transfer` enum('Y','N') NOT NULL DEFAULT 'N',
  `min_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qrcode` enum('Y','N') NOT NULL DEFAULT 'N',
  `filepic` varchar(100) NOT NULL,
  `pompay_default` enum('Y','N') NOT NULL DEFAULT 'N',
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rate_auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `payment` enum('Y','N') NOT NULL DEFAULT 'N',
  `slip` enum('Y','N') NOT NULL DEFAULT 'N',
  `expired_date` timestamp NULL DEFAULT NULL,
  `deposit_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus_max` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remark` text DEFAULT NULL,
  `visibility_scope` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `banks` (`banks`) USING BTREE,
  KEY `banks_account_date_start_time_start_index` (`date_start`,`time_start`),
  KEY `banks_account_date_end_time_end_index` (`date_end`,`time_end`),
  KEY `banks_account_start_at_index` (`start_at`),
  KEY `banks_account_end_at_index` (`end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banks_configs`
--

DROP TABLE IF EXISTS `banks_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks_configs` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `acc_name` varchar(255) NOT NULL DEFAULT '',
  `acc_no` varchar(100) NOT NULL,
  `banks` int(11) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `fee` enum('Y','N') NOT NULL DEFAULT 'Y',
  `auto` enum('Y','N') NOT NULL,
  `date_start` date NOT NULL DEFAULT '0000-00-00',
  `date_stop` date NOT NULL DEFAULT '0000-00-00',
  `enable` enum('Y','N') DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banks_rule`
--

DROP TABLE IF EXISTS `banks_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks_rule` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `types` enum('IF','IFNOT') NOT NULL DEFAULT 'IF',
  `bank_code` int(11) DEFAULT NULL,
  `method` enum('CAN','CANNOT') NOT NULL DEFAULT 'CAN',
  `bank_number` mediumtext NOT NULL,
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `batch_user`
--

DROP TABLE IF EXISTS `batch_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `batch_user` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `game_id` varchar(50) NOT NULL DEFAULT '',
  `prefix` varchar(10) NOT NULL DEFAULT '',
  `batch_start` int(11) NOT NULL DEFAULT 0,
  `batch_stop` int(11) NOT NULL DEFAULT 0,
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `ref_id` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `pro_name` varchar(100) DEFAULT NULL,
  `transfer_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=W-G,2=G-W,3=B',
  `amount` decimal(11,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `credit_bonus` decimal(11,2) NOT NULL,
  `credit_before` decimal(11,2) NOT NULL,
  `credit_after` decimal(11,2) NOT NULL,
  `credit_balance` decimal(11,2) NOT NULL,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `amount_request` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `method` varchar(50) DEFAULT NULL,
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `refer_table` varchar(100) DEFAULT NULL,
  `complete` enum('Y','N','R') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE,
  KEY `emp_code` (`emp_code`) USING BTREE,
  KEY `game_code` (`game_code`) USING BTREE,
  KEY `pro_code` (`pro_code`) USING BTREE,
  KEY `idx_bills_enable_transfer_date_pro` (`enable`,`transfer_type`,`date_create`,`pro_code`),
  KEY `idx_bills_enable_date` (`enable`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bills_free`
--

DROP TABLE IF EXISTS `bills_free`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills_free` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `ref_id` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `transfer_type` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1=W-G,2=G-W,3=B',
  `amount` decimal(11,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `credit_bonus` decimal(11,2) NOT NULL,
  `credit_before` decimal(11,2) NOT NULL,
  `credit_after` decimal(11,2) NOT NULL,
  `credit_balance` decimal(11,2) NOT NULL,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE,
  KEY `emp_code` (`emp_code`) USING BTREE,
  KEY `game_code` (`game_code`) USING BTREE,
  KEY `pro_code` (`pro_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus`
--

DROP TABLE IF EXISTS `bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonus` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `refer_coupon` int(11) NOT NULL DEFAULT 0,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'Y',
  `name` varchar(100) NOT NULL,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Y','N') NOT NULL DEFAULT 'N',
  `user_create` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `date_expire` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus_spin`
--

DROP TABLE IF EXISTS `bonus_spin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonus_spin` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `bonus_name` varchar(100) NOT NULL DEFAULT '',
  `credit` decimal(10,2) NOT NULL,
  `credit_before` decimal(11,2) NOT NULL,
  `credit_after` decimal(11,2) NOT NULL,
  `diamond_balance` decimal(10,2) NOT NULL,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `reward_type` varchar(10) NOT NULL DEFAULT 'WALLET',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `broadcast_sessions`
--

DROP TABLE IF EXISTS `broadcast_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `broadcast_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `broadcast_sessions_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `check_case`
--

DROP TABLE IF EXISTS `check_case`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `check_case` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_code` int(10) unsigned DEFAULT NULL,
  `txid` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payamount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(100) NOT NULL,
  `detail` varchar(100) NOT NULL,
  `url` varchar(100) NOT NULL,
  `qrcode` longtext DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `expired_date` timestamp NULL DEFAULT NULL,
  `method` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1=Deposit, 2=Withdraw',
  `bankAccountNumber` varchar(10) DEFAULT NULL,
  `bankAccountName` varchar(50) DEFAULT NULL,
  `bankName` varchar(10) DEFAULT NULL,
  `promptpayNumber` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `checkins`
--

DROP TABLE IF EXISTS `checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkins` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `date_start` date NOT NULL,
  `date_stop` date NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `client_mode_events`
--

DROP TABLE IF EXISTS `client_mode_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_mode_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `mode` varchar(16) NOT NULL,
  `name` varchar(64) NOT NULL,
  `props` longtext DEFAULT NULL CHECK (json_valid(`props`)),
  `client_id` varchar(64) DEFAULT NULL,
  `ua` varchar(191) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_mode_events_user_id_mode_created_at_index` (`user_id`,`mode`,`created_at`),
  KEY `client_mode_events_name_index` (`name`),
  KEY `client_mode_events_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `client_presence`
--

DROP TABLE IF EXISTS `client_presence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_presence` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` varchar(64) NOT NULL,
  `mode` varchar(16) NOT NULL,
  `display_mode` varchar(32) DEFAULT NULL,
  `sw` tinyint(1) NOT NULL DEFAULT 0,
  `ua` varchar(191) DEFAULT NULL,
  `last_path` varchar(191) DEFAULT NULL,
  `first_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_presence_user_id_client_id_unique` (`user_id`,`client_id`),
  KEY `client_presence_user_id_mode_index` (`user_id`,`mode`),
  KEY `client_presence_last_seen_at_index` (`last_seen_at`),
  KEY `client_presence_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configs`
--

DROP TABLE IF EXISTS `configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `configs` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(255) NOT NULL DEFAULT '',
  `name_en` varchar(255) NOT NULL DEFAULT '',
  `lineid` varchar(100) NOT NULL DEFAULT '',
  `linelink` varchar(255) NOT NULL DEFAULT '',
  `minwithdraw` decimal(10,2) NOT NULL,
  `maxwithdraw_day` decimal(10,2) NOT NULL,
  `maxtransfer_time` decimal(10,2) NOT NULL,
  `mintransfer` decimal(10,2) NOT NULL,
  `mintransferback` decimal(10,2) NOT NULL,
  `maxsetcredit` decimal(10,2) NOT NULL,
  `free_mintransfer` decimal(10,2) NOT NULL,
  `free_maxtransfer` decimal(10,2) NOT NULL,
  `free_maxout` decimal(10,2) NOT NULL,
  `free_minwithdraw` decimal(10,2) NOT NULL,
  `free_maxwithdraw` decimal(10,2) NOT NULL,
  `onoff` enum('Y','N') NOT NULL DEFAULT 'Y',
  `pro_onoff` enum('Y','N') NOT NULL DEFAULT 'Y',
  `website` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `notice` varchar(191) DEFAULT NULL,
  `mintransfer_pro` decimal(10,2) DEFAULT 0.00,
  `pro_wallet` enum('Y','N') NOT NULL DEFAULT 'Y',
  `reward_open` enum('Y','N') DEFAULT 'N',
  `point_open` enum('Y','N') DEFAULT 'Y',
  `diamond_open` enum('Y','N') DEFAULT 'Y',
  `points` decimal(10,2) DEFAULT 0.00,
  `diamonds` decimal(10,2) DEFAULT 0.00,
  `logo` varchar(100) NOT NULL DEFAULT 'logo.png',
  `favicon` varchar(100) NOT NULL DEFAULT 'favicon.png',
  `title` varchar(191) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `multigame_open` enum('Y','N') NOT NULL DEFAULT 'Y',
  `freecredit_open` enum('Y','N') NOT NULL DEFAULT 'Y',
  `freecredit_all` enum('Y','N') NOT NULL DEFAULT 'Y',
  `free_mintransferback` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sitename` varchar(100) NOT NULL DEFAULT '',
  `admin_navbar_color` varchar(100) NOT NULL DEFAULT 'navbar-white navbar-light',
  `admin_brand_color` varchar(100) NOT NULL DEFAULT 'navbar-gray-dark',
  `admin_darkmode_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `wallet_navbar_color` varchar(100) NOT NULL DEFAULT '#6f0000',
  `wallet_body_stop_color` varchar(100) NOT NULL DEFAULT '#6f0000',
  `wallet_body_start_color` varchar(100) NOT NULL DEFAULT '#200122',
  `wallet_footer_color` varchar(100) NOT NULL DEFAULT '#6f0000',
  `wallet_footer_active` varchar(191) NOT NULL DEFAULT '#ffc937',
  `wallet_footer_exchange` varchar(191) NOT NULL DEFAULT '#6f0000',
  `maxspin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diamond_per_bill` enum('Y','N') NOT NULL DEFAULT 'N',
  `diamonds_topup` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diamonds_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `verify_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `verify_sms` enum('Y','N') NOT NULL DEFAULT 'N',
  `sms_provider` tinyint(1) DEFAULT NULL,
  `sms_username` varchar(50) NOT NULL,
  `sms_password` varchar(50) NOT NULL,
  `cashback_time` char(5) NOT NULL DEFAULT '00:30',
  `ic_time` char(5) NOT NULL DEFAULT '00:50',
  `customer_inform` enum('Y','N') NOT NULL DEFAULT 'N',
  `contributor` varchar(191) NOT NULL,
  `wheel_open` enum('Y','N') NOT NULL DEFAULT 'Y',
  `diamond_in_game` enum('Y','N') NOT NULL DEFAULT 'N',
  `header_code` mediumtext DEFAULT NULL,
  `seamless` enum('Y','N') NOT NULL DEFAULT 'N',
  `pro_reset` decimal(10,2) NOT NULL,
  `auto_wallet` enum('Y','N') NOT NULL DEFAULT 'Y',
  `onegame` enum('Y','N') NOT NULL DEFAULT 'N',
  `money_tran_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `sms_token` mediumtext NOT NULL,
  `wallet_withdraw_all` enum('Y','N') NOT NULL DEFAULT 'N',
  `content` longtext NOT NULL,
  `content_header` varchar(191) NOT NULL,
  `content_detail` mediumtext NOT NULL,
  `luckypay` enum('Y','N') NOT NULL DEFAULT 'N',
  `papayapay` enum('Y','N') NOT NULL DEFAULT 'N',
  `pompay` enum('Y','N') NOT NULL DEFAULT 'N',
  `hengpay` enum('Y','N') NOT NULL DEFAULT 'N',
  `deposit_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `point_per_bill` enum('Y','N') NOT NULL DEFAULT 'N',
  `points_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `points_topup` decimal(10,2) NOT NULL DEFAULT 0.00,
  `superrich` enum('Y','N') NOT NULL DEFAULT 'N',
  `qrscan` enum('Y','N') NOT NULL DEFAULT 'N',
  `withdraw_status` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact_channels`
--

DROP TABLE IF EXISTS `contact_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_channels` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('line','telegram','facebook','whatsapp') NOT NULL,
  `label` varchar(191) NOT NULL,
  `link` varchar(191) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cornjobs`
--

DROP TABLE IF EXISTS `cornjobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cornjobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `description` varchar(150) NOT NULL,
  `status` varchar(1) NOT NULL DEFAULT 'N',
  `update_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(110) NOT NULL,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'N',
  `amount` int(11) NOT NULL DEFAULT 0,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_start` timestamp NULL DEFAULT NULL,
  `date_stop` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `gen` enum('Y','N') NOT NULL DEFAULT 'N',
  `money` decimal(10,2) NOT NULL DEFAULT 0.00,
  `same_coupon` enum('Y','N') NOT NULL DEFAULT 'N',
  `refill_start` timestamp NULL DEFAULT NULL,
  `refill_stop` timestamp NULL DEFAULT NULL,
  `date_expire` tinyint(4) NOT NULL DEFAULT 0,
  `norefill` enum('Y','N') NOT NULL DEFAULT 'N',
  `newuser` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `coupons_list`
--

DROP TABLE IF EXISTS `coupons_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons_list` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_code` int(11) NOT NULL,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(20) NOT NULL,
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` int(11) NOT NULL DEFAULT 0,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `money` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'N',
  `status` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `date_start` timestamp NULL DEFAULT NULL,
  `date_stop` timestamp NULL DEFAULT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_expire` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `daily_stat`
--

DROP TABLE IF EXISTS `daily_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_stat` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `member_all` int(11) NOT NULL DEFAULT 0,
  `member_new` int(11) NOT NULL DEFAULT 0,
  `member_new_refill` int(11) NOT NULL DEFAULT 0,
  `member_all_refill` int(11) NOT NULL DEFAULT 0,
  `deposit_count` int(11) NOT NULL DEFAULT 0,
  `deposit_sum` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_count` int(11) NOT NULL DEFAULT 0,
  `withdraw_sum` decimal(10,2) NOT NULL DEFAULT 0.00,
  `member_new_list` longtext DEFAULT NULL CHECK (json_valid(`member_new_list`)),
  `member_new_refill_list` longtext DEFAULT NULL CHECK (json_valid(`member_new_refill_list`)),
  `setwallet_d_sum` decimal(10,2) NOT NULL DEFAULT 0.00,
  `setwallet_w_sum` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bonus_sum` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dashboard_summary_daily`
--

DROP TABLE IF EXISTS `dashboard_summary_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_summary_daily` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `summary_date` date NOT NULL,
  `web_code` varchar(64) NOT NULL DEFAULT 'panter918',
  `register_total` int(10) unsigned NOT NULL DEFAULT 0,
  `register_direct` int(10) unsigned NOT NULL DEFAULT 0,
  `register_referral` int(10) unsigned NOT NULL DEFAULT 0,
  `register_campaign` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit_total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_total_users` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_success_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit_success_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_success_users` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_pending_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit_pending_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_pending_users` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_reject_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit_reject_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_reject_users` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_deleted_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `deposit_deleted_count` int(10) unsigned NOT NULL DEFAULT 0,
  `deposit_deleted_users` int(10) unsigned NOT NULL DEFAULT 0,
  `withdraw_total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `withdraw_total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `withdraw_total_users` int(10) unsigned NOT NULL DEFAULT 0,
  `withdraw_pending_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `withdraw_pending_count` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus_deposit_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bonus_deposit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus_activity_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bonus_activity_count` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus_manual_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bonus_manual_count` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus_total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bonus_total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `net_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `register_deposit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `register_referral_deposit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `first_deposit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `repeat_deposit_count` int(10) unsigned NOT NULL DEFAULT 0,
  `register_confirmed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `staff_add_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `staff_reduce_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `staff_adjust_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `metric_version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dashboard_summary_daily_date_web` (`summary_date`,`web_code`),
  KEY `idx_dashboard_summary_daily_web_date` (`web_code`,`summary_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `domain_expiries`
--

DROP TABLE IF EXISTS `domain_expiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `domain_expiries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(16) NOT NULL DEFAULT 'public',
  `domain` varchar(255) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `source` varchar(16) DEFAULT NULL,
  `raw` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_expiries_role_domain_unique` (`role`,`domain`),
  KEY `domain_expiries_expires_checked_index` (`expires_at`,`checked_at`),
  KEY `domain_expiries_role_index` (`role`),
  KEY `domain_expiries_expires_at_index` (`expires_at`),
  KEY `domain_expiries_checked_at_index` (`checked_at`),
  KEY `domain_expiries_status_index` (`status`),
  KEY `domain_expiries_source_index` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `department_code` int(11) NOT NULL DEFAULT 0,
  `position_code` int(11) NOT NULL DEFAULT 0,
  `task_code` int(11) NOT NULL DEFAULT 0,
  `id` varchar(20) NOT NULL DEFAULT '',
  `authen_id` varchar(255) NOT NULL DEFAULT '',
  `prefix_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL DEFAULT '',
  `surname` varchar(100) NOT NULL DEFAULT '',
  `nickname` varchar(100) NOT NULL DEFAULT '',
  `birthday` varchar(2) NOT NULL DEFAULT '00',
  `birthmonth` varchar(2) NOT NULL DEFAULT '00',
  `birthyear` varchar(4) NOT NULL DEFAULT '0000',
  `tel` varchar(30) NOT NULL DEFAULT '',
  `mobile` varchar(30) NOT NULL DEFAULT '',
  `address` text NOT NULL,
  `province_code` int(11) NOT NULL DEFAULT 0,
  `zipcode` varchar(5) NOT NULL DEFAULT '',
  `email` varchar(200) NOT NULL DEFAULT '',
  `user_prefix1` varchar(1) NOT NULL DEFAULT '',
  `user_prefix2` varchar(1) NOT NULL DEFAULT '',
  `user_name` varchar(30) NOT NULL DEFAULT '',
  `user_pass` varchar(100) NOT NULL DEFAULT '',
  `user_passdel` varchar(255) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT 0,
  `filepic` varchar(100) DEFAULT '',
  `min_money` decimal(10,2) NOT NULL,
  `max_money` decimal(10,2) DEFAULT NULL,
  `credit` decimal(10,2) NOT NULL,
  `credit_balance` decimal(10,2) NOT NULL,
  `percent` int(11) NOT NULL DEFAULT 0,
  `fight` int(11) NOT NULL DEFAULT 0,
  `superadmin` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `login_session` mediumtext NOT NULL,
  `lastlogin` timestamp NULL DEFAULT NULL,
  `google2fa_secret` varchar(191) DEFAULT NULL,
  `google2fa_enable` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(191) DEFAULT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) DEFAULT NULL,
  `connection` mediumtext NOT NULL,
  `queue` mediumtext NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_requests`
--

DROP TABLE IF EXISTS `failed_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trace_id` varchar(64) NOT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `method` varchar(16) DEFAULT NULL,
  `headers` longtext DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `status` varchar(8) DEFAULT NULL,
  `response` longtext DEFAULT NULL,
  `duration` decimal(8,3) DEFAULT NULL,
  `txid` longtext DEFAULT NULL,
  `roundId` longtext DEFAULT NULL,
  `company` varchar(64) DEFAULT NULL,
  `game_user` varchar(128) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_requests_trace_id_unique` (`trace_id`),
  KEY `failed_requests_created_at_index` (`created_at`),
  KEY `failed_requests_status_index` (`status`),
  KEY `failed_requests_company_index` (`company`),
  KEY `failed_requests_game_user_index` (`game_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `question` mediumtext NOT NULL,
  `answer` longtext NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(100) NOT NULL DEFAULT '',
  `game_type` varchar(10) NOT NULL DEFAULT '',
  `user_demo` varchar(100) NOT NULL DEFAULT '',
  `user_demofree` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `filepic` varchar(255) NOT NULL DEFAULT '',
  `link_ios` mediumtext NOT NULL,
  `link_android` mediumtext NOT NULL,
  `link_web` mediumtext NOT NULL,
  `batch_game` enum('Y','N') NOT NULL DEFAULT 'N',
  `auto_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `sort` int(11) NOT NULL DEFAULT 0,
  `status_open` enum('Y','N') NOT NULL DEFAULT 'Y',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'Y',
  `autologin` enum('Y','N') NOT NULL DEFAULT 'N',
  `gamelist` enum('Y','N') NOT NULL DEFAULT 'N',
  `onegame` enum('Y','N') NOT NULL DEFAULT 'N',
  `newuser` enum('Y','N') NOT NULL DEFAULT 'Y',
  `token` mediumtext DEFAULT NULL,
  `token_expired` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_content`
--

DROP TABLE IF EXISTS `games_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_content` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(20) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `filepic` varchar(50) NOT NULL,
  `content` varchar(255) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_creditlog`
--

DROP TABLE IF EXISTS `games_creditlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_creditlog` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `member_user` varchar(100) NOT NULL DEFAULT '',
  `type_record` tinyint(1) NOT NULL,
  `before_credit` varchar(255) NOT NULL DEFAULT '',
  `amount` decimal(11,2) NOT NULL,
  `after_credit` varchar(255) NOT NULL DEFAULT '',
  `game_code` int(11) NOT NULL DEFAULT 0,
  `gamebalance` decimal(11,2) NOT NULL,
  `username` varchar(100) NOT NULL,
  `remark` varchar(200) NOT NULL,
  `bonus` varchar(100) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(200) NOT NULL,
  `user_update` varchar(200) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_seamless`
--

DROP TABLE IF EXISTS `games_seamless`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_seamless` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(20) NOT NULL,
  `game_type` varchar(10) NOT NULL,
  `method` varchar(10) NOT NULL DEFAULT 'seamless',
  `name` varchar(100) NOT NULL DEFAULT '',
  `filepic` varchar(255) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `icon` varchar(255) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_single`
--

DROP TABLE IF EXISTS `games_single`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_single` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(20) NOT NULL,
  `game_type` varchar(10) NOT NULL,
  `method` varchar(10) NOT NULL DEFAULT 'seamless',
  `name` varchar(100) NOT NULL DEFAULT '',
  `filepic` varchar(255) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `cashback` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `icon` varchar(255) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_type`
--

DROP TABLE IF EXISTS `games_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_type` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(20) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status_open` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `filepic` varchar(50) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `content` varchar(255) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_user`
--

DROP TABLE IF EXISTS `games_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_user` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `balance` decimal(10,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `user_name` (`user_name`),
  KEY `game_code` (`game_code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_user_event`
--

DROP TABLE IF EXISTS `games_user_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_user_event` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL,
  `user_pass` varchar(100) NOT NULL,
  `method` varchar(15) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `complete` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games_user_free`
--

DROP TABLE IF EXISTS `games_user_free`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_user_free` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `balance` decimal(10,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `withdraw_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `game_code` (`game_code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_has_permissions`
--

DROP TABLE IF EXISTS `group_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_has_permissions` (
  `group_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_accounts`
--

DROP TABLE IF EXISTS `line_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `channel_id` varchar(191) DEFAULT NULL,
  `channel_secret` varchar(191) DEFAULT NULL,
  `access_token` mediumtext DEFAULT NULL,
  `webhook_token` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'active',
  `remark` mediumtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `line_accounts_webhook_token_unique` (`webhook_token`),
  KEY `line_accounts_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_contacts`
--

DROP TABLE IF EXISTS `line_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_account_id` bigint(20) unsigned NOT NULL,
  `line_user_id` varchar(191) NOT NULL,
  `display_name` varchar(191) DEFAULT NULL,
  `preferred_language` varchar(20) DEFAULT NULL,
  `last_detected_language` varchar(20) DEFAULT NULL,
  `picture_url` varchar(191) DEFAULT NULL,
  `status_message` varchar(191) DEFAULT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `member_username` varchar(50) DEFAULT NULL,
  `member_mobile` varchar(20) DEFAULT NULL,
  `tags` longtext DEFAULT NULL CHECK (json_valid(`tags`)),
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_contacts_line_account_id_line_user_id_index` (`line_account_id`,`line_user_id`),
  KEY `line_contacts_line_user_id_index` (`line_user_id`),
  KEY `line_contacts_member_id_index` (`member_id`),
  KEY `line_contacts_member_username_index` (`member_username`),
  KEY `line_contacts_member_mobile_index` (`member_mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_conversation_notes`
--

DROP TABLE IF EXISTS `line_conversation_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_conversation_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_conversation_id` bigint(20) unsigned NOT NULL,
  `line_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_contact_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_conversation_notes_line_conversation_id_index` (`line_conversation_id`),
  KEY `line_conversation_notes_line_account_id_index` (`line_account_id`),
  KEY `line_conversation_notes_line_contact_id_index` (`line_contact_id`),
  KEY `line_conversation_notes_employee_id_index` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_conversations`
--

DROP TABLE IF EXISTS `line_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_account_id` bigint(20) unsigned NOT NULL,
  `line_contact_id` bigint(20) unsigned NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'open',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `outgoing_language` varchar(20) DEFAULT NULL,
  `incoming_language` varchar(20) DEFAULT NULL,
  `last_message_preview` text DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `unread_count` int(10) unsigned NOT NULL DEFAULT 0,
  `assigned_employee_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_employee_name` varchar(191) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `closed_by_employee_id` bigint(20) unsigned DEFAULT NULL,
  `closed_by_employee_name` varchar(191) DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `locked_by_employee_id` bigint(20) unsigned DEFAULT NULL,
  `locked_by_employee_name` varchar(191) DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_conversations_line_account_id_line_contact_id_index` (`line_account_id`,`line_contact_id`),
  KEY `line_conversations_status_last_message_at_index` (`status`,`last_message_at`),
  KEY `line_conversations_assigned_employee_id_index` (`assigned_employee_id`),
  KEY `line_conversations_locked_by_employee_id_index` (`locked_by_employee_id`),
  KEY `line_conversations_is_pinned_index` (`is_pinned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_messages`
--

DROP TABLE IF EXISTS `line_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_conversation_id` bigint(20) unsigned NOT NULL,
  `line_account_id` bigint(20) unsigned NOT NULL,
  `line_contact_id` bigint(20) unsigned NOT NULL,
  `direction` varchar(20) NOT NULL DEFAULT 'inbound',
  `source` varchar(20) NOT NULL DEFAULT 'user',
  `type` varchar(50) NOT NULL DEFAULT 'text',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `line_message_id` varchar(191) DEFAULT NULL,
  `text` text DEFAULT NULL,
  `payload` longtext DEFAULT NULL CHECK (json_valid(`payload`)),
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `sender_employee_id` bigint(20) unsigned DEFAULT NULL,
  `sender_bot_key` varchar(100) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_messages_line_conversation_id_index` (`line_conversation_id`),
  KEY `line_messages_line_account_id_index` (`line_account_id`),
  KEY `line_messages_line_contact_id_index` (`line_contact_id`),
  KEY `line_messages_line_message_id_index` (`line_message_id`),
  KEY `line_messages_sender_employee_id_index` (`sender_employee_id`),
  KEY `line_messages_sender_bot_key_index` (`sender_bot_key`),
  KEY `line_messages_sent_at_index` (`sent_at`),
  KEY `line_messages_is_pinned_index` (`is_pinned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_register_sessions`
--

DROP TABLE IF EXISTS `line_register_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_register_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_contact_id` bigint(20) unsigned NOT NULL,
  `line_conversation_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'in_progress',
  `current_step` varchar(50) DEFAULT NULL,
  `data` longtext DEFAULT NULL CHECK (json_valid(`data`)),
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `error_message` mediumtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_register_sessions_status_current_step_index` (`status`,`current_step`),
  KEY `line_register_sessions_line_contact_id_index` (`line_contact_id`),
  KEY `line_register_sessions_line_conversation_id_index` (`line_conversation_id`),
  KEY `line_register_sessions_status_index` (`status`),
  KEY `line_register_sessions_member_id_index` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_templates`
--

DROP TABLE IF EXISTS `line_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `key` varchar(100) NOT NULL,
  `message_type` varchar(10) NOT NULL DEFAULT 'text',
  `message` longtext NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `line_templates_key_unique` (`key`),
  KEY `line_templates_category_index` (`category`),
  KEY `line_templates_message_type_index` (`message_type`),
  KEY `line_templates_enabled_index` (`enabled`),
  KEY `line_templates_created_by_index` (`created_by`),
  KEY `line_templates_updated_by_index` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `line_webhook_logs`
--

DROP TABLE IF EXISTS `line_webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `line_webhook_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_conversation_id` bigint(20) unsigned NOT NULL,
  `line_contact_id` bigint(20) unsigned NOT NULL,
  `line_message_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `event_id` varchar(100) DEFAULT NULL,
  `request_id` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(191) DEFAULT NULL,
  `headers` longtext DEFAULT NULL CHECK (json_valid(`headers`)),
  `body` longtext DEFAULT NULL,
  `http_status` smallint(5) unsigned DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `line_webhook_logs_event_type_created_at_index` (`event_type`,`created_at`),
  KEY `line_webhook_logs_line_account_id_index` (`line_account_id`),
  KEY `line_webhook_logs_event_type_index` (`event_type`),
  KEY `line_webhook_logs_event_id_index` (`event_id`),
  KEY `line_webhook_logs_request_id_index` (`request_id`),
  KEY `line_webhook_logs_is_processed_index` (`is_processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logger_admin_activity`
--

DROP TABLE IF EXISTS `logger_admin_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logger_admin_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` longtext NOT NULL,
  `details` longtext DEFAULT NULL,
  `userType` varchar(191) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `route` longtext DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` mediumtext DEFAULT NULL,
  `locale` varchar(191) DEFAULT NULL,
  `referer` longtext DEFAULT NULL,
  `methodType` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logger_user_activity`
--

DROP TABLE IF EXISTS `logger_user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logger_user_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` longtext NOT NULL,
  `details` longtext DEFAULT NULL,
  `userType` varchar(191) NOT NULL,
  `userId` int(11) DEFAULT NULL,
  `route` longtext DEFAULT NULL,
  `ipAddress` varchar(45) DEFAULT NULL,
  `userAgent` mediumtext DEFAULT NULL,
  `locale` varchar(191) DEFAULT NULL,
  `referer` longtext DEFAULT NULL,
  `methodType` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `code` bigint(11) NOT NULL AUTO_INCREMENT,
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(100) NOT NULL DEFAULT '',
  `record` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `item_before` longtext NOT NULL,
  `item` longtext NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs_archive`
--

DROP TABLE IF EXISTS `logs_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_archive` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs_backup`
--

DROP TABLE IF EXISTS `logs_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_backup` (
  `code` bigint(11) NOT NULL AUTO_INCREMENT,
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(100) NOT NULL DEFAULT '',
  `record` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `item_before` longtext NOT NULL,
  `item` longtext NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs_type`
--

DROP TABLE IF EXISTS `logs_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_type` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(20) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `code` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_draw_bet_settings`
--

DROP TABLE IF EXISTS `lotto_draw_bet_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_draw_bet_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draw_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `min_bet` decimal(10,2) NOT NULL,
  `max_bet` decimal(12,2) NOT NULL,
  `max_per_number` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_draw_bet_settings_draw_id_bet_type_unique` (`draw_id`,`bet_type`),
  CONSTRAINT `lotto_draw_bet_settings_draw_id_foreign` FOREIGN KEY (`draw_id`) REFERENCES `lotto_draws` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_draws`
--

DROP TABLE IF EXISTS `lotto_draws`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_draws` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `market_id` bigint(20) unsigned NOT NULL,
  `draw_date` date NOT NULL,
  `open_at` datetime NOT NULL,
  `close_at` datetime NOT NULL,
  `result_at` datetime DEFAULT NULL,
  `status` enum('draft','open','closed','resulted') NOT NULL DEFAULT 'draft',
  `result_number` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_number`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lotto_draws_market_id_draw_date_index` (`market_id`,`draw_date`),
  KEY `lotto_draws_status_index` (`status`),
  CONSTRAINT `lotto_draws_market_id_foreign` FOREIGN KEY (`market_id`) REFERENCES `lotto_markets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_groups`
--

DROP TABLE IF EXISTS `lotto_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_groups_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_market_bet_settings`
--

DROP TABLE IF EXISTS `lotto_market_bet_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_market_bet_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `market_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `min_bet` decimal(10,2) NOT NULL DEFAULT 1.00,
  `max_bet` decimal(12,2) NOT NULL DEFAULT 100000.00,
  `max_per_number` decimal(12,2) NOT NULL DEFAULT 1000000.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_market_bet_settings_market_id_bet_type_unique` (`market_id`,`bet_type`),
  CONSTRAINT `lotto_market_bet_settings_market_id_foreign` FOREIGN KEY (`market_id`) REFERENCES `lotto_markets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_markets`
--

DROP TABLE IF EXISTS `lotto_markets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_markets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_markets_code_unique` (`code`),
  KEY `lotto_markets_group_id_foreign` (`group_id`),
  CONSTRAINT `lotto_markets_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `lotto_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_number_blocks`
--

DROP TABLE IF EXISTS `lotto_number_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_number_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draw_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `number` varchar(191) NOT NULL,
  `mode` enum('block','limit_future') NOT NULL DEFAULT 'block',
  `reason` text DEFAULT NULL,
  `blocked_by` bigint(20) unsigned DEFAULT NULL,
  `blocked_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_number_blocks_draw_id_bet_type_number_unique` (`draw_id`,`bet_type`,`number`),
  KEY `lotto_number_blocks_draw_id_blocked_at_index` (`draw_id`,`blocked_at`),
  CONSTRAINT `lotto_number_blocks_draw_id_foreign` FOREIGN KEY (`draw_id`) REFERENCES `lotto_draws` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_number_exposures`
--

DROP TABLE IF EXISTS `lotto_number_exposures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_number_exposures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `draw_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `number` varchar(191) NOT NULL,
  `sold_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_number_exposures_draw_id_bet_type_number_unique` (`draw_id`,`bet_type`,`number`),
  KEY `lotto_number_exposures_draw_id_bet_type_index` (`draw_id`,`bet_type`),
  CONSTRAINT `lotto_number_exposures_draw_id_foreign` FOREIGN KEY (`draw_id`) REFERENCES `lotto_draws` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_rate_plan_items`
--

DROP TABLE IF EXISTS `lotto_rate_plan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_rate_plan_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rate_plan_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `payout` decimal(8,2) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lotto_rate_plan_items_rate_plan_id_bet_type_unique` (`rate_plan_id`,`bet_type`),
  CONSTRAINT `lotto_rate_plan_items_rate_plan_id_foreign` FOREIGN KEY (`rate_plan_id`) REFERENCES `lotto_rate_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_rate_plans`
--

DROP TABLE IF EXISTS `lotto_rate_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_rate_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `lotto_rate_plans_group_id_foreign` (`group_id`),
  CONSTRAINT `lotto_rate_plans_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `lotto_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_ticket_items`
--

DROP TABLE IF EXISTS `lotto_ticket_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_ticket_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `bet_type` varchar(191) NOT NULL,
  `number` varchar(191) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payout_at_time` decimal(8,2) NOT NULL,
  `result_status` varchar(191) DEFAULT NULL,
  `win_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lotto_ticket_items_ticket_id_index` (`ticket_id`),
  CONSTRAINT `lotto_ticket_items_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `lotto_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lotto_tickets`
--

DROP TABLE IF EXISTS `lotto_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lotto_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `draw_id` bigint(20) unsigned NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('active','cancelled','resulted') NOT NULL DEFAULT 'active',
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `refund_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lotto_tickets_member_id_draw_id_index` (`member_id`,`draw_id`),
  KEY `lotto_tickets_draw_id_status_index` (`draw_id`,`status`),
  CONSTRAINT `lotto_tickets_draw_id_foreign` FOREIGN KEY (`draw_id`) REFERENCES `lotto_draws` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lotto_tickets_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marketing_campaigns`
--

DROP TABLE IF EXISTS `marketing_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `admin_username` mediumtext DEFAULT NULL,
  `is_ended` tinyint(1) NOT NULL DEFAULT 0,
  `ended_at` timestamp NULL DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `marketing_campaigns_team_id_foreign` (`team_id`),
  CONSTRAINT `marketing_campaigns_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `marketing_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marketing_teams`
--

DROP TABLE IF EXISTS `marketing_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `username` varchar(191) NOT NULL,
  `password_hash` varchar(191) NOT NULL,
  `bank_code` int(10) unsigned DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `bank_account_name` varchar(191) NOT NULL,
  `bank_account_no` varchar(191) NOT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketing_teams_username_unique` (`username`),
  KEY `marketing_teams_bank_code_foreign` (`bank_code`),
  CONSTRAINT `marketing_teams_bank_code_foreign` FOREIGN KEY (`bank_code`) REFERENCES `banks` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member_deposit_stats`
--

DROP TABLE IF EXISTS `member_deposit_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_deposit_stats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(10) unsigned NOT NULL,
  `deposit_success_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'จำนวนบิลฝากที่สำเร็จแล้ว',
  `deposit_success_sum` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'ยอดฝากสะสม (สำเร็จแล้วเท่านั้น)',
  `legacy_at` timestamp NULL DEFAULT NULL COMMENT 'ผ่านเงื่อนไขลูกค้าเก่าครั้งแรก',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_member_deposit_stats_member_code` (`member_code`),
  KEY `idx_member_deposit_stats_legacy_at` (`legacy_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member_lotto_permissions`
--

DROP TABLE IF EXISTS `member_lotto_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_lotto_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `group_id` bigint(20) unsigned DEFAULT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_lotto_permissions_member_id_group_id_unique` (`member_id`,`group_id`),
  KEY `member_lotto_permissions_group_id_foreign` (`group_id`),
  CONSTRAINT `member_lotto_permissions_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `lotto_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_lotto_permissions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member_lotto_settings`
--

DROP TABLE IF EXISTS `member_lotto_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_lotto_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(10) unsigned NOT NULL,
  `rate_plan_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_lotto_settings_member_id_unique` (`member_id`),
  KEY `member_lotto_settings_rate_plan_id_foreign` (`rate_plan_id`),
  CONSTRAINT `member_lotto_settings_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`code`) ON DELETE CASCADE,
  CONSTRAINT `member_lotto_settings_rate_plan_id_foreign` FOREIGN KEY (`rate_plan_id`) REFERENCES `lotto_rate_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `member_satang`
--

DROP TABLE IF EXISTS `member_satang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_satang` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `bank_code` int(11) NOT NULL,
  `shortcode` varchar(10) NOT NULL,
  `value` int(11) DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `memberbank` (`shortcode`,`bank_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `bank_code` int(11) NOT NULL DEFAULT 0,
  `upline_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(40) NOT NULL DEFAULT '',
  `firstname` varchar(20) NOT NULL DEFAULT '',
  `lastname` varchar(20) NOT NULL DEFAULT '',
  `name_addon` varchar(40) DEFAULT NULL,
  `firstname_addon` varchar(20) DEFAULT NULL,
  `lastname_addon` varchar(20) DEFAULT NULL,
  `user_name` varchar(15) NOT NULL DEFAULT '',
  `user_pass` varchar(15) NOT NULL DEFAULT '',
  `user_pin` varchar(6) NOT NULL DEFAULT '',
  `check_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `acc_no` varchar(20) NOT NULL DEFAULT '',
  `acc_check` varchar(100) NOT NULL DEFAULT '',
  `acc_bay` varchar(100) NOT NULL DEFAULT '',
  `acc_kbank` varchar(100) NOT NULL DEFAULT '',
  `tel` varchar(15) NOT NULL DEFAULT '',
  `birth_day` date NOT NULL DEFAULT '0000-00-00',
  `age` varchar(100) NOT NULL DEFAULT '',
  `lineid` varchar(15) NOT NULL DEFAULT '',
  `confirm` enum('N','Y') NOT NULL DEFAULT 'N',
  `refer` varchar(200) NOT NULL DEFAULT '',
  `point_deposit` decimal(10,2) NOT NULL,
  `count_deposit` int(11) NOT NULL DEFAULT 0,
  `diamond` decimal(10,2) NOT NULL,
  `upline` varchar(255) NOT NULL DEFAULT '',
  `credit` decimal(10,2) NOT NULL,
  `balance` decimal(11,2) NOT NULL,
  `balance_free` decimal(12,2) NOT NULL,
  `date_regis` date NOT NULL DEFAULT '0000-00-00',
  `pro` int(11) NOT NULL DEFAULT 0,
  `status_pro` int(1) NOT NULL DEFAULT 0,
  `acc_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `otp` varchar(200) NOT NULL DEFAULT '',
  `pic_id` varchar(200) NOT NULL DEFAULT '',
  `scode` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `lastlogin` timestamp NULL DEFAULT NULL,
  `remark` mediumtext NOT NULL,
  `sms_status` varchar(200) NOT NULL DEFAULT '',
  `promotion` enum('N','Y') NOT NULL DEFAULT 'N',
  `pro_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime2` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime3` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime4` enum('N','Y') DEFAULT 'N',
  `prefix` varchar(255) NOT NULL DEFAULT '',
  `gender` enum('M','F') NOT NULL DEFAULT 'M',
  `deposit` int(11) NOT NULL DEFAULT 0,
  `allget_downline` decimal(11,2) NOT NULL,
  `aff_get` enum('Y','N') NOT NULL DEFAULT 'N',
  `oldmember` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `user_delay` int(11) NOT NULL DEFAULT 0,
  `session_ip` varchar(200) NOT NULL DEFAULT '',
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `session_page` mediumtext NOT NULL DEFAULT '',
  `session_limit` timestamp NULL DEFAULT NULL,
  `payment_task` varchar(20) NOT NULL DEFAULT '',
  `payment_token` varchar(255) NOT NULL DEFAULT '',
  `payment_level` int(11) NOT NULL DEFAULT 0,
  `payment_game` int(11) NOT NULL DEFAULT 0,
  `payment_pro` int(11) NOT NULL DEFAULT 0,
  `payment_balance` decimal(11,2) NOT NULL,
  `payment_amount` decimal(11,2) NOT NULL,
  `payment_limit` timestamp NULL DEFAULT NULL,
  `payment_delay` timestamp NULL DEFAULT NULL,
  `payment_mac` varchar(255) NOT NULL DEFAULT '',
  `payment_browser` varchar(100) NOT NULL DEFAULT '',
  `payment_device` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `wallet_id` varchar(20) DEFAULT NULL,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(10,2) NOT NULL DEFAULT 0.00,
  `faststart` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maxwithdraw_day` decimal(10,2) NOT NULL DEFAULT 0.00,
  `game_user` varchar(40) DEFAULT NULL,
  `sum_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_withdraw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `nocashback` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_index` (`upline_code`,`date_create`,`user_name`),
  KEY `member_confirm` (`confirm`,`date_create`),
  KEY `members_upline_code_user_name_date_create_index` (`upline_code`,`user_name`,`date_create`),
  KEY `members_confirm_date_create_index` (`confirm`,`date_create`),
  KEY `members_user_name_index` (`user_name`),
  KEY `member_all_index` (`code`,`firstname`,`lastname`,`user_name`,`user_pass`,`acc_no`,`tel`,`lineid`,`wallet_id`,`date_create`),
  KEY `members_team_id_foreign` (`team_id`),
  KEY `members_campaign_id_foreign` (`campaign_id`),
  KEY `idx_members_date_regis` (`date_regis`),
  KEY `idx_members_date_regis_campaign` (`date_regis`,`campaign_id`),
  KEY `idx_members_date_regis_upline` (`date_regis`,`upline_code`),
  CONSTRAINT `members_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `marketing_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_cashback`
--

DROP TABLE IF EXISTS `members_cashback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_cashback` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `downline_code` int(11) NOT NULL,
  `balance` decimal(11,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(11,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(11,2) NOT NULL DEFAULT 0.00,
  `date_cashback` date NOT NULL,
  `amount` decimal(11,2) NOT NULL DEFAULT 0.00,
  `topupic` varchar(1) NOT NULL DEFAULT 'N',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `date_approve` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(255) NOT NULL,
  `user_update` varchar(200) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `winlose` decimal(10,2) NOT NULL DEFAULT 0.00,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `game_user` varchar(40) NOT NULL,
  `sum_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_withdraw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `unique` (`member_code`,`date_cashback`),
  UNIQUE KEY `new` (`date_cashback`,`game_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_cb`
--

DROP TABLE IF EXISTS `members_cb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_cb` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `bank_code` int(11) NOT NULL DEFAULT 0,
  `upline_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(40) NOT NULL DEFAULT '',
  `firstname` varchar(20) NOT NULL DEFAULT '',
  `lastname` varchar(20) NOT NULL DEFAULT '',
  `user_name` varchar(15) NOT NULL DEFAULT '',
  `user_pass` varchar(15) NOT NULL DEFAULT '',
  `user_pin` varchar(6) NOT NULL DEFAULT '',
  `check_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `acc_no` varchar(20) NOT NULL DEFAULT '',
  `acc_check` varchar(100) NOT NULL DEFAULT '',
  `acc_bay` varchar(100) NOT NULL DEFAULT '',
  `acc_kbank` varchar(100) NOT NULL DEFAULT '',
  `tel` varchar(15) NOT NULL DEFAULT '',
  `birth_day` date NOT NULL DEFAULT '0000-00-00',
  `age` varchar(100) NOT NULL DEFAULT '',
  `lineid` varchar(15) NOT NULL DEFAULT '',
  `confirm` enum('N','Y') NOT NULL DEFAULT 'N',
  `refer` varchar(200) NOT NULL DEFAULT '',
  `point_deposit` decimal(10,2) NOT NULL,
  `count_deposit` int(11) NOT NULL DEFAULT 0,
  `diamond` decimal(10,2) NOT NULL,
  `upline` varchar(255) NOT NULL DEFAULT '',
  `credit` decimal(10,2) NOT NULL,
  `balance` decimal(11,2) NOT NULL,
  `balance_free` decimal(12,2) NOT NULL,
  `date_regis` date NOT NULL DEFAULT '0000-00-00',
  `pro` int(11) NOT NULL DEFAULT 0,
  `status_pro` int(1) NOT NULL DEFAULT 0,
  `acc_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `otp` varchar(200) NOT NULL DEFAULT '',
  `pic_id` varchar(200) NOT NULL DEFAULT '',
  `scode` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `lastlogin` timestamp NULL DEFAULT NULL,
  `remark` mediumtext NOT NULL,
  `sms_status` varchar(200) NOT NULL DEFAULT '',
  `promotion` enum('N','Y') NOT NULL DEFAULT 'N',
  `pro_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime2` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime3` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime4` enum('N','Y') DEFAULT 'N',
  `prefix` varchar(255) NOT NULL DEFAULT '',
  `gender` enum('M','F') NOT NULL DEFAULT 'M',
  `deposit` int(11) NOT NULL DEFAULT 0,
  `allget_downline` decimal(11,2) NOT NULL,
  `aff_get` enum('Y','N') NOT NULL DEFAULT 'N',
  `oldmember` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `user_delay` int(11) NOT NULL DEFAULT 0,
  `session_ip` varchar(200) NOT NULL DEFAULT '',
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `session_page` varchar(255) NOT NULL DEFAULT '',
  `session_limit` timestamp NULL DEFAULT NULL,
  `payment_task` varchar(20) NOT NULL DEFAULT '',
  `payment_token` varchar(255) NOT NULL DEFAULT '',
  `payment_level` int(11) NOT NULL DEFAULT 0,
  `payment_game` int(11) NOT NULL DEFAULT 0,
  `payment_pro` int(11) NOT NULL DEFAULT 0,
  `payment_balance` decimal(11,2) NOT NULL,
  `payment_amount` decimal(11,2) NOT NULL,
  `payment_limit` timestamp NULL DEFAULT NULL,
  `payment_delay` timestamp NULL DEFAULT NULL,
  `payment_mac` varchar(255) NOT NULL DEFAULT '',
  `payment_browser` varchar(100) NOT NULL DEFAULT '',
  `payment_device` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `wallet_id` varchar(20) DEFAULT NULL,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(10,2) NOT NULL DEFAULT 0.00,
  `faststart` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `nocashback` enum('Y','N') NOT NULL DEFAULT 'N',
  `sum_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_withdraw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maxwithdraw_day` decimal(10,2) NOT NULL DEFAULT 0.00,
  `game_user` varchar(40) DEFAULT NULL,
  `refund` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `members_wallet_id_unique` (`wallet_id`),
  KEY `member_index` (`upline_code`,`date_create`,`user_name`),
  KEY `member_confirm` (`confirm`,`date_create`),
  KEY `members_upline_code_user_name_date_create_index` (`upline_code`,`user_name`,`date_create`),
  KEY `members_confirm_date_create_index` (`confirm`,`date_create`),
  KEY `members_user_name_index` (`user_name`),
  KEY `member_all_index` (`code`,`firstname`,`lastname`,`user_name`,`user_pass`,`acc_no`,`tel`,`lineid`,`wallet_id`,`date_create`),
  KEY `members_team_id_foreign` (`team_id`),
  KEY `members_campaign_id_foreign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_checkin`
--

DROP TABLE IF EXISTS `members_checkin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_checkin` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_checkin` date NOT NULL,
  `check_code` int(11) NOT NULL DEFAULT 0,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_credit_free_log`
--

DROP TABLE IF EXISTS `members_credit_free_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_credit_free_log` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `bank_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `credit_type` enum('D','W') NOT NULL DEFAULT 'D',
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `refer_table` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(191) NOT NULL DEFAULT '',
  `kind` varchar(10) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`),
  KEY `members_credit_free_log_index` (`kind`,`member_code`,`date_create`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_credit_log`
--

DROP TABLE IF EXISTS `members_credit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_credit_log` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `bank_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `credit_type` enum('D','W') NOT NULL DEFAULT 'D',
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `refer_table` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_after` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(191) NOT NULL DEFAULT '',
  `kind` varchar(10) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `amount_balance` decimal(10,2) NOT NULL,
  `withdraw_limit` decimal(10,2) NOT NULL,
  `withdraw_limit_amount` decimal(10,2) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `members_credit_log_index` (`kind`,`member_code`,`date_create`),
  KEY `idx_mcl_kind_credit_type_date` (`kind`,`credit_type`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_diamondlog`
--

DROP TABLE IF EXISTS `members_diamondlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_diamondlog` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `diamond_type` enum('D','W') NOT NULL DEFAULT 'D',
  `diamond` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diamond_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diamond_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diamond_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` text NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `member_code` (`member_code`),
  KEY `emp_code` (`emp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_edit_log`
--

DROP TABLE IF EXISTS `members_edit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_edit_log` (
  `code` bigint(11) NOT NULL AUTO_INCREMENT,
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `emp_user` varchar(40) NOT NULL,
  `mode` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `member_user` varchar(40) NOT NULL,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `item_before` longtext NOT NULL,
  `item` longtext NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_freecredit`
--

DROP TABLE IF EXISTS `members_freecredit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_freecredit` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(20) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `credit_type` enum('D','W') NOT NULL DEFAULT 'D',
  `credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `members_freecredit_kind_member_code_date_create_index` (`kind`,`member_code`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_ic`
--

DROP TABLE IF EXISTS `members_ic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_ic` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `downline_code` int(11) NOT NULL,
  `balance` decimal(11,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(11,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(11,2) NOT NULL DEFAULT 0.00,
  `date_cashback` date NOT NULL,
  `amount` decimal(11,2) NOT NULL DEFAULT 0.00,
  `topupic` varchar(1) NOT NULL DEFAULT 'N',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `date_approve` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(255) NOT NULL,
  `user_update` varchar(200) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `winlose` decimal(10,2) NOT NULL DEFAULT 0.00,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `game_user` varchar(40) NOT NULL,
  `sum_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_withdraw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_ic_bk`
--

DROP TABLE IF EXISTS `members_ic_bk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_ic_bk` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL,
  `downline_code` int(11) NOT NULL,
  `balance` decimal(11,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(11,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(11,2) NOT NULL DEFAULT 0.00,
  `date_cashback` date NOT NULL,
  `amount` decimal(11,2) NOT NULL DEFAULT 0.00,
  `topupic` varchar(1) NOT NULL DEFAULT 'N',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `date_approve` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(255) NOT NULL,
  `user_update` varchar(200) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `winlose` decimal(10,2) NOT NULL DEFAULT 0.00,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `game_user` varchar(40) NOT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_log`
--

DROP TABLE IF EXISTS `members_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_log` (
  `code` bigint(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(100) NOT NULL DEFAULT '',
  `record` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `item_before` longtext NOT NULL,
  `item` longtext NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `username_real` varchar(40) DEFAULT NULL,
  `password_real` varchar(40) DEFAULT NULL,
  `summary` mediumtext DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_log_bk`
--

DROP TABLE IF EXISTS `members_log_bk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_log_bk` (
  `code` bigint(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `mode` varchar(100) NOT NULL DEFAULT '',
  `menu` varchar(100) NOT NULL DEFAULT '',
  `record` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `item_before` longtext NOT NULL,
  `item` longtext NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `username_real` varchar(40) DEFAULT NULL,
  `password_real` varchar(40) DEFAULT NULL,
  `summary` mediumtext DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_pointlog`
--

DROP TABLE IF EXISTS `members_pointlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_pointlog` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `point_type` enum('D','W') NOT NULL DEFAULT 'D',
  `point` decimal(10,2) NOT NULL,
  `point_amount` decimal(10,2) NOT NULL,
  `point_before` decimal(10,2) NOT NULL,
  `point_balance` decimal(10,2) NOT NULL,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE,
  KEY `emp_code` (`emp_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_promotionlog`
--

DROP TABLE IF EXISTS `members_promotionlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_promotionlog` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_start` date DEFAULT NULL,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `game_name` varchar(100) NOT NULL,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `gameuser_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `pro_name` varchar(191) NOT NULL,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `complete` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL,
  `total_amount_balance` decimal(10,2) NOT NULL,
  `withdraw_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`),
  KEY `bill_code` (`bill_code`),
  KEY `gameuser_code` (`gameuser_code`),
  KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_real`
--

DROP TABLE IF EXISTS `members_real`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_real` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `refer_code` int(11) NOT NULL DEFAULT 0,
  `bank_code` int(11) NOT NULL DEFAULT 0,
  `upline_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(40) NOT NULL DEFAULT '',
  `firstname` varchar(20) NOT NULL DEFAULT '',
  `lastname` varchar(20) NOT NULL DEFAULT '',
  `user_name` varchar(15) NOT NULL DEFAULT '',
  `user_pass` varchar(15) NOT NULL DEFAULT '',
  `user_pin` varchar(6) NOT NULL DEFAULT '',
  `check_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `acc_no` varchar(20) NOT NULL DEFAULT '',
  `acc_check` varchar(100) NOT NULL DEFAULT '',
  `acc_bay` varchar(100) NOT NULL DEFAULT '',
  `acc_kbank` varchar(100) NOT NULL DEFAULT '',
  `tel` varchar(15) NOT NULL DEFAULT '',
  `birth_day` date NOT NULL DEFAULT '0000-00-00',
  `age` varchar(100) NOT NULL DEFAULT '',
  `lineid` varchar(15) NOT NULL DEFAULT '',
  `confirm` enum('N','Y') NOT NULL DEFAULT 'N',
  `refer` varchar(200) NOT NULL DEFAULT '',
  `point_deposit` decimal(10,2) NOT NULL,
  `count_deposit` int(11) NOT NULL DEFAULT 0,
  `diamond` decimal(10,2) NOT NULL,
  `upline` varchar(255) NOT NULL DEFAULT '',
  `credit` decimal(10,2) NOT NULL,
  `balance` decimal(11,2) NOT NULL,
  `balance_free` decimal(12,2) NOT NULL,
  `date_regis` date NOT NULL DEFAULT '0000-00-00',
  `pro` int(11) NOT NULL DEFAULT 0,
  `status_pro` int(1) NOT NULL DEFAULT 0,
  `acc_status` enum('Y','N') NOT NULL DEFAULT 'N',
  `otp` varchar(200) NOT NULL DEFAULT '',
  `pic_id` varchar(200) NOT NULL DEFAULT '',
  `scode` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `lastlogin` timestamp NULL DEFAULT NULL,
  `remark` mediumtext NOT NULL,
  `sms_status` varchar(200) NOT NULL DEFAULT '',
  `promotion` enum('N','Y') NOT NULL DEFAULT 'N',
  `pro_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime2` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime3` enum('N','Y') NOT NULL DEFAULT 'N',
  `hottime4` enum('N','Y') DEFAULT 'N',
  `prefix` varchar(255) NOT NULL DEFAULT '',
  `gender` enum('M','F') NOT NULL DEFAULT 'M',
  `deposit` int(11) NOT NULL DEFAULT 0,
  `allget_downline` decimal(11,2) NOT NULL,
  `aff_get` enum('Y','N') NOT NULL DEFAULT 'N',
  `oldmember` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `user_delay` int(11) NOT NULL DEFAULT 0,
  `session_ip` varchar(200) NOT NULL DEFAULT '',
  `session_id` varchar(200) NOT NULL DEFAULT '',
  `session_page` varchar(255) NOT NULL DEFAULT '',
  `session_limit` timestamp NULL DEFAULT NULL,
  `payment_task` varchar(20) NOT NULL DEFAULT '',
  `payment_token` varchar(255) NOT NULL DEFAULT '',
  `payment_level` int(11) NOT NULL DEFAULT 0,
  `payment_game` int(11) NOT NULL DEFAULT 0,
  `payment_pro` int(11) NOT NULL DEFAULT 0,
  `payment_balance` decimal(11,2) NOT NULL,
  `payment_amount` decimal(11,2) NOT NULL,
  `payment_limit` timestamp NULL DEFAULT NULL,
  `payment_delay` timestamp NULL DEFAULT NULL,
  `payment_mac` varchar(255) NOT NULL DEFAULT '',
  `payment_browser` varchar(100) NOT NULL DEFAULT '',
  `payment_device` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `wallet_id` varchar(20) DEFAULT NULL,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cashback` decimal(10,2) NOT NULL DEFAULT 0.00,
  `faststart` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `nocashback` enum('Y','N') NOT NULL DEFAULT 'N',
  `sum_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sum_withdraw` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maxwithdraw_day` decimal(10,2) NOT NULL DEFAULT 0.00,
  `game_user` varchar(40) DEFAULT NULL,
  `refund` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `members_wallet_id_unique` (`wallet_id`),
  KEY `member_index` (`upline_code`,`date_create`,`user_name`),
  KEY `member_confirm` (`confirm`,`date_create`),
  KEY `members_upline_code_user_name_date_create_index` (`upline_code`,`user_name`,`date_create`),
  KEY `members_confirm_date_create_index` (`confirm`,`date_create`),
  KEY `members_user_name_index` (`user_name`),
  KEY `member_all_index` (`code`,`firstname`,`lastname`,`user_name`,`user_pass`,`acc_no`,`tel`,`lineid`,`wallet_id`,`date_create`),
  KEY `members_team_id_foreign` (`team_id`),
  KEY `members_campaign_id_foreign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_remark`
--

DROP TABLE IF EXISTS `members_remark`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_remark` (
  `code` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(191) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_select_pro`
--

DROP TABLE IF EXISTS `members_select_pro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_select_pro` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `pro_name` varchar(100) NOT NULL,
  `pro_id` varchar(30) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  UNIQUE KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `members_transfer`
--

DROP TABLE IF EXISTS `members_transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members_transfer` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `to_member_code` int(11) NOT NULL DEFAULT 0,
  `to_user_name` varchar(100) NOT NULL DEFAULT '',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `member_code` (`member_code`),
  KEY `to_member_code` (`to_member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notices`
--

DROP TABLE IF EXISTS `notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notices` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `route` varchar(191) NOT NULL,
  `message` varchar(191) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `notices_route_unique` (`route`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notices_new`
--

DROP TABLE IF EXISTS `notices_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notices_new` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `route` varchar(191) NOT NULL,
  `message` longtext NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_provider_accounts`
--

DROP TABLE IF EXISTS `payment_provider_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_provider_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `member_code` bigint(20) unsigned NOT NULL,
  `customer_id` varchar(64) DEFAULT NULL,
  `customer_account_id` varchar(64) DEFAULT NULL,
  `account_identifier` varchar(64) NOT NULL,
  `account_platform` varchar(32) NOT NULL,
  `currency_code` varchar(10) NOT NULL DEFAULT 'THB',
  `name` varchar(150) DEFAULT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `bank_code` int(11) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `sync_hash` varchar(64) DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_provider_identifier_platform_currency` (`provider`,`account_identifier`,`account_platform`,`currency_code`),
  KEY `idx_provider_member_code` (`provider`,`member_code`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_customer_account_id` (`customer_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `date_pay` date NOT NULL DEFAULT '0000-00-00',
  `name` varchar(255) NOT NULL DEFAULT '',
  `amount` decimal(11,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_log`
--

DROP TABLE IF EXISTS `payments_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments_log` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `transfer_type` tinyint(4) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `confirm` enum('Y','N') NOT NULL DEFAULT 'N',
  `status` varchar(50) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `showmsg` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_log_free`
--

DROP TABLE IF EXISTS `payments_log_free`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments_log_free` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `transfer_type` tinyint(4) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `confirm` enum('Y','N') NOT NULL DEFAULT 'N',
  `status` varchar(50) NOT NULL DEFAULT '',
  `msg` varchar(255) NOT NULL DEFAULT '',
  `showmsg` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_promotion`
--

DROP TABLE IF EXISTS `payments_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments_promotion` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `downline_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `credit_bonus` decimal(11,2) NOT NULL,
  `credit_before` decimal(11,2) NOT NULL,
  `credit_after` decimal(11,2) NOT NULL,
  `credit_balance` decimal(11,2) NOT NULL,
  `ip` varchar(30) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE,
  KEY `pro_code` (`pro_code`) USING BTREE,
  KEY `idx_pp_pro_enable_date` (`pro_code`,`enable`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_waiting`
--

DROP TABLE IF EXISTS `payments_waiting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments_waiting` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `transfer_type` tinyint(3) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `credit` decimal(11,2) NOT NULL,
  `credit_bonus` decimal(11,2) NOT NULL,
  `credit_before` decimal(11,2) NOT NULL,
  `credit_after` decimal(11,2) NOT NULL,
  `credit_balance` decimal(11,2) NOT NULL,
  `remark` varchar(255) NOT NULL DEFAULT '',
  `confirm` enum('N','Y','X') DEFAULT 'X',
  `date_approve` timestamp NULL DEFAULT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(200) NOT NULL DEFAULT '',
  `user_update` varchar(200) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `member_code` (`member_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(100) NOT NULL DEFAULT '',
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `per_code` int(11) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `level_open` varchar(255) NOT NULL DEFAULT '',
  `level` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions_type`
--

DROP TABLE IF EXISTS `permissions_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions_type` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(100) NOT NULL DEFAULT '',
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `level` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(20) NOT NULL DEFAULT '',
  `name_th` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `depart_code` int(11) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prefixs`
--

DROP TABLE IF EXISTS `prefixs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prefixs` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `shortname_th` varchar(255) NOT NULL DEFAULT '',
  `shortname_en` varchar(255) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(100) NOT NULL DEFAULT '',
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `withdraw_limit` decimal(11,2) NOT NULL,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `length_type` varchar(10) NOT NULL DEFAULT '',
  `bonus_min` decimal(11,2) NOT NULL DEFAULT 0.00,
  `bonus_max` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus_percent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `use_manual` enum('Y','N') NOT NULL DEFAULT 'N',
  `use_wallet` enum('Y','N') NOT NULL DEFAULT 'N',
  `use_auto` enum('Y','N') NOT NULL DEFAULT 'N',
  `content` longtext NOT NULL,
  `filepic` varchar(255) NOT NULL DEFAULT '',
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `withdraw_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_min` decimal(10,2) NOT NULL DEFAULT 0.00,
  `slot` enum('Y','N') NOT NULL DEFAULT 'N',
  `casino` enum('Y','N') NOT NULL DEFAULT 'N',
  `sport` enum('Y','N') NOT NULL DEFAULT 'N',
  `huay` enum('Y','N') NOT NULL DEFAULT 'N',
  `lotto` enum('Y','N') NOT NULL DEFAULT 'N',
  `keno` enum('Y','N') NOT NULL DEFAULT 'N',
  `card` enum('Y','N') NOT NULL DEFAULT 'N',
  `cock` enum('Y','N') NOT NULL DEFAULT 'N',
  `poker` enum('Y','N') NOT NULL DEFAULT 'N',
  `fish` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=1006 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions_amount`
--

DROP TABLE IF EXISTS `promotions_amount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions_amount` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `deposit_amount` decimal(10,2) NOT NULL,
  `deposit_stop` double(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions_content`
--

DROP TABLE IF EXISTS `promotions_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions_content` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `content` mediumtext NOT NULL,
  `filepic` varchar(191) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime DEFAULT NULL,
  `date_update` datetime DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions_time`
--

DROP TABLE IF EXISTS `promotions_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions_time` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `time_start` varchar(8) NOT NULL DEFAULT '00:00',
  `time_stop` varchar(8) NOT NULL DEFAULT '00:00',
  `deposit_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deposit_stop` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `endpoint` varchar(191) NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `queue_monitor`
--

DROP TABLE IF EXISTS `queue_monitor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_monitor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `queue` varchar(191) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `started_at_exact` varchar(191) DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `finished_at_exact` varchar(191) DEFAULT NULL,
  `time_elapsed` double(12,6) DEFAULT NULL,
  `failed` tinyint(1) NOT NULL DEFAULT 0,
  `attempt` int(11) NOT NULL DEFAULT 0,
  `progress` int(11) DEFAULT NULL,
  `exception` longtext DEFAULT NULL,
  `exception_message` mediumtext DEFAULT NULL,
  `exception_class` mediumtext DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `queue_monitor_job_id_index` (`job_id`),
  KEY `queue_monitor_started_at_index` (`started_at`),
  KEY `queue_monitor_time_elapsed_index` (`time_elapsed`),
  KEY `queue_monitor_failed_index` (`failed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `refers`
--

DROP TABLE IF EXISTS `refers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `refers` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `sort` varchar(255) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registration_link_clicks`
--

DROP TABLE IF EXISTS `registration_link_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_link_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `registration_link_id` bigint(20) unsigned DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` mediumtext DEFAULT NULL,
  `referrer` mediumtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registration_link_clicks_registration_link_id_foreign` (`registration_link_id`),
  CONSTRAINT `registration_link_clicks_registration_link_id_foreign` FOREIGN KEY (`registration_link_id`) REFERENCES `registration_links` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registration_links`
--

DROP TABLE IF EXISTS `registration_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_links_code_unique` (`code`),
  KEY `registration_links_team_id_foreign` (`team_id`),
  KEY `registration_links_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `registration_links_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `marketing_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registration_links_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `marketing_teams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reward_redemptions`
--

DROP TABLE IF EXISTS `reward_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_redemptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reward_id` bigint(20) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL,
  `reward_code_snapshot` varchar(80) DEFAULT NULL,
  `reward_name_snapshot` varchar(255) DEFAULT NULL,
  `point_cost_snapshot` int(10) unsigned NOT NULL DEFAULT 0,
  `point_debited` tinyint(1) NOT NULL DEFAULT 1,
  `reward_type_snapshot` varchar(50) NOT NULL,
  `fulfillment_mode_snapshot` varchar(20) NOT NULL,
  `credit_amount_snapshot` decimal(12,2) DEFAULT NULL,
  `gem_amount_snapshot` int(10) unsigned DEFAULT NULL,
  `payload_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_snapshot`)),
  `redeemed_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `note_user` varchar(1000) DEFAULT NULL,
  `note_staff` varchar(1000) DEFAULT NULL,
  `contact_channel` varchar(30) DEFAULT NULL,
  `contact_value` varchar(255) DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `refunded_by` int(10) unsigned DEFAULT NULL,
  `handled_by` int(10) unsigned DEFAULT NULL,
  `idempotency_key` varchar(120) DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `request_ua` varchar(500) DEFAULT NULL,
  `request_source` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_redemptions_idempotency_key_unique` (`idempotency_key`),
  KEY `reward_redemptions_member_status_idx` (`member_id`,`status`),
  KEY `reward_redemptions_reward_status_idx` (`reward_id`,`status`),
  KEY `reward_redemptions_status_created_idx` (`status`,`created_at`),
  KEY `reward_redemptions_reward_id_index` (`reward_id`),
  KEY `reward_redemptions_member_id_index` (`member_id`),
  KEY `reward_redemptions_reward_code_snapshot_index` (`reward_code_snapshot`),
  KEY `reward_redemptions_reward_type_snapshot_index` (`reward_type_snapshot`),
  KEY `reward_redemptions_fulfillment_mode_snapshot_index` (`fulfillment_mode_snapshot`),
  KEY `reward_redemptions_status_index` (`status`),
  KEY `reward_redemptions_contact_channel_index` (`contact_channel`),
  KEY `reward_redemptions_fulfilled_at_index` (`fulfilled_at`),
  KEY `reward_redemptions_cancelled_at_index` (`cancelled_at`),
  KEY `reward_redemptions_rejected_at_index` (`rejected_at`),
  KEY `reward_redemptions_handled_by_index` (`handled_by`),
  KEY `reward_redemptions_member_reward_created_idx` (`member_id`,`reward_id`,`created_at`),
  KEY `reward_redemptions_member_reward_status_idx` (`member_id`,`reward_id`,`status`),
  KEY `reward_redemptions_request_ip_index` (`request_ip`),
  KEY `reward_redemptions_request_source_index` (`request_source`),
  KEY `reward_redemptions_point_debited_index` (`point_debited`),
  KEY `reward_redemptions_refunded_at_index` (`refunded_at`),
  KEY `reward_redemptions_refunded_by_index` (`refunded_by`),
  KEY `reward_redemptions_redeemed_at_index` (`redeemed_at`),
  CONSTRAINT `reward_redemptions_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `employees` (`code`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reward_redemptions_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`code`) ON UPDATE CASCADE,
  CONSTRAINT `reward_redemptions_refunded_by_foreign` FOREIGN KEY (`refunded_by`) REFERENCES `employees` (`code`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reward_redemptions_reward_id_foreign` FOREIGN KEY (`reward_id`) REFERENCES `rewards_list` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rewards`
--

DROP TABLE IF EXISTS `rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rewards` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `short_details` varchar(199) NOT NULL,
  `details` mediumtext NOT NULL,
  `qty` tinyint(3) NOT NULL DEFAULT 0,
  `points` decimal(10,2) NOT NULL DEFAULT 0.00,
  `filepic` varchar(100) NOT NULL DEFAULT '',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rewards_list`
--

DROP TABLE IF EXISTS `rewards_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rewards_list` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `reward_type` varchar(50) NOT NULL DEFAULT 'wallet_credit',
  `fulfillment_mode` varchar(20) NOT NULL DEFAULT 'auto',
  `auto_claim` tinyint(1) NOT NULL DEFAULT 1,
  `require_staff_contact` tinyint(1) NOT NULL DEFAULT 0,
  `point_cost` int(10) unsigned NOT NULL DEFAULT 0,
  `limit_type` varchar(20) NOT NULL DEFAULT 'unlimited' COMMENT 'unlimited | per_reward | per_period',
  `limit_period` varchar(20) DEFAULT NULL COMMENT 'day | week | month | event',
  `limit_per_period` int(10) unsigned DEFAULT NULL COMMENT 'จำนวนครั้งที่แลกได้ต่อช่วงเวลา',
  `strict_limit` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true = บังคับตรวจ limit แบบ strict (ใช้ lock/tx)',
  `limit_per_user` int(10) unsigned DEFAULT NULL,
  `limit_total` int(10) unsigned DEFAULT NULL,
  `cooldown_minutes` int(10) unsigned DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Bangkok',
  `stock_unlimited` tinyint(1) NOT NULL DEFAULT 1,
  `stock` int(10) unsigned DEFAULT NULL,
  `reserved_stock` int(10) unsigned NOT NULL DEFAULT 0,
  `auto_disable_when_out_of_stock` tinyint(1) NOT NULL DEFAULT 1,
  `credit_amount` decimal(12,2) DEFAULT NULL,
  `gem_amount` int(10) unsigned DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `event_id` bigint(20) unsigned DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rewards_list_code_unique` (`code`),
  KEY `rewards_status_time_idx` (`status`,`start_at`,`end_at`),
  KEY `rewards_type_fulfillment_idx` (`reward_type`,`fulfillment_mode`),
  KEY `rewards_list_slug_index` (`slug`),
  KEY `rewards_list_reward_type_index` (`reward_type`),
  KEY `rewards_list_fulfillment_mode_index` (`fulfillment_mode`),
  KEY `rewards_list_point_cost_index` (`point_cost`),
  KEY `rewards_list_start_at_index` (`start_at`),
  KEY `rewards_list_end_at_index` (`end_at`),
  KEY `rewards_list_stock_unlimited_index` (`stock_unlimited`),
  KEY `rewards_list_credit_amount_index` (`credit_amount`),
  KEY `rewards_list_gem_amount_index` (`gem_amount`),
  KEY `rewards_list_status_index` (`status`),
  KEY `rewards_list_priority_index` (`priority`),
  KEY `rewards_list_is_featured_index` (`is_featured`),
  KEY `rewards_list_is_hidden_index` (`is_hidden`),
  KEY `rewards_list_campaign_id_index` (`campaign_id`),
  KEY `rewards_list_event_id_index` (`event_id`),
  KEY `rewards_list_created_by_index` (`created_by`),
  KEY `rewards_list_updated_by_index` (`updated_by`),
  KEY `rewards_list_limit_type_index` (`limit_type`),
  KEY `rewards_list_limit_period_index` (`limit_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `code` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `permission_type` varchar(191) NOT NULL,
  `permissions` longtext DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `slides`
--

DROP TABLE IF EXISTS `slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `slides` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `filepic` varchar(100) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_campaigns`
--

DROP TABLE IF EXISTS `sms_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `sender_name` varchar(50) DEFAULT NULL,
  `message` mediumtext NOT NULL,
  `audience_mode` varchar(20) NOT NULL DEFAULT 'member_all',
  `filter_json` longtext DEFAULT NULL CHECK (json_valid(`filter_json`)),
  `respect_opt_out` tinyint(1) NOT NULL DEFAULT 1,
  `require_consent` tinyint(1) NOT NULL DEFAULT 1,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `window_start` time DEFAULT NULL,
  `window_end` time DEFAULT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Bangkok',
  `throttle_per_minute` int(10) unsigned NOT NULL DEFAULT 120,
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `retry_backoff_seconds` int(10) unsigned NOT NULL DEFAULT 30,
  `provider` varchar(30) NOT NULL DEFAULT 'vonage',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `total_recipients` bigint(20) unsigned NOT NULL DEFAULT 0,
  `queued_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `sent_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `delivered_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `failed_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `invalid_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `duplicate_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `opted_out_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `suppressed_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `subject` varchar(180) DEFAULT NULL,
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_campaigns_code_unique` (`code`),
  KEY `sms_campaigns_team_status_sched_idx` (`team_id`,`status`,`scheduled_at`),
  KEY `sms_campaigns_team_id_index` (`team_id`),
  KEY `sms_campaigns_audience_mode_index` (`audience_mode`),
  KEY `sms_campaigns_scheduled_at_index` (`scheduled_at`),
  KEY `sms_campaigns_provider_index` (`provider`),
  KEY `sms_campaigns_status_index` (`status`),
  KEY `sms_campaigns_created_by_index` (`created_by`),
  KEY `sms_campaigns_updated_by_index` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_delivery_receipts`
--

DROP TABLE IF EXISTS `sms_delivery_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_delivery_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'vonage',
  `message_id` varchar(80) NOT NULL,
  `msisdn` varchar(20) DEFAULT NULL,
  `to` varchar(50) DEFAULT NULL,
  `network_code` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `err_code` varchar(20) DEFAULT NULL,
  `scts` varchar(20) DEFAULT NULL,
  `api_key` varchar(50) DEFAULT NULL,
  `message_timestamp` varchar(50) DEFAULT NULL,
  `price` varchar(30) DEFAULT NULL,
  `payload` longtext DEFAULT NULL CHECK (json_valid(`payload`)),
  `received_at` timestamp NULL DEFAULT NULL,
  `process_status` varchar(20) NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `process_error` mediumtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_dlr_provider_msg_status_err_scts_uniq` (`provider`,`message_id`,`status`,`err_code`,`scts`),
  KEY `sms_delivery_receipts_team_id_index` (`team_id`),
  KEY `sms_delivery_receipts_campaign_id_index` (`campaign_id`),
  KEY `sms_delivery_receipts_recipient_id_index` (`recipient_id`),
  KEY `sms_delivery_receipts_provider_index` (`provider`),
  KEY `sms_delivery_receipts_message_id_index` (`message_id`),
  KEY `sms_delivery_receipts_msisdn_index` (`msisdn`),
  KEY `sms_delivery_receipts_status_index` (`status`),
  KEY `sms_delivery_receipts_err_code_index` (`err_code`),
  KEY `sms_delivery_receipts_api_key_index` (`api_key`),
  KEY `sms_delivery_receipts_received_at_index` (`received_at`),
  KEY `sms_delivery_receipts_process_status_index` (`process_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_import_batches`
--

DROP TABLE IF EXISTS `sms_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_import_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_mime` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `file_sha1` varchar(40) DEFAULT NULL,
  `storage_disk` varchar(50) DEFAULT NULL,
  `storage_path` varchar(255) DEFAULT NULL,
  `source_label` varchar(120) DEFAULT NULL,
  `phone_column` varchar(50) DEFAULT NULL,
  `country_code` varchar(8) NOT NULL DEFAULT '66',
  `has_header` tinyint(1) NOT NULL DEFAULT 1,
  `consent_basis` varchar(50) DEFAULT NULL,
  `consent_note` mediumtext DEFAULT NULL,
  `total_rows` bigint(20) unsigned NOT NULL DEFAULT 0,
  `valid_phones` bigint(20) unsigned NOT NULL DEFAULT 0,
  `invalid_phones` bigint(20) unsigned NOT NULL DEFAULT 0,
  `duplicate_phones` bigint(20) unsigned NOT NULL DEFAULT 0,
  `suppressed_phones` bigint(20) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'uploaded',
  `error_message` mediumtext DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_import_batches_team_campaign_status_idx` (`team_id`,`campaign_id`,`status`),
  KEY `sms_import_batches_team_id_index` (`team_id`),
  KEY `sms_import_batches_campaign_id_index` (`campaign_id`),
  KEY `sms_import_batches_file_sha1_index` (`file_sha1`),
  KEY `sms_import_batches_status_index` (`status`),
  KEY `sms_import_batches_uploaded_by_index` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_opt_outs`
--

DROP TABLE IF EXISTS `sms_opt_outs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_opt_outs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `phone_raw` varchar(50) DEFAULT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'admin',
  `reason` varchar(120) DEFAULT NULL,
  `note` mediumtext DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_opt_outs_phone_unique` (`phone_e164`),
  KEY `sms_opt_outs_team_id_index` (`team_id`),
  KEY `sms_opt_outs_phone_e164_index` (`phone_e164`),
  KEY `sms_opt_outs_source_index` (`source`),
  KEY `sms_opt_outs_created_by_index` (`created_by`),
  KEY `sms_opt_outs_opted_out_at_index` (`opted_out_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_recipients`
--

DROP TABLE IF EXISTS `sms_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `import_batch_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` varchar(20) NOT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `phone_e164` varchar(20) NOT NULL,
  `phone_raw` varchar(50) DEFAULT NULL,
  `country_code` varchar(8) NOT NULL DEFAULT '66',
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `has_consent` tinyint(1) DEFAULT NULL,
  `consent_at` timestamp NULL DEFAULT NULL,
  `is_opted_out` tinyint(1) NOT NULL DEFAULT 0,
  `opted_out_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `provider` varchar(30) NOT NULL DEFAULT 'vonage',
  `provider_message_id` varchar(80) DEFAULT NULL,
  `dlr_status_raw` varchar(30) DEFAULT NULL,
  `dlr_err_code` varchar(20) DEFAULT NULL,
  `dlr_scts` varchar(20) DEFAULT NULL,
  `dlr_received_at` timestamp NULL DEFAULT NULL,
  `dlr_payload` longtext DEFAULT NULL CHECK (json_valid(`dlr_payload`)),
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `queued_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `error_code` varchar(60) DEFAULT NULL,
  `error_message` mediumtext DEFAULT NULL,
  `recipient_fingerprint` char(40) DEFAULT NULL,
  `meta` longtext DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_recipients_campaign_phone_unique` (`campaign_id`,`phone_e164`),
  KEY `sms_recipients_campaign_status_idx` (`campaign_id`,`status`),
  KEY `sms_recipients_team_campaign_status_idx` (`team_id`,`campaign_id`,`status`),
  KEY `sms_recipients_team_id_index` (`team_id`),
  KEY `sms_recipients_campaign_id_index` (`campaign_id`),
  KEY `sms_recipients_import_batch_id_index` (`import_batch_id`),
  KEY `sms_recipients_source_type_index` (`source_type`),
  KEY `sms_recipients_source_id_index` (`source_id`),
  KEY `sms_recipients_phone_e164_index` (`phone_e164`),
  KEY `sms_recipients_status_index` (`status`),
  KEY `sms_recipients_provider_index` (`provider`),
  KEY `sms_recipients_provider_message_id_index` (`provider_message_id`),
  KEY `sms_recipients_dlr_status_raw_index` (`dlr_status_raw`),
  KEY `sms_recipients_dlr_err_code_index` (`dlr_err_code`),
  KEY `sms_recipients_dlr_received_at_index` (`dlr_received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spins_new`
--

DROP TABLE IF EXISTS `spins_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `spins_new` (
  `code` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `types` varchar(10) NOT NULL DEFAULT 'WALLET',
  `amount` decimal(10,2) NOT NULL,
  `winloss` int(11) NOT NULL DEFAULT 0,
  `spincolor` varchar(20) NOT NULL DEFAULT '',
  `filepic` varchar(100) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(150) NOT NULL DEFAULT '',
  `name_en` varchar(150) NOT NULL DEFAULT '',
  `shortname` varchar(20) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks_permission`
--

DROP TABLE IF EXISTS `tasks_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks_permission` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `task_type` varchar(5) NOT NULL DEFAULT '',
  `task_code` int(11) NOT NULL DEFAULT 0,
  `menu_code` int(11) NOT NULL DEFAULT 0,
  `per_code` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telegram_admins`
--

DROP TABLE IF EXISTS `telegram_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `first_name` varchar(191) DEFAULT NULL,
  `last_name` varchar(191) DEFAULT NULL,
  `username` varchar(191) DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telegram_admins_user_id_unique` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telegram_config`
--

DROP TABLE IF EXISTS `telegram_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bot_token` varchar(191) NOT NULL,
  `register_code` varchar(6) NOT NULL,
  `channel_chat_id` bigint(20) DEFAULT NULL,
  `channel_title` varchar(191) DEFAULT NULL,
  `channel_registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `telegram_config_register_code_unique` (`register_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telegram_customer_menus`
--

DROP TABLE IF EXISTS `telegram_customer_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_customer_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'url',
  `value` varchar(191) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telegram_welcome_messages`
--

DROP TABLE IF EXISTS `telegram_welcome_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_welcome_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message` mediumtext DEFAULT NULL,
  `media_url` varchar(191) DEFAULT NULL,
  `media_type` varchar(20) DEFAULT NULL,
  `lang` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_index`
--

DROP TABLE IF EXISTS `temp_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_index` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `id` varchar(5) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `types`
--

DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `types` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name_th` varchar(100) NOT NULL DEFAULT '',
  `name_en` varchar(100) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT 0,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `upload_files`
--

DROP TABLE IF EXISTS `upload_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `upload_files` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `folder_code` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filetype` varchar(5) NOT NULL DEFAULT '',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `upload_folders`
--

DROP TABLE IF EXISTS `upload_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `upload_folders` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `enable` varchar(100) NOT NULL DEFAULT '',
  `user_create` varchar(100) NOT NULL,
  `user_update` varchar(100) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_activities`
--

DROP TABLE IF EXISTS `user_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_ip` varchar(191) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95990 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_918Kaya`
--

DROP TABLE IF EXISTS `users_918Kaya`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_918Kaya` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_ambfun`
--

DROP TABLE IF EXISTS `users_ambfun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_ambfun` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_avenger`
--

DROP TABLE IF EXISTS `users_avenger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_avenger` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_betflix`
--

DROP TABLE IF EXISTS `users_betflix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_betflix` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_biobet`
--

DROP TABLE IF EXISTS `users_biobet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_biobet` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_chuba`
--

DROP TABLE IF EXISTS `users_chuba`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_chuba` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_dreamtech`
--

DROP TABLE IF EXISTS `users_dreamtech`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_dreamtech` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_eslot`
--

DROP TABLE IF EXISTS `users_eslot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_eslot` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_gclub`
--

DROP TABLE IF EXISTS `users_gclub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_gclub` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_joker`
--

DROP TABLE IF EXISTS `users_joker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_joker` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_pgslot`
--

DROP TABLE IF EXISTS `users_pgslot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_pgslot` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_sagaming`
--

DROP TABLE IF EXISTS `users_sagaming`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_sagaming` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_slotx`
--

DROP TABLE IF EXISTS `users_slotx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_slotx` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_slotxo`
--

DROP TABLE IF EXISTS `users_slotxo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_slotxo` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_star`
--

DROP TABLE IF EXISTS `users_star`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_star` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_ufabet`
--

DROP TABLE IF EXISTS `users_ufabet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_ufabet` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `batch_code` (`batch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_xe88`
--

DROP TABLE IF EXISTS `users_xe88`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_xe88` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `member_code` int(11) NOT NULL DEFAULT 0,
  `use_account` enum('Y','N') NOT NULL DEFAULT 'N',
  `freecredit` enum('Y','N') NOT NULL DEFAULT 'N',
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ip` varchar(100) NOT NULL DEFAULT '',
  `date_join` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `batch_code` (`batch_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `websites`
--

DROP TABLE IF EXISTS `websites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `websites` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` mediumtext NOT NULL,
  `api` mediumtext NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `emp_code` int(11) NOT NULL DEFAULT 0,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`) USING BTREE,
  KEY `emp_code` (`emp_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `websockets_statistics_entries`
--

DROP TABLE IF EXISTS `websockets_statistics_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `websockets_statistics_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(191) NOT NULL,
  `peak_connection_count` int(11) NOT NULL,
  `websocket_message_count` int(11) NOT NULL,
  `api_message_count` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraws`
--

DROP TABLE IF EXISTS `withdraws`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraws` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `member_user` varchar(100) NOT NULL,
  `account_code` int(11) NOT NULL DEFAULT 0,
  `bankout` varchar(100) NOT NULL,
  `bankm_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_record` date NOT NULL DEFAULT '0000-00-00',
  `timedept` time NOT NULL,
  `ck_deposit` enum('N','Y') NOT NULL DEFAULT 'N',
  `check_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_withdraw` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_balance` enum('N','Y') NOT NULL DEFAULT 'N',
  `oldcredit` decimal(11,2) DEFAULT NULL,
  `aftercredit` decimal(11,2) NOT NULL,
  `fee` decimal(11,2) NOT NULL,
  `remark` mediumtext NOT NULL,
  `ckb_user` varchar(255) NOT NULL DEFAULT '',
  `ckb_date` timestamp NULL DEFAULT NULL,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `remark_admin` text DEFAULT NULL,
  `emp_approve` int(11) NOT NULL DEFAULT 0,
  `date_approve` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` int(11) NOT NULL DEFAULT 0,
  `ck_step2` int(11) NOT NULL DEFAULT 0,
  `date_bank` date NOT NULL DEFAULT '0000-00-00',
  `time_bank` varchar(10) NOT NULL DEFAULT '',
  `status_bank` varchar(50) NOT NULL,
  `status_withdraw` varchar(1) NOT NULL DEFAULT 'W',
  `txid` varchar(40) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `pro_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `unique` (`member_code`,`amount`,`date_create`),
  KEY `idx_wd_status_enable_approve` (`status`,`enable`,`date_approve`),
  KEY `idx_wd_status_enable_create` (`status`,`enable`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraws_detail`
--

DROP TABLE IF EXISTS `withdraws_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraws_detail` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `withdraw_code` int(11) NOT NULL DEFAULT 0,
  `game_code` int(11) NOT NULL DEFAULT 0,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(100) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `balance` decimal(10,2) NOT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_update` timestamp NULL DEFAULT NULL,
  `bill_code` int(11) NOT NULL DEFAULT 0,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `turnpro` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `withdraw_limit_rate` int(11) NOT NULL DEFAULT 0,
  `withdraw_limit_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`code`),
  KEY `withdraw_code` (`withdraw_code`),
  KEY `member_code` (`member_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraws_free`
--

DROP TABLE IF EXISTS `withdraws_free`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraws_free` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `member_user` varchar(100) NOT NULL,
  `account_code` int(11) NOT NULL DEFAULT 0,
  `bankout` varchar(100) NOT NULL,
  `bankm_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `date_record` date NOT NULL DEFAULT '0000-00-00',
  `timedept` time NOT NULL,
  `ck_deposit` enum('N','Y') NOT NULL DEFAULT 'N',
  `check_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_withdraw` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_balance` enum('N','Y') NOT NULL DEFAULT 'N',
  `oldcredit` decimal(11,2) DEFAULT NULL,
  `aftercredit` decimal(11,2) NOT NULL,
  `fee` decimal(11,2) NOT NULL,
  `remark` mediumtext NOT NULL,
  `ckb_user` varchar(255) NOT NULL DEFAULT '',
  `ckb_date` timestamp NULL DEFAULT NULL,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `remark_admin` varchar(255) NOT NULL DEFAULT '',
  `emp_approve` int(11) NOT NULL DEFAULT 0,
  `date_approve` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` int(1) NOT NULL DEFAULT 0,
  `ck_step2` int(11) NOT NULL DEFAULT 0,
  `date_bank` date NOT NULL DEFAULT '0000-00-00',
  `time_bank` varchar(10) NOT NULL DEFAULT '',
  `status_withdraw` varchar(1) NOT NULL DEFAULT 'W',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraws_seamless`
--

DROP TABLE IF EXISTS `withdraws_seamless`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraws_seamless` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `member_user` varchar(100) NOT NULL,
  `account_code` int(11) NOT NULL DEFAULT 0,
  `bankout` varchar(100) NOT NULL,
  `bankm_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_record` date NOT NULL DEFAULT '0000-00-00',
  `timedept` time NOT NULL,
  `ck_deposit` enum('N','Y') NOT NULL DEFAULT 'N',
  `check_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_withdraw` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_balance` enum('N','Y') NOT NULL DEFAULT 'N',
  `oldcredit` decimal(11,2) DEFAULT NULL,
  `aftercredit` decimal(11,2) NOT NULL,
  `fee` decimal(11,2) NOT NULL,
  `remark` mediumtext NOT NULL,
  `ckb_user` varchar(255) NOT NULL DEFAULT '',
  `ckb_date` timestamp NULL DEFAULT NULL,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `remark_admin` varchar(255) NOT NULL DEFAULT '',
  `emp_approve` int(11) NOT NULL DEFAULT 0,
  `date_approve` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` int(11) NOT NULL DEFAULT 0,
  `ck_step2` int(11) NOT NULL DEFAULT 0,
  `date_bank` date NOT NULL DEFAULT '0000-00-00',
  `time_bank` varchar(10) NOT NULL DEFAULT '',
  `status_bank` varchar(50) NOT NULL,
  `status_withdraw` varchar(1) NOT NULL DEFAULT 'W',
  `txid` varchar(40) NOT NULL,
  `pro_code` int(11) NOT NULL DEFAULT 0,
  `pro_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `idx_wds_status_enable_approve` (`status`,`enable`,`date_approve`),
  KEY `idx_wds_status_enable_create` (`status`,`enable`,`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdraws_seamless_free`
--

DROP TABLE IF EXISTS `withdraws_seamless_free`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdraws_seamless_free` (
  `code` int(11) NOT NULL AUTO_INCREMENT,
  `member_code` int(11) NOT NULL DEFAULT 0,
  `member_user` varchar(100) NOT NULL,
  `account_code` int(11) NOT NULL DEFAULT 0,
  `bankout` varchar(100) NOT NULL,
  `bankm_code` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(11,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_limit_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_record` date NOT NULL DEFAULT '0000-00-00',
  `timedept` time NOT NULL,
  `ck_deposit` enum('N','Y') NOT NULL DEFAULT 'N',
  `check_status` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_withdraw` enum('N','Y') NOT NULL DEFAULT 'N',
  `ck_balance` enum('N','Y') NOT NULL DEFAULT 'N',
  `oldcredit` decimal(11,2) DEFAULT NULL,
  `aftercredit` decimal(11,2) NOT NULL,
  `fee` decimal(11,2) NOT NULL,
  `remark` mediumtext NOT NULL,
  `ckb_user` varchar(255) NOT NULL DEFAULT '',
  `ckb_date` timestamp NULL DEFAULT NULL,
  `ip` varchar(50) NOT NULL DEFAULT '',
  `ip_admin` varchar(100) NOT NULL DEFAULT '',
  `remark_admin` varchar(255) NOT NULL DEFAULT '',
  `emp_approve` int(11) NOT NULL DEFAULT 0,
  `date_approve` timestamp NULL DEFAULT NULL,
  `user_create` varchar(100) NOT NULL DEFAULT '',
  `user_update` varchar(100) NOT NULL DEFAULT '',
  `date_create` timestamp NULL DEFAULT NULL,
  `date_update` timestamp NULL DEFAULT NULL,
  `enable` enum('Y','N') NOT NULL DEFAULT 'Y',
  `status` int(11) NOT NULL DEFAULT 0,
  `ck_step2` int(11) NOT NULL DEFAULT 0,
  `date_bank` date NOT NULL DEFAULT '0000-00-00',
  `time_bank` varchar(10) NOT NULL DEFAULT '',
  `status_bank` varchar(50) NOT NULL,
  `status_withdraw` varchar(1) NOT NULL DEFAULT 'W',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-21  3:14:30

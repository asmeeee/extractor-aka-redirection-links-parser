-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.5.50 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table redirection_links_parser.links
CREATE TABLE IF NOT EXISTS `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parser_type` varchar(255) DEFAULT NULL,
  `prweb_article_link` varchar(1020) DEFAULT NULL,
  `prweb_redirection_link` varchar(1020) DEFAULT NULL,
  `proz_company` varchar(1020) DEFAULT NULL,
  `proz_link` varchar(1020) DEFAULT NULL,
  `getapp_category` varchar(1020) DEFAULT NULL,
  `getapp_company` varchar(1020) DEFAULT NULL,
  `getapp_redirection_link` varchar(1020) DEFAULT NULL,
  `getapp_software_by` varchar(1020) DEFAULT NULL,
  `getapp_link` varchar(1020) DEFAULT NULL,
  `auriga_email` varchar(1020) DEFAULT NULL,
  `auriga_website_link` varchar(1020) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

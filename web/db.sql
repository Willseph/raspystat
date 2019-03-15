SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `raspystat` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `raspystat`;
DROP TABLE IF EXISTS `controllers`;
CREATE TABLE IF NOT EXISTS `controllers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `secret` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `status` enum('off','fan','heat','cool') NOT NULL DEFAULT 'off',
  `resting` tinyint(1) NOT NULL,
  `error` varchar(64) NOT NULL,
  `updated` int(11) NOT NULL,
  `ver` varchar(64) NOT NULL,
  `needsupdate` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=2 ;
DROP TABLE IF EXISTS `history`;
CREATE TABLE IF NOT EXISTS `history` (
  `date` int(11) NOT NULL,
  `average` int(11) NOT NULL DEFAULT '-1',
  `sensors` varchar(512) NOT NULL DEFAULT '',
  `status` enum('off','fan','heat','cool') NOT NULL DEFAULT 'off',
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `sensors`;
CREATE TABLE IF NOT EXISTS `sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `secret` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(64) NOT NULL,
  `observed` tinyint(1) NOT NULL DEFAULT '1',
  `icon` varchar(32) NOT NULL,
  `temp` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL DEFAULT '0',
  `error` varchar(64) NOT NULL,
  `updated` int(11) NOT NULL,
  `ver` varchar(64) NOT NULL,
  `needsupdate` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret` (`secret`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=7 ;
DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` enum('1') NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `fan` tinyint(1) NOT NULL DEFAULT '0',
  `heat` tinyint(1) NOT NULL DEFAULT '0',
  `cool` tinyint(1) NOT NULL DEFAULT '0',
  `format` enum('C','F') NOT NULL DEFAULT 'F',
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  `historyperiod` int(11) NOT NULL DEFAULT '3600',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `tokens`;
CREATE TABLE IF NOT EXISTS `tokens` (
  `user` varchar(128) NOT NULL,
  `token` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `active` tinyint(1) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`token`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user` varchar(128) NOT NULL,
  `hash` char(128) NOT NULL,
  `salt` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`user`) REFERENCES `users` (`user`) ON DELETE CASCADE ON UPDATE CASCADE;
INSERT INTO `settings` (`id`, `min`, `max`) VALUES ('1', 60000, 80000);
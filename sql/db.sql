-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: localhost:8889
-- Erstellungszeit: 31. Dez 2014 um 16:21
-- Server Version: 5.5.34
-- PHP-Version: 5.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Datenbank: `gong`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `friends`
--

CREATE TABLE `friends` (
  `friends_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_1` int(11) NOT NULL,
  `user_2` int(11) NOT NULL,
  PRIMARY KEY (`friends_id`),
  UNIQUE KEY `unique_id` (`user_1`,`user_2`),
  KEY `user_1_constr` (`user_1`),
  KEY `user_2_constr` (`user_2`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;

--
-- Daten für Tabelle `friends`
--

INSERT INTO `friends` (`friends_id`, `user_1`, `user_2`) VALUES
(13, 10, 10),
(12, 10, 11),
(11, 11, 10),
(14, 11, 15),
(15, 11, 16),
(16, 11, 17),
(17, 15, 11),
(18, 16, 11),
(20, 16, 15),
(19, 17, 11);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `notification_queue`
--

CREATE TABLE `notification_queue` (
  `queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=28 ;

--
-- Daten für Tabelle `notification_queue`
--

INSERT INTO `notification_queue` (`queue_id`, `user_id`, `friend_id`, `timestamp`) VALUES
(2, 12, 21, '2014-12-28 13:52:51'),
(3, 1, 11, '2014-12-28 14:01:28'),
(4, 10, 11, '2014-12-28 14:15:10'),
(5, 15, 11, '2014-12-28 14:15:10'),
(6, 16, 11, '2014-12-28 14:15:10'),
(7, 17, 11, '2014-12-28 14:15:10'),
(8, 10, 11, '2014-12-28 14:15:20'),
(9, 15, 11, '2014-12-28 14:15:20'),
(10, 16, 11, '2014-12-28 14:15:20'),
(11, 17, 11, '2014-12-28 14:15:20'),
(12, 10, 11, '2014-12-28 14:15:22'),
(13, 15, 11, '2014-12-28 14:15:22'),
(14, 16, 11, '2014-12-28 14:15:22'),
(15, 17, 11, '2014-12-28 14:15:22'),
(16, 10, 11, '2014-12-28 14:15:31'),
(17, 15, 11, '2014-12-28 14:15:31'),
(18, 16, 11, '2014-12-28 14:15:31'),
(19, 17, 11, '2014-12-28 14:15:31'),
(20, 10, 11, '2014-12-28 14:17:39'),
(21, 15, 11, '2014-12-28 14:17:39'),
(22, 16, 11, '2014-12-28 14:17:39'),
(23, 17, 11, '2014-12-28 14:17:39'),
(24, 10, 11, '2014-12-28 14:17:53'),
(25, 15, 11, '2014-12-28 14:17:53'),
(26, 16, 11, '2014-12-28 14:17:53'),
(27, 17, 11, '2014-12-28 14:17:53');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(20) NOT NULL,
  `user_hash` varchar(255) NOT NULL,
  `user_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_hash`, `user_token`) VALUES
(10, 'saverio', '$2y$10$8P/Szj6rC11BtF9QlcI7J.8zOjc4gZcnPqpR3f0CjTdz4XmoTNjCq', NULL),
(11, 'thomas', '$2y$10$lnycR/xnwUl6o4GQwH8MUeq34N5hFfISAjy3qJr.O.Npv34AA.Wka', NULL),
(15, 'len', '$2y$10$pccaeABm/H7y5mG.spwoIe9YZ07Dq3XpIB/PYMkcYGhJDSwZRGKAq', NULL),
(16, 'ramon', '$2y$10$QZDbO637wvCPFMzMQR6RdOBQ4WuUo4aEu4Re50RoPcnfoYIbK40lW', NULL),
(17, 'fabian', '$2y$10$buZ1tMjeWIT8aA42TrMbfuWLOVfq8Sq7eZVFoWdurzlCGb6MMUU8y', NULL);

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `user_1_constr` FOREIGN KEY (`user_1`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_2_constr` FOREIGN KEY (`user_2`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

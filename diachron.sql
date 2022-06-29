-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: vik.iad1-mysql-e2-10a.dreamhost.com
-- Generation Time: Jun 28, 2022 at 03:36 PM
-- Server version: 8.0.28-0ubuntu0.20.04.3
-- PHP Version: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `diachron`
--
CREATE DATABASE IF NOT EXISTS `diachron` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `diachron`;

-- --------------------------------------------------------

--
-- Table structure for table `environments`
--

CREATE TABLE `environments` (
  `id` int NOT NULL,
  `description` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `environments`
--

INSERT INTO `environments` (`id`, `description`) VALUES
(1, 'before vowel'),
(2, 'before fricative'),
(3, 'between vowels'),
(4, 'before /r/'),
(5, 'word-initial'),
(6, 'before /n/'),
(7, 'before /m/'),
(8, 'before consonant'),
(9, 'after consonant'),
(10, 'word-final'),
(11, 'before /stʲ/');

-- --------------------------------------------------------

--
-- Table structure for table `environments_pairs`
--

CREATE TABLE `environments_pairs` (
  `id` int NOT NULL,
  `pair_id` int NOT NULL,
  `environment_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `environments_pairs`
--

INSERT INTO `environments_pairs` (`id`, `pair_id`, `environment_id`) VALUES
(1, 19, 1),
(2, 32, 2),
(3, 34, 3),
(4, 34, 4),
(5, 35, 3),
(6, 35, 5),
(7, 36, 6),
(8, 37, 7),
(9, 38, 8),
(10, 38, 9),
(11, 38, 10),
(12, 39, 3),
(13, 40, 5),
(14, 41, 11),
(15, 42, 11);

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`) VALUES
(41, 'Akkadian'),
(46, 'Amharic'),
(42, 'Arabic'),
(43, 'Aramaic'),
(23, 'Azerbaijani'),
(50, 'Bleurgh'),
(15, 'Canaanite'),
(38, 'Cantonese'),
(29, 'Catalan'),
(13, 'Central Semitic'),
(25, 'Chuvash'),
(5, 'Classical Armenian'),
(6, 'Classical Tibetan'),
(19, 'Early Middle Chinese'),
(12, 'East Semitic'),
(34, 'Eastern Armenian'),
(55, 'English'),
(16, 'Ethiopic (Ge\'ez)'),
(32, 'French'),
(17, 'Germanic'),
(44, 'Hebrew'),
(26, 'Italian'),
(7, 'Late Middle Chinese'),
(4, 'Latin'),
(36, 'Lhasa Tibetan'),
(37, 'Mandarin'),
(14, 'Northwest Semitic'),
(30, 'Occitan'),
(18, 'Old Chinese'),
(54, 'Old English'),
(48, 'Old High German'),
(21, 'Old Norse'),
(3, 'Old Turkic'),
(2, 'PIE'),
(33, 'Portuguese'),
(49, 'Proto-Armenian'),
(8, 'Proto-Austronesian'),
(1, 'Proto-Germanic'),
(20, 'Proto-Indo-European'),
(9, 'Proto-Malayo-Polynesian'),
(40, 'Proto-Oceanic'),
(47, 'Proto-Romance'),
(10, 'Proto-Semitic'),
(28, 'Romanian'),
(27, 'Sardinian'),
(31, 'Spanish'),
(39, 'Standard Hakka'),
(45, 'Tigrinya'),
(22, 'Turkish'),
(24, 'Turkmen'),
(11, 'West Semitic'),
(35, 'Western Armenian');

-- --------------------------------------------------------

--
-- Table structure for table `pairs`
--

CREATE TABLE `pairs` (
  `id` int NOT NULL,
  `source_phone_id` int DEFAULT NULL,
  `target_phone_id` int DEFAULT NULL,
  `transition_id` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8 COLLATE utf8_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `pairs`
--

INSERT INTO `pairs` (`id`, `source_phone_id`, `target_phone_id`, `transition_id`, `notes`) VALUES
(1, 1, 30, 1, ''),
(2, 2, 23, 1, ''),
(3, 3, 9, 1, ''),
(4, 4, 12, 1, ''),
(5, 5, 31, 1, 'possible'),
(6, 6, 32, 1, 'dubious'),
(7, 7, 6, 2, ''),
(8, 8, 33, 2, ''),
(9, 9, 2, 2, ''),
(10, 3, 5, 2, ''),
(11, 10, 7, 2, ''),
(12, 11, 8, 2, ''),
(13, 12, 9, 2, ''),
(14, 13, 3, 2, ''),
(15, 14, 10, 2, ''),
(16, 15, 11, 2, ''),
(17, 16, 12, 2, ''),
(18, 17, 13, 2, ''),
(19, 13, 10, 2, ''),
(20, 18, 9, 3, ''),
(21, 9, 12, 4, ''),
(22, 19, 33, 5, ''),
(23, 20, 34, 5, ''),
(24, 20, 30, 6, ''),
(25, 21, 35, 6, 'probably through ʃ > ɬ > l'),
(26, 11, 30, 6, 'd > ð > z > r'),
(27, 10, 7, 6, ''),
(28, 22, 36, 6, ''),
(29, 12, 22, 6, ''),
(30, 22, 37, 6, ''),
(31, 23, 37, 35, ''),
(32, 24, 37, 35, ''),
(33, 22, 38, 35, ''),
(34, 10, 38, 35, ''),
(35, 25, 39, 35, ''),
(36, 12, 40, 35, ''),
(37, 12, 41, 35, ''),
(38, 26, 19, 35, ''),
(39, 26, 19, 35, ''),
(40, 27, 42, 35, ''),
(41, 28, 43, 35, ''),
(42, 29, 44, 35, '');

-- --------------------------------------------------------

--
-- Table structure for table `phones`
--

CREATE TABLE `phones` (
  `id` int NOT NULL,
  `ipa` varchar(5) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `phones`
--

INSERT INTO `phones` (`id`, `ipa`) VALUES
(46, 'ar'),
(45, 'au'),
(10, 'b'),
(14, 'bʰ'),
(11, 'd'),
(15, 'dʰ'),
(28, 'eː'),
(32, 'f'),
(12, 'g'),
(16, 'gʰ'),
(13, 'gʷ'),
(17, 'gʷʰ'),
(23, 'h'),
(31, 'hʷ'),
(43, 'iː'),
(25, 'j'),
(9, 'k'),
(26, 'ks'),
(3, 'kʷ'),
(35, 'l'),
(24, 'n'),
(29, 'oː'),
(7, 'p'),
(18, 'q'),
(30, 'r'),
(19, 's'),
(27, 'sC'),
(8, 't'),
(44, 'uː'),
(41, 'u̯'),
(36, 'v'),
(22, 'w'),
(2, 'x'),
(5, 'xʷ'),
(20, 'z'),
(34, 'ð'),
(40, 'ɣ'),
(21, 'ʃ'),
(39, 'ɟ'),
(4, 'ɡʷ'),
(42, 'ɪsC'),
(6, 'ɸ'),
(1, 'ʐ'),
(38, 'β'),
(33, 'θ'),
(37, '∅');

-- --------------------------------------------------------

--
-- Table structure for table `transitions`
--

CREATE TABLE `transitions` (
  `id` int NOT NULL,
  `source_language_id` int NOT NULL,
  `target_language_id` int NOT NULL,
  `citation` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `notes` text CHARACTER SET utf8 COLLATE utf8_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `transitions`
--

INSERT INTO `transitions` (`id`, `source_language_id`, `target_language_id`, `citation`, `notes`) VALUES
(1, 1, 21, '', ''),
(2, 2, 1, '', ''),
(3, 3, 22, '', ''),
(4, 3, 23, '', ''),
(5, 3, 24, '', ''),
(6, 3, 25, '', ''),
(7, 4, 26, '', 'Proto-Romance Intermediate'),
(8, 4, 27, '', 'Proto-Romance Intermediate'),
(9, 4, 28, '', 'Proto-Romance Intermediate'),
(10, 4, 29, '', 'Proto-Romance Intermediate'),
(11, 4, 30, '', 'Proto-Romance Intermediate'),
(12, 4, 31, '', 'Proto-Romance Intermediate'),
(13, 4, 32, 'https://en.wikipedia.org/wiki/Phonological_history_of_French?wprov=sfti1', 'see many intermediate stages'),
(14, 4, 33, '', 'Proto-Romance Intermediate'),
(15, 5, 34, 'https://en.wikipedia.org/wiki/Eastern_Armenian', ''),
(16, 5, 35, 'https://en.wikipedia.org/wiki/Western_Armenian', ''),
(17, 6, 36, 'https://evols.library.manoa.hawaii.edu/bitstream/10524/52482/D03AlvesJSEALS141_2021_book.pdf', ''),
(18, 7, 37, 'https://en.wikipedia.org/wiki/Historical_Chinese_phonology', ''),
(19, 7, 38, 'https://en.wikipedia.org/wiki/Historical_Chinese_phonology', ''),
(20, 7, 39, 'https://en.wikipedia.org/wiki/Historical_Chinese_phonology', ''),
(21, 8, 9, '', ''),
(22, 9, 40, '', ''),
(23, 10, 12, '', ''),
(24, 10, 11, '', ''),
(25, 11, 13, '', ''),
(26, 12, 41, '', ''),
(27, 13, 42, '', ''),
(28, 13, 14, '', ''),
(29, 14, 43, '', ''),
(30, 14, 15, '', ''),
(31, 15, 44, '', ''),
(32, 11, 16, '', ''),
(33, 16, 45, '', ''),
(34, 16, 46, '', ''),
(35, 4, 47, 'https://en.wikipedia.org/wiki/Phonological_changes_from_Classical_Latin_to_Proto-Romance', ''),
(36, 17, 48, 'https://en.wikipedia.org/wiki/High_German_consonant_shift', ''),
(37, 18, 19, 'https://en.wikipedia.org/wiki/Historical_Chinese_phonology', ''),
(38, 19, 7, 'https://en.wikipedia.org/wiki/Historical_Chinese_phonology', ''),
(39, 20, 49, 'https://en.wikipedia.org/wiki/Proto-Armenian_language', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `environments`
--
ALTER TABLE `environments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `environments_pairs`
--
ALTER TABLE `environments_pairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `environment_key` (`environment_id`),
  ADD KEY `pair_key` (`pair_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `pairs`
--
ALTER TABLE `pairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `source_phone_key` (`source_phone_id`),
  ADD KEY `target_phone_key` (`target_phone_id`),
  ADD KEY `transition_key` (`transition_id`);

--
-- Indexes for table `phones`
--
ALTER TABLE `phones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ipa` (`ipa`);

--
-- Indexes for table `transitions`
--
ALTER TABLE `transitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `source_language_key` (`source_language_id`),
  ADD KEY `target_language_key` (`target_language_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `environments`
--
ALTER TABLE `environments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `environments_pairs`
--
ALTER TABLE `environments_pairs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `pairs`
--
ALTER TABLE `pairs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `phones`
--
ALTER TABLE `phones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `transitions`
--
ALTER TABLE `transitions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `environments_pairs`
--
ALTER TABLE `environments_pairs`
  ADD CONSTRAINT `environment_key` FOREIGN KEY (`environment_id`) REFERENCES `environments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `pair_key` FOREIGN KEY (`pair_id`) REFERENCES `pairs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `pairs`
--
ALTER TABLE `pairs`
  ADD CONSTRAINT `source_phone_key` FOREIGN KEY (`source_phone_id`) REFERENCES `phones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `target_phone_key` FOREIGN KEY (`target_phone_id`) REFERENCES `phones` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `transition_key` FOREIGN KEY (`transition_id`) REFERENCES `transitions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `transitions`
--
ALTER TABLE `transitions`
  ADD CONSTRAINT `source_key` FOREIGN KEY (`source_language_id`) REFERENCES `languages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `target_key` FOREIGN KEY (`target_language_id`) REFERENCES `languages` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

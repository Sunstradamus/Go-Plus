-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 11, 2014 at 06:33 PM
-- Server version: 5.1.71
-- PHP Version: 5.3.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `tstdump_goplus`
--

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(4) NOT NULL,
  `level` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `career` varchar(15) NOT NULL,
  `wqb` int(10) unsigned NOT NULL,
  `units` int(10) unsigned NOT NULL,
  `prereq` varchar(500) NOT NULL,
  `desc` varchar(8000) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject`,`level`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE IF NOT EXISTS `instructor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE IF NOT EXISTS `section` (
  `course_id` int(10) unsigned NOT NULL,
  `id` int(10) unsigned NOT NULL,
  `type` varchar(3) NOT NULL,
  `section` varchar(4) NOT NULL,
  `term` int(10) unsigned NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`),
  KEY `course_id_2` (`course_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sectionComponent`
--

CREATE TABLE IF NOT EXISTS `sectionComponent` (
  `section_id` int(11) unsigned NOT NULL,
  `day` int(11) NOT NULL,
  `instructor` int(11) unsigned NOT NULL,
  `timeslot` int(11) NOT NULL,
  `campus` varchar(15) NOT NULL,
  `room` varchar(15) NOT NULL,
  `dates` varchar(30) NOT NULL,
  KEY `instructor` (`instructor`),
  KEY `section_id` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wqbLookup`
--

CREATE TABLE IF NOT EXISTS `wqbLookup` (
  `bitwise` int(11) NOT NULL,
  `designation` varchar(15) NOT NULL,
  UNIQUE KEY `bitwise` (`bitwise`,`designation`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `section_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`);

--
-- Constraints for table `sectionComponent`
--
ALTER TABLE `sectionComponent`
  ADD CONSTRAINT `sectionComponent_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `section` (`id`),
  ADD CONSTRAINT `sectionComponent_ibfk_2` FOREIGN KEY (`instructor`) REFERENCES `instructor` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

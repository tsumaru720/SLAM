-- phpMyAdmin SQL Dump
-- version 3.3.2deb1ubuntu1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 24, 2012 at 02:07 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `tracker`
--
CREATE DATABASE `tracker` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `tracker`;

-- --------------------------------------------------------

--
-- Table structure for table `computerChangeLog`
--

CREATE TABLE IF NOT EXISTS `computerChangeLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computerid` int(11) NOT NULL,
  `changedby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `old` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `new` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `computerChangeLog`
--


-- --------------------------------------------------------

--
-- Table structure for table `computers`
--

CREATE TABLE IF NOT EXISTS `computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `location` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `maintenanceFee` float NOT NULL,
  `createdby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `modifiedby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `created` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `computers`
--


-- --------------------------------------------------------

--
-- Table structure for table `configuration`
--

CREATE TABLE IF NOT EXISTS `configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `userPref` tinyint(4) NOT NULL,
  `values` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `validation` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `configuration`
--

INSERT INTO `configuration` (`id`, `name`, `friendlyName`, `value`, `userPref`, `values`, `validation`) VALUES
(1, 'defaultMaintenanceFee', 'Default Maintenance Cost', '5.5', 1, '', ''),
(3, 'authType', 'Authentication Handler', 'slam', 0, 'slam', ''),
(4, 'linkType', 'Result Link Type', 'inline', 1, 'inline,popout', ''),
(5, 'pageSize', 'Number of Results to display per page', '50', 1, '', ''),
(6, 'timeout', 'Session Timeout in seconds', '300', 0, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `kbFiles`
--

CREATE TABLE IF NOT EXISTS `kbFiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kbid` int(11) NOT NULL,
  `filename` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `urlfilename` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `path` varchar(400) COLLATE utf8_unicode_ci NOT NULL,
  `size` int(11) NOT NULL,
  `type` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  `createdby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `index` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `kbFiles`
--


-- --------------------------------------------------------

--
-- Table structure for table `kbList`
--

CREATE TABLE IF NOT EXISTS `kbList` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `createdby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `modifiedby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  `modified` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `kbList`
--


-- --------------------------------------------------------

--
-- Table structure for table `kbTags`
--

CREATE TABLE IF NOT EXISTS `kbTags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kbid` int(11) NOT NULL,
  `tag` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `kbTags`
--


-- --------------------------------------------------------

--
-- Table structure for table `licenses`
--

CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computerid` int(11) NOT NULL,
  `orderid` int(11) NOT NULL,
  `product` int(11) NOT NULL,
  `license` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `licenses`
--


-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `corporate` tinyint(4) NOT NULL DEFAULT '0',
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `friendlyName`, `corporate`, `hidden`) VALUES
(1, 'Default Location', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orderChangeLog`
--

CREATE TABLE IF NOT EXISTS `orderChangeLog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `changedby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `old` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `new` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `orderChangeLog`
--


-- --------------------------------------------------------

--
-- Table structure for table `orderStatuses`
--

CREATE TABLE IF NOT EXISTS `orderStatuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `orderStatuses`
--

INSERT INTO `orderStatuses` (`id`, `friendlyName`) VALUES
(1, 'Ordered'),
(2, 'Awaiting Delivery'),
(3, 'Dispatched');

-- --------------------------------------------------------

--
-- Table structure for table `orderSuppliers`
--

CREATE TABLE IF NOT EXISTS `orderSuppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `orderSuppliers`
--

INSERT INTO `orderSuppliers` (`id`, `friendlyName`) VALUES
(1, 'Default Supplier');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderno` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `reference` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(11) NOT NULL,
  `enteredby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `orderedfor` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `authby` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `supplier` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `enduser` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `containslicenses` tinyint(4) NOT NULL,
  `products` text COLLATE utf8_unicode_ci NOT NULL,
  `cost` float NOT NULL,
  `status` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(4) NOT NULL,
  `cancelled` tinyint(4) NOT NULL,
  `nominalcode` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  `confirmed` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `orders`
--


-- --------------------------------------------------------

--
-- Table structure for table `ordersCanAuthorize`
--

CREATE TABLE IF NOT EXISTS `ordersCanAuthorize` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `ordersCanAuthorize`
--

INSERT INTO `ordersCanAuthorize` (`id`, `friendlyName`) VALUES
(1, 'The Boss');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `defaultKey` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `products`
--


-- --------------------------------------------------------

--
-- Table structure for table `userAccounts`
--

CREATE TABLE IF NOT EXISTS `userAccounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `passwordHash` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `firstName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `lastName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `displayName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `emailAddress` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `created` int(11) NOT NULL,
  `lastSeen` int(11) NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `isAdmin` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `userAccounts`
--

INSERT INTO `userAccounts` (`id`, `username`, `passwordHash`, `firstName`, `lastName`, `displayName`, `emailAddress`, `created`, `lastSeen`, `enabled`, `isAdmin`) VALUES
(1, 'administrator', '9f8ccf9afb8dd57c0a4e87cd8dbff31b', 'Administrator', '', 'Administrator', 'admin@youremail.com', 1332006906, 1337864711, 1, 1),
(2, 'test', '7ee363afd403529d3597f7a53bddead9', 'Test', '', 'Test Account', 'test@youremail.com', 1332006906, 1337675948, 1, 0);

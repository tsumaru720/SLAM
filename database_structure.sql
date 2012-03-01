-- phpMyAdmin SQL Dump
-- version 3.3.2deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 01, 2012 at 10:21 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.9

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `computerChangeLog`
--

INSERT INTO `computerChangeLog` (`id`, `computerid`, `changedby`, `field`, `old`, `new`, `date`) VALUES
(1, 1, 'Bob Jones', 'License', '', 'Added Microsoft Office 2010 Standard License', 1330597194);

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `computers`
--

INSERT INTO `computers` (`id`, `serial`, `name`, `location`, `maintenanceFee`, `createdby`, `modifiedby`, `created`, `modified`, `comments`, `active`) VALUES
(1, 'AB123456789', 'TESTBOX', '1', 5.5, 'Bob Jones', 'Bob Jones', 1330597095, 1330597095, 'THIS PC IS A TEST PC', 1);

-- --------------------------------------------------------

--
-- Table structure for table `configuration`
--

CREATE TABLE IF NOT EXISTS `configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `configuration`
--

INSERT INTO `configuration` (`id`, `name`, `friendlyName`, `value`) VALUES
(1, 'defaultMaintenanceFee', 'Default Maintenance Cost', '5.5');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `licenses`
--

INSERT INTO `licenses` (`id`, `computerid`, `orderid`, `product`, `license`) VALUES
(1, 1, 1, 1, 'AAA-AAA-AAA-AAA');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `friendlyName`, `corporate`, `hidden`) VALUES
(1, 'Head Office', 1, 0),
(2, 'Remote Location', 1, 0);

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `orderChangeLog`
--

INSERT INTO `orderChangeLog` (`id`, `orderid`, `changedby`, `field`, `old`, `new`, `date`) VALUES
(1, 1, 'Bob Jones', 'Status', 'Ordered', 'Complete', 1330597176),
(2, 1, 'Bob Jones', 'License', '', 'Added 1 Licenses', 1330597182);

-- --------------------------------------------------------

--
-- Table structure for table `orderStatuses`
--

CREATE TABLE IF NOT EXISTS `orderStatuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `orderSuppliers`
--

INSERT INTO `orderSuppliers` (`id`, `friendlyName`) VALUES
(1, 'Insight'),
(2, 'Microsoft'),
(3, 'Misco');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `orderno`, `reference`, `date`, `enteredby`, `orderedfor`, `authby`, `supplier`, `enduser`, `containslicenses`, `products`, `cost`, `status`, `completed`, `cancelled`, `nominalcode`, `comments`, `confirmed`) VALUES
(1, '300101', 'WB102786', 1330597149, 'Bob Jones', 'Boss Man', 'Jane Doe', 'Microsoft', 'Boss Man', 1, '1x Office for Boss Man', 199.79, 'Complete', 1, 0, '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ordersCanAuthorize`
--

CREATE TABLE IF NOT EXISTS `ordersCanAuthorize` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `ordersCanAuthorize`
--

INSERT INTO `ordersCanAuthorize` (`id`, `friendlyName`) VALUES
(1, 'Joe Blogs'),
(2, 'Jane Doe'),
(3, 'Fred Smith');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendlyName` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `defaultKey` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `friendlyName`, `defaultKey`) VALUES
(1, 'Microsoft Office 2010 Standard', 'AAA-AAA-AAA-AAA'),
(2, 'Microsoft Visio 2010 Retail', '');

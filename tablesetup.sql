SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `facebook_users`
--

CREATE TABLE IF NOT EXISTS `facebook_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sw_uid` int(11) NOT NULL,
  `session` varchar(5000) NOT NULL,
  `session_key` varchar(80) NOT NULL,
  `uid` varchar(80) NOT NULL,
  `expires` varchar(80) NOT NULL,
  `secret` varchar(80) NOT NULL,
  `access_token` varchar(120) NOT NULL,
  `sig` varchar(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `linkcache`
--

CREATE TABLE IF NOT EXISTS `linkcache` (
  `url` varchar(255) NOT NULL,
  `replacetext` varchar(20000) NOT NULL,
  `wholeblock` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sw_users`
--

CREATE TABLE IF NOT EXISTS `sw_users` (
  `sw_uid` int(11) NOT NULL AUTO_INCREMENT,
  `secret` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `columns` varchar(1000) DEFAULT NULL,
  `colsperscreen` smallint(6) NOT NULL DEFAULT '3',
  `posttoservices` varchar(1000) DEFAULT NULL,
  `theme` varchar(50) NOT NULL DEFAULT 'default',
  `blocklist` varchar(1000) DEFAULT NULL,
  `utcoffset` varchar(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`sw_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `twitter_users`
--

CREATE TABLE IF NOT EXISTS `twitter_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sw_uid` int(11) NOT NULL,
  `uid` varchar(80) NOT NULL,
  `username` varchar(80) NOT NULL,
  `access_token` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

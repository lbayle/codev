
-- this script is to be executed to update CodevTT DB v12 to v13.

-- -----------------

-- create WBS table

-- TODO


-- --------------------------------------------------------

--
-- Structure de la table `codev_wbselement_table`
--

CREATE TABLE IF NOT EXISTS `codev_wbselement_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) default NULL,
  `icon` varchar(64) default NULL,
  `font` varchar(64) default NULL,
  `color` varchar(64) default NULL,
  `bug_id` int(11) default NULL,
  `parent_id` int(11) default NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `bug_id` (`bug_id`),
  KEY `parent_id` (`parent_id`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

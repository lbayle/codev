
--
-- Structure de la table `codev_uo_table`
--

CREATE TABLE IF NOT EXISTS `codev_uo_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timetrackid` int(11) NOT NULL,
  `value` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`timetrackid`) REFERENCES codev_timetracking_table(`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `codev_timetrack_note_table`
--

CREATE TABLE IF NOT EXISTS `codev_timetrack_note_table` (
  `timetrackid` int(11) NOT NULL,
  `noteid` int(11) NOT NULL,
  PRIMARY KEY (`timetrackid`, `noteid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


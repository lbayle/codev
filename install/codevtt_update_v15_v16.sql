
-- this script is to be executed to update CodevTT DB v15 to v16.
-- CodevTT v1.1.0 -> v1.2.0


-- --------------------------------------------------------

--
-- Table structure for table `codev_timetrack_note_table`
--

CREATE TABLE IF NOT EXISTS `codev_timetrack_note_table` (
  `timetrackid` int(11) NOT NULL,
  `noteid` int(11) NOT NULL,
  PRIMARY KEY (`timetrackid`, `noteid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- activate all new plugins
UPDATE `codev_plugin_table` SET `status`='1' WHERE `status`='0';

-- tag version
UPDATE `codev_config_table` SET `value`='16' WHERE `config_id`='database_version';

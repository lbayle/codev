
-- this script is to be executed to update CodevTT DB v13 to v14.


-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--

CREATE TABLE IF NOT EXISTS `codev_plugin_table` (
  `name` varchar(64) NOT NULL,
  `status` int(11) NOT NULL default 0,
  `domains` varchar(250) NOT NULL,
  `categories` varchar(250) NOT NULL,
  `version` varchar(10) NOT NULL,
  `description` varchar(250) default NULL,
  PRIMARY KEY  (`name`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

INSERT INTO `codev_plugin_table` (`name`, `status`, `domains`, `categories`, `version`, `description`) VALUES
('BudgetDriftHistoryIndicator2', 1, 'Command,CommandSet', 'Roadmap', '1.0.0', 'Display the budget history'),
('EffortEstimReliabilityIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the EffortEstim reliability rate history<br>rate = EffortEstim / elapsed (on resolved tasks only)'),
('HelloWorldIndicator', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'A simple HelloWorld plugin'),
('LoadPerJobIndicator2', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Check all the timetracks of the period and return their repartition per Job'),
('LoadPerProjCategoryIndicator', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per project categories'),
('LoadPerUserIndicator', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Check all the timetracks of the period and return their repartition per User'),
('ProgressHistoryIndicator2', 1, 'Command,CommandSet', 'Roadmap', '1.0.0', 'Display the progress history'),
('ReopenedRateIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display the bug reopened rate history'),
('StatusHistoryIndicator2', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Display Issue Status history');

-- tag version
UPDATE `codev_config_table` SET `value`='14' WHERE `config_id`='database_version';


-- this script is to be executed to update CodevTT DB v13 to v14.


-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--

CREATE TABLE IF NOT EXISTS `codev_plugin_table` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `pathname` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `properties` varchar(100) default NULL,
  `description` varchar(100) default NULL,
  `type` int(11) NOT NULL,
  `sub_type` int(11) NOT NULL default 0,
  `enabled` int(11) NOT NULL default 0,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)

) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `codev_plugin_table` (`id`, `pathname`, `name`) VALUES
(1, 'ActivityIndicator', 'Activity indicator plugin'),
(2, 'BacklogVariationIndicator', 'Backlog variation indicator plugin'),
(3, 'BudgetDriftHistoryIndicator', 'Budget drift history indicator plugin'),
(4, 'DaysPerJobIndicator', 'Days per job indicator plugin'),
(5, 'DetailedChargesIndicator', 'Detailed charges indicator plugin'),
(6, 'EffortEstimReliabilityIndicator', 'Effort estim reliability indicator plugin'),
(7, 'ProgressHistoryIndicator', 'Progress history indicator plugin'),
(8, 'ReopenedRateIndicator', 'Reopened rate indicator plugin')
(9, 'StatusHistoryIndicator', 'Status history indicator plugin');

-- tag version
UPDATE `codev_config_table` SET `value`='14' WHERE `config_id`='database_version';


-- this script is to be executed to update CodevTT DB v17 to v18.


-- --------------------------------------------------------

--
-- Structure de la table `codev_user_dailyrate_table`
--
CREATE TABLE IF NOT EXISTS `codev_userdailycost_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `start_date` int(11) unsigned NOT NULL,
  `daily_rate` int(11) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL default 'EUR',
  `description` varchar(250) default NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_team_date` (`user_id`,`team_id`, `start_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Structure de la table `codev_currencies_table`
--
CREATE TABLE IF NOT EXISTS `codev_currencies_table` (
  `currency` varchar(3) NOT NULL,
  `coef` int(11) unsigned NOT NULL,
  PRIMARY KEY (`currency`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES ('EUR', 1000000); -- 1.0
INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES ('USD',  930709); -- 0.930709
INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES ('GBP', 1153988); -- 1.153988
INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES ('CNY',  134703); -- 0.134703
INSERT INTO `codev_currencies_table` (`currency`, `coef`) VALUES ('INR',   14125); -- 0.014125


-- ALTER TABLE `codev_timetracking_table` ADD `cost` int(11) unsigned DEFAULT NULL AFTER `commit_date`;
-- ALTER TABLE `codev_timetracking_table` ADD `currency` varchar(3) default NULL AFTER `cost`;

ALTER TABLE `codev_team_table` ADD `average_daily_cost` int(11) default NULL AFTER `date`;
ALTER TABLE `codev_team_table` ADD `currency` varchar(3) NOT NULL default 'EUR' AFTER `average_daily_cost`;

ALTER TABLE codev_wbs_table CHANGE `order` `wbs_order` int(11) NOT NULL;

-- tag version
UPDATE `codev_config_table` SET `value`='18' WHERE `config_id`='database_version';

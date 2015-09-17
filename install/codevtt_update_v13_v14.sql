
-- this script is to be executed to update CodevTT DB v13 to v14.


-- --------------------------------------------------------

--
-- Structure de la table `codev_plugin_table`
--
ALTER TABLE `codev_timetracking_table` ADD `committer_id` int(11) DEFAULT NULL AFTER `duration`;
ALTER TABLE `codev_timetracking_table` ADD `commit_date` int(11) DEFAULT NULL AFTER `committer_id`;

-- tag version
UPDATE `codev_config_table` SET `value`='14' WHERE `config_id`='database_version';

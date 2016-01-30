
-- this script is to be executed to update CodevTT DB v14 to v15.


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
('AvailableWorkforceIndicator', 1, 'Team', 'Planning', '1.0.0', 'Man-days available in period, except leaves and external tasks'),
('BacklogPerUserIndicator', 1, 'Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Répartition du RAF des tâches de la sélection par utilisateur'),
('BudgetDriftHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche la dérive Budget'),
('DeadlineAlertIndicator', 1, 'User,Team,Project,Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche les tâches ayant du être livrées (date jalon dépassée)'),
('DriftAlertIndicator', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Risk', '1.0.0', 'Tâches dont le consommé est supérieur à la charge initiale'),
('EffortEstimReliabilityIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Taux de fiabilité de la Charge estimée (ChargeMgr, ChargeInit)<br>Taux = charge estimée / consommé (tâches résolues uniquement)'),
('HelloWorldIndicator', 0, 'Command,Team,User,Project,CommandSet,ServiceContract,Admin', 'Quality', '1.0.0', 'un plugin exemple pour développeurs'),
('IssueBacklogVariationIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Affiche l''évolution du RAF dans le temps (burndown chart)'),
('LoadPerJobIndicator2', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Affiche le consommé sur la période, réparti par poste'),
('LoadPerProjCategoryIndicator', 1, 'Team,Project,User,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par catégorie projet'),
('LoadPerProjectIndicator', 1, 'User,Team,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par projet'),
('LoadPerUserIndicator', 1, 'Task,Team,User,Project,Command,CommandSet,ServiceContract', 'Activity', '1.0.0', 'Affiche le consommé sur la période, réparti par utilisateur'),
('ProgressHistoryIndicator2', 1, 'Command,CommandSet,ServiceContract', 'Roadmap', '1.0.0', 'Affiche la progression de l''avancement dans le temps'),
('ReopenedRateIndicator2', 1, 'Team,Project,Command,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Historique du nombre de fiches ayant été réouvertes'),
('StatusHistoryIndicator2', 1, 'Command,Team,User,Project,CommandSet,ServiceContract', 'Quality', '1.0.0', 'Affiche l''évolution de la répartition des tâches par statut'),
('TimePerStatusIndicator', 1, 'Task', 'Roadmap', '1.0.0', 'Répartition du temps par status'),
('TimetrackDetailsIndicator', 1, 'Admin', 'Admin', '1.0.0', 'Affiche des informations suplémentaires sur les imputations');

-- tag version
UPDATE `codev_config_table` SET `value`='15' WHERE `config_id`='database_version';

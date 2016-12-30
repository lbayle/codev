--
-- Index pour la table `mantis_bug_history_table`
--
ALTER TABLE `mantis_bug_history_table`
  ADD KEY `idx_bug_history_field_name` (`field_name`);


-- Query	SELECT * FROM `mantis_bug_history_table` WHERE field_name = (SELECT name FROM `mantis_custom_field_table` WHERE id = 7) AND bug_id = '8450' AND date_modified <= '1458514800' ORDER BY date_modified DESC LIMIT 1








<?php
/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

class CommandTools {

   /**
    * @param Command $command
    * @return mixed[]
    */
   private static function getCommandIssues(Command $command) {
      $issueArray = array();

      $issues = $command->getIssueSelection()->getIssueList();
      foreach ($issues as $id => $issue) {
         $driftMgr = $issue->getDriftMgr();
         $driftMgrColor = $issue->getDriftColor($driftMgr);
         $drift = $issue->getDrift();
         $driftColor = $issue->getDriftColor($drift);

         $user = UserCache::getInstance()->getUser($issue->getHandlerId());

         $issueArray[$id] = array(
            "mantisLink" => Tools::mantisIssueURL($issue->getId(), NULL, TRUE),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->getId())),
            "extRef" => $issue->getTcId(),
            "project" => $issue->getProjectName(),
            "category" => $issue->getCategoryName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "progress" => round(100 * $issue->getProgress()),
            "effortEstim" => $issue->getEffortEstim(),
            "mgrEffortEstim" => $issue->getMgrEffortEstim(),
            "elapsed" => $issue->getElapsed(),
            "driftMgr" => $driftMgr,
            "driftMgrColor" => $driftMgrColor,
            "drift" => $drift,
            "driftColor" => $driftColor,
            "duration" => $issue->getDuration(),
            "summary" => $issue->getSummary(),
            "type" => $issue->getType(),
            "handlerName" => $user->getName()
         );
      }
      return $issueArray;
   }

   /**
    * @param Command $cmd
    * @return mixed[]
    */
   private static function getParentCommandSets(Command $cmd) {
      $commandsets = array();

      $cmdsetList = $cmd->getCommandSetList();

      // TODO return URL for 'name' ?

      foreach ($cmdsetList as $cmdset) {
         $commandsets[$cmdset->getId()] = $cmdset->getName();
      }
      return $commandsets;
   }

   /**
    * @param Command $command
    * @return mixed[]
    */
   public static function getCommandStateList(Command $command = NULL) {
      $cmdState = (is_null($command)) ? 0 : $command->getState();
      return SmartyTools::getSmartyArray(Command::$stateNames, $cmdState);
   }


   /**
    * @param Command $command
    * @return mixed[]
    */
   private static function getProvisionList(Command $command, int $type = NULL) {
      $provArray = array();

      $provisions = $command->getProvisionList($type);
      foreach ($provisions as $id => $prov) {

         $formatedSummary = str_replace("'", "\'", $prov->getSummary());
         $formatedSummary = str_replace('"', "\'", $formatedSummary);

         $provArray["$id"] = array(
            'id' => $id,
            'date' => date("Y-m-d", $prov->getDate()),
            'type' => CommandProvision::$provisionNames[$prov->getType()],
            'type_id' => $prov->getType(),
            'budget_days' => $prov->getProvisionDays(),
            'budget' => $prov->getProvisionBudget(),
            'average_daily_rate' => $prov->getAverageDailyRate(),
            'currency' => $prov->getCurrency(),
            'summary' => $formatedSummary,
            'isInCheckBudget' => $prov->isInCheckBudget()
         );
      }
      return $provArray;
   }

   /**
    * @param Command $command
    * @return mixed[]
    */
   private static function getProvisionTotalList(Command $command, $teamCurrency, int $type = NULL) {

      $provTotalArray =  NULL;

      // compute data
      $provisions = $command->getProvisionList($type);

      if (!empty($provisions)) {

        foreach ($provisions as $id => $prov) {

            // a provision
            $type = CommandProvision::$provisionNames[$prov->getType()];
            $budget_days = $prov->getProvisionDays();
            $budget = $prov->getProvisionBudget($teamCurrency);

            // compute total per category
            $provDaysTotalArray["$type"] += $budget_days;
            $provBudgetTotalArray["$type"] += $budget;

            // compute total for all categories
            $globalDaysTotal += $budget_days;
            $globalBudgetTotal += $budget;
        }
        // prepare for the view
        $provTotalArray = array();
        foreach($provDaysTotalArray as $type => $daysPerType) {

           $provTotalArray[$type] = array(
              'type' => $type,
              'budget_days' => $daysPerType,
              'budget' => sprintf("%01.2f", $provBudgetTotalArray[$type]),
              'currency' => $teamCurrency,
           );
        }
        $provTotalArray['TOTAL'] = array(
           'type' => 'TOTAL',
           'budget_days' => $globalDaysTotal,
           'budget' => sprintf("%01.2f", $globalBudgetTotal),
           'currency' => $teamCurrency,
         );
      }
      return $provTotalArray;
   }



   /**
    * code factorisation
    *
    * returns the input params for some indicators.
    *
    * @param Command $cmd
    * @return array [startTimestamp, endTimestamp, interval]
    */
   private static function computeTimestampsAndInterval(Command $cmd) {

      $cmdIssueSel = $cmd->getIssueSelection();

      $startTT = $cmdIssueSel->getFirstTimetrack();
      if ((NULL != $startTT) && (0 != $startTT->getDate())) {
         $startTimestamp = $startTT->getDate();
      } else {
         $startTimestamp = $cmd->getStartDate();
         #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
         if (0 == $startTimestamp) {
            $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
            $startTimestamp = $team->getDate();
            #echo "team Date ".date("Y-m-d", $startTimestamp).'<br>';
         }
      }

      // endTimestamp = max(latest_timetrack, latest_update)
      $latestTrack = $cmdIssueSel->getLatestTimetrack();
      $latestTrackTimestamp = (!is_null($latestTrack)) ? $latestTrack->getDate() : 0;
      $lastUpdatedTimestamp = $cmdIssueSel->getLastUpdated();
      $endTimestamp = max(array($latestTrackTimestamp, $lastUpdatedTimestamp));
      #echo "getLatestTimetrack = ".date('Y-m-d', $latestTrackTimestamp)." getLastUpdated = ".date('Y-m-d', $lastUpdatedTimestamp).' endDate = '.date('Y-m-d', $endTimestamp).'<br>';

      if (0 == $endTimestamp) {
         $endTimestamp = $startTimestamp;
      }

      // Calculate a nice day interval
      $nbWeeks = ($endTimestamp - $startTimestamp) / 60 / 60 / 24;
      $interval = ceil($nbWeeks / 20);

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => $interval
      );

      return $params;
   }

   /**
    * compute budget indicator values
    * @param Command $cmd
    * @return array of smarty variables
    */
   public static function getBudgetIndicatorValues(Command $cmd) {

      $mgrEE = $cmd->getIssueSelection()->mgrEffortEstim;
      $cmdProvAndMeeDays = $mgrEE + $cmd->getProvisionDays(TRUE);

      $cmdTotalReestimated = $cmd->getIssueSelection()->getReestimated();

      $cmdTotalDrift = $cmdTotalReestimated - $cmdProvAndMeeDays;
      $cmdTotalDriftPercent = (0 == $cmdProvAndMeeDays) ? 0 : round( ($cmdTotalDrift * 100 / $cmdProvAndMeeDays) , 2);

      $cmdTotalDriftColor = ($cmdTotalDrift >= 0) ? "fcbdbd" : "bdfcbd";
      $cmdTotalReestimatedColor = ($cmdTotalReestimated > $cmdProvAndMeeDays) ? "fcbdbd" : "bdfcbd";

      $budgetValues = array(
          'cmdProvAndMeeDays'       => round($cmdProvAndMeeDays, 2),
          'cmdTotalReestimated'     => round($cmdTotalReestimated, 2),
          'cmdTotalDrift'           => round($cmdTotalDrift, 2),
          'cmdTotalDriftPercent'    => $cmdTotalDriftPercent,
          'cmdTotalDriftColor'           => $cmdTotalDriftColor,
          'cmdTotalReestimatedColor'     => $cmdTotalReestimatedColor,
      );
      return $budgetValues;
   }

   /**
    * @param SmartyHelper $smartyHelper
    * @param Command $cmd
    */
   public static function displayCommand(SmartyHelper $smartyHelper, Command $cmd, $isManager, $teamid) {


      $smartyHelper->assign('cmdid', $cmd->getId());
      $smartyHelper->assign('cmdName', $cmd->getName());
      $smartyHelper->assign('cmdReference', $cmd->getReference());
      $smartyHelper->assign('cmdVersion', $cmd->getVersion());
      $smartyHelper->assign('cmdReporter', $cmd->getReporter());
      $smartyHelper->assign('cmdStateList', self::getCommandStateList($cmd));
      $smartyHelper->assign('cmdState', Command::$stateNames[$cmd->getState()]);

      // DEPRECATED, see UserDailyCost
      if (0 != $cmd->getAverageDailyRate()) {
         $smartyHelper->assign('cmdAverageDailyRate', $cmd->getAverageDailyRate());
         $smartyHelper->assign('cmdCurrency', $cmd->getCurrency());
      }

      if (!is_null($cmd->getStartDate())) {
         $smartyHelper->assign('cmdStartDate', date("Y-m-d", $cmd->getStartDate()));
      }
      if (!is_null($cmd->getDeadline())) {
         $smartyHelper->assign('cmdDeadline', date("Y-m-d", $cmd->getDeadline()));
      }
      $smartyHelper->assign('cmdDesc', $cmd->getDesc());

      $team = TeamCache::getInstance()->getTeam($teamid);
      $teamCurrency = $team->getTeamCurrency();
      $smartyHelper->assign('cmdProvisionList', self::getProvisionList($cmd));
      $smartyHelper->assign('cmdProvisionTypeMngt', CommandProvision::provision_mngt);
      $smartyHelper->assign('cmdProvisionTotalList', self::getProvisionTotalList($cmd, $teamCurrency));

      $cmdTotalSoldDays = $cmd->getTotalSoldDays();
      $smartyHelper->assign('cmdTotalSoldDays', $cmdTotalSoldDays);

      // budget
      $budgetIndicSmartyValues = self::getBudgetIndicatorValues($cmd);
      foreach ($budgetIndicSmartyValues as $smartyKey => $value) {
         $smartyHelper->assign($smartyKey, $value);
      }

      // set CommandSets I belong to
      $smartyHelper->assign('parentCmdSets', self::getParentCommandSets($cmd));

      // set task list
      $cmdIssueSel = $cmd->getIssueSelection();
      $smartyHelper->assign('cmdNbIssues', $cmdIssueSel->getNbIssues());
      $smartyHelper->assign('cmdIssues', self::getCommandIssues($cmd));

      // used to create mantis link to view_all_bug_page.php:
      // view_all_set.php?type=1&temporary=y&FilterBugList_list=5079,5073,5108,5107,49396,5006
      #$mantisFilterBugList= implode(',', array_keys($cmdIssueSel->getIssueList()));
      #$smartyHelper->assign('mantisFilterBugList', $mantisFilterBugList);

   }

   /**
    *
    * @param SmartyHelper $smartyHelper
    * @param Command $cmd
    * @param int $userid
    */
   public static function dashboardSettings(SmartyHelper $smartyHelper, Command $cmd, $userid) {

      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $cmd->getIssueSelection());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $cmd->getTeamid());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_PROVISION_DAYS, $cmd->getProvisionDays());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $userid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_COMMAND_ID, $cmd->getId());

      $params = self::computeTimestampsAndInterval($cmd);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $params['startTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $params['endTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $params['interval']);

      $dashboardName = 'Command'.$cmd->getId();
      $dashboardDomain = IndicatorPluginInterface::DOMAIN_COMMAND;

      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_DOMAIN, $dashboardDomain);

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard($dashboardName);
      $dashboard->setDomain($dashboardDomain);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
          IndicatorPluginInterface::CATEGORY_FINANCIAL,
          IndicatorPluginInterface::CATEGORY_IMPORT,
         ));
      $dashboard->setTeamid($cmd->getTeamid());
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
   }
   // </editor-fold>
}

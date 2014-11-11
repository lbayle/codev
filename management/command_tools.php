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
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "progress" => round(100 * $issue->getProgress()),
            "effortEstim" => $issue->getEffortEstim() + $issue->getEffortAdd(),
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
            'date' => date(T_("Y-m-d"), $prov->getDate()),
            'type' => CommandProvision::$provisionNames[$prov->getType()],
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
   private static function getProvisionTotalList(Command $command, int $type = NULL) {

      $provTotalArray =  NULL;
      
      // compute data
      $provisions = $command->getProvisionList($type);
      
      if (!empty($provisions)) {
          
        foreach ($provisions as $id => $prov) {

            // a provision
            $type = CommandProvision::$provisionNames[$prov->getType()];
            $budget_days = $prov->getProvisionDays();
            $budget = $prov->getProvisionBudget();

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
              'budget' => $provBudgetTotalArray[$type],
           );
        }
        $provTotalArray['TOTAL'
            ] = array(
             'type' => 'TOTAL',
             'budget_days' => $globalDaysTotal,
             'budget' => $globalBudgetTotal,
         );
      }
      return $provTotalArray;
   }


   /**
    * get all internal bugs of the command
    *
    * @param Command $cmd
    * @return IssueSelection
    */
   private static function filterInternalBugs(Command $cmd) {

      $cmdSel = $cmd->getIssueSelection();

      // Filter only BUGS
      $bugFilter = new IssueCodevTypeFilter('bugFilter');
      $bugFilter->addFilterCriteria(IssueCodevTypeFilter::tag_Bug);
      $outputList = $bugFilter->execute($cmdSel);

      if (empty($outputList)) {
         #echo "TYPE Bug not found !<br>";
         return NULL;
      }
      $bugSel = $outputList[IssueCodevTypeFilter::tag_Bug];

      // Filter only NoExtRef
      $extIdFilter = new IssueExtIdFilter('extIdFilter');
      $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_no_extRef);
      $outputList2 = $extIdFilter->execute($bugSel);

      if (empty($outputList2)) {
         #echo "noExtRef not found !<br>";
         return NULL;
      }
      $issueSel = $outputList2[IssueExtIdFilter::tag_no_extRef];

      return $issueSel;
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
    * @static
    * @param Command $cmd
    * @return mixed[]
    */
   public static function getProgressHistory(Command $cmd) {
      $cmdIssueSel = $cmd->getIssueSelection();

      $params = self::computeTimestampsAndInterval($cmd);

      $indicator = new ProgressHistoryIndicator();
      $indicator->execute($cmdIssueSel, $params);

      $smartyVariables = $indicator->getSmartyObject();

      return $smartyVariables;
   }

   /**
    * return smartyVariables for BudgetDriftHistoryIndicator
    *
    * @static
    * @param Command $cmd
    * @return array smartyVariables
    */
   public static function getBudgetDriftHistoryIndicator(Command $cmd) {
      $cmdIssueSel = $cmd->getIssueSelection();

      $params = self::computeTimestampsAndInterval($cmd);
      $params['provisionDays'] = $cmd->getProvisionDays(TRUE);

      $indicator = new BudgetDriftHistoryIndicator();
      $indicator->execute($cmdIssueSel, $params);

      $smartyVariables = $indicator->getSmartyObject();

      return $smartyVariables;
   }

   /**
    * return smartyVariables for BudgetDriftHistoryIndicator
    *
    * @static
    * @param Command $cmd
    * @return array smartyVariables
    */
   public static function getReopenedRateIndicator(Command $cmd) {
      $cmdIssueSel = $cmd->getIssueSelection();

      $params = self::computeTimestampsAndInterval($cmd);

      $indicator = new ReopenedRateIndicator();
      $indicator->execute($cmdIssueSel, $params);

      $smartyVariables = $indicator->getSmartyObject();

      return $smartyVariables;
   }

   public static function getDetailedCharges(Command $cmd, $isManager, $selectedFilters) {

      $issueSel = $cmd->getIssueSelection();

      $allFilters = "ProjectFilter,ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

      $params = array(
         'isManager' => $isManager,
         'teamid' => $cmd->getTeamid(),
         'selectedFilters' => $selectedFilters,
         'allFilters' => $allFilters,
         'maxTooltipsPerPage' => Constants::$maxTooltipsPerPage
      );


      $detailedChargesIndicator = new DetailedChargesIndicator();
      $detailedChargesIndicator->execute($issueSel, $params);

      $smartyVariable = $detailedChargesIndicator->getSmartyObject();
      $smartyVariable['selectFiltersSrcId'] = $cmd->getId();

      return $smartyVariable;
   }

   public static function getStatusHistory(Command $cmd, $interval = 7) {

      $issueSel = $cmd->getIssueSelection();

      $startTT = $issueSel->getFirstTimetrack();
      if ((NULL != $startTT) && (0 != $startTT->getDate())) {
         $startTimestamp = $startTT->getDate();
      } else {
         $startTimestamp = $cmd->getStartDate();
         if (0 == $startTimestamp) {
            $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
            $startTimestamp = $team->getDate();
         }
      }

      $endTimestamp =  time();

      #echo "cmd StartDate ".date("Y-m-d", $startTimestamp).'<br>';
      #echo "cmd EndDate ".date("Y-m-d", $endTimestamp).'<br>';

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => $interval
      );

      $statusHistoryIndicator = new StatusHistoryIndicator();
      $statusHistoryIndicator->execute($issueSel, $params);
      $smartyVariable = $statusHistoryIndicator->getSmartyObject();

      return $smartyVariable;
   }

   public static function getInternalBugsStatusHistory(Command $cmd, $interval = 7) {


      $issueSel = self::filterInternalBugs($cmd);
      if (is_null($issueSel)) {
         return array();
      }
      // -------

      $startTT = $issueSel->getFirstTimetrack();
      if ((!is_null($startTT)) && (0 != $startTT->getDate())) {
         $startTimestamp = $startTT->getDate();
      } else {
         $startTimestamp = $cmd->getStartDate();
         if (0 == $startTimestamp) {
            $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
            $startTimestamp = $team->getDate();
         }
      }

      $endTimestamp =  time();

      #echo "cmd StartDate ".date("Y-m-d", $startTimestamp).'<br>';
      #echo "cmd EndDate ".date("Y-m-d", $endTimestamp).'<br>';

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => $interval
      );

      $statusHistoryIndicator = new StatusHistoryIndicator();
      $statusHistoryIndicator->execute($issueSel, $params);
      $smartyVariable = $statusHistoryIndicator->getSmartyObject();

      return $smartyVariable;
   }

   /**
    * return smartyVariables for BudgetDriftHistoryIndicator
    *
    * @static
    * @param Command $cmd
    * @return array smartyVariables
    */
   public static function getInternalBugsReopenedRateIndicator(Command $cmd) {

      $issueSel = self::filterInternalBugs($cmd);
      if (is_null($issueSel)) {
         return array();
      }

      $params = self::computeTimestampsAndInterval($cmd);

      $indicator = new ReopenedRateIndicator();
      $indicator->execute($issueSel, $params);

      $smartyVariables = $indicator->getSmartyObject();

      return $smartyVariables;
   }


   /**
    * @param SmartyHelper $smartyHelper
    * @param Command $cmd
    */
   public static function displayCommand(SmartyHelper $smartyHelper, Command $cmd, $isManager, $selectedFilters='') {

      if ($isManager) {
         $smartyHelper->assign('isManager', true);
      }

      $smartyHelper->assign('cmdid', $cmd->getId());
      $smartyHelper->assign('cmdName', $cmd->getName());
      $smartyHelper->assign('cmdReference', $cmd->getReference());
      $smartyHelper->assign('cmdVersion', $cmd->getVersion());
      $smartyHelper->assign('cmdReporter', $cmd->getReporter());
      $smartyHelper->assign('cmdStateList', self::getCommandStateList($cmd));
      $smartyHelper->assign('cmdState', Command::$stateNames[$cmd->getState()]);
      $smartyHelper->assign('cmdAverageDailyRate', $cmd->getAverageDailyRate());
      $smartyHelper->assign('cmdCurrency', $cmd->getCurrency());
      if (!is_null($cmd->getStartDate())) {
         $smartyHelper->assign('cmdStartDate', date("Y-m-d", $cmd->getStartDate()));
      }
      if (!is_null($cmd->getDeadline())) {
         $smartyHelper->assign('cmdDeadline', date("Y-m-d", $cmd->getDeadline()));
      }
      $smartyHelper->assign('cmdDesc', $cmd->getDesc());

      $smartyHelper->assign('cmdProvisionList', self::getProvisionList($cmd));
      $smartyHelper->assign('cmdProvisionTypeMngt', CommandProvision::provision_mngt);
      $smartyHelper->assign('cmdProvisionTotalList', self::getProvisionTotalList($cmd));

      $cmdTotalSoldDays = $cmd->getTotalSoldDays();
      $smartyHelper->assign('cmdTotalSoldDays', $cmdTotalSoldDays);

      // --------------

      // TODO math should not be in here !
      $mgrEE = $cmd->getIssueSelection()->mgrEffortEstim;
      $cmdProvAndMeeCost = ($mgrEE * $cmd->getAverageDailyRate()) + $cmd->getProvisionBudget(TRUE);
      $smartyHelper->assign('cmdProvAndMeeCost', round($cmdProvAndMeeCost, 2));
      $cmdProvAndMeeDays = $mgrEE + $cmd->getProvisionDays(TRUE);
      $smartyHelper->assign('cmdProvAndMeeDays', round($cmdProvAndMeeDays, 2));


      // TODO math should not be in here !
      $cmdTotalReestimated = $cmd->getIssueSelection()->getReestimated();
      $smartyHelper->assign('cmdTotalReestimated',$cmdTotalReestimated);
      $cmdTotalReestimatedCost = $cmdTotalReestimated * $cmd->getAverageDailyRate();
      $smartyHelper->assign('cmdTotalReestimatedCost',$cmdTotalReestimatedCost);

      // TODO math should not be in here !
      #$cmdTotalElapsed = $cmd->getIssueSelection()->getElapsed();
      #$cmdOutlayCost = $cmdTotalElapsed * $cmd->getAverageDailyRate();
      #$smartyHelper->assign('cmdOutlayCost',$cmdOutlayCost);
      #$smartyHelper->assign('cmdTotalElapsed',$cmdTotalElapsed);

      // TODO math should not be in here !
      $cmdTotalDrift = round($cmdTotalReestimated - $cmdProvAndMeeDays, 2);
      $cmdTotalDriftCost = round($cmdTotalReestimatedCost - $cmdProvAndMeeCost, 2);

      $cmdTotalDriftPercent = (0 == $cmdProvAndMeeDays) ? 0 : round( ($cmdTotalDrift * 100 / $cmdProvAndMeeDays) , 2);
      $smartyHelper->assign('cmdTotalDrift',$cmdTotalDrift);
      $smartyHelper->assign('cmdTotalDriftCost',$cmdTotalDriftCost);
      $smartyHelper->assign('cmdTotalDriftPercent', $cmdTotalDriftPercent);

      #$color1 = ($cmdOutlayCost > $cmdProvAndMeeCost) ? "fcbdbd" : "bdfcbd";
      #$smartyHelper->assign('cmdOutlayCostColor', $color1);
      #$color2 = ($cmdTotalElapsed > $cmdProvAndMeeDays) ? "fcbdbd" : "bdfcbd";
      #$smartyHelper->assign('cmdTotalElapsedColor',$color2);

      $color3 = ($cmdTotalReestimated > $cmdProvAndMeeDays) ? "fcbdbd" : "bdfcbd";
      $smartyHelper->assign('cmdTotalReestimatedColor',$color3);
      $color4 = ($cmdTotalReestimatedCost > $cmdProvAndMeeCost) ? "fcbdbd" : "bdfcbd";
      $smartyHelper->assign('cmdTotalReestimatedCostColor',$color4);
      $color5 = ($cmdTotalDrift >= 0) ? "fcbdbd" : "bdfcbd";
      $smartyHelper->assign('cmdTotalDriftColor',$color5);

      // --------------

      // set CommandSets I belong to
      $smartyHelper->assign('parentCmdSets', self::getParentCommandSets($cmd));

      // set task list
      $cmdIssueSel = $cmd->getIssueSelection();
      $smartyHelper->assign('cmdNbIssues', $cmdIssueSel->getNbIssues());
      $smartyHelper->assign('cmdIssues', self::getCommandIssues($cmd));
      
      // used to create mantis link to view_all_bug_page.php:
      // view_all_set.php?type=1&temporary=y&FilterBugList_list=5079,5073,5108,5107,49396,5006
      $mantisFilterBugList= implode(',', array_keys($cmdIssueSel->getIssueList()));
      $smartyHelper->assign('mantisFilterBugList', $mantisFilterBugList);
      
      // --------------
      // Indicators & statistics

      // DetailedChargesIndicator
      $data = self::getDetailedCharges($cmd, $isManager, $selectedFilters);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

      // InternalBugsHistoryIndicator
      $data = CommandTools::getInternalBugsStatusHistory($cmd);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

      // InternalBugsReopenedRateIndicator
      $data = CommandTools::getInternalBugsReopenedRateIndicator($cmd);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
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

      $params = self::computeTimestampsAndInterval($cmd);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $params['startTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $params['endTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $params['interval']);

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard('Command');
      $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_COMMAND);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
         ));
      $dashboard->setTeamid($cmd->getTeamid());
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }
}

?>

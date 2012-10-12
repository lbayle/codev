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

         $issueArray[$id] = array(
            "mantisLink" => Tools::mantisIssueURL($issue->getId(), NULL, TRUE),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->getId())),
            "extRef" => $issue->getTcId(),
            "project" => $issue->getProjectName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "progress" => round(100 * $issue->getProgress()),
            "effortEstim" => $issue->getMgrEffortEstim(),
            "elapsed" => $issue->getElapsed(),
            "driftMgr" => $driftMgr,
            "driftMgrColor" => $driftMgrColor,
            "duration" => $issue->getDuration(),
            "summary" => $issue->getSummary()
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
   private static function getServiceContractStateList(Command $command = NULL) {
      $cmdState = (NULL == $command) ? 0 : $command->getState();
      return SmartyTools::getSmartyArray(Command::$stateNames, $cmdState);
   }

   /**
    * @static
    * @param Command $cmd
    * @return mixed[]
    */
   public static function getProgressHistory(Command $cmd) {
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

      $endTT = $cmdIssueSel->getLatestTimetrack();
      $endTimestamp = ((NULL != $endTT) && (0 != $endTT->getDate())) ? $endTT->getDate() : time();

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => 14
      );

      $progressIndicator = new ProgressHistoryIndicator();
      $progressIndicator->execute($cmdIssueSel, $params);

      return array($progressIndicator->getSmartyObject(),$startTimestamp,$endTimestamp);
   }

   /**
    * show users activity on the Command during the given period.
    *
    * if start & end dates not defined, the last month will be displayed.
    *
    * @param Command $cmd
    * @return string
    *
    */
   public static function getCommandActivity(Command $cmd, $startTimestamp = NULL, $endTimestamp = NULL) {
      $issueSel = $cmd->getIssueSelection();

      $month = date('m');
      $year = date('Y');

      if (!isset($startTimestamp)) {
         // The first day of the current month
         $startdate = Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year));
         $startTimestamp = Tools::date2timestamp($startdate);
      }
      if (!isset($endTimestamp)) {
         $nbDaysInMonth = date("t", $startTimestamp);
         $enddate = Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, $nbDaysInMonth, $year));
         $endTimestamp = Tools::date2timestamp($enddate);
      }

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'teamid' => $cmd->getTeamid()
      );

      $activityIndicator = new ActivityIndicator();
      $activityIndicator->execute($issueSel, $params);

      return array($activityIndicator->getSmartyObject(), $startTimestamp, $endTimestamp);
   }

   public static function getDetailedCharges(Command $cmd, $isManager, $selectedFilters) {

      $issueSel = $cmd->getIssueSelection();

      $allFilters = "ProjectFilter,ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

      $params = array(
         'isManager' => $isManager,
         'selectedFilters' => $selectedFilters,
         'allFilters' => $allFilters
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

      $cmdSel = $cmd->getIssueSelection();

      // Filter only BUGS
      $bugFilter = new IssueCodevTypeFilter('bugFilter');
      $bugFilter->addFilterCriteria(IssueCodevTypeFilter::tag_Bug);
      $outputList = $bugFilter->execute($cmdSel);

      if (empty($outputList)) {
         echo "TYPE Bug not found !<br>";
         return array();
      }
      $bugSel = $outputList[IssueCodevTypeFilter::tag_Bug];

      // Filter only NoExtRef
      $extIdFilter = new IssueExtIdFilter('extIdFilter');
      $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_no_extRef);
      $outputList2 = $extIdFilter->execute($bugSel);

      if (empty($outputList2)) {
         echo "noExtRef not found !<br>";
         return array();
      }
      $issueSel = $outputList2[IssueExtIdFilter::tag_no_extRef];

      // -------

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

   /**
    * @param SmartyHelper $smartyHelper
    * @param Command $cmd
    */
   public static function displayCommand(SmartyHelper $smartyHelper, Command $cmd, $isManager, $selectedFilters='') {
      $smartyHelper->assign('cmdid', $cmd->getId());
      $smartyHelper->assign('cmdName', $cmd->getName());
      $smartyHelper->assign('cmdReference', $cmd->getReference());
      $smartyHelper->assign('cmdVersion', $cmd->getVersion());
      $smartyHelper->assign('cmdReporter', $cmd->getReporter());
      $smartyHelper->assign('cmdStateList', self::getServiceContractStateList($cmd));
      $smartyHelper->assign('cmdState', Command::$stateNames[$cmd->getState()]);
      $smartyHelper->assign('cmdCost', $cmd->getCost());
      $smartyHelper->assign('cmdCurrency', $cmd->getCurrency());
      $smartyHelper->assign('cmdBudgetDev', $cmd->getBudgetDev());
      $smartyHelper->assign('cmdBudgetMngt', $cmd->getBudgetMngt());
      $smartyHelper->assign('cmdBudgetGarantie', $cmd->getBudgetGarantie());
      $smartyHelper->assign('cmdBudgetTotal', $cmd->getBudgetDev() + $cmd->getBudgetMngt() + $cmd->getBudgetGarantie());
      $smartyHelper->assign('cmdStartDate', date("Y-m-d", $cmd->getStartDate()));
      $smartyHelper->assign('cmdDeadline', date("Y-m-d", $cmd->getDeadline()));
      $smartyHelper->assign('cmdAverageDailyRate', $cmd->getAverageDailyRate());
      $smartyHelper->assign('cmdDesc', $cmd->getDesc());

      // set CommandSets I belong to
      $smartyHelper->assign('parentCmdSets', self::getParentCommandSets($cmd));

      // set Issues that belong to me
      $cmdIssueSel = $cmd->getIssueSelection();
      $smartyHelper->assign('cmdNbIssues', $cmdIssueSel->getNbIssues());
      $smartyHelper->assign('cmdShortIssueList', $cmdIssueSel->getFormattedIssueList());

      $smartyHelper->assign('cmdIssues', self::getCommandIssues($cmd));

      // Indicators & statistics
      #$smartyHelper->assign('backlogHistoryGraph', getBacklogHistory($cmd));

      $data = self::getCommandActivity($cmd);
      $smartyHelper->assign('activityIndic_data', $data[0]);
      $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
      $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
      $smartyHelper->assign('workdays', Holidays::getInstance()->getWorkdays($data[1], $data[2]));

      // DetailedChargesIndicator
      $data = self::getDetailedCharges($cmd, $isManager, $selectedFilters);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

      // StatusHistoryIndicator

      //$data = CommandTools::getStatusHistory($cmd);
      $data = CommandTools::getInternalBugsStatusHistory($cmd);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }
}

?>

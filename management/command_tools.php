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

require_once ('remaining_history_indicator.class.php');
require_once ('elapsed_history_indicator.class.php');
require_once ('progress_historical_indicator.class.php');

/**
 *
 * @param Command $command
 * @return array
 */
function getCommandIssues($command) {

   $issueArray = array();

   $issues = $command->getIssueSelection()->getIssueList();
   foreach ($issues as $id => $issue) {

      $driftMgr = $issue->getDriftMgr();
      $driftMgrColor = $issue->getDriftColor($driftMgr);
      $formattedDriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $issueInfo = array();
      $issueInfo["mantisLink"] = mantisIssueURL($issue->bugId, NULL, true);
      $issueInfo["bugid"] = issueInfoURL(sprintf("%07d\n",   $issue->bugId));
      $issueInfo["extRef"] = $issue->getTC();
      $issueInfo["project"] = $issue->getProjectName();
      $issueInfo["target"] = $issue->getTargetVersion();
      $issueInfo["status"] = $issue->getCurrentStatusName();
      $issueInfo["progress"] = round(100 * $issue->getProgress());
      $issueInfo["effortEstim"] = $issue->mgrEffortEstim;
      $issueInfo["elapsed"] = $issue->getElapsed();
      $issueInfo["driftMgr"] = $driftMgr;
      $issueInfo["driftMgrColor"] = $formattedDriftMgrColor;
      $issueInfo["durationMgr"] = $issue->getDurationMgr();
      $issueInfo["summary"] = $issue->summary;

      $issueArray[$id] = $issueInfo;
   }
   return $issueArray;
}

/**
 *
 * @param Command $cmd
 * @param int $selectedCmdsetId
 * @return type 
 */
function getParentCommandSets($cmd) {

   $commandsets = array();

   $cmdsetList = $cmd->getCommandSetList();

   // TODO return URL for 'name' ?

   foreach ($cmdsetList as $id => $cmdsetName) {

      $cmdset = CommandSetCache::getInstance()->getCommandSet($id);
      $teamid = $cmdset->getTeamid();
      $team = TeamCache::getInstance()->getTeam($teamid);

      $commandsets[] = array(
         'id' => $id,
         'name' => $cmdsetName,
         'team' => $team->name
      );
   }
   return $commandsets;
}

/**
 *
 * @param type $command
 * @return type
 */
function getServiceContractStateList($command = NULL) {

   $stateList = NULL;
   $cmdState = (NULL == $command) ? 0 : $command->getState();

   foreach (Command::$stateNames as $id => $name) {
       $stateList[$id] = array('id'       => $id,
                            'name'     => $name,
                            'selected' => ($id == $cmdState)
       );
   }
   return $stateList;
}

function getBacklogHistory(Command $cmd) {

   $cmdIssueSel = $cmd->getIssueSelection();

   $startTT = $cmdIssueSel->getFirstTimetrack();
   if ((NULL != $startTT) && (0 != $startTT->date)) {
      $startTimestamp = $startTT->date;
   } else {
      $startTimestamp = $cmd->getStartDate();
      #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
      if (0 == $startTimestamp) {
         $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
         $startTimestamp = $team->date;
         #echo "team Date ".date("Y-m-d", $startTimestamp).'<br>';
      }
   }

   $endTT = $cmdIssueSel->getLatestTimetrack();
   $endTimestamp = ((NULL != $endTT) && (0 != $endTT->date)) ? $endTT->date : time();

   #echo "startTimestamp = ".date('Y-m-d', $startTimestamp)." endTimestamp = ".date('Y-m-d', $endTimestamp);

   $params = array('startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
                   'endTimestamp' => $endTimestamp,
                   'interval' => 14 );

   $backlogHistoryIndicator = new BacklogHistoryIndicator();
   $backlogData = $backlogHistoryIndicator->execute($cmdIssueSel, $params);

   $elapsedHistoryIndicator = new ElapsedHistoryIndicator();
   $elapsedData = $elapsedHistoryIndicator->execute($cmdIssueSel, $params);

   //
   $elapsedList = array();
   $backlogList = array();
   $bottomLabel = array();
   foreach ($backlogData as $timestamp => $backlog) {

      $backlogList[] = (NULL == $backlog) ? 0 : $backlog; // TODO
      #$elapsedList[]   = (NULL == $elapsedData[$timestamp]) ? 0 : $elapsedData[$timestamp]; // TODO
      $bottomLabel[]   = Tools::formatDate("%d %b", $timestamp);
   }

   foreach ($elapsedData as $timestamp => $elapsed) {

      $elapsedList[] = (NULL == $elapsed) ? 0 : $elapsed; // TODO
   }

   $strVal1 = implode(':', array_values($backlogList));
   $strVal2 = implode(':', array_values($elapsedList));

   #echo "strVal1 $strVal1<br>";
   $strBottomLabel = implode(':', $bottomLabel);

   $smartyData = Tools::SmartUrlEncode('title='.T_('Backlog history').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('Backlog').'&x1='.$strVal1.'&leg2='.T_('Elapsed').'&x2='.$strVal2);


   return $smartyData;
}

/**
 *
 * @param Command $cmd 
 */
function getProgressHistorical(Command $cmd) {

   $cmdIssueSel = $cmd->getIssueSelection();

   $startTT = $cmdIssueSel->getFirstTimetrack();
   if ((NULL != $startTT) && (0 != $startTT->date)) {
      $startTimestamp = $startTT->date;
   } else {
      $startTimestamp = $cmd->getStartDate();
      #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
      if (0 == $startTimestamp) {
         $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
         $startTimestamp = $team->date;
         #echo "team Date ".date("Y-m-d", $startTimestamp).'<br>';
      }
   }

   $endTT = $cmdIssueSel->getLatestTimetrack();
   $endTimestamp = ((NULL != $endTT) && (0 != $endTT->date)) ? $endTT->date : time();

   $params = array('startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
                   'endTimestamp' => $endTimestamp,
                   'interval' => 14 );

   // ---------------

   $progressIndicator = new ProgressHistoricalIndicator();
   $progressIndicator->execute($cmdIssueSel, $params);

   return $progressIndicator->getSmartyObject();
}


function displayCommand($smartyHelper, Command $cmd) {

   $smartyHelper->assign('cmdid', $cmd->getId());
   $smartyHelper->assign('cmdName', $cmd->getName());
   $smartyHelper->assign('cmdReference', $cmd->getReference());
   $smartyHelper->assign('cmdVersion', $cmd->getVersion());
   $smartyHelper->assign('cmdReporter', $cmd->getReporter());
   $smartyHelper->assign('cmdStateList', getServiceContractStateList($cmd));
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
   $parentCmdsets = getParentCommandSets($cmd);
   $smartyHelper->assign('parentCmdSets', $parentCmdsets);
   $smartyHelper->assign('nbParentCmdSets', count($parentCmdsets));


   // set Issues that belong to me
   $cmdIssueSel = $cmd->getIssueSelection();
   $cmdDetailedMgr = getIssueSelectionDetailedMgr($cmdIssueSel);
   $smartyHelper->assign('cmdDetailedMgr', $cmdDetailedMgr);
   $smartyHelper->assign('cmdNbIssues', $cmdIssueSel->getNbIssues());
   $smartyHelper->assign('cmdShortIssueList', $cmdIssueSel->getFormattedIssueList());

   $issueList = getCommandIssues($cmd);
   $smartyHelper->assign('cmdIssues', $issueList);

   // Indicators & statistics
   #$smartyHelper->assign('backlogHistoryGraph', getBacklogHistory($cmd));

   $smartyHelper->assign('jqplotTitle',      'Historical Progression Chart');
   $smartyHelper->assign('jqplotYaxisLabel', '% Progress');
   $smartyHelper->assign('jqplotData', getProgressHistorical($cmd));


}


?>

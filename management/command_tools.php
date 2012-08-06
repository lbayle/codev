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

require_once('indicator_plugins/progress_history_indicator.class.php');

include_once('classes/command.class.php');
include_once('classes/commandset_cache.class.php');
include_once('classes/team_cache.class.php');

require_once('smarty_tools.php');
require_once('tools.php');

/**
 * @param Command $command
 * @return mixed[]
 */
function getCommandIssues(Command $command) {
   $issueArray = array();

   $issues = $command->getIssueSelection()->getIssueList();
   foreach ($issues as $id => $issue) {
      $driftMgr = $issue->getDriftMgr();
      $driftMgrColor = $issue->getDriftColor($driftMgr);
      $formattedDriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $issueArray[$id] = array(
         "mantisLink" => Tools::mantisIssueURL($issue->bugId, NULL, true),
         "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->bugId)),
         "extRef" => $issue->getTC(),
         "project" => $issue->getProjectName(),
         "target" => $issue->getTargetVersion(),
         "status" => $issue->getCurrentStatusName(),
         "progress" => round(100 * $issue->getProgress()),
         "effortEstim" => $issue->mgrEffortEstim,
         "elapsed" => $issue->getElapsed(),
         "driftMgr" => $driftMgr,
         "driftMgrColor" => $formattedDriftMgrColor,
         "durationMgr" => $issue->getDurationMgr(),
         "summary" => $issue->summary
      );
   }
   return $issueArray;
}

/**
 * @param Command $cmd
 * @return mixed[]
 */
function getParentCommandSets(Command $cmd) {
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
function getServiceContractStateList(Command $command = NULL) {
   $cmdState = (NULL == $command) ? 0 : $command->getState();
   return SmartyTools::getSmartyArray(Command::$stateNames, $cmdState);
}

/**
 * @param Command $cmd
 * @return string
 */
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

   $elapsedList = array();
   $backlogList = array();
   $bottomLabel = array();
   foreach ($backlogData as $timestamp => $backlog) {
      $backlogList[] = (NULL == $backlog) ? 0 : $backlog; // TODO
      #$elapsedList[]   = (NULL == $elapsedData[$timestamp]) ? 0 : $elapsedData[$timestamp]; // TODO
      $bottomLabel[]   = Tools::formatDate("%d %b", $timestamp);
   }

   foreach ($elapsedData as $elapsed) {
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
 * @param Command $cmd
 * @return string
 */
function getProgressHistory(Command $cmd) {
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

   $params = array(
      'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
      'endTimestamp' => $endTimestamp,
      'interval' => 14
   );

   $progressIndicator = new ProgressHistoryIndicator();
   $progressIndicator->execute($cmdIssueSel, $params);

   return $progressIndicator->getSmartyObject();
}

/**
 * @param SmartyHelper $smartyHelper
 * @param Command $cmd
 */
function displayCommand(SmartyHelper $smartyHelper, Command $cmd) {
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
   $smartyHelper->assign('parentCmdSets', getParentCommandSets($cmd));

   // set Issues that belong to me
   $cmdIssueSel = $cmd->getIssueSelection();
   $smartyHelper->assign('cmdDetailedMgr', SmartyTools::getIssueSelectionDetailedMgr($cmdIssueSel));
   $smartyHelper->assign('cmdNbIssues', $cmdIssueSel->getNbIssues());
   $smartyHelper->assign('cmdShortIssueList', $cmdIssueSel->getFormattedIssueList());

   $smartyHelper->assign('cmdIssues', getCommandIssues($cmd));

   // Indicators & statistics
   #$smartyHelper->assign('backlogHistoryGraph', getBacklogHistory($cmd));

   $smartyHelper->assign('jqplotTitle', 'Historical Progression Chart');
   $smartyHelper->assign('jqplotYaxisLabel', '% Progress');
   $smartyHelper->assign('jqplotData', getProgressHistory($cmd));
}

?>

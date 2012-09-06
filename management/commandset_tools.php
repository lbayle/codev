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

class CommandSetTools {

   /**
    * @param int $teamid
    * @param int $selectedCmdSetId
    * @return mixed[]
    */
   public static function getCommandSets($teamid, $selectedCmdSetId) {
      $commandsets = array();
      if (0 != $teamid) {

         $team = TeamCache::getInstance()->getTeam($teamid);
         $commandsetList = $team->getCommandSetList();

         foreach ($commandsetList as $id => $commandset) {
            $commandsets[] = array(
               'id' => $id,
               'name' => $commandset->getName(),
               'selected' => ($id == $selectedCmdSetId)
            );
         }
      }
      return $commandsets;
   }

   /**
    * @param CommandSet $cset
    * @return mixed[]
    */
   public static function getParentContracts(CommandSet $cset) {
      $contracts = array();

      $contractList = $cset->getServiceContractList();

      // TODO return URL for 'name' ?

      foreach ($contractList as $contract) {
         $contracts[$contract->getId()] = $contract->getName();
      }
      return $contracts;
   }

   /**
    * @param int $commandsetid
    * @param int $type
    * @return mixed[]
    */
   private static function getCommandSetCommands($commandsetid, $type) {
      $commands = array();

      if (0 != $commandsetid) {
         $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

         $cmdList = $commandset->getCommands($type);
         foreach ($cmdList as $id => $cmd) {
            $issueSelection = $cmd->getIssueSelection();
            $cmdDetailedMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);

            $cmdDetailedMgr['name'] = $cmd->getName();
            $cmdDetailedMgr['reference'] = $cmd->getReference();
            $cmdDetailedMgr['description'] = $cmd->getDesc();

            $teamid = $cmd->getTeamid();
            $team = TeamCache::getInstance()->getTeam($teamid);
            $cmdDetailedMgr['team'] = $team->getName();

            $commands[$id] = $cmdDetailedMgr;
         }
      }
      return $commands;
   }

   /**
    * @param int $commandsetid
    * @param int $type Command::type_general
    * @return mixed[]
    */
   private static function getCommandSetDetailedMgr($commandsetid, $type) {
      $csetDetailedMgr = NULL;
      if (0 != $commandsetid) {
         $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

         $issueSelection = $commandset->getIssueSelection($type);
         $csetDetailedMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
      }
      return $csetDetailedMgr;
   }

   /**
    * @param CommandSet $commandSet
    * @return string
    */
   private static function getCSetProgressHistory(CommandSet $commandSet) {
      $cmdIssueSel = $commandSet->getIssueSelection(Command::type_general);

      $startTT = $cmdIssueSel->getFirstTimetrack();
      if ((NULL != $startTT) && (0 != $startTT->getDate())) {
         $startTimestamp = $startTT->getDate();
      } else {
         $startTimestamp = $commandSet->getDate();
         #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
         if (0 == $startTimestamp) {
            $team = TeamCache::getInstance()->getTeam($commandSet->getTeamid());
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

      return array($progressIndicator->getSmartyObject(), $startTimestamp, $endTimestamp);
   }

   /**
    * show users activity on the SC during the given period.
    *
    * if start & end dates not defined, the last month will be displayed.
    *
    * @param CommandSet $cmdset
    * @return string
    *
    */
   public static function getCommandSetActivity(CommandSet $cmdset, $startTimestamp = NULL, $endTimestamp = NULL) {
      $issueSel = $cmdset->getIssueSelection(Command::type_general);

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
         'teamid' => $cmdset->getTeamid()
      );

      $activityIndicator = new ActivityIndicator();
      $activityIndicator->execute($issueSel, $params);

      return array($activityIndicator->getSmartyObject(), $startTimestamp, $endTimestamp);
   }


   /**
    * @param SmartyHelper $smartyHelper
    * @param CommandSet $commandset
    */
   public static function displayCommandSet(SmartyHelper $smartyHelper, CommandSet $commandset) {
      #$smartyHelper->assign('commandsetId', $commandset->getId());
      $smartyHelper->assign('teamid', $commandset->getTeamid());
      $smartyHelper->assign('commandsetName', $commandset->getName());
      $smartyHelper->assign('commandsetReference', $commandset->getReference());
      $smartyHelper->assign('commandsetDesc', $commandset->getDesc());
      $smartyHelper->assign('commandsetBudget', $commandset->getBudgetDays());
      $smartyHelper->assign('commandsetCost', $commandset->getCost());
      $smartyHelper->assign('commandsetCurrency', $commandset->getCurrency());
      $smartyHelper->assign('commandsetDate', Tools::formatDate("%Y-%m-%d", $commandset->getDate()));

      $smartyHelper->assign('cmdList', self::getCommandSetCommands($commandset->getId(), Command::type_general));
      $smartyHelper->assign('cmdsetDetailedMgr', self::getCommandSetDetailedMgr($commandset->getId(), Command::type_general));

      $data = self::getCSetProgressHistory($commandset);
      $smartyHelper->assign('indicators_jqplotData', $data[0]);
      $smartyHelper->assign('indicators_plotMinDate', Tools::formatDate("%Y-%m-01", $data[1]));
      $smartyHelper->assign('indicators_plotMaxDate', Tools::formatDate("%Y-%m-01", strtotime(date("Y-m-d", $data[2]) . " +2 month")));

      $data = self::getCommandSetActivity($commandset);
      $smartyHelper->assign('activityIndic_data', $data[0]);
      $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
      $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
   }

}

?>

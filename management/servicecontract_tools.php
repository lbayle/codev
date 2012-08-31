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

class ServiceContractTools {

   /**
    * @param int $teamid
    * @param int $selectedId
    * @return mixed[]
    */
   public static function getServiceContracts($teamid, $selectedId) {
      $servicecontracts = array();
      if (0 != $teamid) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $servicecontractList = $team->getServiceContractList();

         foreach ($servicecontractList as $id => $servicecontract) {
            $servicecontracts[] = array(
               'id' => $id,
               'name' => $servicecontract->getName(),
               'selected' => ($id == $selectedId)
            );
         }
      }
      return $servicecontracts;
   }

   /**
    * @param ServiceContract $contract
    * @return string[]
    */
   public static function getServiceContractStateList(ServiceContract $contract = NULL) {
      $contractState = (NULL == $contract) ? 0 : $contract->getState();
      return SmartyTools::getSmartyArray(ServiceContract::$stateNames, $contractState);
   }

   /**
    * @param int $servicecontractid
    * @param int $cset_type CommandSet::type_general
    * @param int $cmd_type Command::type_general
    * @return mixed[]
    */
   private static function getServiceContractCommandSets($servicecontractid, $cset_type, $cmd_type) {
      $commands = array();

      if (0 != $servicecontractid) {
         $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $csetList = $servicecontract->getCommandSets($cset_type);
         foreach ($csetList as $id => $cset) {

            $issueSelection = $cset->getIssueSelection($cmd_type);
            $detailledMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);

            $detailledMgr['name'] = $cset->getName();
            $detailledMgr['description'] = $cset->getDesc();

            $teamid = $cset->getTeamid();
            $team = TeamCache::getInstance()->getTeam($teamid);
            $detailledMgr['team'] = $team->getName();

            $commands[$id] = $detailledMgr;
         }
      }
      return $commands;
   }

   /**
    * @param int $servicecontractid
    * @param int $cset_type CommandSet::type_general
    * @param int $cmd_type Command::type_general
    * @return mixed[]
    */
   private static function getServiceContractCommands($servicecontractid, $cset_type, $cmd_type) {
      $commands = array();

      if (0 != $servicecontractid) {

         $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $csetList = $servicecontract->getCommandSets($cset_type);
         foreach ($csetList as $id => $cset) {
            $cmdList = $cset->getCommands($cmd_type);
            foreach ($cmdList as $cmdid => $cmd) {

               $issueSelection = $cmd->getIssueSelection();
               $cmdDetailedMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);

               $cmdDetailedMgr['id'] = $cmd->getId();
               $cmdDetailedMgr['name'] = $cmd->getName();
               $cmdDetailedMgr['reference'] = $cmd->getReference();
               $cmdDetailedMgr['description'] = $cmd->getDesc();

               $teamid = $cmd->getTeamid();
               $team = TeamCache::getInstance()->getTeam($teamid);
               $cmdDetailedMgr['team'] = $team->getName();

               $commands[$id.'_'.$cmdid] = $cmdDetailedMgr;
            }
         }
      }
      return $commands;
   }

   /**
    * @param int $servicecontractid
    * @return mixed[]
    */
   private static function getServiceContractProjects($servicecontractid) {
      $projects = array();

      if (0 != $servicecontractid) {
         $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $projList = $servicecontract->getProjects();
         foreach ($projList as $id => $project) {
            $proj['name'] = $project->getName();
            $proj['description'] = $project->getDescription();

            $projects[$id] = $proj;
         }
      }
      return $projects;
   }

   /**
    * @param int $servicecontractid
    * @param int $cset_type CommandSet::type_general
    * @param int $cmd_type Command::type_general
    * @return mixed[]
    */
   private static function getServiceContractCmdsetTotalDetailedMgr($servicecontractid, $cset_type, $cmd_type) {
      $cmdsetTotalDetailedMgr = NULL;
      if (0 != $servicecontractid) {
         $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $issueSelection = $servicecontract->getIssueSelection($cset_type, $cmd_type);
         $cmdsetTotalDetailedMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
      }
      return $cmdsetTotalDetailedMgr;
   }

   /**
    * @param int $servicecontractid
    * @return IssueSelection
    */
   private static function getContractSidetasksSelection($servicecontractid) {
      $issueSelection = NULL;
      if (0 != $servicecontractid) {
         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

         $issueSelection = new IssueSelection("TotalSideTasks");
         foreach ($sidetasksPerCategory as $iSel) {
            $issueSelection->addIssueList($iSel->getIssueList());

         }
      }
      return $issueSelection;
   }

   /**
    * @param int $servicecontractid
    * @return mixed[] array[category_id] = IssueSelection
    */
   private static function getContractSidetasksDetailedMgr($servicecontractid) {
      $stasksPerCat = NULL;
      if (0 != $servicecontractid) {
         $stasksPerCat = array();

         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
         $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

         foreach ($sidetasksPerCategory as $id => $issueSelection) {
            $detailledMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
            $detailledMgr['name'] = $issueSelection->name;
            $stasksPerCat[$id] = $detailledMgr;
         }
      }
      return $stasksPerCat;
   }

   /**
    * @param IssueSelection $issueSelection
    * @return mixed[]
    */
   private static function getContractSidetasksTotalDetailedMgr(IssueSelection $issueSelection) {
      $detailledMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
      $detailledMgr['name'] = "TotalSideTasks";
      return $detailledMgr;
   }

   /**
    * @param int $servicecontractid
    * @return mixed[]
    */
   private static function getContractTotalDetailedMgr($servicecontractid) {
      $detailledMgr = NULL;
      if (0 != $servicecontractid) {
         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
         $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

         $issueSelection = new IssueSelection("Total");
         foreach ($sidetasksPerCategory as $id => $iSel) {
            $issueSelection->addIssueList($iSel->getIssueList());
         }

         $cmdsetsIssueSelection = $contract->getIssueSelection(CommandSet::type_general, Command::type_general);
         $issueSelection->addIssueList($cmdsetsIssueSelection->getIssueList());

         $detailledMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
         $detailledMgr['name'] = $issueSelection->name;
      }

      return $detailledMgr;
   }

   /**
    * @param ServiceContract $serviceContract
    * @return string
    */
   private static function getSContractProgressHistory(ServiceContract $serviceContract) {
      $cmdIssueSel = $serviceContract->getIssueSelection(CommandSet::type_general, Command::type_general);

      $startTT = $cmdIssueSel->getFirstTimetrack();
      if ((NULL != $startTT) && (0 != $startTT->getDate())) {
         $startTimestamp = $startTT->getDate();
      } else {
         $startTimestamp = $serviceContract->getStartDate();
         #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
         if (0 == $startTimestamp) {
            $team = TeamCache::getInstance()->getTeam($serviceContract->getTeamid());
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
    * @param ServiceContract $serviceContract
    * @return string
    */
   public static function getSContractActivity(ServiceContract $serviceContract, $startTimestamp = NULL, $endTimestamp = NULL) {
      $issueSel = $serviceContract->getIssueSelection(CommandSet::type_general, Command::type_general);

      if (!isset($startTimestamp)) {
         $startTT = $issueSel->getFirstTimetrack();
         if ((NULL != $startTT) && (0 != $startTT->getDate())) {
            $startTimestamp = $startTT->getDate();
         } else {
            $startTimestamp = $serviceContract->getStartDate();
            #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
            if (0 == $startTimestamp) {
               $team = TeamCache::getInstance()->getTeam($serviceContract->getTeamid());
               $startTimestamp = $team->getDate();
               #echo "team Date ".date("Y-m-d", $startTimestamp).'<br>';
            }
         }
      }
      if (!isset($endTimestamp)) {
         $endTT = $issueSel->getLatestTimetrack();
         $endTimestamp = ((NULL != $endTT) && (0 != $endTT->getDate())) ? $endTT->getDate() : time();
      }

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'teamid' => $serviceContract->getTeamid()
      );

      $activityIndicator = new ActivityIndicator();
      $activityIndicator->execute($issueSel, $params);

      return array($activityIndicator->getSmartyObject(), $startTimestamp, $endTimestamp);
   }

   /**
    * @param SmartyHelper $smartyHelper
    * @param ServiceContract $servicecontract
    */
   public static function displayServiceContract(SmartyHelper $smartyHelper, $servicecontract) {
      #$smartyHelper->assign('servicecontractId', $servicecontract->getId());
      $smartyHelper->assign('teamid', $servicecontract->getTeamid());
      $smartyHelper->assign('servicecontractName', $servicecontract->getName());
      $smartyHelper->assign('servicecontractReference', $servicecontract->getReference());
      $smartyHelper->assign('servicecontractVersion', $servicecontract->getVersion());
      $smartyHelper->assign('servicecontractReporter', $servicecontract->getReporter());
      $smartyHelper->assign('servicecontractDesc', $servicecontract->getDesc());
      $smartyHelper->assign('servicecontractStartDate', date("Y-m-d", $servicecontract->getStartDate()));
      $smartyHelper->assign('servicecontractEndDate', date("Y-m-d", $servicecontract->getEndDate()));
      $smartyHelper->assign('servicecontractStateList', self::getServiceContractStateList($servicecontract));
      $smartyHelper->assign('servicecontractState', ServiceContract::$stateNames[$servicecontract->getState()]);

      $smartyHelper->assign('cmdsetList', self::getServiceContractCommandSets($servicecontract->getId(), CommandSet::type_general, Command::type_general));
      $smartyHelper->assign('cmdsetTotalDetailedMgr', self::getServiceContractCmdsetTotalDetailedMgr($servicecontract->getId(), CommandSet::type_general, Command::type_general));

      $smartyHelper->assign('cmdList', self::getServiceContractCommands($servicecontract->getId(), CommandSet::type_general, Command::type_general));

      $smartyHelper->assign('projectList', self::getServiceContractProjects($servicecontract->getId()));
      $smartyHelper->assign('sidetasksDetailedMgr', self::getContractSidetasksDetailedMgr($servicecontract->getId()));

      $issueSelection = self::getContractSidetasksSelection($servicecontract->getId());
      $smartyHelper->assign('sidetasksTotalDetailedMgr', self::getContractSidetasksTotalDetailedMgr($issueSelection));
      $smartyHelper->assign('sidetasksList', SmartyTools::getIssueListInfo($issueSelection));
      $smartyHelper->assign('nbSidetasksList', $issueSelection->getNbIssues());

      $smartyHelper->assign('servicecontractTotalDetailedMgr', self::getContractTotalDetailedMgr($servicecontract->getId()));

      $data = self::getSContractProgressHistory($servicecontract);
      $smartyHelper->assign('indicators_jqplotData', $data[0]);
      $smartyHelper->assign('indicators_plotMinDate', Tools::formatDate("%Y-%m-01", $data[1]));
      $smartyHelper->assign('indicators_plotMaxDate', Tools::formatDate("%Y-%m-01", strtotime(date("Y-m-d", $data[2]) . " +2 month")));

      $data = self::getSContractActivity($servicecontract);
      $smartyHelper->assign('activityIndic_usersActivityList', $data[0]);
      $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
      $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
   }

}

?>

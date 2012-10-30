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
               'reference' => $servicecontract->getReference(),
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
      $contractState = (is_null($contract)) ? 0 : $contract->getState();
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
   private static function getContractSidetasksSelection($servicecontractid, $provDaysByType) {
      $issueSelection = NULL;
      if (0 != $servicecontractid) {
         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

         $issueSelection = new IssueSelection("TotalSideTasks");
         foreach ($sidetasksPerCategory as $iSel) {
            $issueSelection->addIssueList($iSel->getIssueList());
         }

         // add provisions
         foreach ($provDaysByType as $prov_type => $nbDays) {
            $issueSelection->addProvision($nbDays);
         }
         #echo 'TotalSideTasks provision = '.$issueSelection->getProvision().'<br>';
      }
      return $issueSelection;
   }

   /**
    * @param int $servicecontractid
    * @return mixed[] array[category_id] = IssueSelection
    */
   private static function getContractSidetasksDetailedMgr($servicecontractid, $provDaysByType) {
      $stasksPerCat = NULL;
      if (0 != $servicecontractid) {
         $stasksPerCat = array();

         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         // Provisions
         foreach ($provDaysByType as $prov_type => $nbDays) {

            if ($prov_type != CommandProvision::provision_mngt) {
               $provDesc = array(
                  'name' => T_('Provision').' '.CommandProvision::$provisionNames[$prov_type],
                  'effortEstim' => $nbDays,
                  'reestimated' => 'N/A',
                  'elapsed' => '',
                  'backlog' => 'N/A',
                  'driftColor' => '',
                  'drift' => (-$nbDays),
                  'progress' => 'N/A',
               );
               $stasksPerCat['Provision_'.$prov_type] = $provDesc;
               }
         }

         // SideTsks
         $sidetasksPerCategory = $contract->getSidetasksPerCategoryType(true);
         foreach ($sidetasksPerCategory as $id => $issueSelection) {

            // REM: getSidetasksPerCategoryType returns non_numeric keys if cat_type not found for cat_id
            if (is_numeric($id) && (Project::cat_mngt_regular == $id)) {
               #echo "Ah type $id is management, add provision ".$provDaysByType[CommandProvision::provision_mngt]."<br>";
               $issueSelection->addProvision($provDaysByType[CommandProvision::provision_mngt]);
            }

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
   private static function getContractTotalDetailedMgr($servicecontractid, $provDaysByType) {
      $detailledMgr = NULL;
      if (0 != $servicecontractid) {
         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
         $issueSelection = new IssueSelection("Total");

         // sidetasks
         $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);
         foreach ($sidetasksPerCategory as $id => $iSel) {
            $issueSelection->addIssueList($iSel->getIssueList());
         }

         // tasks
         $cmdsetsIssueSelection = $contract->getIssueSelection(CommandSet::type_general, Command::type_general);
         $issueSelection->addIssueList($cmdsetsIssueSelection->getIssueList());

         // provisions
         foreach ($provDaysByType as $nbDays) {
            $issueSelection->addProvision($nbDays);
         }
         #echo 'TotalSideTasks provision = '.$issueSelection->getProvision().'<br>';

         $detailledMgr = SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
         $detailledMgr['name'] = $issueSelection->name;
      }

      return $detailledMgr;
   }

   /**
    * @param Command $command
    * @return mixed[]
    */
   private static function getProvisionList(ServiceContract $contract, int $type = NULL) {
      $provArray = array();

      $provisions = $contract->getProvisionList(CommandSet::type_general, Command::type_general, $type);
      foreach ($provisions as $id => $prov) {

         $provArray["$id"] = array(
            'id' => $id,
            'date' => date(T_("Y-m-d"), $prov->getDate()),
            'type' => CommandProvision::$provisionNames[$prov->getType()],
            'budget_days' => $prov->getProvisionDays(),
            'budget' => $prov->getProvisionBudget(),
            'average_daily_rate' => $prov->getAverageDailyRate(),
            'currency' => $prov->getCurrency(),
            'cmd_name' => $prov->getCommandName(),
            'summary' => $prov->getSummary()
         );
      }
      return $provArray;
   }


   /**
    * @static
    * @param ServiceContract $serviceContract
    * @return mixed[]
    */
   public static function getSContractProgressHistory(ServiceContract $serviceContract) {
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
      
      // Calculate a nice day interval
      $nbWeeks = ($endTimestamp - $startTimestamp) / 60 / 60 / 24;
      $interval = ceil($nbWeeks / 20);

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => $interval
      );

      $progressIndicator = new ProgressHistoryIndicator();
      $progressIndicator->execute($cmdIssueSel, $params);

      return array($progressIndicator->getSmartyObject(),$startTimestamp,$endTimestamp,ceil($interval/30));
   }

   /**
    * show users activity on the SC during the given period.
    *
    * if start & end dates not defined, the last month will be displayed.
    *
    * @param ServiceContract $serviceContract
    * @return string
    *
    */
   public static function getSContractActivity(ServiceContract $serviceContract, $startTimestamp = NULL, $endTimestamp = NULL) {
      $issueSel = $serviceContract->getIssueSelection(CommandSet::type_general, Command::type_general);

      $sidetasksPerCategory = $serviceContract->getSidetasksPerCategory(true);
      foreach ($sidetasksPerCategory as $iSel) {
         $issueSel->addIssueList($iSel->getIssueList());
      }

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
         'teamid' => $serviceContract->getTeamid(),
         'showSidetasks' => false
      );

      $activityIndicator = new ActivityIndicator();
      $activityIndicator->execute($issueSel, $params);

      return array($activityIndicator->getSmartyObject(), $startTimestamp, $endTimestamp);
   }

   public static function getDetailedCharges(ServiceContract $serviceContract, $isManager, $selectedFilters) {

      $issueSel = $serviceContract->getIssueSelection(CommandSet::type_general, Command::type_general);

      $allFilters = "ProjectFilter,ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

      $params = array(
         'isManager' => $isManager,
         'selectedFilters' => $selectedFilters,
         'allFilters' => $allFilters
      );

      $detailedChargesIndicator = new DetailedChargesIndicator();
      $detailedChargesIndicator->execute($issueSel, $params);

      $smartyVariable = $detailedChargesIndicator->getSmartyObject();
      $smartyVariable['selectFiltersSrcId'] = $serviceContract->getId();

      return $smartyVariable;
   }

   /**
    * @param SmartyHelper $smartyHelper
    * @param ServiceContract $servicecontract
    */
   public static function displayServiceContract(SmartyHelper $smartyHelper, $servicecontract, $isManager, $selectedFilters = '') {
      #$smartyHelper->assign('servicecontractId', $servicecontract->getId());
      $smartyHelper->assign('teamid', $servicecontract->getTeamid());
      $smartyHelper->assign('servicecontractName', $servicecontract->getName());
      $smartyHelper->assign('servicecontractReference', $servicecontract->getReference());
      $smartyHelper->assign('servicecontractVersion', $servicecontract->getVersion());
      $smartyHelper->assign('servicecontractReporter', $servicecontract->getReporter());
      $smartyHelper->assign('servicecontractDesc', $servicecontract->getDesc());
      if (!is_null( $servicecontract->getStartDate())) {
         $smartyHelper->assign('servicecontractStartDate', date("Y-m-d", $servicecontract->getStartDate()));
      }
      if (!is_null( $servicecontract->getEndDate())) {
         $smartyHelper->assign('servicecontractEndDate', date("Y-m-d", $servicecontract->getEndDate()));
      }

      // Note: StateList is empty, uncomment following lines if ServiceContract::$stateNames is used
      //$smartyHelper->assign('servicecontractStateList', self::getServiceContractStateList($servicecontract));
      //$smartyHelper->assign('servicecontractState', ServiceContract::$stateNames[$servicecontract->getState()]);

      $smartyHelper->assign('cmdsetList', self::getServiceContractCommandSets($servicecontract->getId(), CommandSet::type_general, Command::type_general));
      $smartyHelper->assign('cmdsetTotalDetailedMgr', self::getServiceContractCmdsetTotalDetailedMgr($servicecontract->getId(), CommandSet::type_general, Command::type_general));

      $smartyHelper->assign('cmdList', self::getServiceContractCommands($servicecontract->getId(), CommandSet::type_general, Command::type_general));

      $provDaysByType = $servicecontract->getProvisionDaysByType(CommandSet::type_general, Command::type_general);
      $smartyHelper->assign('sidetasksDetailedMgr', self::getContractSidetasksDetailedMgr($servicecontract->getId(), $provDaysByType));
      $issueSelection = self::getContractSidetasksSelection($servicecontract->getId(), $provDaysByType);
      $smartyHelper->assign('sidetasksTotalDetailedMgr', self::getContractSidetasksTotalDetailedMgr($issueSelection));

      $smartyHelper->assign('sidetasksList', SmartyTools::getIssueListInfo($issueSelection));
      $smartyHelper->assign('nbSidetasksList', $issueSelection->getNbIssues());

      $smartyHelper->assign('cmdProvisionList', self::getProvisionList($servicecontract));


      $smartyHelper->assign('servicecontractTotalDetailedMgr', self::getContractTotalDetailedMgr($servicecontract->getId(), $provDaysByType));

      $data = self::getSContractActivity($servicecontract);
      $smartyHelper->assign('activityIndic_data', $data[0]);
      $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
      $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
      $smartyHelper->assign('workdays', Holidays::getInstance()->getWorkdays($data[1], $data[2]));

      // DetailedChargesIndicator
      $data = self::getDetailedCharges($servicecontract, $isManager, $selectedFilters);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
   }

}

?>

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

         $sidetasksPerCategory = $contract->getSidetasksPerCategoryType(true);

         $issueSelection = new IssueSelection("TotalSideTasks");
         foreach ($sidetasksPerCategory as $id => $iSel) {
            if (is_numeric($id) && (Project::cat_st_inactivity == $id)) {
               continue;
            }
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
               #echo "getContractSidetasksDetailedMgr: Ah type $id is management, add provision ".$provDaysByType[CommandProvision::provision_mngt]."<br>";
               $issueSelection->addProvision($provDaysByType[CommandProvision::provision_mngt]);
            }
            if (is_numeric($id) && (Project::cat_st_inactivity == $id)) {
               #echo "getContractSidetasksDetailedMgr: Ah type $id is inactivity, skip this issueSelection !<br>";
               continue;
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
         $sidetasksPerCategory = $contract->getSidetasksPerCategoryType(true);
         foreach ($sidetasksPerCategory as $id => $iSel) {
            if (is_numeric($id) && (Project::cat_st_inactivity == $id)) {
               continue;
            }
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
    * @param Command $commandSet
    * @return mixed[]
    */
   private static function getProvisionTotalList(ServiceContract $contract, int $type = NULL) {

      $provTotalArray =  NULL;
      
      // compute data
      $provisions = $contract->getProvisionList(CommandSet::type_general, Command::type_general, $type);
      
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
    * code factorisation
    *
    * returns the input params for some indicators.
    *
    * @param Command $cmd
    * @return array [startTimestamp, endTimestamp, interval]
    */
   private static function computeTimestampsAndInterval(ServiceContract $serviceContract) {
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

      // endTimestamp = max(latest_timetrack, latest_update)
      $latestTrack = $cmdIssueSel->getLatestTimetrack();
      $latestTrackTimestamp = (!is_null($latestTrack)) ? $latestTrack->getDate() : 0;
      $lastUpdatedTimestamp = $cmdIssueSel->getLastUpdated();
      $endTimestamp = max(array($latestTrackTimestamp, $lastUpdatedTimestamp));

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

   public static function getDetailedCharges(ServiceContract $serviceContract, $isManager, $selectedFilters) {

      $issueSel = $serviceContract->getIssueSelection(CommandSet::type_general, Command::type_general);

      $allFilters = "ProjectFilter,ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

      $params = array(
         'isManager' => $isManager,
         'teamid' => $serviceContract->getTeamid(),
         'selectedFilters' => $selectedFilters,
         'allFilters' => $allFilters,
         'maxTooltipsPerPage' => Constants::$maxTooltipsPerPage
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
      $smartyHelper->assign('cmdProvisionTotalList', self::getProvisionTotalList($servicecontract));

      $smartyHelper->assign('servicecontractTotalDetailedMgr', self::getContractTotalDetailedMgr($servicecontract->getId(), $provDaysByType));

      // DetailedChargesIndicator
      $data = self::getDetailedCharges($servicecontract, $isManager, $selectedFilters);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }

   /**
    *
    * @param SmartyHelper $smartyHelper
    * @param ServiceContract $servicecontract
    * @param int $userid
    */
   public static function dashboardSettings(SmartyHelper $smartyHelper, ServiceContract $servicecontract, $userid) {

      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $servicecontract->getIssueSelection(CommandSet::type_general, Command::type_general));
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $servicecontract->getTeamid());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_PROVISION_DAYS, $servicecontract->getProvisionDays(CommandSet::type_general, Command::type_general, TRUE));
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $userid);

      $params = self::computeTimestampsAndInterval($servicecontract);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $params['startTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $params['endTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $params['interval']);

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard('ServiceContract'.$servicecontract->getId());
      $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
         ));
      $dashboard->setTeamid($servicecontract->getTeamid());
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
   }

}

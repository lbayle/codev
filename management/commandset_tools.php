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
               'reference' => $commandset->getReference(),
               'name' => $commandset->getName(),
               'selected' => ($id == $selectedCmdSetId)
            );
         }
      }
      return $commandsets;
   }

   private static function getCommandSetIssues(IssueSelection $isel) {
      $issueArray = array();

      $issues = $isel->getIssueList();
      foreach ($issues as $id => $issue) {
         $driftMgr = $issue->getDriftMgr();
         $driftMgrColor = $issue->getDriftColor($driftMgr);
         $drift = $issue->getDrift();
         $driftColor = $issue->getDriftColor($drift);

         $user = UserCache::getInstance()->getUser($issue->getHandlerId());

         $issueArray[$id] = array(
            "mantisLink" => Tools::mantisIssueURL($issue->getId(), NULL, TRUE),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->getId())),
            'commandList' => implode(',<br>', $issue->getCommandList()),
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
    * @param Command $command
    * @return mixed[]
    */
   private static function getProvisionList(CommandSet $commandSet, int $type = NULL) {
      $provArray = array();

      $provisions = $commandSet->getProvisionList(Command::type_general, $type);
      foreach ($provisions as $id => $prov) {

         $provArray["$id"] = array(
            'id' => $id,
            'date' => date('Y-m-d', $prov->getDate()),
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
   private static function getProvisionTotalList(CommandSet $commandSet, $targetCurrency, int $type = NULL) {

      $provTotalArray =  NULL;

      // compute data
      $provisions = $commandSet->getProvisionList(Command::type_general, $type);

      if (!empty($provisions)) {

        foreach ($provisions as $id => $prov) {

            // a provision
            $type = CommandProvision::$provisionNames[$prov->getType()];
            $budget_days = $prov->getProvisionDays();
            $budget = $prov->getProvisionBudget($targetCurrency);

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
              'currency' => $targetCurrency,
           );
        }
        $provTotalArray['TOTAL'] = array(
             'type' => 'TOTAL',
             'budget_days' => $globalDaysTotal,
             'budget' => sprintf("%01.2f", $globalBudgetTotal),
             'currency' => $targetCurrency,
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
   private static function computeTimestampsAndInterval(CommandSet $commandSet) {
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

   /**
    * @param SmartyHelper $smartyHelper
    * @param CommandSet $commandset
    */
   public static function displayCommandSet(SmartyHelper $smartyHelper, CommandSet $commandset, $isManager, $teamid) {
      #$smartyHelper->assign('commandsetId', $commandset->getId());
      $smartyHelper->assign('teamid', $commandset->getTeamid());
      $smartyHelper->assign('commandsetName', $commandset->getName());
      $smartyHelper->assign('commandsetReference', $commandset->getReference());
      $smartyHelper->assign('commandsetDesc', $commandset->getDesc());

      if (!is_null( $commandset->getDate())) {
         $smartyHelper->assign('commandsetDate', Tools::formatDate("%Y-%m-%d", $commandset->getDate()));
      }
      $smartyHelper->assign('cmdList', self::getCommandSetCommands($commandset->getId(), Command::type_general));
      $smartyHelper->assign('cmdsetDetailedMgr', self::getCommandSetDetailedMgr($commandset->getId(), Command::type_general));

      $csetTotalElapsed = $commandset->getIssueSelection(Command::type_general)->getElapsed();
      $smartyHelper->assign('commandsetTotalElapsed',$csetTotalElapsed);

      $csetIssueSel = $commandset->getIssueSelection(CommandSet::type_general);
      $smartyHelper->assign('csetNbIssues', $csetIssueSel->getNbIssues());
      $smartyHelper->assign('csetIssues', self::getCommandSetIssues($csetIssueSel));

      $team = TeamCache::getInstance()->getTeam($teamid);
      $teamCurrency = $team->getTeamCurrency();
      $smartyHelper->assign('cmdProvisionList', self::getProvisionList($commandset));
      $smartyHelper->assign('cmdProvisionTotalList', self::getProvisionTotalList($commandset, $teamCurrency));
   }

   /**
    *
    * @param SmartyHelper $smartyHelper
    * @param Command $cmdset
    * @param int $userid
    */
   public static function dashboardSettings(SmartyHelper $smartyHelper, CommandSet $cmdset, $userid) {

      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $cmdset->getIssueSelection(Command::type_general));
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $cmdset->getTeamid());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_PROVISION_DAYS, $cmdset->getProvisionDays(Command::type_general, TRUE));
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $userid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_COMMAND_SET_ID, $cmdset->getId());

      $params = self::computeTimestampsAndInterval($cmdset);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $params['startTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $params['endTimestamp']);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $params['interval']);

      $dashboardName = 'CommandSet'.$cmdset->getId();
      $dashboardDomain = IndicatorPluginInterface::DOMAIN_COMMAND_SET;

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
         ));
      $dashboard->setTeamid($cmdset->getTeamid());
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }
}

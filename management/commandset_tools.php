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



/**
 *
 * @param int $teamid
 * @param int $selectedCmdSetId
 * @return array
 */
function getCommandSets($teamid, $selectedCmdSetId) {

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
 *
 * @param CommandSet $cmd
 * @param int $selectedCmdsetId
 * @return type
 */
function getParentContracts(CommandSet $cset) {

   $contracts = array();

   $contractList = $cset->getServiceContractList();

   // TODO return URL for 'name' ?

   foreach ($contractList as $id => $contractName) {

      $contract = ServiceContractCache::getInstance()->getServiceContract($id);
      $teamid = $contract->getTeamid();
      $team = TeamCache::getInstance()->getTeam($teamid);

      $contracts[] = array(
         'id' => $id,
         'name' => $contractName,
         'team' => $team->name
      );
   }
   return $contracts;
}


/**
 *
 * @param int $commandsetid
 * @param int $type
 * @return array
 */
function getCommandSetCommands($commandsetid, $type) {

   $commands = array();

   if (0 != $commandsetid) {

      $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

      $cmdList = $commandset->getCommands($type);
      foreach ($cmdList as $id => $cmd) {

         $issueSelection = $cmd->getIssueSelection();
         $cmdDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);


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
 *
 * @param int $commandsetid
 * @param int $type Command::type_general
 * @return array
 */
function getCommandSetDetailedMgr($commandsetid, $type) {

   if (0 != $commandsetid) {
      $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

      $issueSelection = $commandset->getIssueSelection($type);
      $csetDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);
   }
   return $csetDetailedMgr;
}


/**
 *
 * @param type $smartyHelper
 * @param CommandSet $commandset
 */
function displayCommandSet($smartyHelper, CommandSet $commandset) {

   #$smartyHelper->assign('commandsetId', $commandset->getId());
   $smartyHelper->assign('teamid',               $commandset->getTeamid());
   $smartyHelper->assign('commandsetName',       $commandset->getName());
   $smartyHelper->assign('commandsetReference',  $commandset->getReference());
   $smartyHelper->assign('commandsetDesc',       $commandset->getDesc());
   $smartyHelper->assign('commandsetBudget',     $commandset->getBudgetDays());
   $smartyHelper->assign('commandsetCost',       $commandset->getCost());
   $smartyHelper->assign('commandsetCurrency',   $commandset->getCurrency());
   $smartyHelper->assign('commandsetDate',       date("Y-m-d", $commandset->getDate()));

   $commands = getCommandSetCommands($commandset->getId(), Command::type_general);
   $smartyHelper->assign('nbCommands', count($commands));
   $smartyHelper->assign('cmdList', $commands);
   $smartyHelper->assign('cmdsetDetailedMgr', getCommandSetDetailedMgr($commandset->getId(), Command::type_general));

}

?>

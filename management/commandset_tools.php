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
function getServices($teamid, $selectedCmdSetId) {

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
 * @param int $commandsetid
 * @param int $type
 * @return array
 */
function getServiceCommands($commandsetid, $type) {

   $commands = array();

   if (0 != $commandsetid) {
      $commandset = new CommandtSet($commandsetid); // TODO use cache

      $cmdList = $commandset->getCommands($type);
      foreach ($cmdList as $id => $cmd) {

         $issueSelection = $cmd->getIssueSelection();
         $cmdDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);

         $cmdDetailedMgr['name'] = $cmd->getName();
         $cmdDetailedMgr['description'] = $cmd->getDesc();

         $commands[$id] = $cmdDetailedMgr;
      }
   }
   return $commands;
}

/**
 *
 * @param type $smartyHelper
 * @param CommandtSet $commandset
 */
function displayService($smartyHelper, $commandset) {

   #$smartyHelper->assign('commandsetId', $commandset->getId());
   $smartyHelper->assign('commandsetName', $commandset->getName());
   $smartyHelper->assign('commandsetDesc', $commandset->getDesc());
   $smartyHelper->assign('commandsetDate', date("Y-m-d", $commandset->getDate()));

   $smartyHelper->assign('cmdList', getServiceCommands($commandset->getId(), CommandtSet::cmdType_dev));

   // DEPRECATED, a commandset has only one type of command
   #$smartyHelper->assign('mngtCommands', getServiceCommands($commandsetid, Service::cmdType_mngt));

}

?>

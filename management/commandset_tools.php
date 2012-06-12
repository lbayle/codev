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
function getServiceEngagements($commandsetid, $type) {

   $engagements = array();

   if (0 != $commandsetid) {
      $commandset = new CommandtSet($commandsetid); // TODO use cache

      $engList = $commandset->getEngagements($type);
      foreach ($engList as $id => $eng) {

         $issueSelection = $eng->getIssueSelection();
         $engDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);

         $engDetailedMgr['name'] = $eng->getName();
         $engDetailedMgr['description'] = $eng->getDesc();

         $engagements[$id] = $engDetailedMgr;
      }
   }
   return $engagements;
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

   $smartyHelper->assign('engList', getServiceEngagements($commandset->getId(), CommandtSet::engType_dev));

   // DEPRECATED, a commandset has only one type of engagement
   #$smartyHelper->assign('mngtEngagements', getServiceEngagements($commandsetid, Service::engType_mngt));

}

?>

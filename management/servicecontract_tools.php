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
 * @param int $selectedId
 * @return array
 */
function getServiceContracts($teamid, $selectedId) {

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
 *
 * @param type $contract
 * @return type
 */
function getServiceContractStateList($contract = NULL) {

   $stateList = NULL;
   $contractState = (NULL == $contract) ? 0 : $contract->getState();

   foreach (Command::$stateNames as $id => $name) {
       $stateList[$id] = array('id'       => $id,
                            'name'     => $name,
                            'selected' => ($id == $contractState)
       );
   }
   return $stateList;
}


/**
 *
 * @param int $servicecontractid
 * @param int $cset_type CommandSet::type_general
 * @param int $cmd_type Command::type_general
 * @return array
 */
function getServiceContractCommandSets($servicecontractid, $cset_type, $cmd_type) {

   $commands = array();

   if (0 != $servicecontractid) {

      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $csetList = $servicecontract->getCommandSets($cset_type);
      foreach ($csetList as $id => $cset) {

         $issueSelection = $cset->getIssueSelection($cmd_type);
         $detailledMgr = getIssueSelectionDetailedMgr($issueSelection);


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
 *
 * @param int $servicecontractid
 * @return array
 */
function getServiceContractProjects($servicecontractid) {

   $projects = array();

   if (0 != $servicecontractid) {

      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $prjList = $servicecontract->getProjects();
      foreach ($prjList as $id => $project) {

         $issueSelection = $project->getIssueSelection();
         $detailledMgr = getIssueSelectionDetailedMgr($issueSelection);


         $detailledMgr['name'] = $project->getName();

         $projects[$id] = $detailledMgr;
      }
   }
   return $projects;
}



/**
 *
 * @param int $servicecontractid
 * @param int $cset_type CommandSet::type_general
 * @param int $cmd_type Command::type_general
 * @return array
 */
function getServiceContractDetailedMgr($servicecontractid, $cset_type, $cmd_type) {

   if (0 != $servicecontractid) {
      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $issueSelection = $servicecontract->getIssueSelection($cset_type, $cmd_type);
      $servicecontractDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);
   }
   return $servicecontractDetailedMgr;
}


/**
 *
 * @param type $smartyHelper
 * @param ServiceContract $servicecontract
 */
function displayServiceContract($smartyHelper, $servicecontract) {

   #$smartyHelper->assign('servicecontractId', $servicecontract->getId());
   $smartyHelper->assign('teamid',                   $servicecontract->getTeamid());
   $smartyHelper->assign('servicecontractName',      $servicecontract->getName());
   $smartyHelper->assign('servicecontractReference', $servicecontract->getReference());
   $smartyHelper->assign('servicecontractVersion',   $servicecontract->getVersion());
   $smartyHelper->assign('servicecontractReporter',  $servicecontract->getReporter());
   $smartyHelper->assign('servicecontractDesc',      $servicecontract->getDesc());
   $smartyHelper->assign('servicecontractStartDate', date("Y-m-d", $servicecontract->getStartDate()));
   $smartyHelper->assign('servicecontractEndDate',   date("Y-m-d", $servicecontract->getEndDate()));
   $smartyHelper->assign('servicecontractStateList', getServiceContractStateList($servicecontract));
   $smartyHelper->assign('servicecontractState',     ServiceContract::$stateNames[$servicecontract->getState()]);

   $smartyHelper->assign('cmdsetList', getServiceContractCommandSets($servicecontract->getId(), CommandSet::type_general, Command::type_general));
   $smartyHelper->assign('servicecontractDetailedMgr', getServiceContractDetailedMgr($servicecontract->getId(), CommandSet::type_general, Command::type_general));

}

?>

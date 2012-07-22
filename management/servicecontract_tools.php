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

   foreach (ServiceContract::$stateNames as $id => $name) {
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
 * @param int $cset_type CommandSet::type_general
 * @param int $cmd_type Command::type_general
 * @return array
 */
function getServiceContractCommands($servicecontractid, $cset_type, $cmd_type) {

   $commands = array();

   if (0 != $servicecontractid) {

      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $csetList = $servicecontract->getCommandSets($cset_type);
      foreach ($csetList as $id => $cset) {
         $cmdList = $cset->getCommands($cmd_type);
         foreach ($cmdList as $cmdid => $cmd) {

            $issueSelection = $cmd->getIssueSelection();
            $cmdDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);


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
 *
 * @param int $servicecontractid
 * @param int $cset_type CommandSet::type_general
 * @param int $cmd_type Command::type_general
 * @return array
 */
function getServiceContractProjects($servicecontractid) {

   $projects = array();

   if (0 != $servicecontractid) {

      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $projList = $servicecontract->getProjects();
      foreach ($projList as $id => $project) {


         $proj['name'] = $project->name;
         $proj['description'] = $project->description;

         $projects[$id] = $proj;
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
function getServiceContractCmdsetTotalDetailedMgr($servicecontractid, $cset_type, $cmd_type) {

   if (0 != $servicecontractid) {
      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $issueSelection = $servicecontract->getIssueSelection($cset_type, $cmd_type);
      $cmdsetTotalDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);
   }
   return $cmdsetTotalDetailedMgr;
}


function getContractSidetasksSelection($servicecontractid) {

   if (0 != $servicecontractid) {

      $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

      $issueSelection = new IssueSelection("TotalSideTasks");
      foreach ($sidetasksPerCategory as $id => $iSel) {
         $issueSelection->addIssueList($iSel->getIssueList());

      }
   }
   return $issueSelection;
}


/**
 *
 * @param int $servicecontractid
 * @return array[category_id] = IssueSelection
 */
function getContractSidetasksDetailedMgr($servicecontractid) {

   if (0 != $servicecontractid) {

      $stasksPerCat = array();

      $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

      foreach ($sidetasksPerCategory as $id => $issueSelection) {

         $detailledMgr = getIssueSelectionDetailedMgr($issueSelection);
         $detailledMgr['name'] = $issueSelection->name;

         $stasksPerCat[$id] = $detailledMgr;
      }
   }
   return $stasksPerCat;
}




/**
 *
 * @param int $servicecontractid
 * @return array
 */
function getContractSidetasksTotalDetailedMgr(IssueSelection $issueSelection) {

   $detailledMgr = getIssueSelectionDetailedMgr($issueSelection);
   $detailledMgr['name'] = "TotalSideTasks";

   return $detailledMgr;
}

/**
 * info on each sidetask
 *
 * @param int $servicecontractid
 * @return array
 */
function getContractSidetasksInfo(IssueSelection $issueSelection) {

   $issueArray = getIssueListInfo($issueSelection);
   //$issueArray['name'] = "sideTasksList";

   return $issueArray;
}


/**
 *
 * @param int $servicecontractid
 * @return array
 */
function getContractTotalDetailedMgr($servicecontractid) {

   if (0 != $servicecontractid) {

      $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      $sidetasksPerCategory = $contract->getSidetasksPerCategory(true);

      $issueSelection = new IssueSelection("Total");
      foreach ($sidetasksPerCategory as $id => $iSel) {
         $issueSelection->addIssueList($iSel->getIssueList());

      }
   }

   $cmdsetsIssueSelection = $contract->getIssueSelection(CommandSet::type_general, Command::type_general);
   $issueSelection->addIssueList($cmdsetsIssueSelection->getIssueList());

   $detailledMgr = getIssueSelectionDetailedMgr($issueSelection);
   $detailledMgr['name'] = $issueSelection->name;

   return $detailledMgr;
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

   $commandSets = getServiceContractCommandSets($servicecontract->getId(), CommandSet::type_general, Command::type_general);
   $smartyHelper->assign('cmdsetList', $commandSets);
   $smartyHelper->assign('nbCommandSets', count($commandSets));
   $smartyHelper->assign('cmdsetTotalDetailedMgr', getServiceContractCmdsetTotalDetailedMgr($servicecontract->getId(), CommandSet::type_general, Command::type_general));

   $commands = getServiceContractCommands($servicecontract->getId(), CommandSet::type_general, Command::type_general);
   $smartyHelper->assign('cmdList', $commands);
   $smartyHelper->assign('nbCommands', count($commands));


   $smartyHelper->assign('projectList', getServiceContractProjects($servicecontract->getId()));
   $smartyHelper->assign('sidetasksDetailedMgr', getContractSidetasksDetailedMgr($servicecontract->getId()));

   $issueSelection = getContractSidetasksSelection($servicecontract->getId());
   $smartyHelper->assign('sidetasksTotalDetailedMgr', getContractSidetasksTotalDetailedMgr($issueSelection));
   $smartyHelper->assign('sidetasksList', getContractSidetasksInfo($issueSelection));
   $smartyHelper->assign('nbSidetasksList', $issueSelection->getNbIssues());

   $smartyHelper->assign('servicecontractTotalDetailedMgr', getContractTotalDetailedMgr($servicecontract->getId()));

}

?>

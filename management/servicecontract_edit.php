<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

class ServiceContractEditController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("servicecontract_edit");
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamid = 0;
         if (isset($_POST['teamid'])) {
            $teamid = Tools::getSecurePOSTIntValue('teamid');
            $_SESSION['teamid'] = $teamid;
         } else if (isset($_SESSION['teamid'])) {
            $teamid = $_SESSION['teamid'];
         }

         // TODO check if $teamid is set and != 0

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();
         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

         // use the servicecontractid set in the form, if not defined (first page call) use session servicecontractid
         $servicecontractid = 0;
         if(isset($_POST['servicecontractid'])) {
            $servicecontractid = Tools::getSecurePOSTIntValue('servicecontractid');
            $_SESSION['servicecontractid'] = $servicecontractid;
         } else if(isset($_GET['servicecontractid'])) {
            $servicecontractid = Tools::getSecureGETIntValue('servicecontractid');
            $_SESSION['servicecontractid'] = $servicecontractid;
         } else if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
         }
         $action = Tools::getSecurePOSTStringValue('action', '');

         $this->smartyHelper->assign('contracts', ServiceContractTools::getServiceContracts($teamid, $servicecontractid));

         if (0 == $servicecontractid) {
            //  CREATE service contract
            if ("createContract" == $action) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("create new ServiceContract for team $teamid<br>");
               }

               $contractName = Tools::getSecurePOSTStringValue('contractName');

               $servicecontractid = ServiceContract::create($contractName, $teamid);

               $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

               // set all fields
               $this->updateServiceContractInfo($contract);
            }

            // Display Empty Command Form
            // Note: this will be overridden by the 'update' section if the 'createCommandset' action has been called.
            $this->smartyHelper->assign('contractInfoFormBtText', T_('Create'));
            $this->smartyHelper->assign('contractInfoFormAction', 'createContract');
         }

         // Edited or created just before
         if (0 != $servicecontractid) {
            // UPDATE CMDSET
            $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

            // Actions
            if ("addCommandSet" == $action) {
               # TODO
               $commandsetid = Tools::getSecurePOSTIntValue('commandsetid');

               if (0 == $commandsetid) {
                  #$_SESSION['commandsetid'] = 0;
                  header('Location:command_edit.php?commandsetid=0');
               } else {
                  $contract->addCommandSet($commandsetid, CommandSet::type_general);
               }
            } else if ("removeCmdSet" == $action) {
               $commandsetid = Tools::getSecurePOSTIntValue('commandsetid');
               $contract->removeCommandSet($commandsetid);
            } else if ("updateContractInfo" == $action) {
               $this->updateServiceContractInfo($contract);
            } else if ("addProject" == $action) {
               # TODO
               $projectid = Tools::getSecurePOSTIntValue('projectid');

               if (0 != $projectid) {
                  $contract->addSidetaskProject($projectid, Project::type_sideTaskProject);
               }
            } else if ("removeProject" == $action) {
               $projectid = Tools::getSecurePOSTIntValue('projectid');
               $contract->removeSidetaskProject($projectid);
            } else if ("deleteContract" == $action) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("delete ServiceContract servicecontractid (".$contract->getName().")");
               }
               ServiceContract::delete($servicecontractid);
               unset($_SESSION['servicecontractid']);
               header('Location:servicecontract_info.php');
            }

            // Display ServiceContract
            $this->smartyHelper->assign('servicecontractid', $servicecontractid);
            $this->smartyHelper->assign('contractInfoFormBtText', T_('Save'));
            $this->smartyHelper->assign('contractInfoFormAction', 'updateContractInfo');

            $commandsetCandidates = $this->getCmdSetCandidates($session_user);
            $this->smartyHelper->assign('commandsetCandidates', $commandsetCandidates);

            $projectCandidates = $this->getProjectCandidates($servicecontractid);
            $this->smartyHelper->assign('projectCandidates', $projectCandidates);

            $isManager = $session_user->isTeamManager($contract->getTeamid());
            ServiceContractTools::displayServiceContract($this->smartyHelper, $contract, $isManager);
         }
      }
   }

   /**
    * Action on 'Save' button
    *
    * @param ServiceContract $contract
    */
   private function updateServiceContractInfo($contract) {
      // security check
      $contract->setTeamid(Tools::getSecurePOSTIntValue('teamid'));

      $formattedValue = Tools::getSecurePOSTStringValue('servicecontractName');
      $contract->setName($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('servicecontractReference','');
      $contract->setReference($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('servicecontractVersion','');
      $contract->setVersion($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('servicecontractReporter','');
      $contract->setReporter($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('servicecontractDesc','');
      $contract->setDesc($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('serviceContractStartDate','');
      $contract->setStartDate(Tools::date2timestamp($formattedValue));

      $formattedValue = Tools::getSecurePOSTStringValue('serviceContractEndDate','');
      $contract->setEndDate(Tools::date2timestamp($formattedValue));

      $contract->setState(SmartyTools::checkNumericValue($_POST['servicecontractState'], true));

   }

   /**
    * list the Commands that can be added to this ServiceContract.
    *
    * This depends on user's teams
    * @param User $user
    * @return string[]
    */
   private function getCmdSetCandidates(User $user) {
      $cmdsetCandidates = array();

      $lTeamList = $user->getLeadedTeamList();
      $managedTeamList = $user->getManagedTeamList();
      $mTeamList = $user->getDevTeamList();
      $teamList = $mTeamList + $lTeamList + $managedTeamList;

      foreach ($teamList as $teamid => $name) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $commandsetList = $team->getCommandSetList();

         foreach ($commandsetList as $cid => $cmdset) {
            // TODO remove CmdSets already in this contract.
            $cmdsetCandidates[$cid] = $cmdset->getName();
         }
      }
      asort($cmdsetCandidates);

      return $cmdsetCandidates;
   }

   /**
    * list the Sidetasks Projects that can be added to this ServiceContract.
    *
    * This depends on ServiceContract's team
    * @param int $servicecontractid
    * @return string[]
    */
   private function getProjectCandidates($servicecontractid) {
      $candidates = array();

      $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
      $team = TeamCache::getInstance()->getTeam($contract->getTeamid());

      $projList = $team->getProjects();

      foreach ($projList as $projectid => $name) {
         if ($team->isSideTasksProject($projectid)) {
            $candidates[$projectid] = $name;
         }
      }
      return $candidates;
   }

}

// ========== MAIN ===========
ServiceContractEditController::staticInit();
$controller = new ServiceContractEditController('ServiceContract (edit)','Management');
$controller->execute();

?>

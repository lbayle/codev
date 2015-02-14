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

class CommandSetEditController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("commandset_edit");
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('isEditGranted', FALSE);
        } else {
            // only managers can edit the SC
            $isManager = $this->session_user->isTeamManager($this->teamid);
            if (!$isManager) {
               return;
            }
            $this->smartyHelper->assign('isEditGranted', true);

            // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
            $commandsetid = 0;
            if(isset($_POST['commandsetid'])) {
               $commandsetid = $_POST['commandsetid'];
               $_SESSION['commandsetid'] = $commandsetid;
            } else if(isset($_GET['commandsetid'])) {
               $commandsetid = $_GET['commandsetid'];
               $_SESSION['commandsetid'] = $commandsetid;
            } else if(isset($_SESSION['commandsetid'])) {
               $commandsetid = $_SESSION['commandsetid'];
            }

            $action = Tools::getSecurePOSTStringValue('action', '');

            if (0 == $commandsetid) {
               // -------- CREATE CMDSET -------
               if ("createCmdset" == $action) {
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("create new CommandSet for team $this->teamid<br>");
                  }

                  $cmdsetName = Tools::escape_string($_POST['commandsetName']);

                  try {
                     $commandsetid = CommandSet::create($cmdsetName, $this->teamid);

                     $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
                  } catch(Exception $e) {
                     // Smartify
                     echo "Can't create the CommandSet because the CommandSet name is already used";
                  }
               }

               // Display Empty Command Form
               // Note: this will be overridden by the 'update' section if the 'createCommandset' action has been called.
               $this->smartyHelper->assign('cmdsetInfoFormBtText', T_('Create'));
               $this->smartyHelper->assign('cmdsetInfoFormAction', 'createCmdset');
            }

            if (0 != $commandsetid) {
               // -------- UPDATE CMDSET -------
               $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

               // ------ Actions
               if ("addCommand" == $action) {
                  # TODO
                  $cmdid = SmartyTools::checkNumericValue($_POST['cmdid']);

                  if (0 == $cmdid) {
                     #$_SESSION['cmdid'] = 0;
                     header('Location:command_edit.php?cmdid=0');
                  } else {
                     $cmdset->addCommand($cmdid, Command::type_general);
                  }
               } else if ("removeCmd" == $action) {
                  $cmdid = SmartyTools::checkNumericValue($_POST['cmdid']);
                  $cmdset->removeCommand($cmdid);
               } else if ("updateCmdsetInfo" == $action) {
                  $this->updateCommandSetInfo($cmdset);
                  header('Location:commandset_info.php');

               } else if ("deleteCommandSet" == $action) {
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("delete CommandSet $commandsetid (".$cmdset->getName().")");
                  }
                  CommandSet::delete($commandsetid);
                  unset($_SESSION['commandsetid']);
                  header('Location:commandset_info.php');
               }

               // Display CommandSet
               $this->smartyHelper->assign('commandsetid', $commandsetid);
               $this->smartyHelper->assign('cmdsetInfoFormBtText', T_('Save'));
               $this->smartyHelper->assign('cmdsetInfoFormAction', 'updateCmdsetInfo');
               $this->smartyHelper->assign('isAddCmdForm', true);

               $cmdCandidates = $this->getCmdSetCandidates($cmdset, $this->session_user);
               $this->smartyHelper->assign('cmdCandidates', $cmdCandidates);
               $this->smartyHelper->assign('isAddCmdSetForm', true);

               // set CommandSets I belong to
               $this->smartyHelper->assign('parentContracts', CommandSetTools::getParentContracts($cmdset));

               $isManager = $this->session_user->isTeamManager($cmdset->getTeamid());

               CommandSetTools::displayCommandSet($this->smartyHelper, $cmdset, $isManager);
            }

            // you can create OR move SC only to managed teams
            $mTeamList = $this->session_user->getManagedTeamList();
            $this->smartyHelper->assign('grantedTeams', SmartyTools::getSmartyArray($mTeamList, $this->teamid));


         }
   }
   }

   /**
    * Action on 'Save' button
    *
    * @param CommandSet $cmdset
    */
   private function updateCommandSetInfo($cmdset) {

      // TODO check sc_teamid in grantedTeams

      $cset_teamid = Tools::getSecurePOSTIntValue('cset_teamid');

      if ($cset_teamid != $this->teamid) {
         // switch team (because you won't find the SC in current team's contract list)
         $_SESSION['teamid'] = $cset_teamid;
         $this->updateTeamSelector();
      }
      $cmdset->setTeamid($cset_teamid);

      $formattedValue = Tools::escape_string($_POST['commandsetName']);
      $cmdset->setName($formattedValue);

      $formattedValue = Tools::escape_string($_POST['commandsetReference']);
      $cmdset->setReference($formattedValue);

      $formattedValue = Tools::escape_string($_POST['commandsetDesc']);
      $cmdset->setDesc($formattedValue);

      $formattedValue = Tools::escape_string($_POST['commandsetDate']);
      if ('' != $formattedValue) {
         $cmdset->setDate(Tools::date2timestamp($formattedValue));
      }
      $cmdset->setCost(SmartyTools::checkNumericValue($_POST['commandsetCost'], true));

      $cmdset->setBudgetDays(SmartyTools::checkNumericValue($_POST['commandsetBudget'], true));
   }

   /**
    * list the Commands that can be added to this CommandSet.
    *
    * This depends on user's teams
    * @param User $user
    * @return string[]
    */
   private function getCmdSetCandidates(CommandSet $cmdset, User $user) {
      $cmdCandidates = array();

      $lTeamList = $user->getLeadedTeamList();
      $managedTeamList = $user->getManagedTeamList();
      $mTeamList = $user->getDevTeamList();
      $teamList = $mTeamList + $lTeamList + $managedTeamList;

      $cmds = $cmdset->getCommands(Command::type_general);

      foreach ($teamList as $tid => $name) {
         $team = TeamCache::getInstance()->getTeam($tid);
         $cmdList = $team->getCommands();

         foreach ($cmdList as $cid => $cmd) {
            // remove Cmds already in this cset.
            if (!array_key_exists($cid, $cmds)) {
               $cmdCandidates[$cid] = $cmd->getReference() . " ". $cmd->getName();
            }
         }
      }
      asort($cmdCandidates);

      return $cmdCandidates;
   }

}

// ========== MAIN ===========
CommandSetEditController::staticInit();
$controller = new CommandSetEditController('../', 'CommandSet (edit)','Management');
$controller->execute();

?>

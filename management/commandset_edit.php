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
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamid = 0;
         if (isset($_POST['teamid'])) {
            $teamid = $_POST['teamid'];
            $_SESSION['teamid'] = $teamid;
         } else if (isset($_SESSION['teamid'])) {
            $teamid = $_SESSION['teamid'];
         }

         // TODO check if $teamid is set and != 0

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();
         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

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

         $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($teamid, $commandsetid));

         $action = Tools::getSecurePOSTStringValue('action', '');

         if (0 == $commandsetid) {
            // -------- CREATE CMDSET -------
            if ("createCmdset" == $action) {
               $teamid = SmartyTools::checkNumericValue($_POST['teamid']);
               $_SESSION['teamid'] = $teamid;
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("create new CommandSet for team $teamid<br>");
               }

               $cmdsetName = Tools::escape_string($_POST['commandsetName']);

               $commandsetid = CommandSet::create($cmdsetName, $teamid);

               $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

               // set all fields
               $this->updateCommandSetInfo($cmdset);
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
               $teamid = SmartyTools::checkNumericValue($_POST['teamid']);
               $_SESSION['teamid'] = $teamid;

               $this->updateCommandSetInfo($cmdset);
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

            $cmdCandidates = $this->getCmdSetCandidates($session_user);
            $this->smartyHelper->assign('cmdCandidates', $cmdCandidates);
            $this->smartyHelper->assign('isAddCmdSetForm', true);

            // set CommandSets I belong to
            $this->smartyHelper->assign('parentContracts', CommandSetTools::getParentContracts($cmdset));

            $isManager = $session_user->isTeamManager($cmdset->getTeamid());

            CommandSetTools::displayCommandSet($this->smartyHelper, $cmdset, $isManager);
         }
      }
   }

   /**
    * Action on 'Save' button
    *
    * @param CommandSet $cmdset
    */
   private function updateCommandSetInfo($cmdset) {
      // security check
      $cmdset->setTeamid(SmartyTools::checkNumericValue($_POST['teamid']));

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
   private function getCmdSetCandidates(User $user) {
      $cmdCandidates = array();

      $lTeamList = $user->getLeadedTeamList();
      $managedTeamList = $user->getManagedTeamList();
      $mTeamList = $user->getDevTeamList();
      $teamList = $mTeamList + $lTeamList + $managedTeamList;

      foreach ($teamList as $teamid => $name) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $cmdList = $team->getCommands();

         foreach ($cmdList as $cid => $cmd) {
            // TODO remove Cmds already in this cmdset.
            $cmdCandidates[$cid] = $cmd->getReference() . " ". $cmd->getName();
         }
      }
      asort($cmdCandidates);

      return $cmdCandidates;
   }

}

// ========== MAIN ===========
CommandSetEditController::staticInit();
$controller = new CommandSetEditController('CommandSet (edit)','Management');
$controller->execute();

?>

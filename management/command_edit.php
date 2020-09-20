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

class CommandEditController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("command_edit");
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('isEditGranted', FALSE);
        } else {
            $team = TeamCache::getInstance()->getTeam($this->teamid);

            // only managers can edit the SC
            $isManager = $this->session_user->isTeamManager($this->teamid);
            if (!$isManager) {
               return;
            }
            $this->smartyHelper->assign('isEditGranted', true);
            $this->smartyHelper->assign('isManager', true);


            // use the cmdid set in the form, if not defined (first page call) use session cmdid
            $cmdid = 0;
            if(isset($_POST['cmdid'])) {
               $cmdid = $_POST['cmdid'];
               $_SESSION['cmdid'] = $cmdid;
            } else if(isset($_GET['cmdid'])) {
               $cmdid = $_GET['cmdid'];
               $_SESSION['cmdid'] = $cmdid;
            } else if(isset($_SESSION['cmdid'])) {
               $cmdid = $_SESSION['cmdid'];
               self::$logger->error("WARN: cmdid not defined in form, using _SESSION");
            }

            // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
            // Note: It is used for createEnv but will be overridden by the displayed command's commandsetid.
            $commandsetid = 0;
            if(isset($_POST['commandsetid'])) {
               $commandsetid = $_POST['commandsetid'];
               $_SESSION['commandsetid'] = $commandsetid;
            } else if(isset($_SESSION['commandsetid'])) {
               $commandsetid = $_SESSION['commandsetid'];
            }

            $action = isset($_POST['action']) ? $_POST['action'] : '';

            if (0 == $cmdid) {
               // -------- CREATE CMD -------
               if ("createCmd" == $action) {
                  //$this->teamid = Tools::getSecurePOSTIntValue('teamid');
                  //$_SESSION['teamid'] = $this->teamid;
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("create new Command for team $this->teamid<br>");
                  }

                  $cmdName = Tools::getSecurePOSTStringValue('cmdName');

                  // TODO UGLY WORKAROUND: command name cannot contain commas (,) because it is used as field separator in FilterManager
                  $cmdName = str_replace(",", ' ', $cmdName);

                  try {
                     $cmdid = Command::create($cmdName, $this->teamid);
                     $this->smartyHelper->assign('commandid', $cmdid);

                     $cmd = CommandCache::getInstance()->getCommand($cmdid);

                  } catch(Exception $e) {
                     // Smartify
                     echo "Can't create the command because the command name is already used";
                  }
               }

               // ------ Display Empty Command Form
               // Note: this will be overridden by the 'update' section if the 'createCmd' action has been called.
               $this->smartyHelper->assign('cmdInfoFormBtText', T_('Create'));
               $this->smartyHelper->assign('cmdInfoFormAction', 'createCmd');

               $this->smartyHelper->assign('cmdStateList', CommandTools::getCommandStateList());
               $this->smartyHelper->assign('commandsetid', $commandsetid);
               $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($this->teamid, $commandsetid));
            }

            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);

               // -------- CHECK CMD --------
               // this will check command and FIX inconcistencies in WBS (remove issues that have been deleted from mantis, ...)
               $cmd->fixCommand();

               // -------- UPDATE CMD -------
               // Actions
               if ("addCmdIssueList" == $action) {
                  $bugid_list = Tools::getSecurePOSTStringValue('addCmdIssue_bugidList');
                  $bugid_list = str_replace(' ', '', $bugid_list);
                  $bugids = explode(',', $bugid_list);

                  foreach ($bugids as $id) {
                     if (!empty($id)) {
                        if (is_numeric(trim($id))) {
                           $cmd->addIssue(intval($id), true); // DBonly
                        } else {
                           self::$logger->error('Attempt to set non_numeric value ('.$id.')');
                           die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
                        }
                     }
                  }
               } else if ("removeCmdIssue" == $action) {
                  $cmd->removeIssue($_POST['bugid']);
               } else if ("addToCmdSet" == $action) {
                  $commandsetid = $_POST['commandsetid'];
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("add Command $cmdid to CommandSet $commandsetid");
                  }

                  $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
                  $cmdset->addCommand($cmdid, Command::type_general);

               } else if ("removeFromCmdSet" == $action) {
                  $commandsetid = $_POST['commandsetid'];
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("remove Command $cmdid from CommandSet $commandsetid");
                  }

                  $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
                  $cmdset->removeCommand($cmdid);

               } else if ("updateCmdInfo" == $action) {
                  $this->updateCmdInfo($cmd);
                  header('Location:command_info.php');

               } else if ("deleteCommand" == $action) {
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("delete Command $cmdid");
                  }
                  Command::delete($cmdid);
                  unset($_SESSION['cmdid']);
                  header('Location:command_info.php');
               }

               // Display Command
               $this->smartyHelper->assign('commandid', $cmdid);
               $this->smartyHelper->assign('cmdInfoFormBtText', T_('Save'));
               $this->smartyHelper->assign('cmdInfoFormAction', 'updateCmdInfo');
               $this->smartyHelper->assign('isAddIssueForm', true);

               $parentCmdSets = $this->getParentCmdSetCandidates($this->session_user);
               $this->smartyHelper->assign('parentCmdSetCandidates', $parentCmdSets);
               $this->smartyHelper->assign('isAddCmdSetForm', true);

               $isManager = $this->session_user->isTeamManager($cmd->getTeamid());

               CommandTools::displayCommand($this->smartyHelper, $cmd, $isManager, $this->teamid);
               $this->smartyHelper->assign('cmdProvisionType', SmartyTools::getSmartyArray(CommandProvision::$provisionNames, 1));

               // Provisions
               $currencies = Currencies::getInstance()->getCurrencies();
               $teamCurrency = $team->getTeamCurrency();
               foreach ($currencies as $currency => $coef) {
                  $currencyList[$currency] = array(
                     'currency' => $currency,
                     'coef' => $coef,
                     'selected' => ($currency == $teamCurrency),
                  );
               }
               $this->smartyHelper->assign('currencies', $currencyList);
               $this->smartyHelper->assign('teamCurrency', $teamCurrency);


               // WBS
               $this->smartyHelper->assign('wbsRootId', $cmd->getWbsid());

            }


            // you can create a command OR move cmd only to managed teams
            $mTeamList = $this->session_user->getManagedTeamList();
            $this->smartyHelper->assign('grantedTeams', SmartyTools::getSmartyArray($mTeamList, $this->teamid));

         }
      }
   }

   /**
    * @param Command $cmd
    */
   private function updateCmdInfo(Command $cmd) {
      // TODO check cmd_teamid in grantedTeams

      $cmd_teamid = Tools::getSecurePOSTIntValue('cmd_teamid');

      if ($cmd_teamid != $this->teamid) {
         // switch team (because you won't find the cmd in current team's contract list)
         $_SESSION['teamid'] = $cmd_teamid;
         $this->updateTeamSelector();
      }
      $cmd->setTeamid($cmd_teamid);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdName');
      // TODO UGLY WORKAROUND: command name cannot contain commas (,) because it is used as field separator in FilterManager
      $formattedValue = str_replace(",", ' ', $formattedValue);

      $cmd->setName($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdReference','');
      $cmd->setReference($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdVersion','');
      $cmd->setVersion($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdReporter','');
      $cmd->setReporter($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdDesc','');
      $cmd->setDesc($formattedValue);

      $formattedValue = Tools::getSecurePOSTStringValue('cmdStartDate','');
      if ('' != $formattedValue) {
         $cmd->setStartDate(Tools::date2timestamp($formattedValue));
      }
      $formattedValue = Tools::getSecurePOSTStringValue('cmdDeadline', '');
      if ('' != $formattedValue) {
         $cmd->setDeadline(Tools::date2timestamp($formattedValue));
      }

      $cmd->setState(SmartyTools::checkNumericValue($_POST['cmdState'], true));
      $cmd->setTotalSoldDays(SmartyTools::checkNumericValue($_POST['cmdTotalSoldDays'], true));
   }

   /**
    * find CommandSets associated to all the teams i am member of.
    * (observed teams excluded))
    *
    * @param User $user
    * @return string[]
    */
   private function getParentCmdSetCandidates(User $user) {
      $parentCmdSets = array();

      #$lTeamList = $user->getAdministratedTeamList();
      #$managedTeamList = $user->getManagedTeamList();
      #$mTeamList = $user->getDevTeamList();
      #$teamList = $mTeamList + $lTeamList + $managedTeamList;
      $teamList = array($this->teamid => 'curTeam');

      foreach ($teamList as $tid => $name) {
         $team = TeamCache::getInstance()->getTeam($tid);
         $cmdsetList = $team->getCommandSetList();

         foreach ($cmdsetList as $csid => $cmdset) {
            $parentCmdSets[$csid] = $cmdset->getName();
         }
      }

      return $parentCmdSets;
   }

}

// ========== MAIN ===========
CommandEditController::staticInit();
$controller = new CommandEditController('../', 'Command (edition)', 'Management');
$controller->execute();



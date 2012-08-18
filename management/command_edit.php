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
      if (isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamid = 0;
         if (isset($_POST['teamid'])) {
            $teamid = $_POST['teamid'];
         } else if (isset($_SESSION['teamid'])) {
            $teamid = $_SESSION['teamid'];
         }
         $_SESSION['teamid'] = $teamid;

         // TODO check if $teamid is set and != 0

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();
         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

         // use the cmdid set in the form, if not defined (first page call) use session cmdid
         $cmdid = 0;
         if(isset($_POST['cmdid'])) {
            $cmdid = $_POST['cmdid'];
         } else if(isset($_GET['cmdid'])) {
            $cmdid = $_GET['cmdid'];
         } else if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
         }
         $_SESSION['cmdid'] = $cmdid;

         // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
         // Note: It is used for createEnv but will be overridden by the displayed command's commandsetid.
         $commandsetid = 0;
         if(isset($_POST['commandsetid'])) {
            $commandsetid = $_POST['commandsetid'];
         } else if(isset($_SESSION['commandsetid'])) {
            $commandsetid = $_SESSION['commandsetid'];
         }
         $_SESSION['commandsetid'] = $commandsetid;

         $action = isset($_POST['action']) ? $_POST['action'] : '';

         if (0 == $cmdid) {
            // -------- CREATE CMD -------

            // ------ Actions
            if ("createCmd" == $action) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
               $_SESSION['teamid'] = $teamid;
               self::$logger->debug("create new Command for team $teamid<br>");

               $cmdName = Tools::getSecurePOSTStringValue('cmdName');

               try {
                  $cmdid = Command::create($cmdName, $teamid);
                  $this->smartyHelper->assign('commandid', $cmdid);

                  $cmd = CommandCache::getInstance()->getCommand($cmdid);

                  // set all fields
                  $this->updateCmdInfo($cmd);
               } catch(Exception $e) {
                  // Smartify
                  echo "Can't create the command because the command name is already used";
               }
            }

            // ------ Display Empty Command Form
            // Note: this will be overridden by the 'update' section if the 'createCmd' action has been called.
            $this->smartyHelper->assign('cmdInfoFormBtText', 'Create');
            $this->smartyHelper->assign('cmdInfoFormAction', 'createCmd');

            $this->smartyHelper->assign('cmdStateList', ServiceContractTools::getServiceContractStateList());
            $this->smartyHelper->assign('cmdState', Command::$stateNames[0]);

            $this->smartyHelper->assign('commandsetid', $commandsetid);
            $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($teamid, $commandsetid));

            $this->smartyHelper->assign('cmdName', "New command");
         }

         if (0 != $cmdid) {
            // -------- UPDATE CMD -------

            $cmd = CommandCache::getInstance()->getCommand($cmdid);

            // ------ Actions
            if ("addCmdIssue" == $action) {
               $bugid = Tools::getSecurePOSTIntValue('bugid');
               self::$logger->debug("add Issue $bugid on Command $cmdid team $teamid");

               $cmd->addIssue($bugid, true); // DBonly
            } else if ("addCmdIssueList" == $action) {
               $bugid_list = $_POST['bugid_list'];

               self::$logger->debug("add Issues ($bugid_list) on Command $cmdid team $teamid");

               $bugids = explode(',', $bugid_list);

               //$cmd->addIssueList($bugids, true); // DBonly
               foreach ($bugids as $id) {
                  if (is_numeric(trim($id))) {
                     $cmd->addIssue(intval($id), true); // DBonly
                  } else {
                     self::$logger->error('Attempt to set non_numeric value ('.$id.')');
                     die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
                  }
               }
            } else if ("removeCmdIssue" == $action) {
               $cmd->removeIssue($_POST['bugid']);
            } else if ("addToCmdSet" == $action) {
               $commandsetid = $_POST['commandsetid'];
               self::$logger->debug("add Command $cmdid to CommandSet $commandsetid");

               $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
               $cmdset->addCommand($cmdid, Command::type_general);

            } else if ("removeFromCmdSet" == $action) {
               $commandsetid = $_POST['commandsetid'];
               self::$logger->debug("remove Command $cmdid from CommandSet $commandsetid");

               $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
               $cmdset->removeCommand($cmdid);
            } else if ("updateCmdInfo" == $action) {
               $teamid = SmartyTools::checkNumericValue($_POST['teamid']);
               $_SESSION['teamid'] = $teamid;

               $this->updateCmdInfo($cmd);
            } else if ("deleteCommand" == $action) {
               self::$logger->debug("delete Command $cmdid");
               Command::delete($cmdid);
               unset($_SESSION['cmdid']);
               header('Location:command_info.php');
            }

            // Display Command
            $this->smartyHelper->assign('commandid', $cmdid);
            $this->smartyHelper->assign('cmdInfoFormBtText', 'Save');
            $this->smartyHelper->assign('cmdInfoFormAction', 'updateCmdInfo');
            $this->smartyHelper->assign('isAddIssueForm', true);

            $parentCmdSets = $this->getParentCmdSetCandidates($session_user);
            $this->smartyHelper->assign('parentCmdSetCandidates', $parentCmdSets);
            $this->smartyHelper->assign('isAddCmdSetForm', true);

            CommandTools::displayCommand($this->smartyHelper, $cmd);

            // multiple selection dialogBox
            $availableIssueList = $this->getChildIssuesCandidates($teamid);
            $this->smartyHelper->assign('availableIssueList', $availableIssueList);
            $this->smartyHelper->assign('sendSelectIssuesActionName', "addCmdIssueList");
            $this->smartyHelper->assign('selectIssuesBoxTitle', T_('Add tasks to Command').' \''.$cmd->getName().'\'');
            $this->smartyHelper->assign('openDialogLabel', T_("Add multiple tasks"));
            $this->smartyHelper->assign('selectIssuesDoneBtText', T_("Add selection"));
            $this->smartyHelper->assign('selectIssuesBoxDesc', T_("Note: Tasks already assigned to a Command are not displayed."));
            $this->smartyHelper->assign('selectIssuesConfirmMsg', T_("Add the selected issues to the Command ?"));
         }
      }
   }

   /**
    * @param Command $cmd
    */
   private function updateCmdInfo(Command $cmd) {
      $cmd->setTeamid(Tools::getSecurePOSTIntValue('teamid'));

      $formattedValue = Tools::getSecurePOSTStringValue('cmdName');
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
      $cmd->setStartDate(Tools::date2timestamp($formattedValue));

      $formattedValue = Tools::getSecurePOSTStringValue('cmdDeadline');
      $cmd->setDeadline(Tools::date2timestamp($formattedValue));

      $cmd->setState(SmartyTools::checkNumericValue($_POST['cmdState'], true));
      $cmd->setBudgetDev(SmartyTools::checkNumericValue($_POST['cmdBudgetDev'], true));
      $cmd->setCost(SmartyTools::checkNumericValue($_POST['cmdCost'], true));
      $cmd->setBudgetMngt(SmartyTools::checkNumericValue($_POST['cmdBudgetMngt'], true));
      #$cmd->setBudgetGarantie(checkNumericValue($_POST['cmdBudgetGarantie'], true));
      #$cmd->setAverageDailyRate(checkNumericValue($_POST['cmdAverageDailyRate'], true));
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

      $lTeamList = $user->getLeadedTeamList();
      $managedTeamList = $user->getManagedTeamList();
      $mTeamList = $user->getDevTeamList();
      $teamList = $mTeamList + $lTeamList + $managedTeamList;

      foreach ($teamList as $teamid => $name) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $cmdsetList = $team->getCommandSetList();

         foreach ($cmdsetList as $csid => $cmdset) {
            $parentCmdSets[$csid] = $cmdset->getName();
         }
      }

      return $parentCmdSets;
   }

   /**
    * returns all issues not already assigned to a command
    * and which project_id is defined in the team
    *
    * @param int $teamid
    * @return mixed[]
    */
   private function getChildIssuesCandidates($teamid) {
      $issueArray = array();

      // team projects except externalTasksProject & NoStats projects
      $projects = TeamCache::getInstance()->getTeam($teamid)->getProjects();
      $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
      unset($projects[$extProjId]);

      $formattedProjectList = implode (', ', array_keys($projects));

      $query  = "SELECT * FROM `mantis_bug_table` ".
         "WHERE project_id IN ($formattedProjectList) ".
         "AND 0 = is_issue_in_team_commands(id, $teamid) ".
         "ORDER BY id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $issueArray[$row->id] = array(
            //"mantisLink" => mantisIssueURL($issue->bugId, NULL, true),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n",   $issue->bugId)),
            //"bugid" => $issue->bugId,
            "extRef" => $issue->getTC(),
            "project" => $issue->getProjectName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "summary" => $issue->summary
         );
      }

      return $issueArray;
   }
}

// ========== MAIN ===========
CommandEditController::staticInit();
$controller = new CommandEditController('Command (edition)', 'Management');
$controller->execute();

?>

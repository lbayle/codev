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

         if (0 != $this->teamid) {

            // only managers can edit the SC
            $isManager = $this->session_user->isTeamManager($this->teamid);
            if (!$isManager) {
               return;
            }
            $this->smartyHelper->assign('isEditGranted', true);

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

                  try {
                     $cmdid = Command::create($cmdName, $this->teamid);
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
               $this->smartyHelper->assign('cmdInfoFormBtText', T_('Create'));
               $this->smartyHelper->assign('cmdInfoFormAction', 'createCmd');

               $this->smartyHelper->assign('cmdStateList', CommandTools::getCommandStateList());
               $this->smartyHelper->assign('commandsetid', $commandsetid);
               $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($this->teamid, $commandsetid));
            }

            if (0 != $cmdid) {
               // -------- UPDATE CMD -------
               $cmd = CommandCache::getInstance()->getCommand($cmdid);

               // Actions
               if ("addCmdIssue" == $action) {
                  $bugid = Tools::getSecurePOSTIntValue('bugid');
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("add Issue $bugid on Command $cmdid team $this->teamid");
                  }

                  $cmd->addIssue($bugid, true); // DBonly
               } else if ("addCmdIssueList" == $action) {
                  $bugid_list = $_POST['bugid_list'];

                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("add Issues ($bugid_list) on Command $cmdid team $this->teamid");
                  }

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
               } else if ("addProvision" == $action) {

                  # TODO check injections
                  $prov_date = $_POST['date'];
                  $prov_type = $_POST['type'];
                  $prov_budget = $_POST['budget'];
                  $prov_budgetDays = $_POST['budgetDays'];
                  $prov_averageDailyRate = $_POST['averageDailyRate'];
                  $prov_summary = $_POST['summary'];
                  $isInCheckBudget = (0 == Tools::getSecurePOSTIntValue("isInCheckBudget")) ? false : true;

                  $timestamp = Tools::date2timestamp($prov_date);

                  CommandProvision::create($cmd->getId(), $timestamp, $prov_type, $prov_summary, $prov_budgetDays, $prov_budget, $prov_averageDailyRate, $isInCheckBudget);

               } else if ("deleteProvision" == $action) {
                  # TODO check injections
                  $provid = $_POST['provid'];
                  $cmd->deleteProvision($provid);
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

               CommandTools::displayCommand($this->smartyHelper, $cmd, $isManager);
               $this->smartyHelper->assign('cmdProvisionType', SmartyTools::getSmartyArray(CommandProvision::$provisionNames, 1));

               // multiple selection dialogBox
               $availableIssueList = $this->getChildIssuesCandidates($this->teamid);
               $this->smartyHelper->assign('availableIssueList', $availableIssueList);
               $this->smartyHelper->assign('sendSelectIssuesActionName', "addCmdIssueList");
               $this->smartyHelper->assign('selectIssuesBoxTitle', T_('Add tasks to Command').' \''.$cmd->getName().'\'');
               $this->smartyHelper->assign('openDialogLabel', T_("Add multiple tasks"));
               $this->smartyHelper->assign('selectIssuesDoneBtText', T_("Add selection"));
               $this->smartyHelper->assign('selectIssuesBoxDesc', T_("Note: Tasks already assigned to a Command are not displayed."));
               $this->smartyHelper->assign('selectIssuesConfirmMsg', T_("Add the selected issues to the Command ?"));
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
      $cmd->setAverageDailyRate(SmartyTools::checkNumericValue($_POST['cmdAverageDailyRate'], true));

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

      $lTeamList = $user->getLeadedTeamList();
      $managedTeamList = $user->getManagedTeamList();
      $mTeamList = $user->getDevTeamList();
      $teamList = $mTeamList + $lTeamList + $managedTeamList;

      foreach ($teamList as $tid => $name) {
         $team = TeamCache::getInstance()->getTeam($tid);
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
    * @param int $this->teamid
    * @return mixed[]
    */
   private function getChildIssuesCandidates($teamid) {
      $issueArray = array();

      // team projects except externalTasksProject & NoStats projects
      $projects = TeamCache::getInstance()->getTeam($this->teamid)->getProjects();
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
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->getId())),
            //"bugid" => $issue->bugId,
            "extRef" => $issue->getTcId(),
            "project" => $issue->getProjectName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "summary" => htmlspecialchars($issue->getSummary())
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

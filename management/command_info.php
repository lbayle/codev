<?php
require('../include/session.inc.php');

/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

class CommandInfoController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // use the teamid set in the form, if not defined (first page call) use session teamid
         $teamid = 0;
         if (isset($_POST['teamid'])) {
            $teamid = Tools::getSecurePOSTIntValue('teamid');
            $_SESSION['teamid'] = $teamid;
         } else if (isset($_SESSION['teamid'])) {
            $teamid = $_SESSION['teamid'];
         }

         // if cmdid set in URL, use it. else:
         // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
         $cmdid = 0;
         if(isset($_POST['cmdid'])) {
            $cmdid = Tools::getSecurePOSTIntValue('cmdid');
            $_SESSION['cmdid'] = $cmdid;
         } else if(isset($_GET['cmdid'])) {
            $cmdid = Tools::getSecureGETIntValue('cmdid');
            $_SESSION['cmdid'] = $cmdid;
         } else if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
         }

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();
         if (!array_key_exists($teamid, $teamList)) {
            $teamid = 0;
            $cmdid = 0;
         } else {
            $isManager = $session_user->isTeamManager($teamid);
            if ($isManager) {
               $this->smartyHelper->assign('isManager', true);
            }
         }
         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));
         $this->smartyHelper->assign('commandid', $cmdid);
         $this->smartyHelper->assign('commands', $this->getCommands($teamid, $cmdid));

         // ------ Display Command
         if (0 != $cmdid) {
            $cmd = CommandCache::getInstance()->getCommand($cmdid);

            if (array_key_exists($cmd->getTeamid(), $teamList)) {
               $teamid = $cmd->getTeamid();
               $_SESSION['teamid'] = $teamid;

               // get selected filters
               $selectedFilters="";
               if(isset($_GET['selectedFilters'])) {
                  $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');
               } else {
                  $selectedFilters = $session_user->getCommandFilters($cmdid);
               }
               // cleanup filters (remove empty lines)
               $filterList = explode(',', $selectedFilters);
               $filterList = array_filter($filterList, create_function('$a','return $a!="";'));
               $selectedFilters = implode(',', $filterList);

               CommandTools::displayCommand($this->smartyHelper, $cmd, $isManager, $selectedFilters);

               // ConsistencyCheck
               $consistencyErrors = $this->getConsistencyErrors($cmd);
               if(count($consistencyErrors) > 0) {

                  $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               }

               // check if sold days
               $checkTotalSoldDays = $cmd->getTotalSoldDays() - $cmd->getIssueSelection()->mgrEffortEstim - $cmd->getProvisionDays();
               $checkTotalSoldDays = round($checkTotalSoldDays, 2);
               $this->smartyHelper->assign('checkTotalSoldDays', $checkTotalSoldDays);

               // access rights
               if (($isManager) ||
                  ($session_user->isTeamLeader($cmd->getTeamid()))) {
                  $this->smartyHelper->assign('isEditGranted', true);
               }
            } else {
               // TODO smarty error msg
               echo T_('Sorry, You are not allowed to see this command');
            }
         } else {
            unset($_SESSION['commandsetid']);
            unset($_SESSION['servicecontractid']);

            $action = isset($_POST['action']) ? $_POST['action'] : '';
            if ('displayCommand' == $action) {
               header('Location:command_edit.php?cmdid=0');
            }
         }

      }
   }

   /**
    * @param int $teamid
    * @param int $selectedCmdId
    * @return mixed[]
    */
   private function getCommands($teamid, $selectedCmdId) {
      $commands = array();
      if (0 != $teamid) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $cmdList = $team->getCommands();

         foreach ($cmdList as $id => $cmd) {
            $commands[] = array(
               'id' => $id,
               'name' => $cmd->getName(),
               'reference' => $cmd->getReference(),
               'selected' => ($id == $selectedCmdId)
            );
         }
      }
      return $commands;
   }

   /**
    * Get consistency errors
    * @param Command $cmd
    * @return mixed[]
    */
   private function getConsistencyErrors(Command $cmd) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $cerrList = $cmd->getConsistencyErrors();
      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $titleAttr = array(
                  T_('Project') => $issue->getProjectName(),
                  T_('Summary') => $issue->getSummary(),
            );
            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, $titleAttr),
               'issueStatus' => Constants::$statusNames[$cerr->status],
               'user' => $user->getName(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);
         }
      }

      return $consistencyErrors;
   }

}

// ========== MAIN ===========
CommandInfoController::staticInit();
$controller = new CommandInfoController('Command','Management');
$controller->execute();

?>

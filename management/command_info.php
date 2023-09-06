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

   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            // if cmdid set in URL, use it. else:
            // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
            $cmdid = 0;
            if(isset($_POST['cmdid'])) {
               $cmdid = Tools::getSecurePOSTIntValue('cmdid', 0);
               $_SESSION['cmdid'] = $cmdid;
            } else if(isset($_GET['cmdid'])) {
               $cmdid = Tools::getSecureGETIntValue('cmdid');
               $_SESSION['cmdid'] = $cmdid;
            } else if(isset($_SESSION['cmdid'])) {
               $cmdid = $_SESSION['cmdid'];
            }

            if (!array_key_exists($this->teamid, $this->teamList)) {
               $this->teamid = 0;
               $cmdid = 0;
            } else {
               $isManager = $this->session_user->isTeamManager($this->teamid);
               $isObserver = $this->session_user->isTeamObserver($this->teamid);

               $this->smartyHelper->assign('isManager', $isManager);
               $this->smartyHelper->assign('isObserver', $isObserver);
            }   
            $action = Tools::getSecurePOSTStringValue('action', '');

            // --- CmdStateFilters
            if ('setCmdStateFilters' == $action) {
               $cmdStateFiltersStr = Tools::getSecurePOSTStringValue('checkedCmdStateFilters');
               $this->session_user->setCmdStateFilters($cmdStateFiltersStr, $this->teamid);
            } else {
               $cmdStateFiltersStr = $this->session_user->getCmdStateFilters($this->teamid);
            }
            if (!empty($cmdStateFiltersStr)) {
              $cmdStateFilters = Tools::doubleExplode(':', ',', $cmdStateFiltersStr);
              $this->smartyHelper->assign('isCmdStateFilter', true);
            } else {
               $cmdStateFilters = array();
            }

            $cmdStateFilterInfo = array();
            foreach (Command::$stateNames as $stateId => $stateName) {
               $cmdStateFilterInfo[$stateId] = array(
                  'stateId' => $stateId,
                  'stateName' => $stateName,
                  'isChecked' => array_key_exists($stateId, $cmdStateFilters) ? $cmdStateFilters[$stateId] : 1,
               );
            }
            $this->smartyHelper->assign('cmdStateFilterInfo', $cmdStateFilterInfo);

            // --- commands combobox
            $commands = $this->getCommands($this->teamid, $cmdid, $cmdStateFilters);
            $this->smartyHelper->assign('commands', $commands);

            // check if current cmd should be hidden
            if (!array_key_exists($cmdid, $commands)) {
               $cmdid = 0;
            }

            // ------ Display Command
            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);

               if ($cmd->getTeamid() == $this->teamid) {

                  $this->smartyHelper->assign('commandid', $cmdid);

                  CommandTools::displayCommand($this->smartyHelper, $cmd, ($isManager || $isObserver), $this->teamid);

                  // ConsistencyCheck
                  $consistencyErrors = $this->getConsistencyErrors($cmd);
                  if(count($consistencyErrors) > 0) {

                     $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                     $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
                     $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                  }

                  // check if sold days warning should be displayed
                  if (0 != $cmd->getTotalSoldDays()) {
                     $checkTotalSoldDays = $cmd->getTotalSoldDays() - $cmd->getIssueSelection()->mgrEffortEstim - $cmd->getProvisionDays();
                     $checkTotalSoldDays = round($checkTotalSoldDays, 2);
                     if (0 !== $checkTotalSoldDays) {
                        $this->smartyHelper->assign('checkTotalSoldDays', $checkTotalSoldDays);
                     }
                  }

                  // access rights
                  if (($isManager) ||
                     ($this->session_user->isTeamLeader($cmd->getTeamid()))) {
                     $this->smartyHelper->assign('isEditGranted', true);
                  }

                  // WBS
                  $this->smartyHelper->assign('wbsRootId', $cmd->getWbsid());
                  
                  // Dashboard
                  CommandTools::dashboardSettings($this->smartyHelper, $cmd, $this->session_userid);

                  // Gantt
                  // TODO move this section to the ajax page
                  $ganttWindowStartTimestamp = time();
                  $cmdIssueList = $cmd->getIssueSelection()->getIssueList();
                  foreach ($cmdIssueList as $issue) {
                     // unassigned tasks are not displayed in gantt
                     // unresolved tasks have a starting date >= today (scheduler/gantt default)
                     // tasks with no timetracks are not displayed in gantt
                     if ((0 != $issue->getHandlerId()) && $issue->isResolved()) {
                        $tt = $issue->getFirstTimetrack();
                        if ((NULL != $tt) && ($tt->getDate() < $ganttWindowStartTimestamp)) {
                           $ganttWindowStartTimestamp = $tt->getDate();
                           $mybugid = $issue->getId();
                        }
                     }
                  }
                  $this->smartyHelper->assign('ganttWindowStartDate',  date('Y-m-d', $ganttWindowStartTimestamp));

               }
            } else {
               unset($_SESSION['commandsetid']);
               unset($_SESSION['servicecontractid']);

               if ('displayCommand' == $action) {
                  header('Location:command_edit.php?cmdid=0');
               }
            }
         }
      }
   }

   /**
    * @param int $teamid
    * @param int $selectedCmdId
    * @return mixed[]
    */
   private function getCommands($teamid, $selectedCmdId, $cmdStateFilters) {
      
      $commands = array();
      if (0 != $teamid) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $cmdList = $team->getCommands();

         foreach ($cmdList as $id => $cmd) {

            // skip if state filter is set to 0 (if 1 or unset, cmd is visible)
            $state = $cmd->getState();
            if (array_key_exists($state, $cmdStateFilters)) {
               if (0 == $cmdStateFilters[$state]) { continue; }
            }
            $commands[$id] = array(
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

            if (!is_null($cerr->userId)) {
               $user = UserCache::getInstance()->getUser($cerr->userId);
            } else {
               $user = NULL;
            }
            if (Issue::exists($cerr->bugId)) {
               $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
               $projName = $issue->getProjectName();
               $summary = $issue->getSummary();
            } else {
               $projName = '';
               $summary = '';
            }

            $titleAttr = array(
                  T_('Project') => $projName,
                  T_('Summary') => $summary,
            );
            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, $titleAttr),
               'issueStatus' => Constants::$statusNames[$cerr->status],
               'user' => isset($user) ? $user->getName() : '',
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
$controller = new CommandInfoController('../', 'Command','Management');
$controller->execute();


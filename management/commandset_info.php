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

class CommandSetInfoController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         if (0 != $this->teamid) {

            // if cmdid set in URL, use it. else:
            // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
            $commandsetid = 0;
            if(isset($_POST['commandsetid'])) {
               $commandsetid = Tools::getSecurePOSTIntValue('commandsetid');
               $_SESSION['commandsetid'] = $commandsetid;
            } else if(isset($_GET['commandsetid'])) {
               $commandsetid = Tools::getSecureGETIntValue('commandsetid');
               $_SESSION['commandsetid'] = $commandsetid;
            } else if(isset($_SESSION['commandsetid'])) {
               $commandsetid = $_SESSION['commandsetid'];
            }

            // Managed + Observed teams only
            $oTeamList = $this->session_user->getObservedTeamList();
            $mTeamList = $this->session_user->getManagedTeamList();
            $teamList = $oTeamList + $mTeamList;           // array_merge does not work ?!

            if (empty($teamList) || (!array_key_exists($this->teamid, $teamList))) {
               // only managers (and observers) can access this page.
               return;
            }

            $isManager = $this->session_user->isTeamManager($this->teamid);
            if ($isManager) {
               $this->smartyHelper->assign('isManager', true);
            }

            $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($this->teamid, $commandsetid));

            if (0 != $commandsetid) {
               $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

               if ($this->teamid == $commandset->getTeamid()) {

                  $this->smartyHelper->assign('commandsetid', $commandsetid);
                  
                  // set CommandSets I belong to
                  $this->smartyHelper->assign('parentContracts', CommandSetTools::getParentContracts($commandset));

                  // get selected filters
                  $selectedFilters="";
                  if(isset($_GET['selectedFilters'])) {
                     $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');
                  } else {
                     $selectedFilters = $this->session_user->getCommandSetFilters($commandsetid);
                  }

                  CommandSetTools::displayCommandSet($this->smartyHelper, $commandset, $isManager, $selectedFilters);

                  // ConsistencyCheck
                  $consistencyErrors = $this->getConsistencyErrors($commandset);
                  if (0 != $consistencyErrors) {
                     $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                     $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors affecting the CommandSet"));
                     $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                  }

                  // access rights
                  if (($this->session_user->isTeamManager($commandset->getTeamid())) ||
                     ($this->session_user->isTeamLeader($commandset->getTeamid()))) {
                     $this->smartyHelper->assign('isEditGranted', true);
                  }
               } else {
                  // TODO smarty error msg
                  echo T_('Sorry, You are not allowed to see this commandSet');
               }
            } else {
               unset($_SESSION['cmdid']);
               unset($_SESSION['servicecontractid']);

               $action = Tools::getSecurePOSTStringValue('action', '');
               if ('displayCommandSet' == $action) {
                  header('Location:commandset_edit.php?commandsetid=0');
               }
            }
         }
      }
   }

   /**
    * Get consistency errors
    * @param CommandSet $cmdset
    * @return mixed[]
    */
   private function getConsistencyErrors(CommandSet $cmdset) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $cerrList = $cmdset->getConsistencyErrors();
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
CommandSetInfoController::staticInit();
$controller = new CommandSetInfoController('CommandSet','Management');
$controller->execute();

?>

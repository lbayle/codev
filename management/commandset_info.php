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
      if (isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // use the teamid set in the form, if not defined (first page call) use session teamid
         $teamid = Tools::getSecurePOSTIntValue('teamid', 0);
         if(0 == $teamid) {
            if(isset($_SESSION['teamid'])) {
               $teamid = $_SESSION['teamid'];
            }
         }
         $_SESSION['teamid'] = $teamid;

         // if cmdid set in URL, use it. else:
         // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
         $commandsetid = Tools::getSecureGETIntValue('commandsetid', 0);
         if (0 == $commandsetid) {
            if(isset($_POST['commandsetid'])) {
               $commandsetid = $_POST['commandsetid'];
            } else if(isset($_SESSION['commandsetid'])) {
               $commandsetid = $_SESSION['commandsetid'];
            }
         }
         $_SESSION['commandsetid'] = $commandsetid;

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();

         $action = Tools::getSecurePOSTStringValue('action', '');

         if (0 != $commandsetid) {
            $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

            if (array_key_exists($commandset->getTeamid(), $teamList)) {
               $teamid = $commandset->getTeamid();
               $_SESSION['teamid'] = $teamid;

               // set CommandSets I belong to
               $this->smartyHelper->assign('parentContracts', CommandSetTools::getParentContracts($commandset));

               CommandSetTools::displayCommandSet($this->smartyHelper, $commandset);

               // ConsistencyCheck
               $consistencyErrors = $this->getConsistencyErrors($commandset);
               if (0 != $consistencyErrors) {
                  $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors affecting the CommandSet"));
                  $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               }

               // access rights
               if (($session_user->isTeamManager($commandset->getTeamid())) ||
                  ($session_user->isTeamLeader($commandset->getTeamid()))) {
                  $this->smartyHelper->assign('isEditGranted', true);
               }
            } else {
               // TODO smarty error msg
               echo T_('Sorry, You are not allowed to see this commandSet');
            }
         } else {
            unset($_SESSION['cmdid']);
            unset($_SESSION['servicecontractid']);

            if ('displayCommandSet' == $action) {
               header('Location:commandset_edit.php?commandsetid=0');
            }
         }

         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

         $this->smartyHelper->assign('commandsetid', $commandsetid);
         $this->smartyHelper->assign('commandsets', CommandSetTools::getCommandSets($teamid, $commandsetid));
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
            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
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

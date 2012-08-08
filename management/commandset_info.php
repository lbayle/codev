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

require('include/super_header.inc.php');

require('management/commandset_tools.php');

require_once('smarty_tools.php');
require_once('tools.php');

/**
 * Get consistency errors
 * @param CommandSet $cmdset
 * @return mixed[]
 */
function getConsistencyErrors(CommandSet $cmdset) {
   global $statusNames;

   $consistencyErrors = array(); // if null, array_merge fails !

   $cerrList = $cmdset->getConsistencyErrors();
   if (count($cerrList) > 0) {
      foreach ($cerrList as $cerr) {
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $consistencyErrors[] = array(
             'issueURL' => Tools::issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
             'issueStatus' => $statusNames[$cerr->status],
             'user' => $user->getName(),
             'severity' => $cerr->getLiteralSeverity(),
             'severityColor' => $cerr->getSeverityColor(),
             'desc' => $cerr->desc);
      }
   }

   return $consistencyErrors;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('CommandSet'));
$smartyHelper->assign('activeGlobalMenuItem', 'Management');

if (isset($_SESSION['userid'])) {
   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

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

   $action = isset($_POST['action']) ? $_POST['action'] : '';

   if (0 != $commandsetid) {
      $commandset = CommandSetCache::getInstance()->getCommandSet($commandsetid);

      if (array_key_exists($commandset->getTeamid(), $teamList)) {
         $teamid = $commandset->getTeamid();
         $_SESSION['teamid'] = $teamid;

         // set CommandSets I belong to
         $smartyHelper->assign('parentContracts', getParentContracts($commandset));

         displayCommandSet($smartyHelper, $commandset);

               // ConsistencyCheck
         $consistencyErrors = getConsistencyErrors($commandset);
         if (0 != $consistencyErrors) {
            $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
            $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors affecting the CommandSet"));
            $smartyHelper->assign('ccheckErrList', $consistencyErrors);
         }

         // access rights
         if (($session_user->isTeamManager($commandset->getTeamid())) ||
            ($session_user->isTeamLeader($commandset->getTeamid()))) {

            $smartyHelper->assign('isEditGranted', true);
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

   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

   $smartyHelper->assign('commandsetid', $commandsetid);
   $smartyHelper->assign('commandsets', getCommandSets($teamid, $commandsetid));
}

$smartyHelper->displayTemplate($mantisURL);

?>

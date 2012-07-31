<?php

include_once('../include/session.inc.php');
/*
  This file is part of CodevTT

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

require '../path.inc.php';

require('super_header.inc.php');

/* INSERT INCLUDES HERE */
require_once "servicecontract.class.php";
require_once "commandset.class.php";
require_once "command.class.php";
require_once "user.class.php";
require_once "team.class.php";

require_once "servicecontract_tools.php";

require_once "smarty_tools.php";

require('display.inc.php');



/* INSERT FUNCTIONS HERE */
/**
 * Get consistency errors
 * @param Command $cmd
 */
function getConsistencyErrors($serviceContract) {
   global $statusNames;

   $consistencyErrors = array(); // if null, array_merge fails !

   $cerrList = $serviceContract->getConsistencyErrors();
   if (count($cerrList) > 0) {
      $i = 0;
      foreach ($cerrList as $cerr) {
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $consistencyErrors[] = array(
             'issueURL' => issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
             'issueStatus' => $statusNames[$cerr->status],
             'user' => $user->getName(),
             'severity' => $cerr->getLiteralSeverity(),
             'severityColor' => $cerr->getSeverityColor(),
             'desc' => $cerr->desc);
      }
      $i++;
   }

   return $consistencyErrors;
}


// ================ MAIN =================

$logger = Logger::getLogger("servicecontract_info");

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_("Service Contract"));
$smartyHelper->assign('activeGlobalMenuItem', 'Management');

if (isset($_SESSION['userid'])) {

   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if (isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if (isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }
   $_SESSION['teamid'] = $teamid;

   // ---
   // use the servicecontractid set in the form, if not defined (first page call) use session servicecontractid
   $servicecontractid = 0;
   if(isset($_POST['servicecontractid'])) {
      $servicecontractid = $_POST['servicecontractid'];
   } else if(isset($_SESSION['servicecontractid'])) {
      $servicecontractid = $_SESSION['servicecontractid'];
   }
   $_SESSION['servicecontractid'] = $servicecontractid;

   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));

   $smartyHelper->assign('servicecontractid', $servicecontractid);
   $smartyHelper->assign('servicecontracts', getServiceContracts($teamid, $servicecontractid));


   $action = isset($_POST['action']) ? $_POST['action'] : '';

   if (0 != $servicecontractid) {
      $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      displayServiceContract($smartyHelper, $servicecontract);

            // ConsistencyCheck
      $consistencyErrors = getConsistencyErrors($servicecontract);
      if (0 != $consistencyErrors) {
         $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('ccheckErrList', $consistencyErrors);
      }

      // access rights
      if (($session_user->isTeamManager($servicecontract->getTeamid())) ||
          ($session_user->isTeamLeader($servicecontract->getTeamid()))) {

         $smartyHelper->assign('isEditGranted', true);
      }

   } else {
      unset($_SESSION['cmdid']);
      unset($_SESSION['commandsetid']);

      if ('displayServiceContract' == $action) {
         header('Location:servicecontract_edit.php?servicecontractid=0');
      }

   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>
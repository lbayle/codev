<?php
include_once('../include/session.inc.php');

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

require('super_header.inc.php');

include_once('consistency_check.class.php');
include_once('consistency_check2.class.php');
include_once('user.class.php');
include_once('team.class.php');

$logger = Logger::getLogger("check");

/**
 * Get teams
 * @param $teamList
 * @return array
 */
function getTeams($teamList) {
   foreach ($teamList as $tid => $tname) {
      $teams[] = array(
         'id' => $tid,
         'name' => $tname,
         'selected' => $tid == $_SESSION['teamid']
      );
   }
   return $teams;
}


/**
 * Get consistency errors
 * @param int User's id
 */
function getTeamConsistencyErrors($teamid) {

   global $logger;
   global $statusNames;

   $logger->debug("getTeamConsistencyErrors teamid=$teamid");

   // get team projects
   $issueList = Team::getTeamIssues($teamid, true);

   $logger->debug("getTeamConsistencyErrors nbIssues=".count($issueList));

   $ccheck = new ConsistencyCheck2($issueList);
   $cerrList = $ccheck->check();

   if (count($cerrList) > 0) {
      foreach ($cerrList as $cerr) {
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);

         $cerrs[] = array('userName' => $user->getName(),
            'mantisIssueURL' => mantisIssueURL($cerr->bugId, $issue->summary),
            'date' => date("Y-m-d", $cerr->timestamp),
            'status' => $statusNames[$cerr->status],
            'severity' => $cerr->getLiteralSeverity(),
            'project' => $issue->getProjectName(),
            'targetVersion' => $issue->getTargetVersion(),
            'desc' => $cerr->desc);
      }
      return $cerrs;
   }
}



// ================ MAIN =================

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Consistency Check'));

// Consistency errors
if (isset($_SESSION['userid'])) {
   // use the teamid set in the form, if not defined (first page call) use session teamid
   if (isset($_GET['teamid'])) {
      $teamid = $_GET['teamid'];
   } else {
      $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   }
   $_SESSION['teamid'] = $teamid;

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $mTeamList = $session_user->getDevTeamList();
   $lTeamList = $session_user->getLeadedTeamList();
   $oTeamList = $session_user->getObservedTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

   if (count($teamList) > 0) {
      $smartyHelper->assign('teams', getTeams($teamList));

      if (isset($_GET['teamid']) && 0 != $teamid) {

         $consistencyErrors = getTeamConsistencyErrors($teamid);

         $smartyHelper->assign('teamid', $teamid);
         $smartyHelper->assign('count', count($consistencyErrors));
         if(isset($consistencyErrors)) {
            $smartyHelper->assign('consistencyErrors', $consistencyErrors);
         }
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

// log stats
IssueCache::getInstance()->logStats();
ProjectCache::getInstance()->logStats();

?>

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

require('super_header.inc.php');

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

include_once('classes/consistency_check.class.php');
include_once('classes/consistency_check2.class.php');
include_once('classes/user.class.php');
include_once('classes/team.class.php');

$logger = Logger::getLogger("check");

/**
 * Get consistency errors
 * @param int $teamid
 * @return array
 */
function getTeamConsistencyErrors($teamid) {
   global $logger;
   global $statusNames;

   $logger->debug("getTeamConsistencyErrors teamid=$teamid");

   // get team projects
   $issueList = Team::getTeamIssues($teamid, true);

   $logger->debug("getTeamConsistencyErrors nbIssues=".count($issueList));

   #$ccheck = new ConsistencyCheck2($issueList);
   $ccheck = new ConsistencyCheck2($issueList, $teamid);
   
   $cerrList1 = $ccheck->check();
   $cerrList2 = $ccheck->checkTeamTimetracks();
   $cerrList = array_merge($cerrList1, $cerrList2);

   if (count($cerrList) > 0) {
      foreach ($cerrList as $cerr) {
         $user = UserCache::getInstance()->getUser($cerr->userId);
         try {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            $summary = $issue->summary;
            $projName = $issue->getProjectName();
            $targetVersion = $issue->getTargetVersion();
         } catch (Exception $e) {
            $summary = '';
            $projName = '';
            $targetVersion = '';
         }

         $cerrs[] = array('userName' => $user->getName(),
            'issueURL' => issueInfoURL($cerr->bugId, $summary),
            'mantisURL' => mantisIssueURL($cerr->bugId, $summary, true),
            'date' => date("Y-m-d", $cerr->timestamp),
            'status' => $statusNames[$cerr->status],
            'severity' => $cerr->getLiteralSeverity(),
            'project' => $projName,
            'targetVersion' => $targetVersion,
            'desc' => $cerr->desc);
      }
      return $cerrs;
   }
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Consistency Check');

// Consistency errors
if (isset($_SESSION['userid'])) {
   // use the teamid set in the form, if not defined (first page call) use session teamid
   if (isset($_GET['teamid'])) {
      $teamid = getSecureGETIntValue('teamid');
      $_SESSION['teamid'] = $teamid;
   } else {
      $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   }

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $teamList = $session_user->getTeamList();

   if (count($teamList) > 0) {
      $smartyHelper->assign('teams', getTeams($teamList, $_SESSION['teamid']));

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

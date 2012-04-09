<?php
if (!isset($_SESSION)) {
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname);
	session_start();
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
}

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
 * Get consistency errors
 * @param int User's id
 */
function getConsistencyErrors($userid) {
    $sessionUser = new User($userid);

    global $logger;
    global $statusNames;

    $logger->debug("getConsistencyErrors userid=$userid");

    // get projects i'm involved in (dev, Leader, Manager)
    $devTeamList = $sessionUser->getDevTeamList();
    $leadedTeamList = $sessionUser->getLeadedTeamList();
    $managedTeamList = $sessionUser->getManagedTeamList();
    $oTeamList = $sessionUser->getObservedTeamList();

    $teamList = $devTeamList + $leadedTeamList + $managedTeamList + $oTeamList;
    $projectList = $sessionUser->getProjectList($teamList);

    #$ccheck = new ConsistencyCheck($projectList);

    $formatedProjects = implode( ', ', array_keys($projectList));
    $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
	    "FROM `mantis_bug_table` ".
		 "WHERE project_id IN ($formatedProjects) ";

    $result = mysql_query($query);
    if (!$result) {
	    $logger->error("Query FAILED: $query");
	    $logger->error(mysql_error());
	    echo "<span style='color:red'>ERROR: Query FAILED</span>";
	    exit;
    }
    $issueList = array();
    while($row = mysql_fetch_object($result))
    {
	    $issue = IssueCache::getInstance()->getIssue($row->bug_id);
	    $issueList[$row->bug_id] = $issue;
    }

    $ccheck = new ConsistencyCheck2($issueList);
    $cerrList = $ccheck->check();

    if (count($cerrList) > 0) {
        global $count;
        $count = count($cerrList);
        foreach ($cerrList as $cerr) {
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);

            // do not display users that do not belong to a team
            $userProjList = array_keys($user->getProjectList());

            if ((in_array($issue->projectId, $userProjList)) ||
                (0 == $cerr->userId)) {

               $cerrs[] = array('userName' => $user->getName(),
                     'mantisIssueURL' => mantisIssueURL($cerr->bugId, $issue->summary),
                     'date' => date("Y-m-d", $cerr->timestamp),
                     'status' => $statusNames[$cerr->status],
                     'severity' => $cerr->severity,
                     'project' => $issue->getProjectName(),
                     'desc' => $cerr->desc);
            }
        }

        return $cerrs;
    }
}

/**
 * Get consistency errors
 * @param int User's id
 */
function getTeamConsistencyErrors($teamid) {
   $sessionUser = new User($userid);

   global $logger;
   global $statusNames;

   $logger->debug("getTeamConsistencyErrors teamid=$teamid");

   // get team projects
   $projectList = Team::getProjectList($teamid);

   $ccheck = new ConsistencyCheck($projectList);
   $cerrList = $ccheck->check();

   if (count($cerrList) > 0) {
      global $count;
      $count = count($cerrList);
      foreach ($cerrList as $cerr) {
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);

         // check if issue is assigned to a user from the selected teams
         $userTeamList = $user->getTeamList();
         // TODO


         $cerrs[] = array('userName' => $user->getName(),
               'mantisIssueURL' => mantisIssueURL($cerr->bugId, $issue->summary),
               'date' => date("Y-m-d", $cerr->timestamp),
               'status' => $statusNames[$cerr->status],
               'severity' => $cerr->severity,
               'project' => $issue->getProjectName(),
               'desc' => $cerr->desc);
      }

      $sortedCerrList = qsort($cerrs);
      return $sortedCerrList;
   }
}


// ================ MAIN =================

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Consistency Check'));

// Consistency errors
if (isset($_SESSION['userid'])) {
    $consistencyErrors = getConsistencyErrors($_SESSION['userid']);
    $smartyHelper->assign('count', $count);
    if(isset($consistencyErrors)) {
        $smartyHelper->assign('consistencyErrors', $consistencyErrors);
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

// log stats
IssueCache::getInstance()->logStats();
ProjectCache::getInstance()->logStats();

?>

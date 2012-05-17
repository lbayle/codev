<?php
include_once('./include/session.inc.php');

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

// === check if INSTALL needed
if ((!file_exists('constants.php')) || (!file_exists('include/mysql_config.inc.php'))) {
    header('Location: install/install.php');
    exit;
}

include_once ('path.inc.php');

require('super_header.inc.php');

$logger = Logger::getLogger("homepage");

include_once('consistency_check.class.php');
include_once('consistency_check2.class.php');
include_once('user.class.php');
include_once('issue.class.php');

/**
 * The browser is ie ?
 */
function isIE () {
    $useragent = $_SERVER["HTTP_USER_AGENT"];
    if (preg_match("|MSIE ([0-9].[0-9]{1,2})|",$useragent,$matched)) {
        return TRUE;
    }
    return TRUE;
}

/**
 * Get issues in drift
 * @param int User's id
 */
function getIssuesInDrift($userid) {
    $user = UserCache::getInstance()->getUser($userid);
    $allIssueList = $user->getAssignedIssues();

    foreach ($allIssueList as $issue) {
        $driftEE = $issue->getDrift();
        if ($driftEE >= 1) {
            $issueList[] = $issue;
        }
    }
    if (count($issueList) > 0) {
        foreach ($issueList as $issue) {
            // TODO: check if issue in team project list ?
            $driftEE = $issue->getDrift();

            $formatedTitle = $issue->bugId." / ".$issue->tcId;
            $formatedSummary = str_replace("'", "\'", $issue->summary);
            $formatedSummary = str_replace('"', "\'", $formatedSummary);

            $driftedTasks[] = array('issueInfoURL' => issueInfoURL($issue->bugId),
                                    'projectName' => $issue->getProjectName(),
                                    'driftEE' => $driftEE,
                                    'formatedTitle' => $formatedTitle,
                                    'bugId' => $issue->bugId,
                                    'remaining' => $issue->remaining,
                                    'formatedSummary' => $formatedSummary,
                                    'summary' => $issue->summary);
        }
    }

    return $driftedTasks;
}


/**
 * Get consistency errors
 * @param int User's id
 */
function getConsistencyErrors($userid) {

   $consistencyErrors = array(); // if null, array_merge fails !

    $sessionUser = UserCache::getInstance()->getUser($userid);

    $teamList = $sessionUser->getTeamList();
    $projList = $sessionUser->getProjectList($teamList);

    $issueList = $sessionUser->getAssignedIssues($projList, true);

    $ccheck = new ConsistencyCheck2($issueList);

    $cerrList = $ccheck->check();

    if (count($cerrList) > 0) {
        $i = 0;
        foreach ($cerrList as $cerr) {
            if ($sessionUser->id == $cerr->userId) {
                $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
                $consistencyErrors[] = array('issueURL' => issueInfoURL($cerr->bugId, $issue->summary),
                                             'status' => $statusNames[$cerr->status],
                                             'desc' => $cerr->desc);
            }
        }
        $i++;
    }

    return $consistencyErrors;
}

/**
 * managers get some more consistencyErrors
 */
function getConsistencyErrorsMgr($userid) {

   $consistencyErrors = array(); // if null, array_merge fails !

   $sessionUser = UserCache::getInstance()->getUser($userid);

   $consistencyErrors = array(); // if null, array_merge fails !

    $mTeamList = array_keys($sessionUser->getManagedTeamList());
    $lTeamList = array_keys($sessionUser->getLeadedTeamList());
    $teamList = array_merge($mTeamList, $lTeamList);

    $issueList = array();
    foreach ($teamList as $teamid) {
       $issues = Team::getTeamIssues($teamid, true);
       $issueList = array_merge($issueList, $issues);
    }

    $ccheck = new ConsistencyCheck2($issueList);

    // ---
    $cerrList = $ccheck->checkMgrEffortEstim();
    if (count($cerrList) > 0) {
	    $consistencyErrors[] = array('mantisIssueURL' => ' ',
		    'date' => ' ',
			 'status' => ' ',
			 'desc' => count($cerrList).' '.T_("Tasks need MgrEffortEstim to be set."));
    }

    // ---
    $cerrList = $ccheck->checkUnassignedTasks();
    if (count($cerrList) > 0) {
       $consistencyErrors[] = array('mantisIssueURL' => ' ',
          'date' => ' ',
          'status' => ' ',
          'desc' => count($cerrList).' '.T_("Tasks need to be assigned."));
    }

    return $consistencyErrors;
}

// ================ MAIN =================

// updateRemaining DialogBox
$action = isset($_POST['action']) ? $_POST['action'] : '';
if ('updateRemainingAction' == $action) {
    $bugid = isset($_POST['bugid']) ? $_POST['bugid'] : '';
    if ("0" != $bugid) {
        $remaining = isset($_POST['remaining']) ? $_POST['remaining'] : '';
        $issue = IssueCache::getInstance()->getIssue($bugid);
        $issue->setRemaining($remaining);
    }
}

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_($homepage_title));

// Drifted tasks
if($_SESSION['userid']) {
    $driftedTasks = getIssuesInDrift($_SESSION['userid']);
    if(isset($driftedTasks)) {
        $smartyHelper->assign('driftedTasks', $driftedTasks);
    }
}

// Consistency errors
if($_SESSION['userid']) {
    $consistencyErrors    = getConsistencyErrors($_SESSION['userid']);
    $consistencyErrorsMgr = getConsistencyErrorsMgr($_SESSION['userid']);

    $consistencyErrors = array_merge($consistencyErrors, $consistencyErrorsMgr);

    if(count($consistencyErrors) > 0) {
        $smartyHelper->assign('consistencyErrorsTitle', count($consistencyErrors).' '.T_("Errors in your Tasks"));
       $smartyHelper->assign('consistencyErrors', $consistencyErrors);
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

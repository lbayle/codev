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

include_once('team.class.php');
include_once('user_cache.class.php');
include_once('time_tracking.class.php');

$logger = Logger::getLogger("set_holidays");

/**
 * Get users of teams I lead
 * @return array of users
 */
function getUsers() {
    global $logger;
  
    $accessLevel_dev = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;

    $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
    $teamList = $session_user->getLeadedTeamList();

    // separate list elements with ', '
    $formatedTeamString = implode( ', ', array_keys($teamList));

    // show only users from the teams that I lead.
    $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
             "FROM `mantis_user_table`, `codev_team_user_table` ".
             "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
             "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
             "AND codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
             "ORDER BY mantis_user_table.username";

    $result = mysql_query($query);
    if (!$result) {
        $logger->error("Query FAILED: $query");
        $logger->error(mysql_error());
        return;
    }

    while($row = mysql_fetch_object($result)) {
        $users[] = array('id' => $row->id,
                         'name' => $row->username,
                         'selected' => $row->id == $_SESSION['userid']
        );
    }

    return $users;
}

/**
 * Get issues
 * @param int $defaultProjectid
 * @param array $projList
 * @param int $extproj_id
 * @param int $defaultBugid
 * @return array
 */
function getIssues($defaultProjectid, $projList, $extproj_id, $defaultBugid) {
    global $logger;

    $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);

    // --- Task list
    if (0 != $project1->id) {
        $issueList = $project1->getIssueList();
    } else {
        // no project specified: show all tasks
        $issueList = array();
        $formatedProjList = implode(', ', array_keys($projList));

        $query = "SELECT id " .
                 "FROM `mantis_bug_table` " .
                 "WHERE project_id IN ($formatedProjList) " .
                 "ORDER BY id DESC";
        $result = mysql_query($query);
        if (!$result) {
            $logger->error("Query FAILED: $query");
            $logger->error(mysql_error());
            return;
        }
        if (0 != mysql_num_rows($result)) {
            while ($row = mysql_fetch_object($result)) {
                $issueList[] = $row->id;
            }
        }
    }

    foreach ($issueList as $bugid) {
        $issue = IssueCache::getInstance()->getIssue($bugid);
        if (($issue->isVacation()) || ($extproj_id == $issue->projectId)) {
            $issues[] = array('id' => $bugid,
                              'tcId' => $issue->tcId,
                              'summary' => $issue->summary,
                              'selected' => $bugid == $defaultBugid);
        }
    }

    return $issues;
}

/**
 * Get projects
 * @param int $defaultProjectid
 * @param array $projectList
 * @return array
 */
function getProjects($defaultProjectid, $projectList) {
    foreach ($projectList as $pid => $pname) {
        $projects[] = array('id' => $pid,
                            'name' => $pname,
                            'selected' => $pid == $defaultProjectid
        );
    }
    return $projects;
}

/**
 * Get jobs
 * @param int $defaultProjectid
 * @param array $projList
 * @return array|mixed
 */
function getJobs($defaultProjectid, $projList) {
    $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);

    // --- Job list
    if (0 != $project1->id) {
        $jobList = $project1->getJobList();
    } else {
        $jobList = array();
        foreach ($projList as $pid2 => $pname) {
            $tmpPrj1 = ProjectCache::getInstance()->getProject($pid2);
            $jobList += $tmpPrj1->getJobList();
        }
    }
    // do not display selector if only one Job
    if (1 == count($jobList)) {
        reset($jobList);
        return key($jobList);
    } else {
        return $jobList;
    }
}

// =========== MAIN ==========

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Add Holidays'));

if (isset($_SESSION['userid'])) {
    // if first call to this page
    if (!isset($_POST['nextForm'])) {
        $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
        $teamList = $session_user->getLeadedTeamList();
        if (0 != count($teamList)) {
            // User is TeamLeader, let him choose the user he wants to manage
            $users = getUsers($originPage);
            $smartyHelper->assign('users', $users);
        } else {
            // developper & manager can add timeTracks
            $mTeamList = $session_user->getDevTeamList();
            $managedTeamList = $session_user->getManagedTeamList();
            $teamList = $mTeamList + $managedTeamList;

            if (0 != count($teamList)) {
                $_POST['nextForm'] = "addHolidaysForm";
            }
        }
    }

    if ($_POST['nextForm'] == "addHolidaysForm") {
        $userid = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];
        $managed_user = UserCache::getInstance()->getUser($userid);

        // dates
        $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : date("Y-m-d");
        $smartyHelper->assign('startDate', $startdate);

        $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : date("Y-m-d");
        $smartyHelper->assign('endDate', $enddate);

        $defaultBugid = isset($_POST['bugid']) ? $_POST['bugid'] : 0;
        $defaultProjectid  = isset($_POST['projectid']) ? $_POST['projectid'] : 0;

        $action = isset($_POST['action']) ? $_POST['action'] : '';
        if ("addHolidays" == $action) {
            // TODO add tracks !
            $bugid = $_POST['bugid'];
            $job = $_POST['job'];

            $holydays = Holidays::getInstance();

            $startTimestamp = date2timestamp($startdate);
            $endTimestamp = date2timestamp($enddate);

            // save to DB
            $timestamp = $startTimestamp;
            while ($timestamp <= $endTimestamp) {
                // check if not a fixed holiday
                if (!$holydays->isHoliday($timestamp)) {
                    // TODO check existing timetracks on $timestamp and adjust duration
                    $duration  = 1;

                    //echo "INFO  ".date("Y-m-d", $timestamp)." duration $duration job $job<br/>";
                    TimeTrack::create($managed_user->id, $bugid, $job, $timestamp, $duration);
                }
                $timestamp = strtotime("+1 day",$timestamp);;
            }
            // We redirect to holidays report, so the user can verify his holidays
            header('Location:holidays_report.php');
        } elseif ("setProjectid" == $action) {
            // pre-set form fields
            $defaultProjectid  = $_POST['projectid'];
        } elseif ("setBugId" == $action) {
            // --- pre-set form fields
            // find ProjectId to update categories
            $defaultBugid = $_POST['bugid'];
            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            $defaultProjectid  = $issue->projectId;
        }

        $smartyHelper->assign('otherrealname', $managed_user->getRealname());

        $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);

        // --- SideTasks Project List
        $devProjList = $managed_user->getProjectList();
        $managedProjList = $managed_user->getProjectList($managed_user->getManagedTeamList());
        $projList = $devProjList + $managedProjList;

        foreach ($projList as $pid => $pname) {
            // we want only SideTasks projects
            $tmpPrj = ProjectCache::getInstance()->getProject($pid);
            if (!$tmpPrj->isSideTasksProject()) {
                unset($projList[$pid]);
            }
        }
        $extProj = ProjectCache::getInstance()->getProject($extproj_id);
        $projList[$extproj_id] = $extProj->name;

        $smartyHelper->assign('projects', getProjects($defaultProjectid, $projList));
        $smartyHelper->assign('issues', getIssues($defaultProjectid, $projList, $extproj_id, $defaultBugid));
        $smartyHelper->assign('jobs', getJobs($defaultProjectid, $projList));

        $smartyHelper->assign('userid', $managed_user->id);
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

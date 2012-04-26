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

include("team.class.php");
include("user_cache.class.php");

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
 * Get projects
 * @return array
 */
function getProjects() {
    $projectList = Team::getProjectList($_SESSION['teamid'], false);
    foreach ($projectList as $pid => $pname) {
        $projects[] = array(
            'id' => $pid,
            'name' => $pname,
            'selected' => $pid == $_SESSION['projectid']
        );
    }
    return $projects;
}

# ============= MAIN =================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Gantt Chart'));

if (isset($_SESSION['userid'])) {
    $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
    $defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
    $_SESSION['teamid'] = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
    $_SESSION['projectid'] = isset($_POST['projectid']) ? $_POST['projectid'] : $defaultProject;

    $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
    $mTeamList = $session_user->getDevTeamList();
    $lTeamList = $session_user->getLeadedTeamList();
    $oTeamList = $session_user->getObservedTeamList();
    $managedTeamList = $session_user->getManagedTeamList();
    $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;
    if (count($teamList) > 0) {
        $smartyHelper->assign('teams', getTeams($teamList));
        $smartyHelper->assign('projects', getProjects());

        $weekDates = week_dates(date('W'),date('Y'));

        // The first day of the current week
        $defaultStartDate = mktime(0, 0, 0, date("m", $weekDates[1]), date("d", $weekDates[1]), date("Y", $weekDates[1]));
        $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : date("Y-m-d", $defaultStartDate);
        $smartyHelper->assign('startDate', $startdate);

        // The current date plus one year
        $defaultEndDate = mktime(0, 0, 0, date("m"), date("d"), date("Y")+1);
        $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : date("Y-m-d", $defaultEndDate);
        $smartyHelper->assign('endDate', $enddate);

        if ("ganttReport" == $_POST['action'] && 0 != $_SESSION['teamid']) {
            $teamid = $_SESSION['teamid'];
            $projectid = $_SESSION['projectid'];
            $startT = date2timestamp($startdate);
            $endT = date2timestamp($enddate);
            #$endT += 24 * 60 * 60 -1; // + 1 day -1 sec.

            // draw graph
            $graphURL = getServerRootURL()."/graphs/gantt_graph.php?teamid=$teamid&projects=$projectid&startT=$startT&endT=$endT";
            $smartyHelper->assign('urlGraph', SmartUrlEncode($graphURL));
        }
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

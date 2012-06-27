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

require('smarty_tools.php');

require_once("team.class.php");
require_once("user_cache.class.php");

# ============= MAIN =================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Gantt Chart');

if (isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $mTeamList = $session_user->getDevTeamList();
   $lTeamList = $session_user->getLeadedTeamList();
   $oTeamList = $session_user->getObservedTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;
   if (count($teamList) > 0) {
      if(isset($_POST['teamid']) && array_key_exists($_POST['teamid'],$teamList)) {
         $teamid = $_POST['teamid'];
         $_SESSION['teamid'] = $_POST['teamid'];
      }
      else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
         $teamid = $_SESSION['teamid'];
      }
      else {
         $teamsid = array_keys($teamList);
         $teamid = $teamsid[0];
      }
      $smartyHelper->assign('teams', getTeams($teamList,$teamid));

      $projects[0] = T_('All projects');
      $projects += Team::getProjectList($teamid, false);

      $projectid = 0;
      if(isset($_POST['projectid']) && array_key_exists($_POST['projectid'],$projects)) {
         $projectid = $_POST['projectid'];
         $_SESSION['projectid'] = $_POST['projectid'];
      }
      else if(isset($_SESSION['projectid']) && array_key_exists($_SESSION['projectid'],$projects)) {
         $projectid = $_SESSION['projectid'];
      }
      $smartyHelper->assign('projects', getProjects($projects,$projectid));

      // The first day of the current week
      $weekDates = week_dates(date('W'),date('Y'));
      $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : formatDate("%Y-%m-%d",$weekDates[1]);
      $smartyHelper->assign('startDate', $startdate);

      // The current date plus one year
      $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : formatDate("%Y-%m-%d", strtotime('+1 year'));
      $smartyHelper->assign('endDate', $enddate);

      if (isset($_POST['teamid']) && 0 != $teamid) {
         $startT = date2timestamp($startdate);
         $endT = date2timestamp($enddate);
         #$endT += 24 * 60 * 60 -1; // + 1 day -1 sec.

         // draw graph
         $smartyHelper->assign('urlGraph', 'teamid='.$teamid.'&projects='.$projectid.'&startT='.$startT.'&endT='.$endT);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

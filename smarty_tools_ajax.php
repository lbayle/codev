<?php
require('include/session.inc.php');
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

if(isset($_SESSION['userid']) && (isset($_GET['action']) || isset($_POST['action']))) {

   require('path.inc.php');
   require('super_header.inc.php');
   require('smarty_tools.php');

   if(isset($_GET['action'])) {
      require('display.inc.php');
      require('i18n.inc.php');

      $smartyHelper = new SmartyHelper();

      if($_GET['action'] == 'getTeamProjects') {
         require_once('team.class.php');

      $projects[0] = T_('All projects');
      $projects += Team::getProjectList($_GET['teamid'], false);
      $smartyHelper->assign('projects', getProjects($projects));
         $smartyHelper->display('form/projectSelector');
      }
      else if($_GET['action'] == 'getProjectIssues') {
         require_once('user_cache.class.php');

         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // --- define the list of tasks the user can display
         // All projects from teams where I'm a Developper or Manager AND Observer
         $allProject[0] = T_('(all)');
         $dTeamList = $user->getDevTeamList();
         $devProjList = count($dTeamList) > 0 ? $user->getProjectList($dTeamList) : array();
         $managedTeamList = $user->getManagedTeamList();
         $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList) : array();
         $oTeamList = $user->getObservedTeamList();
         $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList) : array();
         $projList = $allProject + $devProjList + $managedProjList + $observedProjList;

         // WORKAROUND
         if($_GET['bugid'] == 'null') {
            $_GET['bugid'] = 0;
         }
         $smartyHelper->assign('bugs', getBugs(getSecureGETIntValue('projectid'),getSecureGETIntValue('bugid',0),$projList));
         $smartyHelper->display('form/bugSelector');
      }
      else if($_GET['action'] == 'getProjectDetails') {
         require_once('reports/productivity_report_tools.php');

         $weekDates  = week_dates(date('W'),date('Y'));
         $startdate  = isset($_GET["startdate"]) ? $_GET["startdate"] : date("Y-m-d", $weekDates[1]);
         $startTimestamp = date2timestamp($startdate);

         $enddate  = isset($_GET["enddate"]) ? $_GET["enddate"] : date("Y-m-d", $weekDates[5]);
         $endTimestamp = date2timestamp($enddate);
         $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $_GET['teamid']);

         $projectid  = $_GET['projectid'];
         $projectDetails = NULL;
         if (isset($projectid) && 0 != $projectid) {
            $projectDetails = getProjectDetails($timeTracking, $projectid);
         } else {
            // all sideTasks
            $projectDetails = getSideTasksProjectDetails($timeTracking);
         }
         $smartyHelper->assign('projectDetails', $projectDetails);
         if($projectDetails != NULL) {
            $smartyHelper->assign('projectDetailsUrl', getProjectDetailsUrl($projectDetails));
         }
         $smartyHelper->display('ajax/projectDetails');
      }
      else if($_GET['action'] == 'getYearsToNow') {
         require_once('team.class.php');

         $team = new Team($_GET['teamid']);
         $min_year = date("Y", $team->date);
         $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;
         $smartyHelper->assign('years', getYearsToNow($min_year,$year));
         $smartyHelper->display('form/yearSelector');
      }
      else {
         sendNotFoundAccess();
      }
   }
   else if($_POST['action']) {
      if($_POST['action'] == 'updateRemainingAction') {
         require_once('issue_cache.class.php');

         $issue = IssueCache::getInstance()->getIssue(getSecurePOSTIntValue('bugid'));
         $issue->setRemaining(getSecurePOSTNumberValue('remaining'));
      }
      else {
         sendNotFoundAccess();
      }
   }
}
else {
   sendUnauthorizedAccess();
}

?>

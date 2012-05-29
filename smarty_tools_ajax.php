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

if(isset($_SESSION['userid']) && isset($_GET['action'])) {

   require('path.inc.php');
   require('super_header.inc.php');
   require('smarty_tools.php');
   require('display.inc.php');
   require('i18n.inc.php');

   $smartyHelper = new SmartyHelper();

   if($_GET['action'] == 'getTeamProjects') {
      include('team.class.php');

      $allProject[] = array('id' => T_('All projects'),
         'name' => T_('All projects')
      );
      $projects = Team::getProjectList($_GET['teamid'], false);
      $smartyHelper->assign('projects', array_merge($allProject,getProjects($projects)));
      $smartyHelper->display('form/projectSelector');
   }
   else if($_GET['action'] == 'getProjectDetails') {
      include('reports/productivity_report_tools.php');

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
      include('team.class.php');

      $team = new Team($_GET['teamid']);
      $min_year = date("Y", $team->date);
      $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;
      $smartyHelper->assign('years', getYearsToNow($min_year,$year));
      $smartyHelper->display('form/yearSelector');
   } else {
      header('HTTP/1.1 404 Not Found');
      exit;
   }
}
else {
   header('HTTP/1.1 403 Forbidden');
   exit;
}

?>

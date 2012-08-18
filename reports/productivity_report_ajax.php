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

if(isset($_SESSION['userid']) && (isset($_GET['action']) || isset($_POST['action']))) {

   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'getProjectDetails') {
         $weekDates  = Tools::week_dates(date('W'),date('Y'));
         $startdate  = Tools::getSecureGETStringValue('startdate', date("Y-m-d", $weekDates[1]));
         $startTimestamp = Tools::date2timestamp($startdate);

         $enddate  = Tools::getSecureGETStringValue('enddate', date("Y-m-d", $weekDates[5]));
         $endTimestamp = Tools::date2timestamp($enddate);
         $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $_GET['teamid']);

         $projectid  = Tools::getSecureGETIntValue('projectid', 0);
         $projectDetails = NULL;
         if (0 != $projectid) {
            $projectDetails = ProductivityReportTools::getProjectDetails($timeTracking, $projectid);
         } else {
            // all sideTasks
            $projectDetails = ProductivityReportTools::getSideTasksProjectDetails($timeTracking);
         }
         $smartyHelper->assign('projectDetails', $projectDetails);
         if($projectDetails != NULL) {
            $smartyHelper->assign('projectDetailsUrl', ProductivityReportTools::getProjectDetailsUrl($projectDetails));
         }
         $smartyHelper->display('ajax/projectDetails');
      }
      else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

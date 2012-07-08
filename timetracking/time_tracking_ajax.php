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

// MAIN
if(isset($_SESSION['userid'])) {
   require('../path.inc.php');
   require('include/super_header.inc.php');

   if(isset($_GET['action'])) {
      require('classes/smarty_helper.class.php');

      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'updateRemainingAction') {
         require('timetracking/time_tracking_tools.php');
         include_once('classes/issue_cache.class.php');
         include_once('classes/time_tracking.class.php');

         $issue = IssueCache::getInstance()->getIssue(getSecureGETIntValue('bugid'));
         $formattedRemaining = getSecureGETNumberValue('remaining');
         $issue->setRemaining($formattedRemaining);

         $weekDates = week_dates(getSecureGETIntValue('weekid'),getSecureGETIntValue('year'));
         $startTimestamp = $weekDates[1];
         $endTimestamp = mktime(23, 59, 59, date('m', $weekDates[7]), date('d', $weekDates[7]), date('Y', $weekDates[7]));
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp);

         $userid = getSecureGETIntValue('userid',$_SESSION['userid']);

         // UTF8 problems in smarty, date encoding needs to be done in PHP
         $smartyHelper->assign('weekDates', array(
               date('Y-m-d',$weekDates[1]) => formatDate("%A %d %B", $weekDates[1]),
               date('Y-m-d',$weekDates[2]) => formatDate("%A %d %B", $weekDates[2]),
               date('Y-m-d',$weekDates[3]) => formatDate("%A %d %B", $weekDates[3]),
               date('Y-m-d',$weekDates[4]) => formatDate("%A %d %B", $weekDates[4]),
               date('Y-m-d',$weekDates[5]) => formatDate("%A %d %B", $weekDates[5]))
         );
         $smartyHelper->assign('weekEndDates', array(formatDate("%A %d %B", $weekDates[6]),formatDate("%A %d %B", $weekDates[7])));

         $smartyHelper->assign('weekTasks', getWeekTask($weekDates,$userid,$timeTracking));
         $smartyHelper->display('ajax/weekTaskDetails');
      }
   }
}
else {
   sendUnauthorizedAccess();
}

?>

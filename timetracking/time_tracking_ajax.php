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

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {

   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'updateBacklogAction') {
         $issue = IssueCache::getInstance()->getIssue(Tools::getSecureGETIntValue('bugid'));
         $formattedBacklog = Tools::getSecureGETNumberValue('backlog');
         $issue->setBacklog($formattedBacklog);

         $weekDates = Tools::week_dates(Tools::getSecureGETIntValue('weekid'),Tools::getSecureGETIntValue('year'));
         $startTimestamp = $weekDates[1];
         $endTimestamp = mktime(23, 59, 59, date('m', $weekDates[7]), date('d', $weekDates[7]), date('Y', $weekDates[7]));
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp);

         $userid = Tools::getSecureGETIntValue('userid',$_SESSION['userid']);
         
         $incompleteDays = $timeTracking->checkCompleteDays($userid, FALSE);
         $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates, $incompleteDays);
         
         // UTF8 problems in smarty, date encoding needs to be done in PHP
         $smartyHelper->assign('weekDates', array(
            $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
         ));
         $smartyHelper->assign('weekEndDates', array(
            $smartyWeekDates[6], $smartyWeekDates[7]
         ));

         $weekTasks = TimeTrackingTools::getWeekTask($weekDates, $userid, $timeTracking, $incompleteDays);
         $smartyHelper->assign('weekTasks', $weekTasks["weekTasks"]);
         $smartyHelper->assign('dayTotalElapsed', $weekTasks["totalElapsed"]);
            
         $smartyHelper->display('ajax/weekTaskDetails');
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

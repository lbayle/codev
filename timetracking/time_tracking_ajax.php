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
   require_once('../path.inc.php');
   require_once('super_header.inc.php');
   require_once('i18n.inc.php');

   if(isset($_GET['action'])) {
      if($_GET['action'] == 'updateRemainingAction') {
         require_once('issue_cache.class.php');
         require_once('holidays.class.php');
         require_once('time_tracking.class.php');

         $issue = IssueCache::getInstance()->getIssue(getSecureGETIntValue('bugid'));
         $formattedRemaining = getSecureGETNumberValue('remaining');
         $issue->setRemaining($formattedRemaining);

         $weekDates = week_dates(getSecureGETIntValue('weekid'),getSecureGETIntValue('year'));
         $startTimestamp = $weekDates[1];
         $endTimestamp = mktime(23, 59, 59, date('m', $weekDates[7]), date('d', $weekDates[7]), date('Y', $weekDates[7]));
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp);

         $userid = getSecureGETIntValue('userid',$_SESSION['userid']);

         echo getWeekTaskDetails($weekDates,$userid,$timeTracking);
      }
   }
}
else {
   sendUnauthorizedAccess();
}

/**
 * TODO Use a specific template for Smarty or Datatables async refresh mecanism
 * @param array $weekDates
 * @param int $userid
 * @param TimeTracking $timeTracking
 * @return String html
 */
function getWeekTaskDetails($weekDates, $userid, TimeTracking $timeTracking) {
   $html = "<table id='weekTaskDetails'>\n";
   $html .= "<tr>\n";
   $html .= '<th>'.T_('Task')."</th>\n";
   $html .= '<th>'.T_('RAF')."</th>\n";
   $html .= '<th>'.T_('Job')."</th>\n";
   $html .= "<th width='80'>".formatDate('%A %d %B', $weekDates[1])."</th>\n";
   $html .= "<th width='80'>".formatDate('%A %d %B', $weekDates[2])."</th>\n";
   $html .= "<th width='80'>".formatDate('%A %d %B', $weekDates[3])."</th>\n";
   $html .= "<th width='80'>".formatDate('%A %d %B', $weekDates[4])."</th>\n";
   $html .= "<th width='80'>".formatDate('%A %d %B', $weekDates[5])."</th>\n";
   $html .= "<th width='80' style='background-color: #D8D8D8;' >".formatDate('%A %d %B', $weekDates[6])."</th>\n";
   $html .= "<th width='80' style='background-color: #D8D8D8;' >".formatDate('%A %d %B', $weekDates[7])."</th>\n";
   $html .= "</tr>\n";

   $linkList = array();
   $holidays = Holidays::getInstance();
   $weekTracks = $timeTracking->getWeekDetails($userid);
   foreach ($weekTracks as $bugid => $jobList) {
      $issue = IssueCache::getInstance()->getIssue($bugid);

      foreach ($jobList as $jobid => $dayList) {
         $linkid = $bugid.'_'.$jobid;
         $linkList[$linkid] = $issue;

         $query3  = 'SELECT name FROM `codev_job_table` WHERE id='.$jobid;
         $result3 = SqlWrapper::getInstance()->sql_query($query3) or die('Query failed: '.$query3);
         $jobName = SqlWrapper::getInstance()->sql_result($result3, 0);

         $description = addslashes(htmlspecialchars($issue->summary));
         $dialogBoxTitle = T_('Task').' '.$issue->bugId.' / '.$issue->tcId.' - '.T_('Update Remaining');

         $html .= "<tr>\n";
         $html .= '<td>'.issueInfoURL($bugid).' / '.$issue->tcId.' : '.$issue->summary."</td>\n";

         // if no remaining set, display a '?' to allow Remaining edition
         if (NULL == $issue->remaining) {

            #if (($team->isSideTasksProject($issue->projectId)) ||
            #    ($team->isNoStatsProject($issue->projectId))) {
            // do not allow to edit sideTasks Remaining
            $formattedRemaining = '';
            #} else {
            #   $formattedRemaining = '?';
            #}
         } else {
            $formattedRemaining = $issue->remaining;
         }
         $html .= "<td><a title='".T_('update remaining')."' href=\"javascript: updateRemaining('".$issue->remaining."', '".$description."', '".$bugid."', '".$dialogBoxTitle."')\" >".$formattedRemaining."</a></td>\n";
         $html .= '<td>'.$jobName."</td>\n";

         for ($i = 1; $i <= 7; $i++) {
            if($i <= 5) {
               $h = $holidays->isHoliday($weekDates[$i]);
               if ($h) {
                  $bgColor = "style='background-color: #".$h->color.";'";
                  #$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
                  $title = "title='".$h->description."'";
               } else {
                  $bgColor = '';
                  $title = '';
               }
            } else {
               $bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
               $title = '';
            }
            $html .= '<td '.$bgColor.' '.$title.'>';
            $html .= array_key_exists($i,$dayList) != NULL ? $dayList[$i] : '';
            $html .= "</td>\n";
         }
         $html .= "</tr>\n";
      }
   }
   $html .= " </table>\n";

   return $html;
}

?>

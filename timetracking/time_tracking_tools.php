<?php
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

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class TimeTrackingTools {

   /**
   * @var Logger The logger
   */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @param int[] $weekDates
    * @param int $userid
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   public static function getWeekTask(array $weekDates, $userid, TimeTracking $timeTracking) {
      $jobs = new Jobs();

      $weekTasks = NULL;
      $holidays = Holidays::getInstance();
      $weekTracks = $timeTracking->getWeekDetails($userid);
      foreach ($weekTracks as $bugid => $jobList) {

         try {
            $issue = IssueCache::getInstance()->getIssue($bugid);

            $backlog = $issue->backlog;
            $extRef = $issue->tcId;
            $summary = $issue->summary;
            $issueURL = Tools::issueInfoURL($bugid);
            $mantisURL = Tools::mantisIssueURL($bugid, NULL, true);

         } catch (Exception $e) {
            $backlog = '!';
            $extRef = '';
            $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
            $issueURL = $bugid;
            $mantisURL = '';
         }

         foreach ($jobList as $jobid => $dayList) {
            // if no backlog set, display a '?' to allow Backlog edition
            if(is_numeric($backlog)) {
               $formattedBacklog = $backlog;
            } else {
               #if (($team->isSideTasksProject($issue->projectId)) ||
               #    ($team->isNoStatsProject($issue->projectId))) {
               // do not allow to edit sideTasks Backlog
               $formattedBacklog = '';
               #} else {
               #   $formattedBacklog = '?';
               #}
               //
            }

            $dayTasks = "";
            for ($i = 1; $i <= 7; $i++) {
               if($i <= 5) {
                  $h = $holidays->isHoliday($weekDates[$i]);
                  if ($h) {
                     $bgColor = $h->color;
                     #$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
                     $title = "title='".$h->description."'";
                  } else {
                     $bgColor = NULL;
                     $title = "";
                  }
               } else {
                  $bgColor = Holidays::$defaultColor;
                  $title = "";
               }
               $dayTasks[] = array('bgColor' => $bgColor,
                  'title' => $title,
                  'day' => $dayList[$i]
               );
            }

            $weekTasks[$bugid."_".$jobid] = array(
               'bugid' => $bugid,
               'issueURL' => $issueURL,
               'mantisURL' => $mantisURL,
               'issueId' => $extRef,
               'summary' => $summary,
               'backlog' => $backlog,
               'description' => addslashes(htmlspecialchars($summary)),
               'formattedBacklog' => $formattedBacklog,
               'jobid' => $jobid,
               'jobName' => $jobs->getJobName($jobid),
               'dayTasks' => $dayTasks
            );
         }
      }

      return $weekTasks;
   }

}

// Initialize complex static variables
Tools::staticInit();

?>

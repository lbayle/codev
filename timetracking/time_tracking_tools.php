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
    * @param array $incompleteDays
    * @return mixed[]
    */
   public static function getWeekTask(array $weekDates, $userid, TimeTracking $timeTracking, array $incompleteDays) {
      $totalElapsed = array();
      for ($i = 1; $i <= 7; $i++) {
         $weekDate = $weekDates[$i];
         $totalElapsed[$weekDate] = array(
            "elapsed" => 0,
            "class" => array_key_exists($weekDate,$incompleteDays) ? "incompleteDay" : ""
         );
      }
      
      $jobs = new Jobs();

      $weekTasks = NULL;
      $holidays = Holidays::getInstance();
      $weekTracks = $timeTracking->getWeekDetails($userid);
      foreach ($weekTracks as $bugid => $jobList) {
         try {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $backlog = $issue->getBacklog();
            $extRef = $issue->getTcId();
            $summary = $issue->getSummary();
            $description = SmartyTools::getIssueDescription($bugid,$extRef,$summary);
         } catch (Exception $e) {
            $backlog = '!';
            $extRef = '';
            $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
            $description = SmartyTools::getIssueDescription($bugid,$extRef,$summary);
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
               $title = NULL;
               $bgColor = NULL;
               if($i <= 5) {
                  $h = $holidays->isHoliday($weekDates[$i]);
                  if ($h) {
                     $bgColor = $h->color;
                     #$bgColor = Holidays::$defaultColor;
                     $title = $h->description;
                  }
               } else {
                  $bgColor = Holidays::$defaultColor;
               }

               $day = 0;
               if(array_key_exists($i,$dayList)) {
                  $day = $dayList[$i];
               }

               $dayTasks[] = array(
                  'bgColor' => $bgColor,
                  'title' => $title,
                  'day' => $day
               );
            
               $totalElapsed[$weekDates[$i]]['elapsed'] += $day;
            }
            $formatedDate = Tools::formatDate(T_("%Y-%m-%d"), $issue->getDeadLine());

            $weekTasks[$bugid."_".$jobid] = array(
               'bugid' => $bugid,
               'description' => $description,
               'backlog' => $backlog,
               'summary' => addslashes(htmlspecialchars($summary)),
               'formattedBacklog' => $formattedBacklog,
               'jobid' => $jobid,
               'jobName' => $jobs->getJobName($jobid),
               'dayTasks' => $dayTasks,
               'effortEstim' => ($issue->getEffortEstim() + $issue->getEffortAdd()),
               'mgrEffortEstim' => $issue->getMgrEffortEstim(),
               'elapsed' => $issue->getElapsed(),
               'drift' => $issue->getDrift(),
               'driftMgr' => $issue->getDriftMgr(),
               'reestimated' => $issue->getReestimated(),
               'reestimatedMgr' => $issue->getReestimatedMgr(),
               'driftColor' => $issue->getDriftColor(),
               'deadline' => $formatedDate,
               'dialogBoxTitle' => $issue->getFormattedIds(),
            );
         }
      }

      return array(
         "weekTasks" => $weekTasks,
         "totalElapsed" => $totalElapsed
      );
   }
   
   /**
    * Get smarty week dates
    * @param array $weekDates
    * @param array $incompleteDays
    * @return array
    */
   public static function getSmartyWeekDates($weekDates, $incompleteDays) {
      $smartyWeekDates = array();
      
      foreach($weekDates as $key => $weekDate) {
         $smartyWeekDates[$key] = array(
             "date" => date('Y-m-d',$weekDate),
             "formattedDate" => Tools::formatDate("%A\n%d %b", $weekDate),
             "class" => array_key_exists($weekDate,$incompleteDays) ? "incompleteDay" : ""
         );
      }
      
      return $smartyWeekDates;
   }

}

// Initialize complex static variables
Tools::staticInit();

?>

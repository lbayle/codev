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
   public static function getWeekTask(array $weekDates, $teamid, $userid, TimeTracking $timeTracking, array $incompleteDays) {

      $totalElapsed = array();
      $todayAtMidnight = mktime(0,0,0);
      for ($i = 1; $i <= 7; $i++) {
         $weekDate = $weekDates[$i];
         $totalElapsed[$weekDate] = array(
            "elapsed" => 0,
            "class" => in_array($weekDate,$incompleteDays) && $weekDate < $todayAtMidnight ? "incompleteDay" : ""
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

            $deadline = $issue->getDeadLine();
            if (!is_null($deadline) || (0 != $deadline)) {
               $formatedDate = Tools::formatDate(T_("%Y-%m-%d"), $deadline);
            }

            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());

            if ((!$project->isSideTasksProject(array($teamid))) &&
                (!$project->isExternalTasksProject())) {

               // TODO does $issue belong to current team's project ? what if not ?
               $tooltipAttr = $issue->getTooltipItems($teamid, $userid);

               #$tooltipAttr[T_('Elapsed')] = $issue->getElapsed();
               #$tooltipAttr[T_('Backlog')] = $issue->getDuration();
               #$tooltipAttr[T_('Drift')] = $issue->getDrift();
               #$tooltipAttr[T_('DriftColor')] = $issue->getDriftColor();

               $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);
            } else {
               $infoTooltip = '';
            }


            // prepare json data for the BacklogDialogbox
            $drift = $issue->getDrift();
            $issueInfo = array(
               'backlog' => $backlog,
               'bugid' => $issue->getId(),
               'summary' => base64_encode($issue->getSummary()),
               'dialogBoxTitle' => $issue->getFormattedIds(),
               'effortEstim' => ($issue->getEffortEstim() + $issue->getEffortAdd()),
               'mgrEffortEstim' => $issue->getMgrEffortEstim(),
               'elapsed' => $issue->getElapsed(),
               'drift' => $drift,
               'driftMgr' => $issue->getDriftMgr(),
               'reestimated' => $issue->getReestimated(),
               'driftColor' => $issue->getDriftColor($drift),
               'currentStatus' => $issue->getCurrentStatus(),
               'availableStatusList' => $issue->getAvailableStatusList(true)
            );
            if (isset($formatedDate)) {
               $issueInfo['deadline'] = $formatedDate;
            }
            $jsonIssueInfo = json_encode($issueInfo);


            // prepare json data for the IssueNoteDialogbox
            if ((!$project->isSideTasksProject(array($teamid))) &&
                (!$project->isExternalTasksProject())) {

               $issueNote = IssueNote::getTimesheetNote($issue->getId());
               if (!is_null($issueNote)) {
                  $issueNoteId = $issueNote->getId();
                  $user = UserCache::getInstance()->getUser($issueNote->getReporterId());
                  $rawNote = $issueNote->getText();
                  $note = trim(IssueNote::removeAllReadByTags($rawNote));

                  // used for the tooltip NOT the dialoBox
                  $tooltipAttr = array (
                     'reporter' => $user->getRealname(),
                     'date' => date('Y-m-d H:i:s', $issueNote->getLastModified()),
                     'Note' => $note,
                  );
                  $readByList = $issueNote->getReadByList(TRUE);
                  if (0 != count($readByList)) {
                     $tooltipAttr['Read by'] = implode(', ', array_keys($readByList));
                  }

                  $noteTooltip = Tools::imgWithTooltip('images/b_note.png', $tooltipAttr, "bugNote_".$issueNote->getBugId(), 'js-add-note-link', 'data-bugId="'.$issueNote->getBugId().'"');
               } else {
                  $issueNoteId = 0;
                  $noteTooltip = Tools::imgWithTooltip('images/b_note_grey.png', T_('Click to add a note'), "bugNote_".$issue->getId(), 'js-add-note-link', 'data-bugId="'.$issue->getId().'"');
               }
            } else {
               $noteTooltip = '';
            }

            $weekTasks[$bugid."_".$jobid] = array(
               'bugid' => $bugid,
               'description' => $description,
               'formattedBacklog' => $formattedBacklog,
               'jobid' => $jobid,
               'jobName' => $jobs->getJobName($jobid),
               'dayTasks' => $dayTasks,
               'infoTooltip' => $infoTooltip,
               'summary' => addslashes(htmlspecialchars($summary)),
               'updateBacklogJsonData' => $jsonIssueInfo,
               'issueNoteId' => $issueNoteId,
               'noteTooltip' => $noteTooltip,
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
   public static function getSmartyWeekDates(array $weekDates, array $incompleteDays) {
      $smartyWeekDates = array();

      $todayAtMidnight = mktime(0,0,0);

      foreach($weekDates as $key => $weekDate) {
            $smartyWeekDates[$key] = array(
               "date" => date('Y-m-d',$weekDate),
               "formattedDate" => Tools::formatDate("%A\n%d %b", $weekDate),
               "class" => in_array($weekDate,$incompleteDays) && $weekDate < $todayAtMidnight ? "incompleteDay" : ""
            );
      }

      return $smartyWeekDates;
   }

}

// Initialize complex static variables
Tools::staticInit();

?>

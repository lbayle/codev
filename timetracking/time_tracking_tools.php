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

               $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);
            } else {
               $infoTooltip = '';
            }


            // prepare json data for the BacklogDialogbox
            $jsonIssueInfo = self::getUpdateBacklogJsonData($issue->getId(), $jobid, $teamid);

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

                  $noteTooltip = Tools::imgWithTooltip('images/b_note.png', $tooltipAttr, NULL, 'js-add-note-link', ' style="cursor: pointer;" data-bugId="'.$issueNote->getBugId().'"');
               } else {
                  $issueNoteId = 0;
                  $noteTooltip = Tools::imgWithTooltip('images/b_note_grey.png', T_('Click to add a note'), NULL, 'js-add-note-link', ' style="cursor: pointer;" data-bugId="'.$issue->getId().'"');
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

   /**
    * get Job list
    *
    * Note: the jobs depend on project type, which depends on the team
    *
    * @param int $projectid
    * @param string $teamid  user's team
    * @return string[]
    */
   public static function getJobs($projectid, $teamid) {

      if ((0 == $projectid) || (0 == $teamid)) {

         //this happens when project = "All", it's a normal case.
         // team == 0 should not happen
         //self::$logger->warn("getJobs($projectid, $teamid): could not find jobList. Action = $action");
         return array();
      }

      $team = TeamCache::getInstance()->getTeam($teamid);
      $project = ProjectCache::getInstance()->getProject($projectid);

      $ptype = $team->getProjectType($projectid);
      $jobList = $project->getJobList($ptype);

      return $jobList;
   }

   /**
    * Get issues
    * 
    * @param int $projectid
    * @param boolean $isOnlyAssignedTo
    * @param int $userid
    * @param string[] $projList
    * @param boolean $isHideResolved
    * @param int $defaultBugid
    * @return mixed[]
    */
   public static function getIssues($teamid, $projectid, $isOnlyAssignedTo, $userid, array $projList, $isHideResolved, $defaultBugid) {

      $team = TeamCache::getInstance()->getTeam($teamid);
      $hideStatusAndAbove = (1 == $team->getGeneralPreference('forbidAddTimetracksOnClosed')) ? Constants::$status_closed : 0;

      if (0 != $projectid) {
         // Project list
         $project1 = ProjectCache::getInstance()->getProject($projectid);

         try {
            $isSideTasksProject = $project1->isSideTasksProject(array($teamid));
            $isNoStatsProject   = $project1->isNoStatsProject(array($teamid));

            // do not filter on userId if SideTask or ExternalTask
            if (($isSideTasksProject) || ($isNoStatsProject)) {
               $handler_id = 0; // all users
               $hideStatusAndAbove = 0; // hide none
               $isHideResolved = false; // do not hide resolved
            } else {
               // normal project
               $handler_id = $isOnlyAssignedTo ? $userid : 0;
            }

         } catch (Exception $e) {
            self::$logger->error("getIssues(): isOnlyAssignedTo & isHideResolved filters not applied : ".$e->getMessage());
            $handler_id = 0; // all users
            $isHideResolved = false; // do not hide resolved
         }
         $issueList = $project1->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove);
      } else {
         // no project specified: show all tasks
         $issueList = array();

         foreach ($projList as $pid => $pname) {
            $proj = ProjectCache::getInstance()->getProject($pid);
            try {
               if (($proj->isSideTasksProject(array($teamid))) ||
                  ($proj->isNoStatsProject(array($teamid)))) {
                  // do not hide any task for SideTasks & ExternalTasks projects
                  $buglist = $proj->getIssues(0, false, 0);
                  $issueList = array_merge($issueList, $buglist);
               } else {
                  $handler_id = $isOnlyAssignedTo ? $userid : 0;
                  $buglist = $proj->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove);
                  $issueList = array_merge($issueList, $buglist);
               }
            } catch (Exception $e) {
               self::$logger->error("getIssues(): task filters not applied for project $pid : ".$e->getMessage());
               // do not hide any task if unknown project type
               $buglist = $proj->getIssues(0, false, 0);
               $issueList = array_merge($issueList, $buglist);

            }
         }
         rsort($issueList);
      }

      $issues = array();
      foreach ($issueList as $issue) {
         //$issue = IssueCache::getInstance()->getIssue($bugid);
         $issues[$issue->getId()] = array(
            'id' => $issue->getId(),
            'tcId' => $issue->getTcId(),
            'summary' => $issue->getSummary(),
            'selected' => $issue->getId() == $defaultBugid);
      }

      // If the default bug is filtered, we add it anyway
      if(!array_key_exists($defaultBugid,$issues) && $defaultBugid != 0) {
         try {
            $issue = IssueCache::getInstance()->getIssue($defaultBugid);
            // Add the bug only if the selected project is the bug project
            if($projectid == 0 || $issue->getProjectId() == $projectid) {
               $issues[$issue->getId()] = array(
                  'id' => $issue->getId(),
                  'tcId' => $issue->getTcId(),
                  'summary' => $issue->getSummary(),
                  'selected' => $issue->getId() == $defaultBugid);
               krsort($issues);
            }
         } catch (Exception $e) {
               self::$logger->error("getIssues(): task not found in MantisDB : ".$e->getMessage());
         }
      }

      // $issues is sorted, but we want the 5 most recent used issues to be in front
      if (0 != $userid) {
         $user = UserCache::getInstance()->getUser($userid);
         $recentBugidList = $user->getRecentlyUsedIssues(5, array_keys($issues));
         #var_dump($recentBugidList);
         $smartyRecentList = array();
         foreach ($recentBugidList as $bugid) {
            if (array_key_exists("$bugid", $issues)) {
               $smartyRecentList["$bugid"] = $issues["$bugid"];
               unset($issues["$bugid"]);
            }
         }
         // insert in front
         $issues = $smartyRecentList + $issues;
      }

      return $issues;
   }

   /**
    * @return string[]
    */
   public static function getDurationList() {
      $duration["0"] = "";
      $duration["1"] = "1";
      $duration["0.9"] = "0.9";
      $duration["0.8"] = "0.8";
      $duration["0.75"] = "0.75";
      $duration["0.7"] = "0.7";
      $duration["0.6"] = "0.6";
      $duration["0.5"] = "0.5";
      $duration["0.4"] = "0.4";
      $duration["0.3"] = "0.3";
      $duration["0.25"] = "0.25";
      $duration["0.2"] = "0.2";
      $duration["0.1"] = "0.1";
      $duration["0.05"] = "0.05";
      return $duration;
   }

   /**
    * get info to display the updateBacklog dialogbox
    *
    * Note: this dialogbox is also responsible for validating the addTrack action.
    *
    * @param type $bugid
    * @param type $trackJobid
    * @param type $teamid
    * @param type $trackUserid
    * @param type $trackDate
    * @param type $trackDuration
    * @return json encoded data to be displayed in the dialogBox
    */
   public static function getUpdateBacklogJsonData($bugid, $trackJobid, $teamid, $trackUserid=0, $trackDate=0, $trackDuration=0) {

      try {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $backlog = $issue->getBacklog();
         $summary = $issue->getSummary();
      } catch (Exception $e) {
         $backlog = '!';
         $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
      }

      // prepare json data for the BacklogDialogbox
      $drift = $issue->getDrift();
      $totalEE = ($issue->getEffortEstim() + $issue->getEffortAdd());
      $issueInfo = array(
         'currentBacklog' => $backlog,
         'bugid' => $issue->getId(),
         'summary' => $summary,
         'dialogBoxTitle' => $issue->getFormattedIds(),
         'effortEstim' => $totalEE,
         'mgrEffortEstim' => $issue->getMgrEffortEstim(),
         'elapsed' => $issue->getElapsed(),
         'drift' => $drift,
         'driftMgr' => $issue->getDriftMgr(),
         'reestimated' => $issue->getReestimated(),
         'driftColor' => $issue->getDriftColor($drift),
         'currentStatus' => $issue->getCurrentStatus(),
         'availableStatusList' => $issue->getAvailableStatusList(true),
         'bugResolvedStatusThreshold' =>  $issue->getBugResolvedStatusThreshold(),
         'trackDuration' => $trackDuration,
         'trackJobid' => $trackJobid,
      );

      if (0 !== $trackDuration) {
         # fill duration combobox values
         $issueInfo['availableDurationList'] = self::getDurationList();
         $issueInfo['trackUserid'] = $trackUserid;
         $issueInfo['trackDate'] = $trackDate;
      }

      // display calculatedBacklog depending on team settings
      $team = TeamCache::getInstance()->getTeam($teamid);
      if (1 == $team->getGeneralPreference('displayCalculatedBacklogInDialogbox')) {

         // Note: if Backlog is NULL, the values to propose in the DialogBox
         //       are not the ones used for ProjectManagement
         if ( !is_null($backlog) && is_numeric($backlog)) {
            // normal case
            $calculatedBacklog = $backlog - $trackDuration;
         } else {
            // reestimated cannot be used...
            $calculatedBacklog = $totalEE - $issue->getElapsed();
         }
         if ($calculatedBacklog < 0) { $calculatedBacklog = 0;}
         $issueInfo['calculatedBacklog'] = $calculatedBacklog;
      }

      $deadline = $issue->getDeadLine();
      if (!is_null($deadline) || (0 != $deadline)) {
         $formatedDate = Tools::formatDate("%Y-%m-%d", $deadline);
         $issueInfo['deadline'] = $formatedDate;
      }

      $jsonIssueInfo = json_encode($issueInfo);
      return $jsonIssueInfo;
   }

}

// Initialize complex static variables
Tools::staticInit();

?>

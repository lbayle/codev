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
    * base struct of a weekTasks element
    */
   public static function weekTasksElement($bugid, $userid, $teamid, array $weekDates) {
      try {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $projectId = $issue->getProjectId();
         $project = ProjectCache::getInstance()->getProject($projectId);

         // if project not declared in current team, then
         // user cannot add a timetrack by clicking in the weekTasks table
         // Note: (this would generate an error on addTimetrack)
         $team = TeamCache::getInstance()->getTeam($teamid);
         $isTeamProject = !is_null($team->getProjectType($projectId));
         $bugResolvedStatusThreshold = $issue->getBugResolvedStatusThreshold();

         if ((!$project->isSideTasksProject(array($teamid))) &&
             (!$project->isExternalTasksProject())) {
            $tooltipAttr = $issue->getTooltipItems($teamid, $userid);
            $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);

            // if backlog is wrong give a chance to correct it
            if ($issue->isResolved() && (0 == $issue->getBacklog())) {
               $isBacklogEditable = false;
            } else {
               $isBacklogEditable = true;
               // TODO change cell color to red
            }
            $isForbidUpdateStatusOnTimetracking = (0 == $team->getGeneralPreference('isForbidUpdateStatusOnTimetracking')) ? false : true;
            $isStatusEditable = $isTeamProject && (!$isForbidUpdateStatusOnTimetracking);
            $statusName = Constants::$statusNames[$issue->getStatus()];

         } else {
            $infoTooltip = '';
            $isBacklogEditable = false;
            $isStatusEditable  = false;
            $statusName = '';
         }

         $weekTask = array(
            "bugId" => $bugid,
            "backlog" => $issue->getBacklog(),
            'statusId' => $issue->getStatus(),
            'statusName' => $statusName,
            //"summary" => $issue->getSummary(),
            "htmlDescription" => SmartyTools::getIssueDescription($bugid,$issue->getTcId(),$issue->getSummary()),
            "infoTooltip" => $infoTooltip,
            "isTeamProject" => $isTeamProject,
            "projectId" => $issue->getProjectId(),
            "defaultJobId" => Jobs::JOB_NA,  // later on, we may want use some custom default job
            'isBacklogEditable' => $isTeamProject ? $isBacklogEditable : false,
            'isStatusEditable' => $isStatusEditable,
            'bugResolvedStatusThreshold' =>  $bugResolvedStatusThreshold,
            "weekDays" => array(),
         );
      } catch (Exception $e) {
         $summary = T_('Error: Task not found in Mantis DB !');
         $weekTask = array(
            "bugId" => $bugid,
            "backlog" => '!',
            'statusId' => 0,
            'statusName' => '',
            "summary" => $summary,
            "htmlDescription" =>  Tools::mantisIssueURL($bugid, NULL, TRUE).' '.$bugid.' : <span class="error_font">'.$summary.'</span>',
            "infoTooltip" => '',
            "isTeamProject" => false,
            "projectId" => -1,
            "defaultJobId" => Jobs::JOB_NA,
            'isBacklogEditable' => false,
            'isStatusEditable' => false,
            'bugResolvedStatusThreshold' =>  0,
            "weekDays" => array(),
         );
      }

      // background color & tooltip depends on fixed holidays
      $holidays = Holidays::getInstance();
      $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($issue->getProjectId());
      $isForbidenStatus = array_key_exists($issue->getStatus(), $ttForbidenStatusList);
      
      $issueExists = Issue::exists($bugid);
      for ($i = 1; $i <= 7; $i++) {
         $title = NULL;
         $bgColor = NULL;
         $isEditable = $issueExists && $isTeamProject && (!$isForbidenStatus);
         if($i <= 5) {
            $h = $holidays->isHoliday($weekDates[$i]);
            if ($h) {
               $bgColor = $h->color;
               $title = $h->description;
               $isEditable = false;
            }
         } else {
            $bgColor = Holidays::$defaultColor;
            $isEditable = false;
         }
         $weekTask['weekDays'][$i] = array (
            'dayId' => "$i",
            'duration' => 0,
            //'timetracks' => '',
            'bgColor' => $bgColor,
            'title' => $title,
            'isEditable' => $isEditable,
         );
      }
      return $weekTask;
   }


   /**
    *
    * @param type $userid
    * @param type $teamid
    * @param type $startTimestamp
    * @param type $endTimestamp
    * @param array $weekDates
    * @param array $incompleteDays
    * @return array mixed
    */
   public static function getWeekTasks_lite($userid, $teamid, $startTimestamp, $endTimestamp, array $weekDates, array $incompleteDays) {
      $weekTasks = array();
      $sql = AdodbWrapper::getInstance();

      // -----
      $projList = TeamCache::getInstance()->getTeam($teamid)->getProjects();
      $formatedProjList = implode( ', ', array_keys($projList));
      $query = "SELECT timetracking.id, timetracking.bugid, timetracking.jobid, timetracking.date, timetracking.duration ".
               " FROM codev_timetracking_table as timetracking ".
               " JOIN {bug} AS bug ON timetracking.bugid = bug.id ".
               " WHERE timetracking.userid =  ".$sql->db_param().
               " AND timetracking.date >= ".$sql->db_param().
               " AND timetracking.date <  ".$sql->db_param();
               //" AND bug.project_id IN (".$formatedProjList.")";
      $q_params[]=$userid;
      $q_params[]=$startTimestamp;
      $q_params[]=$endTimestamp;

      $result = $sql->sql_query($query, $q_params);

      while($row = $sql->fetchObject($result)) {
         if (!array_key_exists($row->bugid, $weekTasks)) {
            $weekTasks[$row->bugid] = self::weekTasksElement($row->bugid, $userid, $teamid, $weekDates);
         }
         $weekTasks[$row->bugid]['weekDays'][date('N',$row->date)]['duration'] += $row->duration;
         //$weekTasks[$row->bugid]['weekDays'][date('N',$row->date)]['timetracks'] .= "$row->id,";
      }

      // prepare 'tfoot' line
      $totalElapsed = array();
      $todayAtMidnight = mktime(0,0,0);
      for ($i = 1; $i <= 7; $i++) {
         $weekDate = $weekDates[$i];
         $totalElapsed[$weekDate] = array(
            'date' => date('Y-m-d', $weekDate),
            "elapsed" => 0,
            "class" => in_array($weekDate,$incompleteDays) && $weekDate < $todayAtMidnight ? "incompleteDay" : ""
         );
      }

      // sum per day
      foreach ($weekTasks as $bugid => $taskInfo) {
         for ($i = 1; $i <= 7; $i++) {
            $totalElapsed[$weekDates[$i]]['elapsed'] += $weekTasks[$bugid]['weekDays'][$i]['duration'];
         }
      }

      // add recent used tasks (no timetracks yet)
      $user = UserCache::getInstance()->getUser($userid);
      $recentBugidList = $user->getRecentlyUsedIssues(8);
      foreach ($recentBugidList as $bugid) {
         if (!array_key_exists($bugid, $weekTasks)) {
            $taskInfo = self::weekTasksElement($bugid, $userid, $teamid, $weekDates);
            $weekTasks[$bugid] = $taskInfo;
         }
      }

      //self::$logger->error('weekTracks=' . var_export($weekTasks, true));
      //self::$logger->error('totalElapsed=' . var_export($totalElapsed, true));
      return array(
         "weekTasks" => $weekTasks,
         "totalElapsed" => $totalElapsed,
      );
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

      // returns : $weekTracks[bugid][jobid][dayOfWeek] = duration
      $weekTracks = $timeTracking->getWeekDetails($userid);

      foreach ($weekTracks as $bugid => $jobList) {
         try {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $backlog = $issue->getBacklog();
            $extRef = $issue->getTcId();
            $summary = $issue->getSummary();
            $description = SmartyTools::getIssueDescription($bugid,$extRef,$summary);
            $projectId =$issue->getProjectId();
         } catch (Exception $e) {
            $backlog = '!';
            $extRef = '';
            $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
            //$description = SmartyTools::getIssueDescription($bugid,$extRef,$summary);
            $description = Tools::mantisIssueURL($bugid, NULL, TRUE).' '.$bugid.' : '.$summary;
            $projectId = -1;
         }

         foreach ($jobList as $jobid => $dayList) {
            // if no backlog set, display a '?' to allow Backlog edition
            if(is_numeric($backlog)) {
               $formattedBacklog = $backlog;
               // prepare json data for the BacklogDialogbox
               $jsonIssueInfo = self::getUpdateBacklogJsonData($bugid, $jobid, $teamid, $userid);
            } else {
               #if (($team->isSideTasksProject($issue->projectId)) ||
               #    ($team->isNoStatsProject($issue->projectId))) {
               // do not allow to edit sideTasks Backlog
               $formattedBacklog = '';
               $jsonIssueInfo = '';
               #} else {
               #   $formattedBacklog = '?';
               #}
               //
            }

            $dayTasks = array();
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
/*
            $deadline = $issue->getDeadLine();

            if (!is_null($deadline) || (0 != $deadline)) {
               $formatedDate = Tools::formatDate(T_("%Y-%m-%d"), $deadline);
            }
*/
            try {
            	$project = ProjectCache::getInstance()->getProject($projectId);
            } catch (Exception $e) {
            	$project = null;
            }

            try {
               if ($project != null) {
                  if ((!$project->isSideTasksProject(array($teamid))) &&
                      (!$project->isExternalTasksProject())) {

                     // TODO does $issue belong to current team's project ? what if not ?
                     $tooltipAttr = $issue->getTooltipItems($teamid, $userid);

                     $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);
                  } else {
                     $infoTooltip = '';
                  }
               } else {
                  $infoTooltip = '';
               }

               // prepare json data for the IssueNoteDialogbox
               if ($project != null) {
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
               } else {
                  $noteTooltip = '';
               }
            } catch (Exception $e) {
               $infoTooltip = '';
            }

            // if project not declared in current team, then
            // user cannot add a timetrack by clicking in the weekTasks table
            // Note: (this would generate an error on addTimetrack)
            $team = TeamCache::getInstance()->getTeam($teamid);
            $isTeamProject = !is_null($team->getProjectType($projectId));

            $weekTasks[$bugid."_".$jobid] = array(
               'bugid' => $bugid,
               'description' => $description,
               'backlog' => $backlog,
               'formattedBacklog' => $formattedBacklog,
               'jobid' => $jobid,
               'jobName' => $jobs->getJobName($jobid),
               'dayTasks' => $dayTasks,
               'infoTooltip' => $infoTooltip,
               'summary' => addslashes(htmlspecialchars($summary)),
               'updateBacklogJsonData' => $jsonIssueInfo,
               'issueNoteId' => $issueNoteId,
               'noteTooltip' => $noteTooltip,
               'isTeamProject' => $isTeamProject,
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
      $jobList = $project->getJobList($ptype, $teamid);

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
   public static function getIssues($teamid, $projectid, $isOnlyAssignedTo, $userid, array $projList, $isHideResolved, $isHideForbidenStatus, $defaultBugid, $hideNoActivitySince=0) {

      //$team = TeamCache::getInstance()->getTeam($teamid);
      $hideStatusAndAbove = 0; // deprecated, was used for forbidAddTimetracksOnClosed

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
         $issueList = $project1->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove, $isHideForbidenStatus, $teamid);
      } else {
         // no project specified: show all tasks
         $issueList = array();

         foreach ($projList as $pid => $pname) {
            $proj = ProjectCache::getInstance()->getProject($pid);
            try {
               if (($proj->isSideTasksProject(array($teamid))) ||
                  ($proj->isNoStatsProject(array($teamid)))) {
                  // do not hide any task for SideTasks & ExternalTasks projects
                  $buglist = $proj->getIssues(0, false, 0, 0, 0);
                  $issueList = array_merge($issueList, $buglist);
               } else {
                  $handler_id = $isOnlyAssignedTo ? $userid : 0;
                  $buglist = $proj->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove, $isHideForbidenStatus, $teamid);
                  $issueList = array_merge($issueList, $buglist);
               }
            } catch (Exception $e) {
               self::$logger->error("getIssues(): task filters not applied for project $pid : ".$e->getMessage());
               // do not hide any task if unknown project type
               $buglist = $proj->getIssues(0, false, 0, 0, 0);
               $issueList = array_merge($issueList, $buglist);

            }
         }
         rsort($issueList);
      }

      // GET coassigned issues from the Scheduler
      if (0 != $handler_id) {

         // GET task user list
         $schedulerManager = new SchedulerManager($userid, $teamid);
         $timePerTaskPerUser = $schedulerManager->getUserOption(SchedulerManager::OPTION_timePerTaskPerUser);

         if ((NULL != $timePerTaskPerUser) &&
             (array_key_exists($handler_id, $timePerTaskPerUser))) {

            // GET current user's coassigned task list
            $coassignedIssueidList = array_keys($timePerTaskPerUser[$handler_id]);
            $coassignedIssueList = array();

            foreach ($coassignedIssueidList as $coassignedIssueid) {
               $issue = IssueCache::getInstance()->getIssue($coassignedIssueid);
               if((0 !== $projectid) && ($projectid != $issue->getProjectId())) {
                  continue;
               }
               if($isHideResolved && $issue->isResolved()) {
                  continue;
               }
               if((0 !== $hideStatusAndAbove) && ($hideStatusAndAbove <= $issue->getCurrentStatus())) {
                  continue;
               }
               // user should be able to add timetracks to this issue
               $coassignedIssueList [] = $issue;
            }
            // ADD Filtered coassigned issues to IssueList
            $issueList = array_merge($issueList, $coassignedIssueList);
            rsort($issueList);
         }
      }
      $issues = array();
      foreach ($issueList as $issue) {

         if (0 != $hideNoActivitySince) {
            $project1 = ProjectCache::getInstance()->getProject($issue->getProjectId());
            $isSideTasksProject = $project1->isSideTasksProject(array($teamid));
            $isNoStatsProject   = $project1->isNoStatsProject(array($teamid));

            if ((false == $isSideTasksProject) && (false == $isNoStatsProject)) {
               $tstamp = strtotime('-'.$hideNoActivitySince.' month');
               $latestTimetrack = $issue->getLatestTimetrack();
               if ((NULL != $latestTimetrack) &&
                  ($issue->getLatestTimetrack()->getDate() < $tstamp)) {

                  //self::$logger->error("HIDE ".$issue->getId()." ".date("Y-m-d", $latestTimetrack->getDate())." < ".date("Y-m-d", $tstamp));
                  unset($issueList[$issue->getId()]);
                  continue;
               }
            }
         }

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
                  'summary' => htmlspecialchars(preg_replace('![\t\r\n]+!',' ',$issue->getSummary())),
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
      //self::$logger->error("Nb issues ".count($issues));

      return $issues;
   }

   /**
    * @return string[]
    */
   public static function getDurationList($teamid) {
      $duration = Config::getValue(Config::id_durationList, array(0, 0, $teamid, 0, 0, 0), true);
      if ($duration == NULL) {
      	  $duration = Constants::$taskDurationList;
      } elseif (!is_array($duration)) {
      	  $duration = Tools::doubleExplode(":", ",", $duration);
      }
      if ($duration != NULL && is_array($duration)) {
          krsort($duration);
      }
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
    * @param type $managedUserid
    * @param type $trackDate
    * @param type $trackDuration
    * @return json encoded data to be displayed in the dialogBox
    */
   public static function getUpdateBacklogJsonData($bugid, $trackJobid, $teamid, $managedUserid, $trackDate=0, $trackDuration=0) {

      try {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $backlog = $issue->getBacklog();
         $summary = $issue->getSummary();
         $drift = $issue->getDrift();
         $effortEstim = $issue->getEffortEstim();
         $formattedIds = $issue->getFormattedIds();
         $mgrEffortEstim = $issue->getMgrEffortEstim();
         $elapsed = $issue->getElapsed();
         $driftMgr = $issue->getDriftMgr();
         $reestimated = $issue->getReestimated();
         $driftColor = $issue->getDriftColor($drift);
         $currentStatus = $issue->getCurrentStatus();
         $availableStatusList = $issue->getAvailableStatusList(true);
         $bugResolvedStatusThreshold = $issue->getBugResolvedStatusThreshold();
         $bugStatusNew = Constants::$status_new;
         $deadline = $issue->getDeadLine();
         $handlerId = $issue->getHandlerId();
         $handler = UserCache::getInstance()->getUser($handlerId);
         $handlerName = $handler->getName();

         $managedUser = UserCache::getInstance()->getUser($managedUserid);
         $managedUserName = $managedUser->getName();

         $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($issue->getProjectId());

         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $versionList = $project->getVersionList();
         rsort($versionList);

         $fixedInVersion = $issue->getFixedInVersion();
         if (empty($fixedInVersion)) {
            $fixedInVersion = $issue->getTargetVersion();
         }

      } catch (Exception $e) {
         $backlog = '!';
         $summary = '<span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';
         $drift = 0;
         $effortEstim = 0;
         $formattedIds = -1;
         $mgrEffortEstim = 0;
         $elapsed = 0;
         $driftMgr = 0;
         $reestimated = 0;
         $driftColor = null;
         $currentStatus = 0;
         $availableStatusList = array();
         $bugResolvedStatusThreshold = 0;
         $bugStatusNew = Constants::$status_new;
         $deadline = null;
         $handlerId = 0;
         $handlerName = 'ERROR';
         $managedUserName = 'ERROR';
         $ttForbidenStatusList = array();
         $versionList = array();
         $fixedInVersion = null;
      }

      // prepare json data for the BacklogDialogbox

      $totalEE = $effortEstim;
      $issueInfo = array(
         'trackUserid' => $managedUserid,
         'trackUserName' => $managedUserName,
         'currentBacklog' => $backlog,
         'bugid' => $bugid,
         'summary' => $summary,
         'dialogBoxTitle' => $formattedIds,
         'effortEstim' => $totalEE,
         'mgrEffortEstim' => $mgrEffortEstim,
         'elapsed' => $elapsed,
         'drift' => $drift,
         'driftMgr' => $driftMgr,
         'reestimated' => $reestimated,
         'driftColor' => $driftColor,
         'currentStatus' => $currentStatus,
         'availableStatusList' => $availableStatusList,
         'bugResolvedStatusThreshold' =>  $bugResolvedStatusThreshold,
         'bugStatusNew' =>  $bugStatusNew,
         'trackDuration' => $trackDuration,
         'trackJobid' => $trackJobid,
         'handlerId' => $handlerId,
         'handlerName' => $handlerName,
         'ttForbidenStatusList' => $ttForbidenStatusList,
         'versionList' => $versionList,
         'fixedInVersion' => $fixedInVersion,
      );

      if (0 !== $trackDuration) {
         # fill duration combobox values
         $issueInfo['availableDurationList'] = self::getDurationList($teamid);
         $issueInfo['trackDate'] = $trackDate;
      }

      // display calculatedBacklog depending on team settings
      if (1 == $team->getGeneralPreference('displayCalculatedBacklogInDialogbox')) {

         // Note: if Backlog is NULL, the values to propose in the DialogBox
         //       are not the ones used for ProjectManagement
         if ( !is_null($backlog) && is_numeric($backlog)) {
            // normal case
            $calculatedBacklog = $backlog - $trackDuration;
         } else {
            // reestimated cannot be used...
            $calculatedBacklog = $totalEE - $issue->getElapsed() - $trackDuration;
         }
         if ($calculatedBacklog < 0) { $calculatedBacklog = 0;}
         $issueInfo['calculatedBacklog'] = round($calculatedBacklog, 3);
      }

      if (!is_null($deadline) || (0 != $deadline)) {
         $formatedDate = Tools::formatDate("%Y-%m-%d", $deadline);
         $issueInfo['deadline'] = $formatedDate;
      }

      $jsonIssueInfo = json_encode($issueInfo);
      return $jsonIssueInfo;
   }

}

// Initialize complex static variables
TimeTrackingTools::staticInit();


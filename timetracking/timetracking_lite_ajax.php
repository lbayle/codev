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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

	$logger = Logger::getLogger("TimeTrackingAjax");

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_userid = $_SESSION['userid'];

   // TODO check $session_userid & teamid ?

   $action = Tools::getSecurePOSTStringValue('action');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

      // ================================================================
      if('searchIssues' == $action) {

         $searchStr        = Tools::getSecurePOSTStringValue('search', '');
         $projectId        = Tools::getSecurePOSTIntValue('projectid');
         $managedUserid    = Tools::getSecurePOSTIntValue('userid');
         $onlyAssignedTo = ('true' == Tools::getSecurePOSTStringValue('onlyAssignedTo')) ? '1' : '0';
         $hideResolved   = ('true' == Tools::getSecurePOSTStringValue('hideResolved')) ? '1' : '0';

         $managedUser = UserCache::getInstance()->getUser($managedUserid);
         $managedUser->setTimetrackingFilter('onlyAssignedTo', $onlyAssignedTo);
         $managedUser->setTimetrackingFilter('hideResolved', $hideResolved);
         
         $isHideForbidenStatus=true;

         $data = array();
         try {
            if (!empty($searchStr)) {
               $projectidList = array($projectId);
               if (0 == $projectId) {
                  $projList = array ();
                     $projectidList = array_keys($team->getProjects(true, false, true));
               }

               $issueList = Issue::search($searchStr, $projectidList);
               foreach ($issueList as $issue) {

                  $project1 = ProjectCache::getInstance()->getProject($issue->getProjectId());

                  // except for SideTask & ExternalTask
                  if ((false == $project1->isExternalTasksProject()) &&
                      (false == $project1->isSideTasksProject(array($teamid)))) {

                     if (('1' == $onlyAssignedTo) && ($managedUserid != $issue->getHandlerId())) {
                        continue;
                     }
                     if (('1' == $hideResolved) && $issue->isResolved()) {
                        continue;
                     }
                     if ($isHideForbidenStatus) {
                        $ttForbidenStatusList = array_keys($team->getTimetrackingForbidenStatusList($issue->getProjectId()));
                        if (in_array($issue->getStatus(), $ttForbidenStatusList)) {
                           continue;
                        }
                     }
                  }
                  // https://select2.org/data-sources/formats
                  $data[] = array('id'=>$issue->getId(), 'text'=>$issue->getFormattedIds().' : '.$issue->getSummary());
               }
            }
         } catch (Exception $e) {
            self::$logger->error("EXCEPTION searchIssues: " . $e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());         
         }

         $jsonData=json_encode($data);
         echo $jsonData;

      // ================================================================
      } elseif ("getWeekTasksElement" == $action) {
         $bugid = Tools::getSecurePOSTIntValue('bugid');
         $userid = Tools::getSecurePOSTIntValue('userid');
         $weekid = Tools::getSecurePOSTIntValue('weekid');
         $year = Tools::getSecurePOSTIntValue('year');

         try {
            $weekDates = Tools::week_dates($weekid, $year);
            $weekTasksElement = TimeTrackingTools::weekTasksElement($bugid, $userid, $teamid, $weekDates);
            $durations = TimeTrackingTools::getDurationList($teamid);

            // return data
            $data = array(
               'statusMsg' => 'SUCCESS',
               'weekTasksElement' => $weekTasksElement,
               'durations' => $durations,
            );
         }  catch (Exception $e) {
            $logger->error("EXCEPTION deleteTrack: ".$e->getMessage());
            $data = array(
               'statusMsg' => 'Could not get weekTasksElement',
            );
         }
         $jsonData = json_encode($data);
         echo $jsonData;

      // ================================================================
		} else if($action == 'updateBacklog') {
         // updateBacklogDoalogbox with 'updateBacklog' action

         try {
            $bugid = Tools::getSecurePOSTIntValue('bugid');
            $backlog = Tools::getSecurePOSTNumberValue('backlog');
            $issue = IssueCache::getInstance()->getIssue($bugid);

            if (false == $issue->isResolved()) {
               if ($backlog > 0) {
                  $issue->setBacklog($backlog);
                  $statusMsg = 'SUCCESS';
               } else {
                  $statusMsg = T_('REFUSED').': '.T_("Task not resolved, backlog cannot be '0'");
               }
            } else {
               if (0 != $backlog) {
                  // should never happen, $isBacklogEditable should be 'false'
                  $statusMsg = T_('REFUSED').': '.T_("Task resolved, backlog must be '0'");
               } else {
                  $issue->setBacklog($backlog);
                  $statusMsg = 'SUCCESS';
               }
            }

         } catch (Exception $e) {
            $logger->error("$action: issue=$bugid, backlog=$backlog");
            $logger->error("EXCEPTION $action: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to update backlog !");
         }
         // return data
         $data = array(
             'statusMsg' => nl2br(htmlspecialchars($statusMsg)),
         );
         $jsonData = json_encode($data);
         echo $jsonData;

      // ================================================================
      } else if ('getAvailableStatusList' == $action) {
         try {
            $bugid = Tools::getSecurePOSTIntValue('bugid');
            $issue = IssueCache::getInstance()->getIssue($bugid);

            $data = array(
               'statusMsg' => 'SUCCESS',
               'currentStatus' => $issue->getStatus(),
               'availableStatusList' => $issue->getAvailableStatusList(true),
            );

         } catch (Exception $e) {
            $logger->error("$action: issue=$bugid");
            $logger->error("EXCEPTION $action: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to getAvailableStatusList !");
            $data = array(
                'statusMsg' => nl2br(htmlspecialchars($statusMsg)),
            );
         }
         $jsonData = json_encode($data);
         echo $jsonData;

      // ================================================================
      } else if ('setStatus' == $action) {
         try {
            $bugid = Tools::getSecurePOSTIntValue('bugid');
            $statusid = Tools::getSecurePOSTIntValue('statusid');
            $issue = IssueCache::getInstance()->getIssue($bugid);
            
            // There is no backup/status update if sideTask or externalTask
            $projType = $team->getProjectType($issue->getProjectId());
            if ((Project::type_regularProject == $projType) &&
                (Jobs::JOB_SUPPORT != $job)) {
               $updateDone = $issue->setStatus($statusid);
               $statusMsg = ($updateDone) ? "SUCCESS" : "status update failed.";
            } else {
               $statusMsg = "No status update if sideTask or externalTask";
            }
            $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($issue->getProjectId());
            $isForbidenStatus = array_key_exists($issue->getStatus(), $ttForbidenStatusList);

            $bugResolvedStatusThreshold = $issue->getBugResolvedStatusThreshold();
            
            $data = array(
               'statusMsg' => $statusMsg,
               'currentStatus' => $issue->getStatus(),
               'availableStatusList' => $issue->getAvailableStatusList(true),
               'isTimetrackEditable' => (!$isForbidenStatus), // user allowed to add/edit timetracks
               'backlog' => $issue->getBacklog(), // may have changed if status => resolved
               'bugResolvedStatusThreshold' =>  $bugResolvedStatusThreshold,
            );

         } catch (Exception $e) {
            $logger->error("$action: issue=$bugid");
            $logger->error("EXCEPTION $action: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to setStatus !");
            $data = array(
                'statusMsg' => nl2br(htmlspecialchars($statusMsg)),
            );
         }
         $jsonData = json_encode($data);
         echo $jsonData;
         
      // ================================================================
      } else if ('setDuration' == $action) {
         try {
            $trackUserid = Tools::getSecurePOSTIntValue('trackUserid');
            $bugid       = Tools::getSecurePOSTIntValue('bugid');
            $trackDate   = Tools::getSecurePOSTStringValue('trackDate');
            $defaultJobId= Tools::getSecurePOSTIntValue('defaultJobId', Jobs::JOB_NA);
            $newDurationSum = Tools::getSecurePOSTNumberValue('duration');

            $timestamp = Tools::date2timestamp($trackDate);
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $timeTracks = $issue->getTimeTracks($trackUserid, $timestamp, $timestamp);
            $nbTracks = count($timeTracks);
            $jobid = getJobid($timeTracks,  $defaultJobId); // best-effort to determinate the jobid

            $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($issue->getProjectId());
            $isForbidenStatus = array_key_exists($issue->getStatus(), $ttForbidenStatusList);
            if ($isForbidenStatus) {
               $msg = T_("Timetracking is forbidden when issue's status is :").' "'.$ttForbidenStatusList[$issue->getStatus()].'"';
               $e = new Exception($msg);
               throw $e;
            }
            
            //$logger->error("setDuration: issue=$bugid, jobid=$jobid, newDurationSum=$newDurationSum date=$trackDate trackUserid=$trackUserid");

            $statusMsg = "Unknown error";
            if (0 == $newDurationSum) {
               // delete all existing timetracks
               //$logger->error("DEBUG: delete all existing timetracks");
               foreach ($timeTracks as $timeTrack) {
                  // check if backlog must be recredited
                  $ttProject = ProjectCache::getInstance()->getProject($timeTrack->getProjectId());
                  if (!$ttProject->isSideTasksProject(array($teamid)) &&
                      !$ttProject->isExternalTasksProject()) {
                     $isRecreditBacklog = (0 == $team->getGeneralPreference('recreditBacklogOnTimetrackDeletion')) ? false : true;
                  } else {
                     // no backlog update for external & side tasks
                     $isRecreditBacklog = false;
                  }
                  // delete track
                  if(!$timeTrack->remove($session_userid, $isRecreditBacklog)) {
                     $e = new Exception("Delete track $trackid  : FAILED");
                     throw $e;
                  }
               }
               $statusMsg = 'SUCCESS';

            } else {
               if (0 == $nbTracks) {
                  // if no existing tt, create a new one
                  //$logger->error("DEBUG: if no existing tt, create a new one");
                  $trackid = TimeTrack::create($trackUserid, $bugid, $jobid, $timestamp, $newDurationSum, $session_userid, $teamid);
                  $statusMsg = 'SUCCESS';
               } elseif (1 == $nbTracks) {
                  // if only one tt exists, with same jobid (should be) : update it's duration
                  //$logger->error("DEBUG: only one tt exists, update it's duration");
                  $timetrack = reset($timeTracks); // get first element
                  $updateDone = $timetrack->update($timetrack->getDate(), $newDurationSum, $timetrack->getJobId());
                  $statusMsg = ($updateDone) ? "SUCCESS" : "timetrack update failed.";
               } else {
                  // if two or more tt exist, best-effort to edit an existing one
                  // behaviour will depend on if i have to increase or decrease the duration
                  $sumPrevDurations = 0;
                  $timetrackCandidate = null;
                  foreach ($timeTracks as $timeTrack) {
                     $sumPrevDurations += $timeTrack->getDuration();

                     // we want the tt with same jobid and biggest duration
                     if ($timeTrack->getJobId() == $jobid) {
                        if ((null == $timetrackCandidate) || ($timeTrack->getDuration() > $timetrackCandidate->getDuration())) {
                           $timetrackCandidate = $timeTrack;
                        }
                     }
                  }
                  if ($newDurationSum > $sumPrevDurations) {
                     // if a tt with same jobid exists: update it, else: add new timetrack with diff
                     $diffToAdd = $newDurationSum - $sumPrevDurations;
                     if (null != $timetrackCandidate) {
                        //$logger->error("DEBUG: increase an existing timetrack");
                        $newDuration = $timetrackCandidate->getDuration() + $diffToAdd;
                        $updateDone = $timetrackCandidate->update($timetrackCandidate->getDate(), $newDuration, $timetrackCandidate->getJobId());
                        $statusMsg = ($updateDone) ? "SUCCESS" : "timetrack update failed.";
                     } else {
                        // if newDurarion > sumPrevDurations then add new timetrack with diff
                        //$logger->error("DEBUG: increase, but no tt has same jobid: create new timetrack");
                        $trackid = TimeTrack::create($trackUserid, $bugid, $jobid, $timestamp, $diffToAdd, $session_userid, $teamid);
                        $statusMsg = 'SUCCESS';
                     }
                  } else {
                     // an existing tt has to be decreased, but this works only if :
                     //  - I find a tt with a duration that is big enough (duration > amount to decrease)
                     $diffToRemove = $sumPrevDurations - $newDurationSum;
                     if (null != $timetrackCandidate) {

                        if (round($timetrackCandidate->getDuration(),3) == round($diffToRemove,3)) {
                        //if ($timetrackCandidate->getDuration() == $diffToRemove) {
                           // if same duration, delete the tt
                           //$logger->error("DEBUG: decrease - found same duration, delete timetrack ".$timetrackCandidate->getDuration());
                           $isRemoved = $timetrackCandidate->remove($session_userid, $isRecreditBacklog);
                           $statusMsg = ($isRemoved) ? "SUCCESS" : "timetrack deletion failed";

                        } elseif ($timetrackCandidate->getDuration() > $diffToRemove) {
                           // if big enough, decrease
                           //$logger->error("DEBUG: decrease an existing timetrack");
                           $newDuration = $timetrackCandidate->getDuration() - $diffToRemove;
                           $updateDone = $timetrackCandidate->update($timetrackCandidate->getDate(), $newDuration, $timetrackCandidate->getJobId());
                           $statusMsg = ($updateDone) ? "SUCCESS" : "timetrack update failed.";
                        } else {
                           // existing tt is too small to be decreased, FORBID the change, do nothing !
                           //$logger->error("DEBUG: existing tt is too small to be decreased tt=".$timetrackCandidate->getDuration()." diff=$diffToRemove");
                           $statusMsg = T_("Sorry, multiple existing timetracks, please use the regular Timetracking page");
                        }
                     } else {
                        // No tt with same jobid, FORBID the change, do nothing !
                        //$logger->error("DEBUG: no tt with same jobid");
                        $statusMsg = T_("Sorry, multiple existing timetracks, please use the regular Timetracking page");
                     }
                  }
               }
            }

            $user = UserCache::getInstance()->getUser($trackUserid);
            $userTimetracks = $user->getTimeTracks($timestamp, $timestamp); // including other tasks
            $dayTotalElapsed = 0;
            foreach ($userTimetracks as $tt) {
               $dayTotalElapsed += $tt->getDuration();
            }
            
         } catch (Exception $e) {
            $logger->error("$action: issue=$bugid, jobid=$jobid, duration=$newDurationSum date=$trackDate trackUserid=$trackUserid");
            $logger->error("EXCEPTION $action: ".$e->getMessage());
            $statusMsg = T_("ERROR: Failed to add/update timetrack !");
            $defaultBugid = 0;
         }

         // return data
         $data = array(
            'statusMsg' => nl2br(htmlspecialchars($statusMsg)),
            'dayTotalElapsed' => round($dayTotalElapsed, 3),
         );
         $jsonData = json_encode($data);
         echo $jsonData;
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

/**
 * set smarty variables needed to display the WeekTaskDetails table
 *
 * @param type $smartyHelper
 * @param type $weekid
 * @param type $year
 * @param type $managed_userid
 * @param type $teamid
 */
function setWeekTaskDetails($smartyHelper, $weekid, $year, $managed_userid, $teamid) {

   $weekDates = Tools::week_dates($weekid,$year);
   $startTimestamp = $weekDates[1];
   $endTimestamp = mktime(23, 59, 59, date('m', $weekDates[7]), date('d', $weekDates[7]), date('Y', $weekDates[7]));
   $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);


   $incompleteDays = array_keys($timeTracking->checkCompleteDays($managed_userid, TRUE));
   $missingDays = $timeTracking->checkMissingDays($managed_userid);
   $errorDays = array_merge($incompleteDays,$missingDays);
   $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates,$errorDays);

   // UTF8 problems in smarty, date encoding needs to be done in PHP
   $smartyHelper->assign('weekDates', array(
      $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
   ));
   $smartyHelper->assign('weekEndDates', array(
      $smartyWeekDates[6], $smartyWeekDates[7]
   ));

   $weekTasks = TimeTrackingTools::getWeekTask($weekDates, $teamid, $managed_userid, $timeTracking, $errorDays);
   $smartyHelper->assign('weekTasks', $weekTasks["weekTasks"]);
   $smartyHelper->assign('dayTotalElapsed', $weekTasks["totalElapsed"]);

   // weekTaskDetails.html includes edit_issueNote.html & update_issueBacklog.html
   // these files need userid,weekid,year to be set.
   $smartyHelper->assign('userid', $managed_userid);
   $smartyHelper->assign('weekid', $weekid);
   $smartyHelper->assign('year', $year);
}


/**
 * best-effort to determinate the jobId
 *   if no timetrack, return defaultJobId
 *   if only one timetrack, return it's jobid
 *   if multiple timetrack with same jobid, return that jobid
 *   if multiple timetrack but != jobid, return defaultJobId
 *
 * @param type $bugid
 * @param type $date YYYY-MM-DD
 */
function getJobid($timetracks, $defaultJobid = Jobs::JOB_NA) {

   $jobid = $defaultJobid;

   if (0 != count($timetracks)) {
      $tt = reset($timetracks); // get first element
      $jobid = $tt->getJobId();
      foreach ($timetracks as $tt) {
         if ($jobid != $tt->getJobId()) {
            // multiple timetracks but different jobs => use defaultJobId
            $jobid = $defaultJobid;
            break; // exit foreach
         }
      }
   }
   return $jobid;
}


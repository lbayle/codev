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

class TeamActivityReportController extends Controller {

   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {

         // TODO SECURITY check array_key_exists($this->teamid, $this->teamList)
        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $year = Tools::getSecurePOSTIntValue('year', date('Y'));
            $weekid = Tools::getSecurePOSTIntValue('weekid', date('W'));

            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
            $this->smartyHelper->assign('years', SmartyTools::getYears($year,1));

            $isDetailed = isset($_POST['cb_detailed']) ? TRUE : FALSE;

            $this->smartyHelper->assign('isChecked', $isDetailed);

            $weekDates = Tools::week_dates($weekid,$year);
            $startTimestamp = $weekDates[1];
            $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

            $this->smartyHelper->assign('weekDetails', $this->getWeekDetails($timeTracking, $isDetailed, $weekDates, $this->session_user->getId()));

            // ConsistencyCheck
            $consistencyErrors = $this->getConsistencyErrors($timeTracking);
            if(count($consistencyErrors) > 0) {
               $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
               $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
            }
         }
      }
   }

   /**
    * @param int $i
    * @param Holidays $holidays
    * @param int[] $weekDates
    * @param int $duration
    * @return mixed[]
    */
   private function getDaysDetails($i, Holidays $holidays, array $weekDates, $duration) {
      $bgColor = NULL;
      $title = NULL;
      if ($i < 6) {
         $h = $holidays->isHoliday($weekDates[$i]);
         if ($h) {
            $bgColor = $h->color;
            //$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
            $title = $h->description;
         }
      }
      else {
         $bgColor = Holidays::$defaultColor;
      }

      return array(
         "color" => $bgColor,
         "title" => $title,
         "duration" => $duration
      );
   }

   /**
    * @param TimeTracking $timeTracking
    * @param bool $isDetailed
    * @param int[] $weekDates
    * @return mixed[]
    */
   private function getWeekDetails(TimeTracking $timeTracking, $isDetailed, $weekDates, $session_userid) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());

      $weekDetails = array();
      $this->session_users = $team->getUsers();
      foreach($this->session_users as $this->session_user) {
         // if user was working on the project during the timestamp

         if (($this->session_user->isTeamDeveloper($timeTracking->getTeamid(), $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp())) ||
            ($this->session_user->isTeamManager($timeTracking->getTeamid(), $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp()))) {

            // PERIOD week
            //$thisWeekId=date("W");

            $weekTracks = $timeTracking->getWeekDetails($this->session_user->getId(), !$isDetailed);
            $holidays = Holidays::getInstance();

            $weekJobDetails = array();
            foreach ($weekTracks as $bugid => $jobList) {
               try {
                  $issue = IssueCache::getInstance()->getIssue($bugid);
               } catch (Exception $e) {
                  self::$logger->error("getWeekDetails() skip issue $bugid : ".$e->getMessage());
                  $weekJobDetails[] = array(
                     "description" => '<span class="error_font">'.$bugid.' : '.T_('Error: Task not found in Mantis DB !').'</span>',
                     "duration" => "!",
                     "progress" => "!",
                     "projectName" => "!",
                     "targetVersion" => "!",
                     "jobName" => "!",
                     "daysDetails" => "!",
                     "totalDuration" => "!"
                  );
                  continue;
               }
               $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
               if ($isDetailed) {
                  $formatedJobList = implode(', ', array_keys($jobList));
                  $query = 'SELECT id, name FROM `codev_job_table` WHERE id IN ('.$formatedJobList.');';
                  $result2 = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result2) {
                     continue;
                  }
                  while($row2 = SqlWrapper::getInstance()->sql_fetch_object($result2)) {
                     $jobName = $row2->name;
                     $dayList = $jobList[$row2->id];

                     $daysDetails = array();
                     $weekDuration = 0;
                     for ($i = 1; $i <= 7; $i++) {
                        $dayDetails = $this->getDaysDetails($i, $holidays, $weekDates, $dayList[$i]);
                        $weekDuration += $dayDetails['duration'];
                        $daysDetails[] = $dayDetails;
                     }

                     if ((!$project->isSideTasksProject(array($team->getId()))) &&
                         (!$project->isExternalTasksProject())) {
                        $tooltipAttr = $issue->getTooltipItems($team->getId(), $session_userid);
                        // force some fields
                        #$tooltipAttr[T_('Elapsed')] = $issue->getElapsed();
                        #$tooltipAttr[T_('Backlog')] = $issue->getDuration();
                        #$tooltipAttr[T_('Drift')] = $issue->getDrift();
                        #$tooltipAttr[T_('DriftColor')] = $issue->getDriftColor();
                        $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);
                     } else {
                        $infoTooltip = NULL;
                     }

                     // prepare json data for the IssueNoteDialogbox
                     $issueNoteData = $this->getIssueNoteTooltip($project, $team, $issue);

                     $weekJobDetails[] = array(
                        'description' => SmartyTools::getIssueDescription($bugid, $issue->getTcId(), $issue->getSummary()),
                        'duration' => $issue->getDuration(),
                        'progress' => round(100 * $issue->getProgress()),
                        'projectName' => $issue->getProjectName(),
                        'targetVersion' => $issue->getTargetVersion(),
                        'jobName' => $jobName,
                        'daysDetails' => $daysDetails,
                        'totalDuration' => $weekDuration,
                        'infoTooltip' => $infoTooltip,
                        'issueNoteId' => $issueNoteData['id'],
                        'noteTooltip' => $issueNoteData['tooltip'],
                     );
                  }
               } else {
                  // for each day, concat jobs duration
                  $daysDetails = array();
                  $weekDuration = 0;
                  for ($i = 1; $i <= 7; $i++) {
                     $duration = 0;
                     foreach ($jobList as $dayList) {
                        if(array_key_exists($i,$dayList)) {
                           $duration += $dayList[$i];
                        }
                     }
                     if($duration == 0) {
                        $duration = "";
                     }
                     $dayDetails = $this->getDaysDetails($i, $holidays, $weekDates, $duration);

                     $weekDuration += $dayDetails['duration'];

                     $daysDetails[] = $dayDetails;
                  }

                  if ((!$project->isSideTasksProject(array($team->getId()))) &&
                      (!$project->isExternalTasksProject())) {
                     $tooltipAttr = $issue->getTooltipItems($team->getId(), $session_userid);
                     // force some fields
                     #$tooltipAttr[T_('Elapsed')] = $issue->getElapsed();
                     #$tooltipAttr[T_('Backlog')] = $issue->getDuration();
                     #$tooltipAttr[T_('Drift')] = $issue->getDrift();
                     #$tooltipAttr[T_('DriftColor')] = $issue->getDriftColor();
                     $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);
                  } else {
                     $infoTooltip = NULL;
                  }

                  // prepare json data for the IssueNoteDialogbox
                  $issueNoteData = $this->getIssueNoteTooltip($project, $team, $issue);

                  $weekJobDetails[] = array(
                     'description' => SmartyTools::getIssueDescription($bugid, $issue->getTcId(), $issue->getSummary()),
                     'duration' => $issue->getDuration(),
                     'progress' => round(100 * $issue->getProgress()),
                     'projectName' => $issue->getProjectName(),
                     //"targetVersion" => $issue->getTargetVersion(),
                     'daysDetails' => $daysDetails,
                     'totalDuration' => $weekDuration,
                     'infoTooltip' => $infoTooltip,
                     'issueNoteId' => $issueNoteData['id'],
                     'noteTooltip' => $issueNoteData['tooltip'],
                  );
               }
            }

            if(!empty($weekJobDetails)) {
               $weekDetails[] = array(
                  'name' => $this->session_user->getName(),
                  'realname' => $this->session_user->getRealname(),
                  'forecastWorkload' => $this->session_user->getForecastWorkload(),
                  'weekDates' => array(
                     Tools::formatDate("%A\n%d %b", $weekDates[1]),
                     Tools::formatDate("%A\n%d %b", $weekDates[2]),
                     Tools::formatDate("%A\n%d %b", $weekDates[3]),
                     Tools::formatDate("%A\n%d %b", $weekDates[4]),
                     Tools::formatDate("%A\n%d %b", $weekDates[5])
                  ),
                  'weekEndDates' => array(
                     Tools::formatDate("%A\n%d %b", $weekDates[6]),
                     Tools::formatDate("%A\n%d %b", $weekDates[7])
                  ),
                  'weekJobDetails' => $weekJobDetails
               );
            }
         }
      }
      return $weekDetails;
   }

   /**
    * Get consistency errors
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getConsistencyErrors(TimeTracking $timeTracking) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $cerrList = ConsistencyCheck2::checkIncompleteDays($timeTracking);

      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            $this->session_user = UserCache::getInstance()->getUser($cerr->userId);
            $consistencyErrors[] = array(
               'date' => date("Y-m-d", $cerr->timestamp),
               'user' => $this->session_user->getName(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);
         }
      }

      return $consistencyErrors;
   }

   private function getIssueNoteTooltip($project, $team, $issue) {
      // prepare json data for the IssueNoteDialogbox
      if ((!$project->isSideTasksProject(array($team->getId()))) &&
         (!$project->isExternalTasksProject())) {

         $issueNote = IssueNote::getTimesheetNote($issue->getId());
         if (!is_null($issueNote)) {
            $issueNoteId = $issueNote->getId();

            // used for the tooltip NOT the dialoBox
            $tooltipAttr = array (
               'date' => date('Y-m-d H:i:s', $issueNote->getLastModified()),
               'Note' => $issueNote->getText(),
            );
            $noteTooltip = Tools::imgWithTooltip('images/b_note.png', $tooltipAttr, "bugNote_".$issueNote->getBugId());
         } else {
            $issueNoteId = 0;
            $noteTooltip = '';
         }
      } else {
         $issueNoteId = 0;
         $noteTooltip = '';
      }
      return array('id' => $issueNoteId, 'tooltip' => $noteTooltip);
   }

}

// ========== MAIN ===========
TeamActivityReportController::staticInit();
$controller = new TeamActivityReportController('../', 'Weekly activities','TimeTracking');
$controller->execute();

?>

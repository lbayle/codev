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

class TeamMonthlyActivityReportController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $isManager = $this->session_user->isTeamManager($this->teamid);
            $isObserver = $this->session_user->isTeamObserver($this->teamid);
            if ($isManager || $isObserver) {
               // observers have access to the same info
               $this->smartyHelper->assign('isManager', true);
            }

            // dates
            $month = date('m');
            $year = date('Y');

            $startdate = Tools::getSecurePOSTStringValue("startdate", Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year)));
            $this->smartyHelper->assign('startDate', $startdate);
            $startTimestamp = Tools::date2timestamp($startdate);

            $nbDaysInMonth = date("t", $startTimestamp);
            $enddate = Tools::getSecurePOSTStringValue("enddate",Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, $nbDaysInMonth, $year)));
            $this->smartyHelper->assign('endDate', $enddate);
            $endTimestamp = Tools::date2timestamp($enddate);

            $isDetailed = isset($_POST['cb_detailed']) ? TRUE : FALSE;
            //$isDetailed = Tools::getSecurePOSTStringValue('cb_detailed','');
            $this->smartyHelper->assign('isDetailed', $isDetailed);

            if ('computeMonthlyActivityReport' == $_POST['action']) {

               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

               // ConsistencyCheck
               $consistencyErrors = $this->getConsistencyErrors($timeTracking);
               if(count($consistencyErrors) > 0) {
                  $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                  $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
               }

               if ($isDetailed) {
                  //INCLUDING tasks on projects not included in the team !
                  $tracks = $timeTracking->getTimeTracks();

               } else {
                  // create issueSelection with issues from team projects
                  $team = TeamCache::getInstance()->getTeam($this->teamid);
                  $teamIssues = $team->getTeamIssueList(true, true); // with disabledProjects ?
                  $teamIssueSelection = new IssueSelection('Team'.$this->teamid.'ISel');
                  $teamIssueSelection->addIssueList($teamIssues);
                  $teamUsers = $team->getActiveMembers($startTimestamp, $endTimestamp);
                  $tracks = $teamIssueSelection->getTimetracks(array_keys($teamUsers), $startTimestamp, $endTimestamp);
               }
               $this->smartyHelper->assign('monthlyActivityReport', $this->getMonthlyActivityReport($tracks));
            }
        }
      }
   }

   private function getMonthlyActivityReport(array $tracks) {

      $userList = array();  // first is 'All', then one per user

      #$userList['0'] = array(); // All users together

      foreach ($tracks as $t) {

         $userid = $t->getUserId();
         $bugid = $t->getIssueId();
         if (!array_key_exists($userid, $userList)) {
            $user = UserCache::getInstance()->getUser($userid);
            $userList["$userid"] = array(
                'name' => $user->getName(),
                'realname' => $user->getRealname(),
                'elapsedInPeriod' => 0,
                'tasks' => array()
            );
            #echo "new user $userid<br>";
         }

         if (!array_key_exists($bugid, $userList["$userid"]['tasks'])) {

            try {
               $issue = IssueCache::getInstance()->getIssue($bugid);
               $project = ProjectCache::getInstance()->getProject($issue->getProjectId());

               if ((!$project->isSideTasksProject(array($this->teamid))) &&
                   (!$project->isExternalTasksProject())) {
                  $tooltipAttr = $issue->getTooltipItems($this->teamid, $this->session_userid);
                  $infoTooltip = Tools::imgWithTooltip('images/b_info.png', $tooltipAttr);

                  $progress = round(100 * $issue->getProgress());
                  $backlog = $issue->getBacklog();
               } else {
                  $infoTooltip = NULL;
                  $progress = NULL;
                  $backlog = NULL;
               }
               $projectName = $issue->getProjectName();
               $summary = SmartyTools::getIssueDescription($bugid, $issue->getTcId(), $issue->getSummary());

            } catch (Exception $e) {
                  $infoTooltip = NULL;
                  $progress = NULL;
                  $backlog = NULL;
                  $projectName = '<span class="error_font">'.T_('Error').'</span>';
                  $summary = $bugid.' : <span class="error_font">'.T_('Error: Task not found in Mantis DB !').'</span>';

            }

            $userList["$userid"]['tasks']["$bugid"] = array(
                'id' => $bugid,
                'infoTooltip' => $infoTooltip,
                'projectName' => $projectName,
                'summary' => $summary,
                'progress' => $progress,
                'backlog' => $backlog,
                'elapsedInPeriod' => 0
            );
            #echo "new UserTask $bugid : ".$issue->getSummary()."<br>";
         }

         $userList["$userid"]['tasks']["$bugid"]['elapsedInPeriod'] += $t->getDuration();
         $userList["$userid"]['elapsedInPeriod'] += $t->getDuration();
         #echo "user $userid task $bugid elapsedInPeriod = ".$userList["$userid"]['tasks']["$bugid"]['elapsedInPeriod'].'<br>';

      }

      #var_dump($userList);
      return $userList;
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

}

// ========== MAIN ===========
TeamMonthlyActivityReportController::staticInit();
$controller = new TeamMonthlyActivityReportController('../', 'Team Monthly Activity');
$controller->execute();

?>

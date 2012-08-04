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

require('include/super_header.inc.php');

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

require('reports/productivity_report_tools.php');

include_once('classes/time_tracking.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Period Statistics');
$smartyHelper->assign('activeGlobalMenuItem', 'ProdReports');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $teamList = $session_user->getTeamList();
   if (0 != count($teamList)) {
      $weekDates = Tools::week_dates(date('W'),date('Y'));

      if(isset($_POST['teamid'])) {
         $teamid = Tools::getSecurePOSTIntValue('teamid');
         $_SESSION['teamid'] = $teamid;
      } else {
         $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      }

      $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

      $startdate = Tools::getSecurePOSTStringValue('startdate', date("Y-m-d", $weekDates[1]));
      $smartyHelper->assign('startDate', $startdate);

      $enddate = Tools::getSecurePOSTStringValue('enddate', date("Y-m-d", $weekDates[5]));
      $smartyHelper->assign('endDate', $enddate);

      if (0 != $teamid) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         if(count($team->getProjects(false)) > 0) {
            $startTimestamp = Tools::date2timestamp($startdate);
            $endTimestamp = Tools::date2timestamp($enddate);
            $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

            $smartyHelper->assign('timeTracking', $timeTracking);

            if (0 != $timeTracking->getProdDays() + $timeTracking->getManagementDays() + ($timeTracking->getProdDaysSideTasks(false) - $timeTracking->getManagementDays())) {
               $smartyHelper->assign('productionDaysUrl', ProductivityReportTools::getProductionDaysUrl($timeTracking));
            }

            $workingDaysPerJobs = ProductivityReportTools::getWorkingDaysPerJob($timeTracking, $teamid);
            $smartyHelper->assign('workingDaysPerJob', $workingDaysPerJobs);
            if($workingDaysPerJobs != NULL) {
               $smartyHelper->assign('workingDaysPerJobUrl', ProductivityReportTools::getWorkingDaysPerJobUrl($workingDaysPerJobs));
            }

            $defaultProjectid = Tools::getSecurePOSTIntValue('projectid', 0);
            $getTeamProjects = SmartyTools::getSmartyArray(ProductivityReportTools::getTeamProjects($teamid),$defaultProjectid);
            $smartyHelper->assign('projects', $getTeamProjects);

            $projectid = 0;
            if(array_key_exists($defaultProjectid, $getTeamProjects)) {
               $projectid = $defaultProjectid;
            }
            $smartyHelper->assign('projectid', $projectid);

            $projectDetails = NULL;
            if (0 != $projectid) {
               $projectDetails = ProductivityReportTools::getProjectDetails($timeTracking, $projectid);
            } else {
               // all sideTasks
               $projectDetails = ProductivityReportTools::getSideTasksProjectDetails($timeTracking);
            }
            $smartyHelper->assign('projectDetails', $projectDetails);
            if($projectDetails != NULL) {
               $smartyHelper->assign('projectDetailsUrl', ProductivityReportTools::getProjectDetailsUrl($projectDetails));
            }

            $workingDaysPerProject = ProductivityReportTools::getWorkingDaysPerProject($timeTracking);
            $smartyHelper->assign('workingDaysPerProject', $workingDaysPerProject);
            if($workingDaysPerProject != NULL) {
               $smartyHelper->assign('workingDaysPerProjectUrl', ProductivityReportTools::getWorkingDaysPerProjectUrl($workingDaysPerProject));
            }

            $smartyHelper->assign('efficiencyRate', round($timeTracking->getEfficiencyRate(), 2));
            $smartyHelper->assign('systemDisponibilityRate', round($timeTracking->getSystemDisponibilityRate(), 3));

            $timeDriftStats = $timeTracking->getTimeDriftStats();
            $smartyHelper->assign('timeDriftStats', $timeDriftStats);
            $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
            $percent = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;
            $smartyHelper->assign('percent', round($percent, 1));

            $resolvedIssues = $timeTracking->getResolvedIssues();
            if (0 != count($resolvedIssues)) {
               $withSupport = true;
               $smartyHelper->assign('resolvedDeviationStats', ProductivityReportTools::getResolvedDeviationStats ($resolvedIssues, $withSupport));

               $managedTeamList = $session_user->getManagedTeamList();
               $isManager = array_key_exists($teamid, $managedTeamList);
               $smartyHelper->assign('isManager', $isManager);
               $smartyHelper->assign('resolvedIssuesInDrift', ProductivityReportTools::getResolvedIssuesInDrift($resolvedIssues, $isManager));
            }

            $smartyHelper->assign('reopenedBugsRate', round($timeTracking->getReopenedRate() * 100, 1));
            $smartyHelper->assign('formattedReopenedTaks', ProductivityReportTools::getFormattedReopenedTaks($timeTracking));

            // warnings
            $consistencyErrors = ProductivityReportTools::getCheckWarnings($timeTracking);
            $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
            $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
            $smartyHelper->assign('ccheckErrList', $consistencyErrors);
         }
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

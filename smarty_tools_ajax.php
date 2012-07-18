<?php
require('include/session.inc.php');
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

if(isset($_SESSION['userid']) && (isset($_GET['action']) || isset($_POST['action']))) {

   require('path.inc.php');
   require('include/super_header.inc.php');
   require('smarty_tools.php');

   if(isset($_GET['action'])) {
      require('classes/smarty_helper.class.php');
      require_once('i18n/i18n.inc.php');

      $smartyHelper = new SmartyHelper();

      if ($_GET['action'] == 'getTeamProjects') {
         include_once('team_cache.class.php');

         $projects = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'))->getProjects(false);
         $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projects, 0));
         $smartyHelper->display('form/projectSelector');
         
      } elseif ($_GET['action'] == 'getTeamAllProjects') {
         include_once('team_cache.class.php');

         $projects[0] = T_('All projects');
         $projects += $projects = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'))->getProjects(false);
         $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projects, 0));
         $smartyHelper->display('form/projectSelector');

      } elseif($_GET['action'] == 'getProjectIssues') {
         include_once('user_cache.class.php');

         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // --- define the list of tasks the user can display
         // All projects from teams where I'm a Developper or Manager AND Observer
         $allProject[0] = T_('(all)');
         $dTeamList = $user->getDevTeamList();
         $devProjList = count($dTeamList) > 0 ? $user->getProjectList($dTeamList) : array();
         $managedTeamList = $user->getManagedTeamList();
         $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList) : array();
         $oTeamList = $user->getObservedTeamList();
         $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList) : array();
         $projList = $allProject + $devProjList + $managedProjList + $observedProjList;

         // WORKAROUND
         if($_GET['bugid'] == 'null') {
            $_GET['bugid'] = 0;
         }
         $smartyHelper->assign('bugs', SmartyTools::getBugs(Tools::getSecureGETIntValue('projectid'),Tools::getSecureGETIntValue('bugid',0),$projList));
         $smartyHelper->display('form/bugSelector');
      }
      else if($_GET['action'] == 'getProjectDetails') {
         require_once('reports/productivity_report_tools.php');

         $weekDates  = Tools::week_dates(date('W'),date('Y'));
         $startdate  = Tools::getSecureGETStringValue('startdate', date("Y-m-d", $weekDates[1]));
         $startTimestamp = Tools::date2timestamp($startdate);

         $enddate  = Tools::getSecureGETStringValue('enddate', date("Y-m-d", $weekDates[5]));
         $endTimestamp = Tools::date2timestamp($enddate);
         $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $_GET['teamid']);

         $projectid  = $_GET['projectid'];
         $projectDetails = NULL;
         if (isset($projectid) && 0 != $projectid) {
            $projectDetails = getProjectDetails($timeTracking, $projectid);
         } else {
            // all sideTasks
            $projectDetails = getSideTasksProjectDetails($timeTracking);
         }
         $smartyHelper->assign('projectDetails', $projectDetails);
         if($projectDetails != NULL) {
            $smartyHelper->assign('projectDetailsUrl', getProjectDetailsUrl($projectDetails));
         }
         $smartyHelper->display('ajax/projectDetails');
      }
      elseif($_GET['action'] == 'getYearsToNow') {
         include_once('classes/team_cache.class.php');

         $team = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'));
         $min_year = date("Y", $team->date);
         $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;
         $smartyHelper->assign('years', SmartyTools::getYearsToNow($min_year,$year));
         $smartyHelper->display('form/yearSelector');
      }
      else {
         Tools::sendNotFoundAccess();
      }
   }
   else if($_POST['action']) {
      if($_POST['action'] == 'updateRemainingAction') {
         include_once('classes/issue_cache.class.php');

         $issue = IssueCache::getInstance()->getIssue(Tools::getSecurePOSTIntValue('bugid'));
         $issue->setRemaining(Tools::getSecurePOSTNumberValue('remaining'));
      }
      else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

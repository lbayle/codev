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

require('path.inc.php');

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {

   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();

      if ($_GET['action'] == 'getTeamProjects') {
         $withDisabled = ('1' == Tools::getSecureGETIntValue('withDisabledProjects', 1)) ? true : false;
         $projects = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'))->getProjects(false, $withDisabled);
         $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projects, 0));
         $smartyHelper->display('form/projectSelector');
         
      } elseif ($_GET['action'] == 'getTeamAllProjects') {
         $withDisabled = ('1' == Tools::getSecureGETIntValue('withDisabledProjects', 1)) ? true : false;
         $projects[0] = T_('All projects');
         $projects += $projects = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'))->getProjects(false, $withDisabled);
         $smartyHelper->assign('projects', SmartyTools::getSmartyArray($projects, 0));
         $smartyHelper->display('form/projectSelector');

      } elseif($_GET['action'] == 'getProjectIssues') {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $withDisabled = ('1' == Tools::getSecureGETIntValue('withDisabledProjects', 1)) ? true : false;

         // --- define the list of tasks the user can display
         // All projects from teams where I'm a Developper or Manager AND Observer
         $allProject[0] = T_('(all)');
         $dTeamList = $user->getDevTeamList();
         $devProjList = count($dTeamList) > 0 ? $user->getProjectList($dTeamList, true, $withDisabled) : array();
         $managedTeamList = $user->getManagedTeamList();
         $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList, true, $withDisabled) : array();
         $oTeamList = $user->getObservedTeamList();
         $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList, true, $withDisabled) : array();
         $projList = $allProject + $devProjList + $managedProjList + $observedProjList;

         $projectid = Tools::getSecureGETIntValue('projectid');
         $bugid = Tools::getSecureGETIntValue('bugid',0);

         $smartyHelper->assign('bugs', SmartyTools::getBugs($projectid, $bugid, $projList));
         $smartyHelper->display('form/bugSelector');
      }
      elseif($_GET['action'] == 'getYearsToNow') {
         $team = TeamCache::getInstance()->getTeam(Tools::getSecureGETIntValue('teamid'));
         $min_year = date("Y", $team->getDate());
         $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;
         $smartyHelper->assign('years', SmartyTools::getYearsToNow($min_year,$year));
         $smartyHelper->display('form/yearSelector');
      }
      else {
         Tools::sendNotFoundAccess();
      }
   }
   else if($_POST['action']) {
      if($_POST['action'] == 'updateBacklogAction') {
         $issue = IssueCache::getInstance()->getIssue(Tools::getSecurePOSTIntValue('bugid'));
         $issue->setBacklog(Tools::getSecurePOSTNumberValue('backlog'));
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

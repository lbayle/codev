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

require('super_header.inc.php');

require('../smarty_tools.php');

require('issue_info_tools.php');

include('user_cache.class.php');
include('consistency_check2.class.php');

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Activity by task');

if(isset($_SESSION['userid'])) {
   $user = UserCache::getInstance()->getUser($_SESSION['userid']);
   $teamList = $user->getTeamList();

   if (count($teamList) > 0) {
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

      $bug_id = getSecureGETIntValue('bugid', 0);
      $bugs = getBugs($defaultProjectid, $bug_id, $projList);
      $smartyHelper->assign('bugs', $bugs);

      $defaultProjectid = 0;
      if($bug_id != 0) {
         $defaultProjectid = $bugs[$bug_id]['projectid'];
      }

      $smartyHelper->assign('projects', getProjects($projList,$defaultProjectid));

      // if 'support' is set in the URL, display graphs for 'with/without Support'
      $displaySupport = isset($_GET['support']) ? true : false;
      if($displaySupport) {
         $smartyHelper->assign('support', $displaySupport);
      }

      // user may not have the rights to see this bug (observers, ...)
      $taskList = $user->getPossibleWorkingTasksList($projList);
      if (in_array($bug_id, $taskList)) {
         $issue = IssueCache::getInstance()->getIssue($bug_id);
         $smartyHelper->assign('issueSummary', $issue->summary);
         $smartyHelper->assign('issueUrl', mantisIssueURL($issue->bugId));
         $smartyHelper->assign('issueStatusName', $issue->getCurrentStatusName());
         $smartyHelper->assign('handlerName', UserCache::getInstance()->getUser($issue->handlerId)->getName());

         $consistencyErrors = NULL;

         $ccheck = new ConsistencyCheck2(array($issue));
         $cerrList = $ccheck->check();
         if (0 != count($cerrList)) {
            foreach ($cerrList as $cerr) {
               $consistencyErrors[] = array(
                  "severity" => $cerr->getLiteralSeverity(),
                  "description" => $cerr->desc
               );
            }
            $smartyHelper->assign('consistencyErrors', $consistencyErrors);
         }

         $isManager = (array_key_exists($issue->projectId, $managedProjList)) ? true : false;
         $smartyHelper->assign('isManager', $isManager);
         $smartyHelper->assign('issueGeneralInfo', getIssueGeneralInfo($issue, $isManager, $displaySupport));
         $smartyHelper->assign('jobDetails', getJobDetails($issue));
         $smartyHelper->assign('timeDrift', getTimeDrift($issue));

         $smartyHelper->assign('months', getCalendar($issue));
         $smartyHelper->assign('durationsByStatus', getDurationsByStatus($issue));
      }
   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

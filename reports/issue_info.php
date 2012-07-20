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

require('reports/issue_info_tools.php');

include_once('classes/consistency_check2.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

// ========== MAIN ===========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Task Info');

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

      // if 'support' is set in the URL, display graphs for 'with/without Support'
      $displaySupport = isset($_GET['support']) ? true : false;
      if($displaySupport) {
         $smartyHelper->assign('support', $displaySupport);
      }

      $bug_id = Tools::getSecureGETIntValue('bugid', 0);
      $bugs = NULL;
      $projects = NULL;
      if($bug_id != 0) {
         try {
            $issue = IssueCache::getInstance()->getIssue($bug_id);

            $defaultProjectid = $issue->projectId;
            $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id);
            if (array_key_exists($bug_id,$bugs)) {
               $consistencyErrors = NULL;
               $ccheck = new ConsistencyCheck2(array($issue));
               $cerrList = $ccheck->check();
               if (0 != count($cerrList)) {
                  foreach ($cerrList as $cerr) {
                     $consistencyErrors[] = array(
                        'severity' => $cerr->getLiteralSeverity(),
                        'severityColor' => $cerr->getSeverityColor(),
                        'desc' => $cerr->desc
                     );
                  }
                  $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
                  $smartyHelper->assign('ccheckErrList', $consistencyErrors);
               }

               $isManager = (array_key_exists($issue->projectId, $managedProjList)) ? true : false;
               $smartyHelper->assign('isManager', $isManager);
               $smartyHelper->assign('issueGeneralInfo', IssueInfoTools::getIssueGeneralInfo($issue, $isManager, $displaySupport));
               $timeTracks = $issue->getTimeTracks();
               $smartyHelper->assign('jobDetails', IssueInfoTools::getJobDetails($timeTracks));
               $smartyHelper->assign('timeDrift', IssueInfoTools::getTimeDrift($issue));

               $smartyHelper->assign('months', IssueInfoTools::getCalendar($issue,$timeTracks));
               $smartyHelper->assign('durationsByStatus', IssueInfoTools::getDurationsByStatus($issue));

               // set Commands I belong to
               $parentCmds = IssueInfoTools::getParentCommands($issue);
               $smartyHelper->assign('parentCommands', $parentCmds);
               $smartyHelper->assign('nbParentCommands', count($parentCmds));
            }
            $projects = SmartyTools::getSmartyArray($projList,$defaultProjectid);
            $_SESSION['projectid'] = $defaultProjectid;
         } catch (Exception $e) {
            // TODO display ERROR "issue not found in mantis DB !"
         }

      } else {
         $defaultProjectid = 0;
         if((isset($_SESSION['projectid'])) && (0 != $_SESSION['projectid'])) {
            $defaultProjectid = $_SESSION['projectid'];
            $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id);
         } else {
            $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id, $projList);
         }
         $projects = SmartyTools::getSmartyArray($projList,$defaultProjectid);
      }
      $smartyHelper->assign('bugs', $bugs);
      $smartyHelper->assign('projects', $projects);
   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

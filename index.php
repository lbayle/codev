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

// check if INSTALL needed
if ((!file_exists('constants.php')) || (!file_exists('include/mysql_config.inc.php'))) {
   header('Location: install/install.php');
   exit;
}

require('path.inc.php');

class IndexController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      // Drifted tasks
      if($_SESSION['userid']) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // updateBacklog DialogBox
         if (isset($_POST['bugid'])) {
            $bugid = Tools::getSecurePOSTStringValue('bugid');
            $backlog = Tools::getSecurePOSTStringValue('backlog', '');
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $issue->setBacklog($backlog);
         }

         $driftedTasks = $this->getIssuesInDrift($user);
         if(isset($driftedTasks)) {
            $this->smartyHelper->assign('driftedTasks', $driftedTasks);
         }

         // Consistency errors
         $consistencyErrors = $this->getConsistencyErrors($user);
         $consistencyErrorsMgr = $this->getConsistencyErrorsMgr($user);

         $consistencyErrors = array_merge($consistencyErrors, $consistencyErrorsMgr);

         if(count($consistencyErrors) > 0) {
            $this->smartyHelper->assign('consistencyErrorsTitle', count($consistencyErrors).' '.T_("Errors in your Tasks"));
            $this->smartyHelper->assign('consistencyErrors', $consistencyErrors);
         }
      }
   }

   /**
    * Get issues in drift
    * @param User $user
    * @return mixed[]
    */
   private function getIssuesInDrift(User $user) {
      $allIssueList = $user->getAssignedIssues();
      $issueList = array();
      $driftedTasks = array();

      foreach ($allIssueList as $issue) {
         $driftEE = $issue->getDrift();
         if ($driftEE >= 1) {
            $issueList[] = $issue;
         }
      }
      if (count($issueList) > 0) {
         foreach ($issueList as $issue) {
            // TODO: check if issue in team project list ?
            $driftEE = $issue->getDrift();

            $formatedTitle = $issue->getFormattedIds();
            $formatedSummary = str_replace("'", "\'", $issue->getSummary());
            $formatedSummary = str_replace('"', "\'", $formatedSummary);

            $driftedTasks[] = array('issueInfoURL' => Tools::issueInfoURL($issue->getId()),
               'projectName' => $issue->getProjectName(),
               'driftEE' => $driftEE,
               'formatedTitle' => $formatedTitle,
               'bugId' => $issue->getId(),
               'backlog' => $issue->getBacklog(),
               'formatedSummary' => $formatedSummary,
               'summary' => $issue->getSummary());
         }
      }

      return $driftedTasks;
   }

   /**
    * Get consistency errors
    * @param User $sessionUser
    * @return mixed[]
    */
   private function getConsistencyErrors(User $sessionUser) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $teamList = $sessionUser->getTeamList();
      $projList = $sessionUser->getProjectList($teamList);

      $issueList = $sessionUser->getAssignedIssues($projList, true);

      $ccheck = new ConsistencyCheck2($issueList);

      $cerrList = $ccheck->check();

      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            if ($sessionUser->getId() == $cerr->userId) {
               $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
               $consistencyErrors[] = array('issueURL' => Tools::issueInfoURL($cerr->bugId, '['.$issue->getProjectName().'] '.$issue->getSummary()),
                  'status' => Constants::$statusNames[$cerr->status],
                  'desc' => $cerr->desc);
            }
         }
      }

      return $consistencyErrors;
   }

   /**
    * managers get some more consistencyErrors
    * @param User $sessionUser
    * @return mixed[]
    */
   private function getConsistencyErrorsMgr(User $sessionUser) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $mTeamList = array_keys($sessionUser->getManagedTeamList());
      $lTeamList = array_keys($sessionUser->getLeadedTeamList());
      $teamList = array_merge($mTeamList, $lTeamList);

      $issueList = array();
      foreach ($teamList as $teamid) {
         $issues = TeamCache::getInstance()->getTeam($teamid)->getTeamIssueList(true);
         $issueList = array_merge($issueList, $issues);
      }

      $ccheck = new ConsistencyCheck2($issueList);

      $cerrList = $ccheck->checkUnassignedTasks();
      if (count($cerrList) > 0) {

         $bugidList = array();
         foreach ($cerrList as $cerr) {
            $bugidList[] = $cerr->bugId;
         }
         $formattedBugidList = implode(', ', $bugidList);

         $consistencyErrors[] = array(
            'mantisIssueURL' => ' ',
            'date' => ' ',
            'status' => ' ',
            'desc' => count($cerrList).' '.T_("Tasks need to be assigned."),
            'addInfo' => $formattedBugidList
         );
      }

      return $consistencyErrors;
   }

}

// ========== MAIN ===========
IndexController::staticInit();
$controller = new IndexController(Constants::$homepage_title);
$controller->execute();

?>

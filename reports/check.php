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

class CheckController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("check");
   }

   protected function display() {
      // Consistency errors
      if (Tools::isConnectedUser()) {

         if (0 != $this->teamid) {
            $consistencyErrors = $this->getTeamConsistencyErrors($this->teamid);

            $this->smartyHelper->assign('teamid', $this->teamid);
            $this->smartyHelper->assign('count', count($consistencyErrors));
            if(isset($consistencyErrors)) {
               $this->smartyHelper->assign('consistencyErrors', $consistencyErrors);
            }
         }
      }

      // log stats
      IssueCache::getInstance()->logStats();
      ProjectCache::getInstance()->logStats();
   }

   /**
    * Get consistency errors
    * @param int $teamid
    * @return mixed[]
    */
   private function getTeamConsistencyErrors($teamid) {
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getTeamConsistencyErrors teamid=$teamid");
      }

      // get team projects
      $issueList = TeamCache::getInstance()->getTeam($teamid)->getTeamIssueList(true, false);

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getTeamConsistencyErrors nbIssues=".count($issueList));
      }

      #$ccheck = new ConsistencyCheck2($issueList);
      $ccheck = new ConsistencyCheck2($issueList, $teamid);

      $cerrList = $ccheck->check();

      $cerrs = NULL;
      if (count($cerrList) > 0) {
         $i = 0;
         foreach ($cerrList as $cerr) {
            $i += 1;
            if (NULL != $cerr->userId) {
               $user = UserCache::getInstance()->getUser($cerr->userId);
            }
            if (Issue::exists($cerr->bugId)) {
               $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
               $summary = $issue->getSummary();
               $projName = $issue->getProjectName();
               $targetVersion = $issue->getTargetVersion();
            } else {
               $summary = '';
               $projName = '';
               $targetVersion = '';
            }

            $cerrs[$i] = array(
               'userName' => isset($user) ? $user->getName() : '',
               'issueURL' => (NULL == $cerr->bugId) ? '' : Tools::issueInfoURL($cerr->bugId, $summary),
               'mantisURL' => (NULL == $cerr->bugId) ? '' : Tools::mantisIssueURL($cerr->bugId, $summary, true),
               'date' =>  (NULL == $cerr->timestamp) ? '' : date("Y-m-d", $cerr->timestamp),
               'status' => (NULL == $cerr->status) ? '' : Constants::$statusNames[$cerr->status],
               'severity' => $cerr->getLiteralSeverity(),
               'project' => $projName,
               'targetVersion' => $targetVersion,
               'desc' => $cerr->desc
            );
         }
      }
      return $cerrs;
   }

}

// ========== MAIN ===========
CheckController::staticInit();
$controller = new CheckController('Consistency Check','ConsistencyCheck');
$controller->execute();

?>

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

class BVIController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }


   private function getResolvedIssues($teamid, $userid = 0, $projects = NULL) {

      $team = TeamCache::getInstance()->getTeam($teamid);

      if (is_null($projects)) {
         $projects = $team->getProjects(false, false, false);
         $formattedProjects = implode(',', array_keys($projects));
      } else {
         $formattedProjects = implode(',', array_values($projects));
      }

      $formattedUsers = (0 != $userid) ? $userid : implode(',', array_keys($team->getActiveMembers()));


      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id FROM {bug} ".
         "WHERE project_id IN (".$formattedProjects.") ".
         " AND handler_id IN (".$formattedUsers.") ".
         " AND status >= get_project_resolved_status_threshold(project_id) ";
      $result = $sql->sql_query($query);
echo "query = $query<br>";

      $isel = new IssueSelection('resolvedIssues');
      while($row = $sql->fetchObject($result)) {
         $isel->addIssue($row->id);
      }
echo implode(',', array_keys($isel->getIssueList()));
      return $isel;
   }


   protected function display() {
      if (Tools::isConnectedUser()) {

         $isel = $this->getResolvedIssues($this->teamid, $this->session_userid);
         #$isel = $this->getResolvedIssues($this->teamid, $this->session_userid, array(18));
         #$isel = $this->getResolvedIssues($this->teamid, 17, array(18));

         #$isel = new IssueSelection('testSel');
         #$isel->addIssue(565);
         #$isel->addIssue(567);
         #$isel->addIssue(377);

         $indic = new BacklogVariationIndicator();
         $indic->execute($isel);

         $data = $indic->getSmartyObject();
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
         

      }
   }

}

// ========== MAIN ===========
BVIController::staticInit();
$controller = new BVIController('../', 'TEST BacklogVariationIndicator', 'Tests');
$controller->execute();

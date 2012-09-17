<?php
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

require_once('consistency_check.class.php');

require_once('classes/constants.class.php');

include_once('classes/config.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/user_cache.class.php');

/**
 * FDJ specificities due to != workflow
 */
class ConsistencyCheckFDJ extends ConsistencyCheck {

   /**
    * perform all consistency checks
    * @return ConsistencyError[]
    */
   public function check() {
      $cerrList_parent = parent::check();
      $cerrList1 = $this->checkAnalyzed();
      $cerrList = array_merge($cerrList_parent, $cerrList1);
      return $cerrList;
   }

   /**
    * fiches analyzed dont BI non renseignes
    * fiches analyzed dont RAE non renseignes
    * @return ConsistencyError[]
    */
   public function checkAnalyzed() {
      // CoDev FDJ custom, defined in Mantis
      $status_analyzed  = Config::getVariableKeyFromValue(Config::id_statusNames, 'analyzed');
      $status_accepted  = Config::getVariableKeyFromValue(Config::id_statusNames, 'accepted');
      $status_deferred  = Config::getVariableKeyFromValue(Config::id_statusNames, 'deferred');

      $FDJ_teamid = Config::getInstance()->getValue(Config::id_ClientTeamid);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT * ".
               "FROM `mantis_bug_table` ".
               "WHERE status in ($status_analyzed, $status_accepted, ".Constants::$status_open.", $status_deferred) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         if (NULL == $issue->getEffortEstim()) {
            $cerr = new ConsistencyError($row->id,
               $row->handler_id,
               $row->status,
               $row->last_updated,
               T_("BI not specified: BI = Time(Analysis + Dev + Tests)"));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
         if (is_null($issue->getBacklog())) {
            $cerr = new ConsistencyError($row->bug_id,
               $row->handler_id,
               $row->status,
               $row->last_updated,
               T_("Backlog not specified: Backlog = Time(BI - Analysis)"));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
         if ($status_analyzed == $row->status) {
            $user = UserCache::getInstance()->getUser($row->handler_id);
            if (! $user->isTeamMember($FDJ_teamid)) {
               $cerr = new ConsistencyError($row->bug_id,
                  $row->handler_id,
                  $row->status,
                  $row->last_updated,
                  T_("Once analysed, a Task must be assigned to 'FDJ' for validation"));
               $cerr->severity = T_("Error");
               $cerrList[] = $cerr;
            }
         }
      }

      // check if fields correctly set
      return $cerrList;
   }

}

?>

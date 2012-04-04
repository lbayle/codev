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

include_once "consistency_check.class.php";

include_once "constants.php";
include_once "issue.class.php";
include_once "user.class.php";

/**
 * FDJ specificities due to != workflow
 */
class ConsistencyCheckFDJ extends ConsistencyCheck {

   // ----------------------------------------------
   /**
    * perform all consistency checks
    */
   public function check() {

      $cerrList_parent = parent::check();
      $cerrList1 = $this->checkAnalyzed();
      $cerrList = array_merge($cerrList_parent, $cerrList1);
      return $cerrList;
   }

   // ----------------------------------------------
   // fiches analyzed dont BI non renseignes
   // fiches analyzed dont RAE non renseignes
   public function checkAnalyzed() {

      global $status_open;

	  // CoDev FDJ custom, defined in Mantis
      $status_analyzed  = Config::getVariableKeyFromValue(Config::id_statusNames, 'analyzed');
      $status_accepted  = Config::getVariableKeyFromValue(Config::id_statusNames, 'accepted');
      $status_deferred  = Config::getVariableKeyFromValue(Config::id_statusNames, 'deferred');
      $status_delivered = Config::getVariableKeyFromValue(Config::id_statusNames, 'delivered');


   	  $FDJ_teamid = Config::getInstance()->getValue(Config::id_ClientTeamid);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_analyzed, $status_accepted, $status_open, $status_deferred) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
      	$issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if (NULL == $issue->effortEstim) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("BI not specified: BI = Time(Analysis + Dev + Tests)"));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      	if (NULL == $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining not specified: Remaining = Time(BI - Analysis)"));
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

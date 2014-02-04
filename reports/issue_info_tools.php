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

class IssueInfoTools {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   /**
    * Get general info of an issue
    * @param Issue $issue The issue
    * @param bool $isManager if true: show MgrEffortEstim column
    * @param bool $displaySupport If true, display support
    * @return mixed[]
    */
   public static function getIssueGeneralInfo(Issue $issue, $isManager=false, $displaySupport=false) {
      $withSupport = true;  // include support in elapsed & Drift

      $drift = $issue->getDrift($withSupport);
      $issueGeneralInfo = array(
         "issueId" => $issue->getId(),
         "issueSummary" => htmlspecialchars($issue->getSummary()),
         "issueType" => $issue->getType(),
         "issueDescription" => htmlspecialchars($issue->getDescription()),
         "projectName" => $issue->getProjectName(),
         "categoryName" => $issue->getCategoryName(),
         "issueExtRef" => $issue->getTcId(),
         'mantisURL'=> Tools::mantisIssueURL($issue->getId(), NULL, true),
         'issueURL' => Tools::mantisIssueURL($issue->getId()),
         'statusName'=> $issue->getCurrentStatusName(),
         'priorityName'=> $issue->getPriorityName(),
         'severityName'=> $issue->getSeverityName(),
         'handlerName'=> UserCache::getInstance()->getUser($issue->getHandlerId())->getName(),

         "issueEffortTitle" => $issue->getEffortEstim().' + '.$issue->getEffortAdd(),
         "issueEffort" => $issue->getEffortEstim() + $issue->getEffortAdd(),
         "issueReestimated" => $issue->getReestimated(),
         "issueBacklog" => $issue->getBacklog(),
         "issueDriftColor" => $issue->getDriftColor($drift),
         "issueDrift" => round($drift, 2),
         "progress" => round(100 * $issue->getProgress()),
      	 "relationTab" => IssueInfoTools::buildTab($issue->getRelationships(NULL), $issue->getRelationshipsType(NULL), IssueInfoTools::getIssueStatus($issue), IssueInfoTools::getIssueStatus($issue), IssueInfoTools::getIssueProgress($issue))
      	);
      if($isManager) {
         $issueGeneralInfo['issueMgrEffortEstim'] = $issue->getMgrEffortEstim();
         $driftMgr = $issue->getDriftMgr($withSupport);
         $issueGeneralInfo['issueDriftMgrColor'] = $issue->getDriftColor($driftMgr);
         $issueGeneralInfo['issueDriftMgr'] = round($driftMgr, 2);
      }
      if ($withSupport) {
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed();
      } else {
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed() - $issue->getElapsed($job_support);
      }
      if ($displaySupport) {
         if ($isManager) {
            $driftMgr = $issue->getDriftMgr(!$withSupport);
            $issueGeneralInfo['issueDriftMgrSupportColor'] = $issue->getDriftColor($driftMgr);
            $issueGeneralInfo['issueDriftMgrSupport'] = round($driftMgr, 2);
         }
         $drift = $issue->getDrift(!$withSupport);
         $issueGeneralInfo['issueDriftSupportColor'] = $issue->getDriftColor($drift);
         $issueGeneralInfo['issueDriftSupport'] = round($drift, 2);
      }

      return $issueGeneralInfo;
   }

   /**
    * Get Status of Issue
    * @static
    */
   public static function getIssueStatus(Issue $issue) {
   	$statusid = array();
   	foreach ($issue->getIssues($issue->getRelationships(NULL)) as $key) {
   		$statusid[] = $key->getCurrentStatusName();
   	}
   	return $statusid;
   }
   
   /**
    * Get Progress of Issue
    * @static
    */
   public static function getIssueProgress(Issue $issue) {
   	$progressid = array();
   	foreach ($issue->getIssues($issue->getRelationships(NULL)) as $key) {
   		$progressid[] = round(100 * $key->getProgress()) . "%";
   	}
   	return $progressid;
   }
   
   /**
    * Construction of array for Relationships
    * @static
    * arrayempty : workaround for bug on display
    */
   
   public static function buildTab ($array1,$array2,$arrayempty,$array3,$array4) {
   
   	$n = count($array1); //size of tab
   
   	$tab = array();
   	for($i=0; $i<$n; $i++)
   	{
   	$tab[$i] = array( $array1[$i], $array2[$i], "empty",$array3[$i], $array4[$i]);
   	}
   	return $tab;
   }
   
}

// Initialize complex static variables
IssueInfoTools::staticInit();

?>

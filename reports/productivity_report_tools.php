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

include_once "project.class.php";
include_once "time_tracking.class.php";

/**
 * @param TimeTracking $timeTracking
 * @param int $projectId
 * @return array
 */
function getProjectDetails(TimeTracking $timeTracking, $projectId) {
   $durationPerCategory = array();
   $formatedBugsPerCategory = array();

   $durPerCat = $timeTracking->getProjectDetails($projectId);
   foreach ($durPerCat as $catName => $bugList) {
      foreach ($bugList as $bugid => $duration) {
         $durationPerCategory[$catName] += $duration;

         if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
      }
   }

   return getProjectDetail($durationPerCategory, $formatedBugsPerCategory);
}

/**
 * @param TimeTracking $timeTracking
 * @return array
 */
function getSideTasksProjectDetails(TimeTracking $timeTracking) {
   global $logger;

   $sideTaskProjectType = Project::type_sideTaskProject;

   // find all sideTasksProjects (type = 1)
   $query = "SELECT project_id ".
      "FROM `codev_team_project_table` ".
      "WHERE team_id = $timeTracking->team_id ".
      "AND type = $sideTaskProjectType";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return;
   }

   $durationPerCategory = array();
   $formatedBugsPerCategory = array();

   while($row = mysql_fetch_object($result)) {
      $durPerCat = $timeTracking->getProjectDetails($row->project_id);
      foreach ($durPerCat as $catName => $bugList) {
         foreach ($bugList as $bugid => $duration) {
            $durationPerCategory[$catName] += $duration;

            if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
         }
      }
   }

   return getProjectDetail($durationPerCategory, $formatedBugsPerCategory);
}
function getProjectDetail($durationPerCategory, $formatedBugsPerCategory) {
   $projectDetails = NULL;
   foreach ($durationPerCategory as $catName => $duration) {
      $projectDetails[] = array(
         'catName' => $catName,
         'duration' => $duration,
         'formatedBugsPerCategory' => $formatedBugsPerCategory[$catName]
      );
   }

   return $projectDetails;
}

/**
 * @param array $projectDetails
 * @return string
 */
function getProjectDetailsUrl(array $projectDetails) {
   $formatedValues = NULL;
   $formatedLegends = NULL;
   foreach ($projectDetails as $catName => $projectDetail) {
      if (0 != $projectDetail['duration']) {
         if (NULL != $formatedValues) {
            $formatedValues .= ":"; $formatedLegends .= ":";
         }
         $formatedValues .= $projectDetail['duration'];
         $formatedLegends .= $projectDetail['catName'];
      }
   }

   if (NULL != $formatedValues) {
      return SmartUrlEncode("legends=$formatedLegends&values=$formatedValues");
   }
}

?>

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

/**
 *
 * @param int $value
 * @return int $value
 * @throws Exception
 */
function checkNumericValue($value, $allowNull = false) {

   global $logger;
   
   if ((NULL == $value) && (true == $allowNull)) { return NULL; }

   $formattedValue = mysql_real_escape_string($value);
   if (!is_numeric($formattedValue)) {
      echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
      $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($value)");
      $logger->fatal("EXCEPTION: ".$e->getMessage());
      $logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      throw $e;
   }
   return $formattedValue;
}


/**
 * Convert a teamList in a Smarty comprehensible array
 * @param array $teamList The teams
 * @param int $selectedTeamId The selected team
 * @return array
 */
function getTeams($teamList, $selectedTeamId = NULL) {
   $teams = NULL;
   foreach ($teamList as $tid => $tname) {
       $teams[] = array('id' => $tid,
                        'name' => $tname,
                        'selected' => ($tid == $selectedTeamId)
       );
   }
   return $teams;
}

/**
 * Convert a projectList in a Smarty comprehensible array
 * @param array $projectList The projects
 * @param int $selectedProjectId The selected project
 * @return array The projects
 */
function getProjects($projectList, $selectedProjectId = NULL) {
    $projects = NULL;
    foreach ($projectList as $pid => $pname) {
        $projects[] = array('id' => $pid,
                            'name' => $pname,
                            'selected' => $pid == $selectedProjectId
        );
    }
    return $projects;
}

/**
 * @param int $defaultBugid
 * @param int $defaultProjectid
 * @return array
 */
function getBugs($projectid = 0, $defaultBugid = 0, $projList) {
   global $logger;

   // Task list
   if (0 != $projectid) {
      $project1 = ProjectCache::getInstance()->getProject($projectid);
      $issueList = $project1->getIssueList();
   } else {
      // no project specified: show all tasks
      $issueList = array();
      $formatedProjList = implode( ', ', array_keys($projList));

      $query  = "SELECT id ".
         "FROM `mantis_bug_table` ".
         "WHERE project_id IN ($formatedProjList) ".
         "ORDER BY id DESC";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 != mysql_num_rows($result)) {
         while($row = mysql_fetch_object($result)) {
            $issueList[] = $row->id;
         }
      }
   }

   $bugs = NULL;
   foreach ($issueList as $bugid) {
      $issue = new Issue ($bugid);
      $externalId = "";
      if(!empty($issue->tcId)) {
         $externalId = ' / '.$issue->tcId;
      }
      $summary = "";
      if(!empty($issue->summary)) {
         $summary = ' : '.$issue->summary;
      }
      $bugs[$bugid] = array('id' => $bugid,
         'name' => $bugid.$externalId.$summary,
         'selected' => $bugid == $defaultBugid,
         'projectid' => $issue->projectId
      );
   }

   return $bugs;
}

/**
 * Get the list of weeks of a specific year in Smarty comprehensible array
 * @param int $weekid The selected week
 * @param int $year The specific year
 * @return array The result
 */
function getWeeks($weekid, $year) {
   for ($i = 1; $i <= 53; $i++) {
      $wDates = week_dates($i,$year);
      $monday = strftime(T_('W').'%U | %d %b', strtotime("Monday",$wDates[1]));
      $friday = strftime("%d %b", strtotime("Friday",$wDates[1]));
      $weeks[] = array('id' => $i,
                       'value' => utf8_encode(ucwords($monday)." - ".ucwords($friday)),
                       'selected' => $i == $weekid);
   }

   return $weeks;
}

/**
 * Get the list of years in [year-offset;year+offset] in Smarty comprehensible array
 * @param int $year The actual year
 * @param int $offset The offset
 * @return array The years
 */
function getYears($year,$offset = 1) {
   $years = array();
   for ($y = ($year-$offset); $y <= ($year+$offset); $y++) {
      $years[] = array('id' => $y,
                       'selected' => $y == $year);
   }
   return $years;
}

/**
 * Get the list of years in [startYear;now] in Smarty comprehensible array
 * @param int $startYear The start year
 * @param int $curYear The actual year
 * @return array The years
 */
function getYearsToNow($startYear, $curYear) {
   for ($y = $startYear; $y <= date('Y'); $y++) {
      $years[] = array('id' => $y,
         'selected' => $y == $curYear);
   }
   return $years;
}

/**
 * Get detailed mgr
 * @param array $issueSelection
 * @return array
 */
function getIssueSelectionDetailedMgr(IssueSelection $issueSelection) {

   //$formatedList  = implode( ',', array_keys($issueSelection->getIssueList()));

   $valuesMgr = $issueSelection->getDriftMgr();

   $driftMgrColor = IssueSelection::getDriftColor($valuesMgr['percent']);
   $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

   $selectionDetailedMgr = array('name' => $issueSelection->name,
      //'progress' => round(100 * $pv->getProgress()),
      'effortEstim' => $issueSelection->mgrEffortEstim,
      'reestimated' => $issueSelection->getReestimatedMgr(),
      'elapsed' => $issueSelection->elapsed,
      'remaining' => $issueSelection->durationMgr,
      'driftColor' => $formatteddriftMgrColor,
      'drift' => round($valuesMgr['nbDays'],2),
      'progress' => round(100 * $issueSelection->getProgressMgr(),2),

   );
   return $selectionDetailedMgr;
}


/**
 * get issues attributes
 *
 * @param IssueSelection $issueSelection
 * @return array
 */
function getIssueListInfo(IssueSelection $issueSelection) {

   $issueArray = array();

   $issues = $issueSelection->getIssueList();
   foreach ($issues as $id => $issue) {

      $driftMgr = $issue->getDriftMgr();
      $driftMgrColor = $issue->getDriftColor($driftMgr);
      $formattedDriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $issueInfo = array();
      $issueInfo["mantisLink"] = mantisIssueURL($issue->bugId, NULL, true);
      $issueInfo["bugid"] = issueInfoURL(sprintf("%07d\n",   $issue->bugId));
      $issueInfo["project"] = $issue->getProjectName();
      $issueInfo["target"] = $issue->getTargetVersion();
      $issueInfo["status"] = $issue->getCurrentStatusName();
      $issueInfo["progress"] = round(100 * $issue->getProgress());
      $issueInfo["effortEstim"] = $issue->mgrEffortEstim;
      $issueInfo["elapsed"] = $issue->elapsed;
      $issueInfo["driftMgr"] = $driftMgr;
      $issueInfo["driftMgrColor"] = $formattedDriftMgrColor;
      $issueInfo["durationMgr"] = $issue->getDurationMgr();
      $issueInfo["summary"] = $issue->summary;
      $issueInfo["category"] = $issue->getCategoryName();

      $issueArray[$id] = $issueInfo;
   }
   return $issueArray;
}


?>

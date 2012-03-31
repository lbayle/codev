<?php
if (!isset($_SESSION)) {
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 

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

include_once '../path.inc.php';

# WARN: this avoids the display of some PHP errors...
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

date_default_timezone_set("Europe/Paris");

# WARN: order of these includes is important.
require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("prod_report_tools");
   $logger->info("LOG activated !");
}

include_once "tools.php";
include_once "mysql_connect.inc.php";
include_once "internal_config.inc.php";
include_once "constants.php";

include_once 'i18n.inc.php';

include_once "project.class.php";
include_once "time_tracking.class.php";

/**
 * 
 * @param TimeTracking $timeTracking
 * @param int $projectId
 */
function displayProjectDetails($timeTracking, $projectId) {
    $durationPerCategory = array();
    $formatedBugsPerCategory = array();

    $durPerCat = $timeTracking->getProjectDetails($projectId);

  foreach ($durPerCat as $catName => $bugList)
  {
      foreach ($bugList as $bugid => $duration) {
         $durationPerCategory[$catName] += $duration;

         if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
      }
  }

  $proj = ProjectCache::getInstance()->getProject($projectId);

  echo "<div class=\"float\">\n";
  echo "<table width='300'>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";

    if (0 != $duration) {
       if (NULL != $formatedValues) {
          $formatedValues .= ":"; $formatedLegends .= ":";
       }
       $formatedValues .= $duration;
       $formatedLegends .= $catName;
    }

  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     $title = $proj->name." ".T_("Categories");
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

// ---------------------------------------------------
/**
 * 
 * @param TimeTracking $timeTracking
 */
function displaySideTasksProjectDetails($timeTracking) {

  $sideTaskProjectType = Project::type_sideTaskProject;

  $durationPerCategory = array();
  $formatedBugsPerCategory = array();
  $stProjList = "";

  // find all sideTasksProjects (type = 1)
  $query     = "SELECT project_id ".
               "FROM `codev_team_project_table` ".
               "WHERE team_id = $timeTracking->team_id ".
               "AND type = $sideTaskProjectType";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

  while($row = mysql_fetch_object($result))
  {
     $durPerCat = $timeTracking->getProjectDetails($row->project_id);
     foreach ($durPerCat as $catName => $bugList)
     {
     	   foreach ($bugList as $bugid => $duration) {
     	   	$durationPerCategory[$catName] += $duration;

     	   	if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
     	   	$issue = IssueCache::getInstance()->getIssue($bugid);
            $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
     	   }
     }

     $proj = ProjectCache::getInstance()->getProject($row->project_id);
     $stProjList[] = $proj->name;

  }
  $formatedProjList = implode( ', ', $stProjList);

  $formatedBugList = "";

  echo "<div class=\"float\">\n";
  echo "<table width='300'>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";

      if (0 != $duration) {
       if (NULL != $formatedValues) {
          $formatedValues .= ":"; $formatedLegends .= ":";
       }
       $formatedValues .= $duration;
       $formatedLegends .= $catName;
    }
  }
  echo "</table>\n";
  echo "</div>\n";

  if (NULL != $formatedValues) {
     echo "<div class=\"float\">\n";
     $graphURL = getServerRootURL()."/graphs/pie_graph.php?size=500:150&legends=$formatedLegends&values=$formatedValues";
     $graphURL = SmartUrlEncode($graphURL);
     echo "<img src='$graphURL'/>";
     echo "</div>\n";
  }
}

# ========== MAIN ==============

if(isset($_GET['action'])) {
	if($_GET['action'] == 'displayProjectDetails') {
		$projectid  = $_GET['projectId'];

		$year = date('Y');
		$weekDates      = week_dates(date('W'),$year);
		$date1  = isset($_GET["date1"]) ? $_GET["date1"] : date("Y-m-d", $weekDates[1]);
		$date2  = isset($_GET["date2"]) ? $_GET["date2"] : date("Y-m-d", $weekDates[5]);
		$startTimestamp = date2timestamp($date1);
		$endTimestamp = date2timestamp($date2);

		$endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

		$timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $_GET['teamid']);

		if (isset($projectid) && 0 != $projectid) {
			displayProjectDetails($timeTracking, $projectid);
		} else {
			// all sideTasks
			displaySideTasksProjectDetails($timeTracking);
		}
	}
}


?>

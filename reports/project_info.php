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

include_once 'i18n.inc.php';

$page_name = T_("Project Info");
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
?>

<script language="JavaScript">

  function submitForm() {
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "displayProject";
    document.forms["form1"].submit();
  }

   // ------ JQUERY ------
  $(function() {

     $( "#tabsVersions" ).tabs();
     $( "#tabsDrift" ).tabs();

  });

</script>


<?php

include_once "issue.class.php";
include_once "project.class.php";
include_once "time_track.class.php";
include_once "user.class.php";
include_once "jobs.class.php";
include_once "holidays.class.php";

include_once "issue.class.php";

$logger = Logger::getLogger("project_info");

// ---------------------------------------------------------------

function displayProjectSelectionForm($originPage, $projList, $defaultProjectid = 0) {

   global $logger;

   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   // Project list
   echo "Project ";
   echo "<select id='projectidSelector' name='projectidSelector' title='".T_("Project")."'>\n";
   echo "<option value='0'></option>\n";
   foreach ($projList as $pid => $pname)
   {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=button value='".T_("Jump")."' onClick='javascript: submitForm()'>\n";

   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";

   echo "</div>";
}


// ------------------------------------------------
function displayProjectVersions($project, $isManager = false) {

   echo "<h3>".T_("Project Versions")."</h3>";
   echo "<div id='tabsVersions'>\n";
   echo "<ul>\n";
   echo "<li><a href='#tab1'>".T_("Overview")."</a></li>\n";
   if ($isManager) {
      echo "<li><a href='#tab2'>".T_("Detailed Mgr")."</a></li>\n";
   }
   echo "<li><a href='#tab3'>".T_("Detailed")."</a></li>\n";
   echo "<li><a href='#tab4'>".T_("Tasks")."</a></li>\n";
   echo "</ul>\n";
   echo "<div id='tab1'>\n";
   echo "<p>";
   displayVersionsOverview($project);
   echo "</p>\n";
   echo "</div>\n";

   if ($isManager) {
      echo "<div id='tab2'>\n";
      echo "<p>";
      displayVersionsDetailedMgr($project);
      echo "</p>\n";
      echo "</div>\n";
   }
   echo "<div id='tab3'>\n";
   echo "<p>";
   displayVersionsDetailed($project);
   echo "</p>\n";
   echo "</div>\n";
   echo "<div id='tab4'>\n";
   echo "<p>";
   displayVersionsIssues($project);
   echo "</p>\n";
   echo "</div>\n";
   echo "</div>\n";

}

// -----------------------------------------
function displayVersionsOverview($project) {
   $projectVersionList = $project->getVersionList();

   echo "<table>\n";

   echo "<tr>\n";
   echo "  <th>".T_("Target version")."</th>\n";
   echo "  <th>".T_("Date")."</th>\n";
   echo "  <th>".T_("Progress Mgr")."</th>\n";
   echo "  <th>".T_("Progress")."</th>\n";
   echo "  <th width='80'>".T_("Drift Mgr")."</th>\n";
   echo "  <th width='80'>".T_("Drift")."</th>\n";
   echo "</tr>\n";

   foreach ($projectVersionList as $version => $pv) {
	   echo "<tr>\n";

       $valuesMgr = $pv->getDriftMgr();
       $formattedDriftMgr = "<span title='".T_("percent")."' class='float'>".round(100 * $valuesMgr['percent'])."%</span>";

	   $driftMgrColor = $pv->getDriftColor($valuesMgr['percent']);
       $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

       $values = $pv->getDrift();
       $formattedDrift    = "<span title='".T_("percent")."' class='float'>".round(100 * $values['percent'])."%</span>";
       $driftColor = $pv->getDriftColor($values['percent']);
       $formatteddriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";

       echo "<td>".$pv->name."</td>\n";
       $vdate =  $pv->getVersionDate();
       if (is_numeric($vdate)) {
          echo "<td>".date("Y-m-d",$vdate)."</td>\n";
       } else {
       	  echo "<td></td>\n";
       }
       echo "<td>".round(100 * $pv->getProgressMgr())."%</td>\n";
       echo "<td>".round(100 * $pv->getProgress())."%</td>\n";
       echo "<td $formatteddriftMgrColor >$formattedDriftMgr</td>\n";
       echo "<td $formatteddriftColor >$formattedDrift</td>\n";
	   echo "</tr>\n";
   }

   $driftMgr = $project->getDriftMgr();
   $driftMgrColor = $pv->getDriftColor($driftMgr['percent']);
   $formattedDriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

   $drift = $project->getDrift();
   $driftColor = $pv->getDriftColor($drift['percent']);
   $formattedDriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";


   echo "<tr class ='row_even'>\n";
   echo "<td>".T_("Total")."</td>\n";
   echo "<td></td>\n";
   echo "<td>".round(100 * $project->getProgressMgr())."%</td>\n";
   echo "<td>".round(100 * $project->getProgress())."%</td>\n";
   echo "<td $formattedDriftMgrColor>".round(100 * $driftMgr['percent'])."%</td>\n";
   echo "<td $formattedDriftColor>".round(100 * $drift['percent'])."%</td>\n";
   echo "</tr>\n";

   echo "</table>\n";
}

// -----------------------------------------
function displayVersionsDetailed($project) {

   global $status_new;

   $projectVersionList = $project->getVersionList();

   $totalDrift = 0;

   echo "<table>\n";

   echo "<tr>\n";
   echo "  <th>".T_("Target version")."</th>\n";
   echo "  <th>".T_("EffortEstim")."</th>\n";
   echo "  <th>".T_("Reestimated")."</th>\n";
   echo "  <th>".T_("Elapsed")."</th>\n";
   echo "  <th>".T_("Remaining")."</th>\n";
   echo "  <th width='80'>".T_("Drift")."</th>\n";
   echo "</tr>\n";

   foreach ($projectVersionList as $version => $pv) {
	   echo "<tr>\n";
	   $totalEffortEstim += $pv->effortEstim + $pv->effortAdd;
	   $totalElapsed += $pv->elapsed;
	   $totalRemaining += $pv->remaining;
	   $formatedList  = implode( ',', array_keys($pv->getIssueList()));


       $values = $pv->getDrift();
       $totalDrift += $values['nbDays'];
       $formattedDrift    = "<span title='".T_("nb days")."'>".$values['nbDays']."</span>";
       $driftColor = $pv->getDriftColor($values['percent']);
       $formatteddriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";

       echo "<td>".$pv->name."</td>\n";
	   #echo "<td>".round(100 * $pv->getProgress())."%</td>\n";
       echo "<td title='$pv->effortEstim + $pv->effortAdd'>".($pv->effortEstim + $pv->effortAdd)."</td>\n";
	   echo "<td>".($pv->remaining + $pv->elapsed)."</td>\n";
       echo "<td>".$pv->elapsed."</td>\n";
	   echo "<td>".$pv->remaining."</td>\n";
	   echo "<td $formatteddriftColor >$formattedDrift</td>\n";
	   echo "</tr>\n";
   }

   $formattedDrift    = "<span title='".T_("nb days")."'>".$totalDrift."</span>";


   echo "<tr class ='row_even'>\n";
   echo "<td>".T_("Total")."</td>\n";
   echo "<td>$totalEffortEstim</td>\n";
	echo "<td>".($totalRemaining + $totalElapsed)."</td>\n";
   echo "<td>".$totalElapsed."</td>\n";
   echo "<td>".$totalRemaining."</td>\n";
   echo "<td>$formattedDrift</td>\n";
   echo "</tr>\n";

   echo "</table>\n";
}

// -----------------------------------------
function displayVersionsDetailedMgr($project) {

	global $status_new;

	$projectVersionList = $project->getVersionList();

	echo "<table>\n";

	echo "<tr>\n";
	echo "  <th>".T_("Target version")."</th>\n";
	echo "  <th>".T_("MgrEffortEstim")."</th>\n";
    echo "  <th title='remainingMgr + elapsed'>".T_("Reestimated Mgr")."</th>\n";
	echo "  <th>".T_("Elapsed")."</th>\n";
	echo "  <th>".T_("Remaining Mgr")."</th>\n";
	echo "  <th width='80'>".T_("Drift Mgr")."</th>\n";
	echo "</tr>\n";

	foreach ($projectVersionList as $version => $pv) {
		echo "<tr>\n";
		$totalEffortEstimMgr += $pv->mgrEffortEstim;
		$totalElapsed += $pv->elapsed;
		$totalRemainingMgr += $pv->remainingMgr;
		$totalReestimatedMgr += $pv->remainingMgr;
		$formatedList  = implode( ',', array_keys($pv->getIssueList()));


		$valuesMgr = $pv->getDriftMgr();
        $totalDriftMgr += $valuesMgr['nbDays'];
		$formattedDriftMgr = "<span title='".T_("nb days")."'>".$valuesMgr['nbDays']."</span>";

		$driftMgrColor = $pv->getDriftColor($valuesMgr['percent']);
		$formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

		echo "<td>".$pv->name."</td>\n";
		#echo "<td>".round(100 * $pv->getProgress())."%</td>\n";
		echo "<td>".$pv->mgrEffortEstim."</td>\n";
		echo "<td>".($pv->remainingMgr + $pv->elapsed)."</td>\n";
		echo "<td>".$pv->elapsed."</td>\n";
		echo "<td>".$pv->remainingMgr."</td>\n";
		echo "<td $formatteddriftMgrColor >$formattedDriftMgr</td>\n";
		echo "</tr>\n";
    }


    $formattedDrift    = "<span title='".T_("nb days")."'>".$totalDriftMgr."</span>";

	echo "<tr class ='row_even'>\n";
	echo "<td>".T_("Total")."</td>\n";
	#echo "<td>".round(100 * $totalProgress)."%</td>\n";
	echo "<td>$totalEffortEstimMgr</td>\n";
	echo "<td>".($totalRemainingMgr + $totalElapsed)."</td>\n";
	echo "<td>".$totalElapsed."</td>\n";
	echo "<td>".$totalRemainingMgr."</td>\n";
	echo "<td>$formattedDrift</td>\n";
    echo "</tr>\n";

    echo "</table>\n";
}

// -----------------------------------------
function displayVersionsIssues($project) {

	global $status_new;

	$projectVersionList = $project->getVersionList();

	echo "<table>\n";

	echo "<tr>\n";
	echo "  <th>".T_("Target version")."</th>\n";
	echo "  <th>".T_("New Tasks")."</th>\n";
	echo "  <th>".T_("Current Tasks")."</th>\n";
	echo "  <th>".T_("Resolved Tasks")."</th>\n";
	echo "</tr>\n";

	foreach ($projectVersionList as $version => $pv) {
		echo "<tr>\n";
		$totalElapsed += $pv->elapsed;
		$totalRemaining += $pv->remaining;
		$formatedList  = implode( ',', array_keys($pv->getIssueList()));

		// format Issues list
		$formatedResolvedList = "";
		$formatedOpenList = "";
		$formatedNewList = "";
		foreach ($pv->getIssueList() as $bugid => $issue) {

			if ($status_new == $issue->currentStatus) {
				if ("" != $formatedNewList) {
					$formatedNewList .= ', ';
				}
				$formatedNewList .= issueInfoURL($bugid, $issue->summary);

			}elseif ($issue->currentStatus >= $issue->bug_resolved_status_threshold) {
				if ("" != $formatedResolvedList) {
					$formatedResolvedList .= ', ';
				}
				$title = "(".$issue->getDrift().") $issue->summary";
				$formatedResolvedList .= issueInfoURL($bugid, $title);
			} else {
				if ("" != $formatedOpenList) {
					$formatedOpenList .= ', ';
				}
				$title = "(".$issue->getDrift().", ".$issue->getCurrentStatusName().") $issue->summary";
				$formatedOpenList .= issueInfoURL($bugid, $title);
			}
		}

		echo "<td>".$pv->name."</td>\n";
		echo "<td>".$formatedNewList."</td>\n";
		echo "<td>".$formatedOpenList."</td>\n";
		echo "<td>".$formatedResolvedList."</td>\n";
		echo "</tr>\n";
   }

   // compute total progress
   if (0 == $totalRemaining) {
      $totalProgress = 1;  // if no Remaining, then Project is 100% done.
   } elseif (0 == $totalElapsed) {
      $totalProgress = 0;  // if no time spent, then no work done.
   } else {
      $totalProgress = $totalElapsed / ($totalElapsed + $totalRemaining);
   }

   echo "</table>\n";
}



// ------------------------------------------------
/**
 *
 * @param unknown_type $project
 * @param unknown_type $isManager
 */
function displayIssuesInDriftTab($project, $isManager = false) {

	echo "<h3>".T_("Tasks in drift")."</h3>";
	echo "<div id='tabsDrift'>\n";
	echo "<ul>\n";
	echo "<li><a href='#tab1'>".T_("Current tasks")."</a></li>\n";
	echo "<li><a href='#tab2'>".T_("Resolved tasks")."</a></li>\n";
	echo "</ul>\n";
	echo "<div id='tab1'>\n";
	echo "<p>";
	displayCurrentIssuesInDrift($project, $isManager);
	echo "</p>\n";
	echo "</div>\n";

	echo "<div id='tab2'>\n";
	echo "<p>";
	displayResolvedIssuesInDrift($project, $isManager);
	echo "</p>\n";
	echo "</div>\n";

	echo "</div>\n"; // tabs

}


// ---------------------------------------------------
/**
 * Display a table containing all "non-resolved" issues that are in drift
 * (ordered by version)
 *
 * @param Project $project
 * @param boolean $isManager
 * @param boolean $withSupport
 */
function displayCurrentIssuesInDrift($project, $isManager = false, $withSupport = true) {

	$bugidList = $project->getIssueList();

	$projectVersionList = $project->getVersionList();


	echo "<table>\n";
	#echo "<caption>".T_("Tasks in drift")."</caption>\n";
	echo "<tr>\n";
	echo "<th>".T_("ID")."</th>\n";
	echo "<th>".T_("Project")."</th>\n";
	echo "<th>".T_("Target")."</th>\n";

	if (true == $isManager) {
		echo "<th title='".T_("Drift relatively to the managers Estimation")."'>".T_("Drift Mgr")."</th>\n";
	}
	echo "<th title='".T_("Drift relatively to (EE + AddEE)")."'>".T_("Drift")."</th>\n";
	echo "<th>".T_("RAF")."</th>\n";
	echo "<th>".T_("Progress")."</th>\n";
	echo "<th>".T_("Status")."</th>\n";
	echo "<th>".T_("Summary")."</th>\n";
	echo "</tr>\n";

	foreach ($projectVersionList as $version => $pv) {
		foreach ($pv->getIssueList() as $bugid => $issue) {

			if ($issue->isResolved()) {
				// skip resolved issues
				continue;
			}

			$driftPrelEE = ($isManager) ? $issue->getDriftMgrEE($withSupport) : 0;
			$driftEE = $issue->getDrift($withSupport);

			if (($driftPrelEE > 0) || ($driftEE > 0)) {
				echo "<tr>\n";
				echo "<td>".issueInfoURL($issue->bugId)."</td>\n";
				echo "<td>".$issue->getProjectName()."</td>\n";
				echo "<td>".$issue->getTargetVersion()."</td>\n";
				if (true == $isManager) {
					$color = "";
					if ($driftPrelEE < -1) {
						$color = "style='background-color: #61ed66;'";
					}
					if ($driftPrelEE > 1) {
						$color = "style='background-color: #fcbdbd;'";
					}
					echo "<td $color >".$driftPrelEE."</td>\n";
			}
			$color = "";
			if ($driftEE < -1) {
			$color = "style='background-color: #61ed66;'";
			}
			if ($driftEE > 1) {
			$color = "style='background-color: #fcbdbd;'";
			}
			echo "<td $color >".$driftEE."</td>\n";
			echo "<td>".$issue->remaining."</td>\n";
			echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
			echo "<td>".$issue->getCurrentStatusName()."</td>\n";
			echo "<td>".$issue->summary."</td>\n";
			echo "</tr>\n";
		}
	}

}
echo "</table>\n";

}

// ---------------------------------------------------
/**
 * Display a table containing all resolved issues that are in drift
 * (ordered by version)
 *
 * @param Project $project
 * @param boolean $isManager
 * @param boolean $withSupport
 */
function displayResolvedIssuesInDrift($project, $isManager = false, $withSupport = true) {

	$bugidList = $project->getIssueList();

	$projectVersionList = $project->getVersionList();


	echo "<table>\n";
	#echo "<caption>".T_("Tasks in drift")."</caption>\n";
	echo "<tr>\n";
	echo "<th>".T_("ID")."</th>\n";
	echo "<th>".T_("Project")."</th>\n";
	echo "<th>".T_("Target")."</th>\n";

	if (true == $isManager) {
		echo "<th title='".T_("Drift relatively to the managers Estimation")."'>".T_("Drift Mgr")."</th>\n";
	}
	echo "<th title='".T_("Drift relatively to (EE + AddEE)")."'>".T_("Drift")."</th>\n";
	echo "<th>".T_("RAF")."</th>\n";
	echo "<th>".T_("Progress")."</th>\n";
	echo "<th>".T_("Status")."</th>\n";
	echo "<th>".T_("Summary")."</th>\n";
	echo "</tr>\n";

	foreach ($projectVersionList as $version => $pv) {
		foreach ($pv->getIssueList() as $bugid => $issue) {

			if (!$issue->isResolved()) {
				// skip non-resolved issues
				continue;
			}

			$driftPrelEE = ($isManager) ? $issue->getDriftMgrEE($withSupport) : 0;
			$driftEE = $issue->getDrift($withSupport);

			if (($driftPrelEE > 0) || ($driftEE > 0)) {
				echo "<tr>\n";
				echo "<td>".issueInfoURL($issue->bugId)."</td>\n";
				echo "<td>".$issue->getProjectName()."</td>\n";
				echo "<td>".$issue->getTargetVersion()."</td>\n";
				if (true == $isManager) {
					$color = "";
					if ($driftPrelEE < -1) {
						$color = "style='background-color: #61ed66;'";
					}
					if ($driftPrelEE > 1) {
						$color = "style='background-color: #fcbdbd;'";
					}
					echo "<td $color >".$driftPrelEE."</td>\n";
			}
					$color = "";
					if ($driftEE < -1) {
					$color = "style='background-color: #61ed66;'";
					}
					if ($driftEE > 1) {
					$color = "style='background-color: #fcbdbd;'";
			}
			echo "<td $color >".$driftEE."</td>\n";
			echo "<td>".$issue->remaining."</td>\n";
			echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
			echo "<td>".$issue->getCurrentStatusName()."</td>\n";
			echo "<td>".$issue->summary."</td>\n";
echo "</tr>\n";
}
}

}
echo "</table>\n";

}


// ================ MAIN =================
$year = date('Y');

$originPage = "project_info.php";

$action           = isset($_POST['action']) ? $_POST['action'] : '';
$session_userid   = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];
$version          = isset($_POST['version']) ? $_POST['version'] : 0;

$defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
$projectid        = isset($_POST['projectid']) ? $_POST['projectid'] : $defaultProject;
$_SESSION['projectid'] = $projectid;

$user = UserCache::getInstance()->getUser($session_userid);

$isManager = true; // TODO

$dTeamList = $user->getDevTeamList();
$lTeamList = $user->getLeadedTeamList();
$oTeamList = $user->getObservedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $oTeamList + $managedTeamList;


// --- define the list of tasks the user can display
// All projects from teams where I'm a Developper or Manager AND Observers
$devProjList     = $user->getProjectList();
$managedProjList = (0 == count($managedTeamList)) ? array() : $user->getProjectList($managedTeamList);
$observedProjList = (0 == count($oTeamList)) ? array() : $user->getProjectList($oTeamList);
$projList = $devProjList + $managedProjList + $observedProjList;


// if bugid is set in the URL, display directly
 if (isset($_GET['projectid'])) {
    $projectid = $_GET['projectid'];

   // user may not have the rights to see this project (observers, ...)
    if (in_array($projectid, $projList)) {
      $action = "displayProject";
   } else {
     $action = "notAllowed";
   }
 }

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
    echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {

    displayProjectSelectionForm($originPage, $projList, $projectid);

    if ("displayProject" == $action) {

       $project = ProjectCache::getInstance()->getProject($projectid);

      echo "<br/>";
      echo "<br/>";
      echo "<br/>";
      echo "<br/>";

      // show progress
      displayProjectVersions($project, $isManager);

      echo "<br/>";
      echo "<br/>";
      echo "<br/>";

      displayIssuesInDriftTab($project, $isManager);

      echo "<br/>";
      echo "<br/>";

    } elseif ("setProjectid" == $action) {

       // pre-set form fields
       $projectid  = $_POST['projectid'];

    } elseif ("notAllowed" == $action) {
      echo "<br/>";
      echo "<br/>";
      echo "<br/>";
      echo "<br/>";
      echo T_("Sorry, you are not allowed to view the details of this project")."<br/>";
  }


}
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";

// log stats
IssueCache::getInstance()->logStats();
ProjectCache::getInstance()->logStats();
UserCache::getInstance()->logStats();
TimeTrackCache::getInstance()->logStats();

?>

</div>

<?php include 'footer.inc.php'; ?>











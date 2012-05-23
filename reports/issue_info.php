<?php
include_once('../include/session.inc.php');

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

$page_name = T_("Activity by task");
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
?>

<script language="JavaScript">
  function submitForm() {
    document.forms["form1"].bugid.value = document.getElementById('bugidSelector').value;
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "displayBug";
    document.forms["form1"].submit();
  }

  function setProjectid() {
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value="setProjectid";
     document.forms["form1"].submit();
  }

   // ------ JQUERY ------
	$(function() {

		var remaining = $( "#remaining" ),
			 allFields = $( [] ).add( remaining ),
			 tips = $( "#validateTips" );

		function updateTips( t ) {
			tips
				.text( t )
				.addClass( "ui-state-highlight" );
			setTimeout(function() {
				tips.removeClass( "ui-state-highlight", 1500 );
			}, 500 );
		}

		function checkRegexp( o, regexp, n ) {
			if ( !( regexp.test( o.val() ) ) ) {
				o.addClass( "ui-state-error" );
				updateTips( n );
				return false;
			} else {
				return true;
			}
		}

		$( "#update_remaining_dialog_form" ).dialog({
			autoOpen: false,
			height: 200,
			width: 500,
			modal: true,
			open: function() {
               // Select input field contents
               $( "#remaining" ).select();
			},
			buttons: {
				"Update": function() {
					var bValid = true;
					allFields.removeClass( "ui-state-error" );
					bValid = bValid && checkRegexp( remaining, /^[0-9]+(\.[0-9]5?)?$/i, "format: '1','0.3' or '1.55'" );

					if ( bValid ) {
						// TODO use AJAX to call php func and update remaining on bugid
						$('#formUpdateRemaining').submit();
					}
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
			close: function() {
				allFields.val( "" ).removeClass( "ui-state-error" );
			}
		});

	});

</script>

<div id="content">

<div id="update_remaining_dialog_form" title="Task XXX - Update Remaining" style='display: none'>
	<p id="validateTips">Set new value</p>
	<form id='formUpdateRemaining' name='formUpdateRemaining' method='post' Action='issue_info.php' >
	   <fieldset>
		   <label for="remaining">Remaining: </label>
		   <input type='text'  id='remaining' name='remaining' size='3' class='text' />
	   </fieldset>
      <input type='hidden' name='bugid'  value='0' >
      <input type='hidden' name='action' value='updateRemainingAction' >
	</form>
</div>

<?php

include_once "issue.class.php";
include_once "project.class.php";
include_once "time_track.class.php";
include_once "user.class.php";
include_once "jobs.class.php";
include_once "holidays.class.php";

include_once "issue_fdj.class.php";

$logger = Logger::getLogger("issue_info");

// ---------------------------------------------------------------

function displayIssueSelectionForm($originPage, $user1, $projList, $defaultBugid, $defaultProjectid) {

   global $logger;

   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' Action='$originPage'>\n";

   // Project list
   echo "&nbsp;";
   echo "&nbsp;";

   #echo "<table class='invisible'>\n";
   #echo "<tr>\n";
   #echo "<td>\n";

   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='".T_("Project")."'>\n";
   echo "<option value='0'>".T_("(all)")."</option>\n";
   foreach ($projList as $pid => $pname)
   {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
      }
   }
   echo "</select>\n";
   #echo "</td>\n";

   #echo "&nbsp;";

   // --- Task list
   if (0 != $defaultProjectid) {
      $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);
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
            while($row = mysql_fetch_object($result))
            {
               $issueList[] = $row->id;
            }
       }
   }
   #echo "<td>\n";
   echo "<select id='bugidSelector' name='bugidSelector' style='width: 600px;' title='".T_("Task")."'>\n";
   echo "<option value='0'></option>\n";

   foreach ($issueList as $bugid) {
         $issue = new Issue ($bugid);
      if ($bugid == $defaultBugid) {
         echo "<option selected value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
      } else {
         echo "<option value='".$bugid."'>".$bugid." / $issue->tcId : $issue->summary</option>\n";
      }
   }
   echo "</select>\n";
   #echo "</td>\n";

   #echo "<td>\n";
   echo "<input type=button value='".T_("Jump")."' onClick='javascript: submitForm()'>\n";
   #echo "</td>\n";
/*
   if (0 != $defaultBugid) {
      #echo "<td>\n";
   	echo "&nbsp;";
   	echo "".mantisIssueURL($defaultBugid, NULL, TRUE);
      #echo "</td>\n";
   }
*/
   #echo "</tr>\n";
   #echo "</table>\n";

   echo "<input type=hidden name=bugid  value=$defaultBugid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";

   echo "</div>";
}


// ---------------------------------------------------------------
/**
 *
 * @param unknown_type $issue
 * @param unknown_type $withSupport  if true: include support in Drift
 * @param unknown_type $displaySupport
 * @param unknown_type $isManager if true: show MgrEffortEstim column
 */
function displayIssueGeneralInfo($issue, $withSupport=true, $displaySupport=false, $isManager=false ) {

  echo "<div>\n";
  echo "<table>\n";
  echo "<tr>\n";
  echo "  <th>".T_("Indicator")."</th>\n";
  if ($isManager) {
     echo "  <th title='".T_("Manager Estimation")."'>".T_("Manager")."</th>\n";
  }
  echo "  <th>".T_("Value")."</th>\n";
  echo "  </tr>\n";

  echo "<tr>\n";
  echo "<td title='BI + BS'>".T_("Estimated effort")."</th>\n";
  # TODO display mgrEE only if teamManager
  if ($isManager) {
     echo "<td>".$issue->mgrEffortEstim."</td>\n";
  }
  echo "<td title='$issue->effortEstim + $issue->effortAdd'>".($issue->effortEstim + $issue->effortAdd)."</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Elapsed")."</td>\n";
  if ($isManager) {
     echo "<td></td>\n";
  }
  if ($withSupport) {
   echo "<td title='".T_("Support included")."'>".$issue->elapsed."</td>\n";
  } else {
   $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
   echo "<td title='".T_("Support NOT included")."'>".($issue->elapsed - $issue->getElapsed($job_support))."</td>\n";
  }
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Remaining")."</td>\n";
  if ($isManager) {
     echo "<td></td>\n";
  }
  echo "<td><a id='update_remaining_link' title='".T_("update remaining")."' href='#' >".$issue->remaining."</a></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Effort Deviation")."</td>\n";

  if ($isManager) {
     $deriveETA = $issue->getDriftMgr($withSupport);
     echo "<td style='background-color: #".$issue->getDriftColor($deriveETA).";'>".round($deriveETA, 2)."</td>\n";
  }
  $derive = $issue->getDrift($withSupport);
  echo "<td style='background-color: #".$issue->getDriftColor($derive).";'>".round($derive, 2)."</td>\n";
  echo "</tr>\n";

  if ($displaySupport) {
      echo "<tr>\n";
      if ($withSupport) {
         echo "<td>".T_("EffortDeviation -Support")."</td>\n";
      } else {
         echo "<td>".T_("EffortDeviation +Support")."</td>\n";
      }
      if ($isManager) {
         $deriveETA = $issue->getDriftMgr(!$withSupport);
         echo "<td style='background-color: #".$issue->getDriftColor($deriveETA).";'>".round($deriveETA,2)."</td>\n";
      }
      $derive = $issue->getDrift(!$withSupport);
      echo "<td style='background-color: #".$issue->getDriftColor($derive).";'>".round($derive,2)."</td>\n";
      echo "</tr>\n";
  }

  echo "<tr>\n";
  echo "<td>".T_("Progress")."</td>\n";
  if ($isManager) {
     echo "<td></td>\n";
  }
  echo "<td>".round(100 * $issue->getProgress())."%</td>\n";
  echo "</tr>\n";



  echo "</table>\n";

   // create links for JQUERY dialogBox
   echo "<script>\n";
   echo "$(function() {\n";
      	echo "$( '#update_remaining_link' ).click(function() {\n";
		echo "   $( '#formUpdateRemaining' ).children('input[name=bugid]').val(".$issue->bugId.");\n";
		echo "   $( '#remaining' ).val(".$issue->remaining.");\n";
		echo "   $( '#validateTips' ).text('".addslashes(htmlspecialchars($issue->summary))."');\n";
		echo "   $( '#update_remaining_dialog_form' ).dialog('option', 'title', 'Task ".$issue->bugId." / ".$issue->tcId." - Update Remaining');\n";
		echo "   $( '#update_remaining_dialog_form' ).dialog( 'open' );\n";
		echo "});\n";
    echo "});\n";
    echo "</script>\n";

  echo "</div>\n";

}

// ---------------------------------------------------------------
function displayTimeDrift($issue) {

  echo "<div>\n";
  echo "<table>\n";
  echo "<tr>\n";
  echo "  <th>".T_("Dates")."</th>\n";
  echo "  <th></th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "  <td>".T_("DeadLine")."</td>\n";
  if (NULL != $issue->getDeadLine()) {
      echo "  <td>".formatDate("%d %b %Y", $issue->getDeadLine())."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";

  echo "<tr>\n";
  echo "  <td>".T_("DeliveryDate")."</td>\n";
  if (NULL != $issue->deliveryDate) {
      echo "  <td>".formatDate("%d %b %Y", $issue->deliveryDate)."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";

  echo "<tr>\n";
  echo "  <td>".T_("Deviation")."</td>\n";
  $timeDrift=$issue->getTimeDrift();
  if (!is_string($timeDrift)) {
      echo "  <td style='background-color: #".$issue->getDriftColor($timeDrift).";'>".round($timeDrift)." ".T_("days")."</td>\n";
  } else {
      echo "  <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
  }
  echo "</tr>\n";
  echo "</table>\n";
  echo "</div>\n";

}

// ---------------------------------------------------------------
function displayJobDetails($issue) {

	$timeTracks = $issue->getTimeTracks();
   $durationByJob = array();
   $jobs = new Jobs();

   echo "<div>\n";
   echo "<table>\n";
   echo "<tr>\n";
   echo "<th>".T_("Job")."</th>\n";
   echo "<th>".T_("Nb Days")."</th>\n";
   echo "<th></th>\n";
   echo "</tr>\n";

   foreach ($timeTracks as $tid => $tdate) {
      $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
      $durationByJob[$tt->jobId] += $tt->duration;
      $totalDuration += $tt->duration;
   }

   #sort($durationByJob);
   foreach ($durationByJob as $jid => $duration) {
      echo "<tr>\n";
      echo "<td>";
      echo "<img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=".$jobs->getJobColor($jid)."'/>";
      echo " ".$jobs->getJobName($jid)."</td>\n";

      echo "<td>$duration</td>\n";
      echo "<td>".round(($duration*100 / $totalDuration))." %</td>\n";
      echo "</tr>\n";
   }
  echo "</table>\n";
  echo"</div>\n";
}


// ---------------------------------------------------------------
function displayMonth($month, $year, $issue) {

  $holidays = Holidays::getInstance();

  $jobs = new Jobs();
  $totalDuration = 0;

  // if no work done this month, do not display month
  $trackList = $issue->getTimeTracks();
  $found = 0;
  foreach ($trackList as $tid => $tdate) {
    if (($month == date('m', $tdate)) &&
        ($year  == date('Y', $tdate))) {
      $found += 1;

      $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
      $totalDuration += $tt->duration;
    }
  }
  if (0 == $found) { return; }

  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $monthFormated = formatDate("%B %Y", $monthTimestamp);
  $nbDaysInMonth = date("t", $monthTimestamp);

  echo "<div>\n";
  echo "<span class='caption_font'>$monthFormated</span> &nbsp;&nbsp; <span>($totalDuration ".T_("days").")</span><br/>\n";
  echo "<table width='70%'>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  for ($i = 1; $i <= $nbDaysInMonth; $i++) {
    if ($i < 10 ) {
      echo "<th>0$i</th>\n";
    }
    else {
      echo "<th>$i</th>\n";
    }
  }
  echo "</tr>\n";

  $userList = $issue->getInvolvedUsers();
  foreach ($userList as $uid => $username) {

    // build $durationByDate[] for this user
    $userTimeTracks = $issue->getTimeTracks($uid);
    $durationByDate = array();
    $jobColorByDate = array();
    foreach ($userTimeTracks as $tid => $tdate) {
      $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
    	$durationByDate[$tdate] += $tt->duration;
    	$jobColorByDate[$tdate] = $jobs->getJobColor($tt->jobId);
    }

   // ------
    echo "<tr>\n";
    echo "<td>$username</td>\n";

    for ($i = 1; $i <= $nbDaysInMonth; $i++) {
      $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);

      if (NULL != $durationByDate[$todayTimestamp]) {
        echo "<td style='background-color: #".$jobColorByDate[$todayTimestamp]."; text-align: center;'>".$durationByDate[$todayTimestamp]."</td>\n";
      } else {
        // if weekend or holiday, display gray
        $h = $holidays->isHoliday($todayTimestamp);
        if (NULL != $h) {
            echo "<td style='background-color: #".Holidays::$defaultColor.";' title='$h->description'></td>\n";
        } else {
            echo "<td></td>\n";
        }
      }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<br/><br/>\n";
}


  // ------------------------------------------
  // Table Repartition du temps par status
  function displayDurationsByStatus($issue) {
    global $statusNames;

    # WARN: use of FDJ custom
    //$issue = new IssueFDJ($issue_->bugId);

    $issue->computeDurations ();

    echo "<div class='float'>\n";

    echo "<table>\n";
    echo "<caption>".T_("Time allocation by status")."</caption>";
    echo "<tr>\n";
    foreach($issue->statusList as $status_id => $status) {
      echo "<th>".$statusNames[$status_id]."</th>\n";
    }
    echo "</tr>\n";

    // REM do not display SuiviOp tasks
    if (!$issue->isSideTaskIssue()) {
      echo "<tr>\n";
      foreach($issue->statusList as $status_id => $status) {
         $res = getDurationLiteral($status->duration);
         echo "<td>$res</td>\n";
      }
      echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
  }



// ================ MAIN =================
$year = date('Y');

// if 'support' is set in the URL, display graphs for 'with/without Support'
$displaySupport  = isset($_GET['support']) ? true: false;
$originPage = isset($_GET['support']) ? "issue_info.php?support" : "issue_info.php";

$withSupport = true;  // include support in elapsed & Drift

$action           = isset($_POST['action']) ? $_POST['action'] : '';
$session_userid   = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];
$bug_id           = isset($_POST['bugid'])  ? intval($_POST['bugid']) : 0;
$defaultProjectid = isset($_POST['projectid']) ? $_POST['projectid'] : 0;
$remaining        = isset($_POST['remaining']) ? $_POST['remaining'] : '';

$user = UserCache::getInstance()->getUser($session_userid);


$dTeamList = $user->getDevTeamList();
$lTeamList = $user->getLeadedTeamList();
$oTeamList = $user->getObservedTeamList();
$managedTeamList = $user->getManagedTeamList();
$teamList = $dTeamList + $lTeamList + $oTeamList + $managedTeamList;


// --- define the list of tasks the user can display
// All projects from teams where I'm a Developper or Manager AND Observer
$devProjList     = (0 == count($dTeamList)) ? array() : $user->getProjectList($dTeamList);
$managedProjList = (0 == count($managedTeamList)) ? array() : $user->getProjectList($managedTeamList);
$observedProjList = (0 == count($oTeamList)) ? array() : $user->getProjectList($oTeamList);
$projList = $devProjList + $managedProjList + $observedProjList;


// if bugid is set in the URL, display directly
 if (isset($_GET['bugid'])) {

    // '000' prefix breaks updateRemaining (happens when link to page is constructed by greasemonkey or manualy)
    $bug_id = intval($_GET['bugid']);

   // user may not have the rights to see this bug (observers, ...)
 	$taskList = $user->getPossibleWorkingTasksList($projList);
 	if (in_array($bug_id, $taskList)) {
      $action = "displayBug";
   } else {
     $action = "notAllowed";
   }
 }



if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
	echo T_("Sorry, you need to be member of a Team to access this page.");
   echo "</div>";

} else {


	displayIssueSelectionForm($originPage, $user, $projList, $bug_id, $defaultProjectid);

    if ("updateRemainingAction" == $action) {

      $issue = IssueCache::getInstance()->getIssue($bug_id);
      $issue->setRemaining($remaining);
	   $action = "displayBug";
	}

	if ("displayBug" == $action) {
      $issue = IssueCache::getInstance()->getIssue($bug_id);
	   $handler = UserCache::getInstance()->getUser($issue->handlerId);

	  echo "<br/><br/>\n";

     echo "<div id='content' class='center'>";
	  echo "<hr width='80%'/>\n";
     echo "<br/>";
     echo "<h2>$issue->summary</h2>\n";
     echo "".mantisIssueURL($issue->bugId)." / <span title='".T_("External ID")."'>$issue->tcId</span><br/>\n";
     echo "<br/>";
     echo "<b><span title='".T_("status")."'>".$issue->getCurrentStatusName()."</span> - <span title='".T_("assigned to")."'>".$handler->getName()."</span></b>\n";
     echo "</div>";

     echo "<br/>";
     echo "<br/>";
     echo "<br/>";
     echo "<br/>";
     echo "<br/>";
     echo "<br/>";

     // -------------
     echo"<div>\n";

     echo "<span style='display: inline-block;'>\n";

     $isManager = (array_key_exists($issue->projectId, $managedProjList)) ? true : false;
     displayIssueGeneralInfo($issue, $withSupport, $displaySupport, $isManager);
     echo "</span>";

     echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
     echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

     echo "<span style='display: inline-block;'>\n";
     displayJobDetails($issue);
     echo "</span>";

     echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
     echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

     echo "<span style='display: inline-block;'>\n";
     displayTimeDrift($issue);
     echo "</span>";

     echo"</div>\n";

     // -------------
	  echo"<div>\n";

     echo "<br/>";
	  echo "<br/>";
	  echo "<br/>";
     echo "<hr/>";
     echo "<br/>";

     for ($y = date('Y', $issue->dateSubmission); $y <= $year; $y++) {
         for ($m = 1; $m <= 12; $m++) {
            displayMonth($m, $y, $issue);
         }
	  }
     echo"</div>\n";

     echo"<div>\n";
     echo "<br/>";
     echo "<br/>";
	  echo "<br/>";
     echo "<br/>";
     echo "<hr/>";
     echo "<br/>";
     displayDurationsByStatus($issue);
     echo"</div>\n";

	} elseif ("setProjectid" == $action) {

       // pre-set form fields
       $defaultProjectid  = $_POST['projectid'];

	} elseif ("notAllowed" == $action) {
      echo "<br/>";
      echo "<br/>";
      echo "<br/>";
      echo "<br/>";
      echo T_("Sorry, you are not allowed to view the details of this task")." (".mantisIssueURL($bug_id).").<br/>";
  }


}
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";

?>

</div>

<?php include 'footer.inc.php'; ?>

<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>

<?php /*
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
*/ ?>

<?php
// === check if INSTALL needed
$constantsFile = "constants.php";
$mysqlConfigFile = "include/mysql_config.inc.php";
if ((!file_exists($constantsFile)) || (!file_exists($mysqlConfigFile))) {
    echo ("<script> parent.location.replace('./install/install.php'); </script>");
    exit;
}
?>

<?php
   include_once 'path.inc.php';
   include 'i18n.inc.php';
   $_POST['page_name'] = T_("Welcome");

   include 'header.inc.php';
   $logger = Logger::getLogger("homepage");
?>

<style>
   fieldset { padding:0; border:0; }
   validateTips { border: 1px solid transparent; padding: 0.3em; }
</style>

<script  language="JavaScript">


  function updateRemaining(dialogBoxTitle, bugid, remaining, description, userid, nextForm ){

     $( "#desc_summary" ).text(description);

     $( "#formUpdateRemaining * #remaining" ).val(remaining);

     $( "#formUpdateRemaining input[name=bugid]" ).val(bugid);
     $( "#formUpdateRemaining input[name=userid]" ).val(userid);
     $( "#formUpdateRemaining input[name=nextForm]" ).val(nextForm);

     $( "#update_remaining_dialog_form" ).dialog('option', 'title', dialogBoxTitle);
     $( "#update_remaining_dialog_form" ).dialog( "open" );

  }

   // ------ JQUERY ------
	$(function() {

		var  remaining = $( "#remaining" ),
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
			height: 250,
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
					bValid = bValid && checkRegexp( remaining, /^[0-9]+(\.[0-9]5?)?$/i, "format:  '1',  '0.3'  or  '2.55'" );

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


<div id="update_remaining_dialog_form" title="Task XXX - Update Remaining" style='display: none'>
	<p id='desc' name='desc'>
	    <label id="desc_summary" name='desc_summary'>summary</label>
	</p>
	<p id="validateTips"> </p>
	<form id='formUpdateRemaining' name='formUpdateRemaining' method='post' Action='index.php' >
	   <fieldset>
		   <label for="remaining">Remaining: </label>
		   <input type='text'  id='remaining' name='remaining' size='3' class='text' value='noValue' />
	   </fieldset>
      <input type='hidden' name='bugid'    value='0' >
      <input type='hidden' name='userid'   value='0' >
      <input type='hidden' name='nextForm' value='unspecifiedForm' >
      <input type='hidden' name='action'   value='updateRemainingAction' >
	</form>
</div>



<?php
include 'login.inc.php';
include 'menu.inc.php';

include_once 'consistency_check.class.php';
include_once 'user.class.php';
include_once 'issue.class.php';

function disclaimer () {
	$useragent = $_SERVER["HTTP_USER_AGENT"];

   if (preg_match("|MSIE ([0-9].[0-9]{1,2})|",$useragent,$matched)) {
      $browser_version=$matched[1];
      $browser = "IE";
      echo "<span style='color:red'>\n";
      echo T_("IE may not correctly display this website,<br/>");
      echo T_("please consider using a standard complient web-browser.<br/>");
      echo "</span>\n";
   }
}

// -----------------------------
function displayLoginForm() {

  echo "<div align=center>\n";
  echo("<form action='login.php' method='post' name='loginForm'>\n");

  echo T_("Login").": <input name='codev_login' type='text' id='codev_login'>\n";
  echo T_("Password").": <input name='codev_passwd' type='password' id='codev_passwd'>\n";
  echo "<input type='submit' name='Submit' value='".T_("log in")."'>\n";

  echo "<input type=hidden name=action      value=pleaseLogin>\n";
  echo "<input type=hidden name=currentForm value=loginForm>\n";
  echo "<input type=hidden name=nextForm    value=loginForm>\n";

  echo("</form>\n");
  echo "</div>\n";
}

function displayLinks() {
   global $mantisURL;

   echo "<div id='homepage_list'  class='left'>\n";
   #echo "<br/>\n";
   #echo "<br/>\n";
   echo "	<ul>\n";
   echo "   <li>\n";
   echo "        <a href='".$mantisURL."'>Mantis</a>";
   echo "   </li>\n";
   #echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/time_tracking.php'>".T_("Time Tracking")."</a>"; // Saisie des CRA
   echo "   </li>\n";
   #echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/holidays_report.php'>".T_("Holidays")."</a>"; // Affichage des cong&eacute;s
   echo "   </li>\n";
   echo "</ul>\n";

   //echo "<br/>\n";
   //echo "<br/>\n";
   #echo "<br/>\n";

   echo "<ul>\n";
   #echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/planning_report.php'>".T_("Planning")."</a>"; // Affichage du planning
   echo "   </li>\n";
   #echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/issue_info.php'>".T_("Task information")."</a>"; // Info fiche
   echo "   </li>\n";
   #echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/team_activity_report.php'>".T_("Weekly activity")."</a>"; // Activit&eacute; hebdomadaire
   echo "   </li>\n";
/*
   echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/productivity_report.php'>".T_("Productivity Reports")."</a>"; // Indicateurs de production
   echo "   </li>\n";
*/
   echo "</ul>\n";
   echo "</div>\n";

}

// -----------------------------
/**
 *
 */
function showIssuesInDrift($userid) {


	$user = UserCache::getInstance()->getUser($userid);
	$allIssueList = $user->getAssignedIssues();

	foreach ($allIssueList as $issue) {

	   $driftEE = $issue->getDrift();

	   if ($driftEE >= 1) {
	       $issueList[] = $issue;
	   }
    }
	if (0 == count($issueList)) {
	   return 0;
	}
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<hr/>\n";
   echo "<br/>\n";
   echo "<br/>\n";

    echo "<table>\n";
    echo "<caption>".T_("Tasks in drift")."</caption>\n";
    echo "<tr>\n";
    echo "<th>".T_("ID")."</th>\n";
    echo "<th>".T_("Project")."</th>\n";
    #echo "<th title='Derive par rapport a l estimation preliminaire'>".T_("Derive PrelEE")."</th>\n";
	echo "<th title='Derive par rapport au BI+BS'>".T_("Drift")."</th>\n";
	echo "<th>".T_("RAF")."</th>\n";
	echo "<th>".T_("Summary")."</th>\n";
    echo "</tr>\n";

	foreach ($issueList as $issue) {

		// TODO: check if issue in team project list ?

		$driftEE = $issue->getDrift();

        $formatedSummary = str_replace("'", "\'", $issue->summary);
        $formatedSummary = str_replace('"', "\'", $formatedSummary);
		$formatedTitle = "Task ".$issue->bugId." / ".$issue->tcId." - Update Remaining";

	   echo "<tr>\n";
	   echo "<td>".issueInfoURL($issue->bugId)."</td>\n";
	   echo "<td>".$issue->getProjectName()."</td>\n";
	   $color = "";
	   if ($driftEE <= -1) { $color = "style='background-color: #61ed66;'"; }
	   if ($driftEE >= 1) { $color = "style='background-color: #fcbdbd;'"; }
	   echo "<td $color >".$driftEE."</td>\n";

	   echo "<td>\n";
	   echo "<a title='".T_("update Remaining")."' href=\"javascript: updateRemaining('".$formatedTitle."', '".$issue->bugId."', '".$issue->remaining."', '".$formatedSummary."', '', '')\" >".$issue->remaining."</a>\n";
	   echo "</td>\n";

	   echo "<td>".$issue->summary."</td>\n";
	   echo "</tr>\n";
	}
   echo "</table>\n";


}


// -----------------------------
function displayConsistencyErrors($sessionUser) {

   // get projects i'm involved in (dev, Leader, Manager)
   $devTeamList = $sessionUser->getDevTeamList();
   $leadedTeamList = $sessionUser->getLeadedTeamList();
   $managedTeamList = $sessionUser->getManagedTeamList();
   $teamList = $devTeamList + $leadedTeamList + $managedTeamList;
   $projectList = $sessionUser->getProjectList($teamList);

   $ccheck = new ConsistencyCheck($projectList);

   $cerrList = $ccheck->check();

   if (0 == count($cerrList)) {
      #echo "Pas d'erreur.<br/>\n";
   } else {

      echo "<br/>\n";
      echo "<hr/>\n";
      echo "<br/>\n";
      echo "<br/>\n";
   	echo "<div align='left'>\n";
      foreach ($cerrList as $cerr) {

         if ($sessionUser->id == $cerr->userId) {
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            echo T_("ERROR on task ").mantisIssueURL($cerr->bugId, $issue->summary)." : &nbsp;&nbsp;<span style='color:red'>".date("Y-m-d", $cerr->timestamp)."&nbsp;&nbsp;".$statusNames[$cerr->status]."&nbsp;&nbsp;$cerr->desc</span><br/>\n";
         }
      }
      echo "</div>\n";
   }

}

// ================ MAIN =================

$bugid     = isset($_POST['bugid']) ? $_POST['bugid'] : '';
$remaining = isset($_POST['remaining']) ? $_POST['remaining'] : '';
$action    = isset($_POST["action"]) ? $_POST["action"] : '';


if (!isset($_SESSION['userid'])) {
   displayLoginForm();
} else {

	$userid = $_SESSION['userid'];
   $sessionUser = UserCache::getInstance()->getUser($userid);

   // --- updateRemaining DialogBox
    if ("updateRemainingAction" == $action) {
	   if ("0" != "$bugid") {
	      $issue = IssueCache::getInstance()->getIssue($bugid);
	      $issue->setRemaining($remaining);
	      #$action = "displayBug";
	   }
	}


   #disclaimer();

   displayLinks();

   echo "<br/>\n";

   showIssuesInDrift($userid);

   echo "<br/>\n";

   displayConsistencyErrors($sessionUser);
}

?>

<br/>
<br/>

<?php include 'footer.inc.php'; ?>

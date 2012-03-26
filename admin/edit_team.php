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

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';
?>

<?php
   $_POST['page_name'] = T_("CoDev Administration : Team Edition");
   include 'header.inc.php';
?>
<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>


<script language="JavaScript">
  function submitTeam(){
    // check fields
    foundError = 0;
    msgString = "Some fields are missing:" + "\n\n";

    if (0 == document.forms["teamSelectForm"].f_teamid.value)  { msgString += "Team\n"; ++foundError; }

    if (0 == foundError) {
      document.forms["teamSelectForm"].submit();
    } else {
      alert(msgString);
    }
  }


  function addTeamMember(){
     // check fields
     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["addTeamMemberForm"].f_memberid.value)  { msgString += "Team Member\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["addTeamMemberForm"].action.value="addTeamMember";
       document.forms["addTeamMemberForm"].submit();
     } else {
       alert(msgString);
     }
   }

  function addMembersFrom(){
     // check fields
     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["addTeamMemberForm"].f_src_teamid.value)  { msgString += "Source Team\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["addTeamMemberForm"].action.value="addMembersFrom";
       document.forms["addTeamMemberForm"].submit();
     } else {
       alert(msgString);
     }
   }



  function setMemberDepartureDate(){
     // check fields
     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["addTeamMemberForm"].f_memberid.value)  { msgString += "Team Member\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["addTeamMemberForm"].action.value="setMemberDepartureDate";
       document.forms["addTeamMemberForm"].submit();
     } else {
       alert(msgString);
     }
   }



  function addTeamProject(){
     // check fields
     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["addTeamProjectForm"].f_projectid.value)  { msgString += "Project\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["addTeamProjectForm"].action.value="addTeamProject";
       document.forms["addTeamProjectForm"].submit();
     } else {
       alert(msgString);
     }
   }

  function removeTeamMember(id, description){
     confirmString = "Desirez-vous vraiment supprimer ce membre de l'equipe ?" + "\n\n" + description;
     if (confirm(confirmString)) {
       document.forms["removeTeamMemberForm"].action.value="removeTeamMember";
       document.forms["removeTeamMemberForm"].f_memberid.value=id;
       document.forms["removeTeamMemberForm"].submit();
     }
   }

  function removeTeamProject(id, description){
     confirmString = "Desirez-vous vraiment supprimer ce Projet de l'equipe ?" + "\n\n" + description;
     if (confirm(confirmString)) {
       document.forms["removeTeamProjectForm"].action.value="removeTeamProject";
       document.forms["removeTeamProjectForm"].f_projectid.value=id;
       document.forms["removeTeamProjectForm"].submit();
     }
   }

  function updateTeamLeader(){
     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:" + "\n\n";

     if (0 == document.forms["updateTeamInfoForm"].f_leaderid.value)  { msgString += "Team Leader\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["updateTeamInfoForm"].action.value="updateTeamLeader";
       document.forms["updateTeamInfoForm"].submit();
     } else {
       alert(msgString);
     }
   }

  function deleteTeam(description){
     confirmString = "Desirez-vous vraiment supprimer definitivement l'equipe '" + description + "' ?";
     if (confirm(confirmString)) {
       document.forms["deleteTeamForm"].action.value="deleteTeam";
       document.forms["deleteTeamForm"].submit();
     }
   }


  function updateTeamCreationDate(){

       document.forms["updateTeamInfoForm"].action.value="updateTeamCreationDate";
       document.forms["updateTeamInfoForm"].submit();
   }

  function addAstreinte() {

	     // check fields
	     foundError = 0;
	     msgString = "Missing field:\n\n";

	     if (0 == document.forms["addAstreinteForm"].astreinteSelector.value) {
	     	msgString += "Inactivity task\n"; ++foundError;
	     }

	     if (0 == foundError) {
	       document.forms["addAstreinteForm"].astreinte_id.value = document.getElementById('astreinteSelector').value;
	       document.forms["addAstreinteForm"].action.value="addAstreinte";
	       document.forms["addAstreinteForm"].submit();
	     } else {
	       alert(msgString);
	     }

	   }

	function deleteAstreinte(id, description){
	   confirmString = "Cette tache ne sera plus consideree comme astreinte.\n" + description;
	   if (confirm(confirmString)) {
	     document.forms["deleteAstreinteForm"].action.value="deleteAstreinte";
	     document.forms["deleteAstreinteForm"].astreinte_id.value=id;
	     document.forms["deleteAstreinteForm"].submit();
	   }
	 }


</script>


<?php
include_once "project.class.php";
include_once "issue.class.php";
include_once "holidays.class.php";
include_once "user.class.php";
include_once "team.class.php";
require_once('tc_calendar.php');

$logger = Logger::getLogger("edit_team");


function setTeamForm($originPage, $defaultSelection, $teamList) {

  // create form
  echo "<div align=center>\n";
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage'>\n";

  echo "Team :\n";
  echo "<select name='f_teamid' onchange='javascript: submitTeam()'>\n";
  echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {

    if ($tid == $defaultSelection) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=hidden name=currentForm value=teamSelectForm>\n";
  echo "<input type=hidden name=nextForm    value=editTeamForm>\n";

  echo "</form>\n";
  echo "</div>\n";
}


// ----------------------------------------------------
function updateTeamInfoForm($team, $originPage) {
   global $logger;

   echo "<div>\n";

   $defaultDay   = date("d", $team->date);
   $defaultMonth = date("m", $team->date);
   $defaultYear  = date("Y", $team->date);

   $myCalendar = new tc_calendar("date_createTeam", true, false);
   $myCalendar->setIcon("../calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("../calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);


   echo "<h2 title='$team->id'>".T_("Team")." $team->name</h2>\n";
   #echo "<span>$team->description</span><br/>";
   echo "<br/>\n";

   echo "<form id='updateTeamInfoForm' name='updateTeamInfoForm' method='post' Action='$originPage'>\n";

   echo "<table class='invisible'>\n";

   echo "<tr>\n";
   echo "   <td>\n";
   echo "       ".T_("Leader");
   echo "   </td>\n";
   echo "   <td>\n";
   echo "<select style='width:100%' name='f_leaderid'>\n";

   $query     = "SELECT id, username FROM `mantis_user_table` ORDER BY username";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
   while($row = mysql_fetch_object($result))
   {
      if ($row->id == $team->leader_id) {
         echo "<option selected value='".$row->id."'>".$row->username."</option>\n";
      } else {
         echo "<option value='".$row->id."'>".$row->username."</option>\n";
      }
   }
   echo "</select>\n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "<input type=button name='btUpdateTeamLeader' value='".T_("Update")."' onClick='javascript: updateTeamLeader()'>\n";
   echo "   </td>\n";


   echo "</tr>\n";
   echo "<tr>\n";
   echo "   <td>".T_("Creation Date")."</td>\n";
   echo "   <td>\n";
   $myCalendar->writeScript();
   echo "   </td>\n";
   echo "   <td>\n";
   echo "       <input style='width:100%' type=button name='btupdateTeamCreationDate' value='".T_("Update")."' onClick='javascript: updateTeamCreationDate()'>\n";
   echo "   </td>\n";
   echo "</tr>\n";
   echo "</table>\n";


   echo "<br/>\n";
   echo "<br/>\n";
   echo "<span class='help_font'>".T_("Note: A <i>TeamLeader</i> must also be declared as <i>TeamMember</i> to be included in the team's productivity report.")."</span></br>\n";


   echo "<input type=hidden name=action       value=noAction>\n";

   echo "</form>\n";

   echo "<br/>\n";
   echo "<br/>\n";

   echo "</div>\n";
}


// ----------------------------------------------------
function displayTeamMemberTuples($teamid) {

   global $logger;
   
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   #echo "<caption>Team Members</caption>\n";
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("login")."</th>\n";
   echo "<th>".T_("Name")."</th>\n";
   echo "<th title='".T_("Arrival date in the team")."'>".T_("Arrival Date")."</th>\n";
   echo "<th title='".T_("Departure date from the team")."'>".T_("Departure Date")."</th>\n";
   echo "<th>".T_("Role")."</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_team_user_table.id, codev_team_user_table.user_id, codev_team_user_table.team_id, codev_team_user_table.access_level, ".
                       "codev_team_user_table.arrival_date, codev_team_user_table.departure_date, mantis_user_table.username, mantis_user_table.realname ".
                "FROM `codev_team_user_table`, `mantis_user_table` ".
                "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
                "AND codev_team_user_table.team_id=$teamid ".
                "ORDER BY mantis_user_table.username";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      echo "<a title='".T_("delete this row")."' href=\"javascript: removeTeamMember('".$row->id."', '$row->username')\" ><img src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td title='$row->user_id'>".$row->username."</td>\n";
      echo "<td>".$row->realname."</td>\n";
      echo "<td>".date("Y-m-d", $row->arrival_date)."</td>\n";
      if (0 != $row->departure_date) {
         echo "<td>".date("Y-m-d", $row->departure_date)."</td>\n";
      } else {
      	echo "<td></td>";
      }
      echo "<td>".Team::$accessLevelNames[$row->access_level]."</td>";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "</div>\n";
}


// ----------------------------------------------------
function addTeamMemberForm($originPage, $defaultDate, $teamid, $teamList) {

   global $logger;

	list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("../calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("../calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);

   $myCalendar2 = new tc_calendar("date2", true, false);
   $myCalendar2->setIcon("../calendar/images/iconCalendar.gif");
   $myCalendar2->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar2->setPath("../calendar/");
   $myCalendar2->setYearInterval(2010, 2015);
   $myCalendar2->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar2->setDateFormat('Y-m-d');
   $myCalendar2->startMonday(true);


   //$teamMembers = Team::getMemberList($teamid);
   //$formatedTeamMembers = implode( ', ', array_keys($teamMembers));

   // Display form
   echo "<h2>".T_("Team Members")."</h2>\n";

   #echo "<div style='text-align: center;'>";
   echo "<div>\n";

   echo "<form id='addTeamMemberForm' name='addTeamMemberForm' method='post' Action='$originPage'>\n";



   echo "<table class='invisible'>\n";
   echo "<tr>\n";
   echo "   <td>\n";
   echo T_("Member").": \n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "<select  style='width:100%' name='f_memberid'>\n";
   echo "<option value='0'></option>\n";

   $query     = "SELECT id, username FROM `mantis_user_table` ".
                //"WHERE id NOT IN ($formatedTeamMembers) ".
                "ORDER BY username";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
   while($row = mysql_fetch_object($result))
   {
      echo "<option value='".$row->id."'>".$row->username."</option>\n";
   }
   echo "</select>\n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "   </td>\n";
   echo "</tr>\n";
   // -------
   echo "<tr>\n";
   echo "   <td>\n";
   echo T_("Role").": \n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "<select  style='width:100%' name='member_access'>\n";

   foreach (Team::$accessLevelNames as $ac_id => $ac_name)
   {
      echo "<option value='$ac_id'>$ac_name</option>\n";
   }
   echo "</select>\n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "   </td>\n";
   echo "</tr>\n";

   // -------
   echo "<tr>\n";
   echo "   <td>".T_("Arrival-Date")."</td>\n";
   echo "   <td>\n";
   $myCalendar->writeScript();
   echo "   </td>\n";
   echo "   <td>\n";
   echo "       <input style='width:100%' type=button name='btAddMember' value='".T_("Add User")."' onClick='javascript: addTeamMember()'>\n";
   echo "   </td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "   <td>".T_("Departure-Date")."</td>\n";
   echo "   <td>\n";
   $myCalendar2->writeScript();
   echo "   </td>\n";
   echo "   <td>\n";
   echo "        <input type=button name='btSetMemberDepartureDate' value='".T_("set Departure Date")."' onClick='javascript: setMemberDepartureDate()'>\n";
   echo "   </td>\n";
   echo "</tr>\n";
   echo "</table>\n";

   echo "<br/>\n";
   echo "<br/>\n";

   // import from other team
   echo "<table class='invisible'>\n";
   echo "<tr>\n";
   echo "   <td>\n";
   echo T_("Import all users from team ")." \n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "<select name='f_src_teamid'>\n";
   echo "  <option value='0'></option>\n";
   foreach ($teamList as $tid => $tname) {
      if ($tid != $teamid) {
         echo "  <option value='".$tid."'>".$tname."</option>\n";
      }
   }
   echo "</select>\n";
   echo "   </td>\n";
   echo "   <td>\n";
   echo "        <input type=button name='btAddMembersFrom' value='".T_("Import")."' onClick='javascript: addMembersFrom()'>\n";
   echo "   </td>\n";
   echo "</tr>\n";
   echo "</table>\n";


   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTeamMemberForm>\n";
   echo "<input type=hidden name=nextForm     value=addTeamMemberForm>\n";
   echo "</form>\n";


   echo "<form id='removeTeamMemberForm' name='removeTeamMemberForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action         value=noAction>\n";
   echo "   <input type=hidden name=f_memberid     value='0'>\n";
   echo "</form>\n";

   echo "</div>\n";
}


// ----------------------------------------------------
function displayTeamProjectTuples($teamid) {

   global $logger;
   global $externalTasksProject;

   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   #echo "<caption>Team Projects</caption>\n";
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("Name")."</th>\n";
   echo "<th>".T_("Description")."</th>\n";
   echo "<th>".T_("Type")."</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_team_project_table.id, codev_team_project_table.type, ".
                       "mantis_project_table.id AS project_id, mantis_project_table.name, mantis_project_table.description ".
                "FROM `codev_team_project_table`, `mantis_project_table` ".
                "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
                "AND codev_team_project_table.team_id=$teamid ".
                "ORDER BY mantis_project_table.name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      // if ExternalTasksProject do not allow to delete
      if ($externalTasksProject != $row->project_id) {
         echo "<a title='".T_("delete this row")."' href=\"javascript: removeTeamProject('".$row->id."', '$row->name')\" ><img src='../images/b_drop.png'></a>\n";
      }
      echo "</td>\n";
      echo "<td title='$row->project_id'>".$row->name."</td>\n";
      echo "<td>".$row->description."</td>\n";
      echo "<td>".Project::$typeNames[$row->type]."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "</div>\n";
}

// ----------------------------------------------------
function addTeamProjectForm($teamid, $originPage) {

   global $logger;
   
   $curProjList=Team::getProjectList($teamid);
   $formatedCurProjList=implode( ', ', array_keys($curProjList));

   #echo "<div style='text-align: center;'>";
   echo "<div>\n";

   echo "<h2>".T_("Team Projects")."</h2>\n";

   echo "<form id='addTeamProjectForm' name='addTeamProjectForm' method='post' Action='$originPage'>\n";


   $query     = "SELECT DISTINCT mantis_project_table.id, mantis_project_table.name, mantis_project_table.description ".
                "FROM `mantis_project_table` ".
                "WHERE mantis_project_table.id NOT IN ($formatedCurProjList) ".
                "ORDER BY name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   echo T_("Project").": <select name='f_projectid'>\n";
   echo "<option value='0'></option>\n";
   while($row = mysql_fetch_object($result))
   {
      echo "   <option value='".$row->id."'>".$row->name."</option>\n";
   }
   echo "</select>\n";

   echo "Type: <select name='project_type'>\n";
   foreach (Project::$typeNames as $pt_id => $pt_name) {
      echo "   <option value='$pt_id'>$pt_name</option>\n";
   }
   echo "</select>\n";



   echo "<input type=button name='btAddProject' value='".T_("Add")."' onClick='javascript: addTeamProject()'>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTeamProjectForm>\n";
   echo "<input type=hidden name=nextForm     value=addTeamProjectForm>\n";
   echo "</form>\n";


   echo "<form id='removeTeamProjectForm' name='removeTeamProjectForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=f_projectid   value='0'>\n";
   echo "</form>\n";

   echo "</div>\n";
}


function deleteTeamForm($originPage, $teamName, $teamid) {
   echo "<div>\n";

   echo "<form id='deleteTeamProjectForm' name='deleteTeamForm' method='post' Action='$originPage'>\n";
   echo T_("Delete team")." $teamName ? ";
   echo "   <input type=button name='btDeleteTeam' value='".T_("Delete")."' onClick=\"javascript: deleteTeam('".$teamName."')\">\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";
   echo "</div>\n";

}

// ----------------------------------------------------
function addAstreinteForm($originPage, $teamid) {

   global $logger;

	$astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);

	// --- get SideTasksProject Inactivity Issues

	$stprojList = array();
	$projList = Team::getProjectList($teamid);
	
	if ((NULL == $projList) || (0 == count($projList))) {
	   // TODO $logger->warn("no project defined for this team, OnDuty tasks are defined in SideTasksProjects");
	   return 0;
	}
	
	$inactivityCatList = array();
	foreach ($projList as $pid => $pname) {
		$p = ProjectCache::getInstance()->getProject($pid);
		if ($p->isSideTasksProject()) {
			#$stprojList[] = $pid;
			$inactivityCatList[$pid] = $p->getInactivityCategoryId();
		}
	}
	
	if (0 == count($inactivityCatList)) {
	   // TODO $logger->warn("no inactivity category defined for SideTasksProjects => no OnDuty tasks ");
	   return 0;
	}
	
	$formatedInactivityCatList=implode( ', ', array_keys($inactivityCatList));


	$issueList = array();
	$query  = "SELECT id, project_id, category_id FROM `mantis_bug_table` ".
	          "WHERE project_id IN ($formatedInactivityCatList) ";

	if (NULL != $astreintesList) {
	   $formatedAstreintesList = implode( ', ', $astreintesList);
	   $query .= "AND id NOT IN ($formatedAstreintesList) ";
	}
	$query .= "ORDER BY id";

	if (isset($_GET['debug_sql'])) {echo "addAstreinteForm(): query = $query<br/>";}

    $result = mysql_query($query);
    if (!$result) {
       $logger->error("Query FAILED: $query");
       $logger->error(mysql_error());
       echo "<span style='color:red'>ERROR: Query FAILED</span>";
       exit;
    }
	if (0 != mysql_num_rows($result)) {
	   while($row = mysql_fetch_object($result)) {
	   	#echo "DEBUG $row->id cat $row->category_id inac[$row->project_id] = ".$inactivityCatList[$row->project_id]."</br>";
	   	if ($row->category_id == $inactivityCatList[$row->project_id]) {
	         $issueList[] = $row->id;
	   	}
	   }
	}

	// ---
	echo "<div>\n";

	echo "<form id='addAstreinteForm' name='addAstreinteForm' method='post' Action='$originPage'>\n";

	echo("   ".T_("Task").": \n");

	echo "   <select id='astreinteSelector' name='astreinteSelector' style='width: 600px;' title='".T_("Inactivity tasks")."'>\n";
	echo "     <option value='0'></option>\n";
	foreach ($issueList as $bugid) {
		$issue = new Issue ($bugid);
		echo "     <option value='".$bugid."'>".$bugid." : $issue->summary</option>\n";
	}
	echo "   </select>\n";


	echo "   <input type=button name='btAddAstreinte' value='".T_("Add")."' onClick='javascript: addAstreinte()'>\n";
	echo "   <input type=hidden name=action       value=noAction>\n";
	echo "   <input type=hidden name=astreinte_id value=0>\n";

	echo "</form>\n";

	echo "</div>\n";
}


// ----------------------------------------------------
function displayAstreintesTuples($teamid) {

	$astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);

	if (NULL == $astreintesList) return;

	// Display previous entries
	echo "<div>\n";
	echo "<table>\n";
	//echo "<caption>Astreintes</caption>\n";
	echo "<tr>\n";
	echo "<th></th>\n";
	echo "<th>".T_("Id")."</th>\n";
	echo "<th>".T_("Description")."</th>\n";
	echo "</tr>\n";


	foreach ($astreintesList as $bugid) {
		$issue = IssueCache::getInstance()->getIssue($bugid);

		$deleteDesc = "$bugid - ".$issue->summary;
		$deleteDesc = str_replace("'", "\'", $deleteDesc);
		$deleteDesc = str_replace('"', "\'", $deleteDesc);

		echo "<tr>\n";
		echo "<td>\n";
		echo "<a title='".T_("delete OnDuty")."' href=\"javascript: deleteAstreinte('".$bugid."', '$deleteDesc')\" ><img src='../images/b_drop.png'></a>\n";
		echo "</td>\n";
		echo "<td>$bugid</td>\n";
		echo "<td>$issue->summary</td>\n";

		echo "</tr>\n";
   }
   echo "</table>\n";

   echo "<form id='deleteAstreinteForm' name='deleteAstreinteForm' method='post' Action='$originPage'>\n";
		echo "   <input type=hidden name=action        value=noAction>\n";
   echo "   <input type=hidden name=astreinte_id  value='0'>\n";
   echo "</form>\n";

   echo "<div>\n";
}




// ================ MAIN =================

global $admin_teamid;

$originPage="edit_team.php";

$action = isset($_POST['action']) ? $_POST['action'] : '';

// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST['f_teamid'])) {
	$teamid = $_POST['f_teamid'];
	$_SESSION['teamid'] = $teamid;
} else {
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
}

// leadedTeams only, except Admins: they can edit all teams
$session_user = new User($_SESSION['userid']);

if ($session_user->isTeamMember($admin_teamid)) {
	$teamList = array();
   $query     = "SELECT id, name FROM `codev_team_table` ORDER BY name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
   while($row = mysql_fetch_object($result))
   {
      $teamList[$row->id] = $row->name;
   }
} else {
   $teamList = $session_user->getLeadedTeamList();
}

//  if user is not Leader of $_SESSION[teamid], do not display current team page
if (NULL == $teamList[$teamid]) { $teamid = 0;}

// --- show team selection form
echo "<h1>".T_("Team Edition")."</h1><br/>";
setTeamForm("edit_team.php", $teamid, $teamList);

echo "<br/>\n";
echo "<br/>\n";
echo "<br/>\n";


// if user is not Leader of any team, do not display current team page
if (0 == count($teamList)) { $teamid = 0;}


if (0 != $teamid) {

    $team = new Team($teamid);

	// --- display current Team
	$teamName  = $team->name;
	#echo "<span title='team_id = $teamid'>".T_("Current Team: ").$teamName."</span><br/>";

   echo "<hr align='left' width='20%'/>\n";
   updateTeamInfoForm($team, $originPage);

   #echo "<div>\n";

   echo "<br/>\n";
   echo "<br/>\n";
   echo "<hr align='left' width='20%'/>\n";

   #echo "<div class=\"float\">\n";
	$defaultDate  = date("Y-m-d", time());
   addTeamMemberForm("edit_team.php", $defaultDate, $teamid, $teamList);
   echo "<br/>\n";
   echo "<br/>\n";
   displayTeamMemberTuples($teamid);
   #echo "</div>\n";


   echo "<br/>\n";
   echo "<br/>\n";
   echo "<hr align='left' width='20%'/>\n";

   #echo "<div class=\"float\">\n";
   addTeamProjectForm($teamid, $originPage);
   echo "<br/>\n";
   echo "<br/>\n";
   displayTeamProjectTuples($teamid);
   #echo "</div>\n";

   #echo "</div>\n";

   #echo "<div class=\"spacer\"> </div>\n";


   echo "<br/>\n";
   echo "<br/>\n";
   echo "<hr align='left' width='20%'/>\n";
   echo "<br/>\n";

   echo "<h2>".T_("Add OnDuty tasks")."</h2>\n";
	echo "<br/>";
	echo T_("In here you can specify which inactivity tasks are 'OnDuty'")."<br/>";
	echo "<br/>";
	addAstreinteForm($originPage, $teamid);
	echo "<br/>";
	displayAstreintesTuples($teamid);
	echo "<br/>";
	echo "<br/>";

	// ---
	echo "<hr align='left' width='20%'/>\n";
	echo "<br/>\n";
   echo "<h2>".T_("Delete Team")."</h2>\n";
	deleteTeamForm($originPage, $teamName, $teamid);


   // ----------- actions ----------
   if ($action == "addTeamMember") {

    $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
    $arrivalTimestamp = date2timestamp($formatedDate);
    $memberid = $_POST['f_memberid'];
    $memberAccess = $_POST['member_access'];

    // save to DB
    $team->addMember($memberid, $arrivalTimestamp, $memberAccess);

    // reload page
    echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "addMembersFrom") {

    $src_teamid = $_POST['f_src_teamid'];

    // add all members declared in Team $src_teamid (same dates, same access)
    // except if already declared
    $team->addMembersFrom($src_teamid);

    // reload page
    echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "setMemberDepartureDate") {

      $formatedDate      = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : "";
      $departureTimestamp = date2timestamp($formatedDate);
      $memberid = $_POST['f_memberid'];

      $team->setMemberDepartureDate($memberid, $departureTimestamp);

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "addTeamProject") {

      $projectid = $_POST['f_projectid'];
      $projecttype= $_POST['project_type'];

      // prepare Project to CoDev (associate with CoDev customFields if needed)
      $project = ProjectCache::getInstance()->getProject($projectid);
      $project->prepareProjectToCodev();

      // save to DB
      $team->addProject($projectid, $projecttype);

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "removeTeamMember") {

      $memberid = $_POST['f_memberid'];
      $query = "DELETE FROM `codev_team_user_table` WHERE id = $memberid;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "removeTeamProject") {

      $projectid = $_POST['f_projectid'];
      $query = "DELETE FROM `codev_team_project_table` WHERE id = $projectid;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "updateTeamLeader") {

      $leaderid = $_POST['f_leaderid'];
      $query = "UPDATE `codev_team_table` SET leader_id = $leaderid WHERE id = $teamid;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "deleteTeam") {
   	$teamidToDelete=$teamid;

      $query = "DELETE FROM `codev_team_project_table` WHERE team_id = $teamidToDelete;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $query = "DELETE FROM `codev_team_user_table` WHERE team_id = $teamidToDelete;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $query = "DELETE FROM `codev_team_table` WHERE id = $teamidToDelete;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

   	unset($_SESSION['teamid']);
   	unset($_POST['f_teamid']);
   	unset($teamid);

   	// reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   } elseif ($action == "updateTeamCreationDate") {

   	$formatedDate = isset($_REQUEST["date_createTeam"]) ? $_REQUEST["date_createTeam"] : "";
      $date_create = date2timestamp($formatedDate);
      $team->setCreationDate($date_create);

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>");

   }elseif ($action == "addAstreinte") {
      $astreinte_id = $_POST['astreinte_id'];
      $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);
	  if (NULL == $astreintesList) {
	  	 $formatedList = "$astreinte_id";
	  } else {
		 $formatedList  = implode( ',', $astreintesList);
		 $formatedList .= ",$astreinte_id";
	  }
	  Config::getInstance()->setValue(Config::id_astreintesTaskList, $formatedList, Config::configType_array);

      // reload page
      echo ("<script> parent.location.replace('$originPage'); </script>");

   } elseif ($action == "deleteAstreinte") {

      $astreinte_id = $_POST['astreinte_id'];
      $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);
	  if (NULL != $astreintesList) {
		 if (1 == count($astreintesList)) {
		 	#Config::getInstance()->deleteValue(Config::id_astreintesTaskList);
	        Config::getInstance()->setValue(Config::id_astreintesTaskList, "", Config::configType_array);
		 } else {
		 	$key = array_search($astreinte_id, $astreintesList);
		 	unset($astreintesList[$key]);
		    $formatedList  = implode( ',', $astreintesList);
	        Config::getInstance()->setValue(Config::id_astreintesTaskList, $formatedList, Config::configType_array);
		 }
	  }
      // reload page
      echo ("<script> parent.location.replace('$originPage'); </script>");
   }



}

?>

<?php include 'footer.inc.php'; ?>

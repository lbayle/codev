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

include_once '../path.inc.php';
include_once 'i18n.inc.php';

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}


$_POST['page_name'] = T_("Clone Project Settings");
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
#echo "<br/>\n";
#include 'menu_admin.inc.php';

?>

<script language="JavaScript">

  function submitProject(){
     document.forms["selectProjectForm"].action.value = "displayPage";
     document.forms["selectProjectForm"].submit();
   }

  function setCloneProject(){
     document.forms["cloneProjectForm"].action.value = "displayPage";
     document.forms["cloneProjectForm"].submit();
   }


  function cloneProject(dialogBoxTitle, projectid, clone_projectid, description, action){

     $( "#desc_action" ).text(description);

     $( "#formClone input[name=projectid]" ).val(projectid);
     $( "#formClone input[name=clone_projectid]" ).val(clone_projectid);
     $( "#formClone input[name=action]" ).val(action);

     $( "#cloneProject_dialog_form" ).dialog('option', 'title', dialogBoxTitle);
     $( "#cloneProject_dialog_form" ).dialog( "open" );
   }


   // ------ JQUERY ------
	$(function() {

      var allFields = $( [] );

		$( "#cloneProject_dialog_form" ).dialog({
			autoOpen: false,
			height: 180,
			width: 400,
			modal: true,

			buttons: {
				"Clone": function() {
               allFields.removeClass( "ui-state-error" );
               $('#formClone').submit();
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


<div id="cloneProject_dialog_form" title="Clone Project" style='display: none'>
	<p id='desc' name='desc'>
	    <label id="desc_action" name='desc_action'>action description</label>
	</p>
	<p id="validateTips"> </p>
	<form id='formClone' name='formClone' method='post' Action='workflow.php' >
      <input type='hidden' name='projectid'    value='0' >
      <input type='hidden' name='clone_projectid'    value='0' >
      <input type='hidden' name='action'   value='noAction' >
	</form>
</div>



<div id="content">
<?php

# ------------------------------------------------------------------

include_once 'project.class.php';
include_once 'user.class.php';


$logger = Logger::getLogger("workflow");

// -----------------------------------------
function setProjectForm($originPage, $defaultSelection, $list) {

   // create form
   echo "<div align=center>\n";
   echo "<form id='selectProjectForm' name='selectProjectForm' method='post' action='$originPage'>\n";

   echo T_("Project")." :\n";
   echo "<select name='projectid' onchange='javascript: submitProject()'>\n";
   echo "<option value='0'></option>\n";

   foreach ($list as $id => $name) {

      if ($id == $defaultSelection) {
         echo "<option selected value='".$id."'>".$name."</option>\n";
      } else {
         echo "<option value='".$id."'>".$name."</option>\n";
      }
   }
   echo "</select>\n";

   #echo "<input type=button value='".T_("Update")."' onClick='javascript: submitProject()'>\n";

   echo "<input type=hidden name=action value=noAction>\n";

   echo "</form>\n";
   echo "</div>\n";
}


// -----------------------------------------
function setCloneProjectForm($originPage, $cur_projectId, $defaultSelection, $list) {

   // create form
   echo "<div align=left>\n";
   echo "<form id='cloneProjectForm' name='cloneProjectForm' method='post' action='$originPage'>\n";

   echo "<h2>".T_("Clone project settings")."</h2><br>\n";
   echo "<select name='clone_projectid' onchange='javascript: setCloneProject()'>\n";
   echo "<option value='0'></option>\n";

   foreach ($list as $id => $name) {

      if ($cur_projectId == $id) { continue; }

      if ($id == $defaultSelection) {
         echo "<option selected value='".$id."'>".$name."</option>\n";
      } else {
         echo "<option value='".$id."'>".$name."</option>\n";
      }
   }
   echo "</select>\n";

   $title_to   = T_("Clonning")." ".Project::getName($cur_projectId)." ".T_("into")." ".Project::getName($defaultSelection);
   $title_from = T_("Clonning")." ".Project::getName($defaultSelection)." ".T_("into")." ".Project::getName($cur_projectId);

   $desc_to   = T_("Are you sure you want to change")." ".Project::getName($defaultSelection)." ".T_("settings ?");
   $desc_from = T_("Are you sure you want to change")." ".Project::getName($cur_projectId)." ".T_("settings ?");

   $enabled =  (0 == $defaultSelection) ? "disabled='disabled'" : "";
   echo "<input type=button $enabled value='".T_("Clone From")."' onClick=\"javascript: cloneProject('$title_from', $cur_projectId, $defaultSelection, '$desc_from', 'cloneFromProject')\">\n";
   echo "<input type=button $enabled value='".T_("Clone To")."'   onClick=\"javascript: cloneProject('$title_to',   $cur_projectId, $defaultSelection, '$desc_to',   'cloneToProject')\">\n";

   echo "<input type=hidden name=action value=noAction>\n";
   echo "<input type=hidden name=projectid value=$cur_projectId>\n";

   echo "</form>\n";
   echo "</div>\n";
}



// ------------------------------------------------
/**
 * get all existing projects,
 *
 * @param $isCodevtt if true, include ExternalTasksProject & SideTasksProjects
 */
function getProjectList($isCodevtt = false) {
	$projectList = array();

	$extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);

	$query  = "SELECT id, name ".
                "FROM `mantis_project_table` ";
	#"WHERE mantis_project_table.id = $this->id ";

	$result = mysql_query($query) or die("Query failed: $query");
	while($row = mysql_fetch_object($result))
	{
		if (false == $isCodevtt) {
			$p = ProjectCache::getInstance()->getProject($row->id);
			if (($extproj_id != $row->id) && (!$p->isSideTasksProject())) {
				$projectList[$row->id] = $row->name;
			}
		} else {
			$projectList[$row->id] = $row->name;
		}
	}
	return $projectList;
}


/**
 * display a table
 */
function displayWorkflow($project) {

   $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

   $wfTrans = $project->getWorkflowTransitions();

   $statusTitles = $wfTrans[0];


   echo "<table>\n";
   echo "<caption>".Project::getName($project->id).": ".T_("Workflow transitions")."</caption>";
   echo "<tr>\n";
   echo "<th></th>\n";
   foreach ( $statusTitles as $sid => $sname) {
      echo "<th>".$statusNames[$sid]."</th>\n";
   }
    echo "</tr>\n";

   foreach ( $wfTrans as $sid => $sList) {
      if (0 == $sid) { continue; }
      echo "<tr>\n";
      echo "<th>".$statusNames[$sid]."</th>\n";
      foreach ( $statusTitles as $sid => $sname) {
      	$val = (null == $sList[$sid]) ? "" : "X";
         echo "<td>".$val."</td>\n";
      }
       echo "</tr>\n";
   }
   echo "</table>\n";



}

// ================ MAIN =================

$originPage = "workflow.php";

$defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
$projectid      = isset($_POST['projectid']) ? $_POST['projectid'] : $defaultProject;
$_SESSION['projectid'] = $projectid;

$clone_projectid  = isset($_POST['clone_projectid']) ? $_POST['clone_projectid'] : 0;

$action     = isset($_POST['action']) ? $_POST['action'] : '';

// Admins only
$session_user = new User($_SESSION['userid']);
if (!$session_user->isTeamMember($admin_teamid)) {
	echo T_("Sorry, you need to be in the admin-team to access this page.");
	exit;
}

// -------

$projectList = getProjectList();

setProjectForm($originPage, $projectid, $projectList);
echo "<br/><br/>\n";
echo "<br/><br/>\n";

// --- display current workflow
if (0 != $projectid) {

	if ("cloneToProject" == $action) {
	   #echo "Clone $projectid ---> $clone_projectid<br>";
	   $errMsg = Project::cloneAllProjectConfig($projectid, $clone_projectid);

	   echo "cloneToProject: $errMsg<br><br>";
	}

	if ("cloneFromProject" == $action) {
	   #echo "Clone $clone_projectid ---> $projectid<br>";
	   $errMsg = Project::cloneAllProjectConfig($clone_projectid, $projectid);

	   echo "cloneFromProject: $errMsg<br><br>";
	}


	setCloneProjectForm($originPage, $projectid, $clone_projectid, $projectList);
	echo "<br/><br/>\n";
	echo "<br/><br/>\n";


   $proj = ProjectCache::getInstance()->getProject($projectid);
   $wfTrans = $proj->getWorkflowTransitions();
   displayWorkflow($proj);

	if (0 != $clone_projectid) {
		echo "<br/><br/>\n";
		echo "<br/><br/>\n";
	   $cproj = ProjectCache::getInstance()->getProject($clone_projectid);
	   $wfTrans = $cproj->getWorkflowTransitions();
	   displayWorkflow($cproj);
	}
}


// status_enum_workflow, set_status_threshold, bug_readonly_status_threshold, bug_assigned_status

?>

</div>
<?php include 'footer.inc.php'; ?>

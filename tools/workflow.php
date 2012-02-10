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

$_POST['page_name'] = T_("Clone Project Settings");
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
#echo "<br/>\n";
#include 'menu_admin.inc.php';

?>

<script language="JavaScript">

  function submitProject(){

     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["selectProjectForm"].projectid.value)  { msgString += "Project\n"; ++foundError; }

     if (0 != foundError) {
       alert(msgString);
     }
     document.forms["selectProjectForm"].action.value = "displayPage";
     document.forms["selectProjectForm"].submit();

   }

  function cloneProject(){

     foundError = 0;
     msgString = "Some fields are missing:" + "\n\n";

     if (0 == document.forms["cloneProjectForm"].src_projectid.value)  { msgString += "Project to clone from\n"; ++foundError; }

     if (0 != foundError) {
       alert(msgString);
     }
     document.forms["cloneProjectForm"].action.value = "cloneProject";
     document.forms["cloneProjectForm"].submit();

   }

</script>


<div id="content">
<?php

# ------------------------------------------------------------------

include_once 'project.class.php';


$logger = Logger::getLogger("workflow");

// -----------------------------------------
function setProjectForm($originPage, $defaultSelection, $list) {

   // create form
   echo "<div align=center>\n";
   echo "<form id='selectProjectForm' name='selectProjectForm' method='post' action='$originPage'>\n";

   echo T_("Project")." :\n";
   echo "<select name='projectid'>\n";
   echo "<option value='0'></option>\n";

   foreach ($list as $id => $name) {

      if ($id == $defaultSelection) {
         echo "<option selected value='".$id."'>".$name."</option>\n";
      } else {
         echo "<option value='".$id."'>".$name."</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=button value='".T_("Update")."' onClick='javascript: submitProject()'>\n";

   echo "<input type=hidden name=action value=noAction>\n";

   echo "</form>\n";
   echo "</div>\n";
}


// -----------------------------------------
function setCloneProjectForm($originPage, $destProjectId, $defaultSelection, $list) {

   // create form
   echo "<div align=left>\n";
   echo "<form id='cloneProjectForm' name='cloneProjectForm' method='post' action='$originPage'>\n";

   echo T_("Clone settings from")." :\n";
   echo "<select name='src_projectid'>\n";
   echo "<option value='0'></option>\n";

   foreach ($list as $id => $name) {

      if ($destProjectId == $id) { continue; }

      if ($id == $defaultSelection) {
         echo "<option selected value='".$id."'>".$name."</option>\n";
      } else {
         echo "<option value='".$id."'>".$name."</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=button value='".T_("Clone")."' onClick='javascript: cloneProject()'>\n";

   echo "<input type=hidden name=action value=noAction>\n";
   echo "<input type=hidden name=projectid value=$destProjectId>\n";

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

$src_projectid = isset($_POST['src_projectid']) ? $_POST['src_projectid'] : 0;

$action     = isset($_POST['action']) ? $_POST['action'] : '';


$projectList = getProjectList();

setProjectForm($originPage, $projectid, $projectList);
echo "<br/><br/>\n";
echo "<br/><br/>\n";

// --- display current workflow
if (0 != $projectid) {

	if ("cloneProject" == $action) {
	   #echo "Clone $src_projectid ---> $projectid<br>";
	   $errMsg = Project::cloneAllProjectConfig($src_projectid, $projectid);

	   echo "$errMsg<br><br>";
	}


	setCloneProjectForm($originPage, $projectid, $src_projectid, $projectList);
	echo "<br/><br/>\n";
	echo "<br/><br/>\n";


   $proj = ProjectCache::getInstance()->getProject($projectid);
   $wfTrans = $proj->getWorkflowTransitions();
   displayWorkflow($proj);

}




?>

</div>
<?php include 'footer.inc.php'; ?>

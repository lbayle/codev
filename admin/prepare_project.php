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

$page_name = T_("CoDev Administration : Prepare Projects");
require_once 'header.inc.php';

require_once 'login.inc.php';
require_once 'menu.inc.php';
?>
<br/>
<?php include 'menu_admin.inc.php'; ?>



<script language="JavaScript">

function prepareProject() {
	document.forms["form1"].action.value="prepareProject";
	document.forms["form1"].submit();
}

</script>

<div id="content">


<?php
include_once 'user.class.php';
include_once 'project.class.php';

$logger = Logger::getLogger("prepare_project");

// ------------------------------------------------
function displayForm($originPage, $projectList) {

	echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";

	#echo "<hr align='left' width='20%'/>\n";


   // ------ Add custom fields to existing projects
  echo "  <br/>\n";
	echo "<h2>".T_("Configure existing Projects")."</h2>\n";
  echo "<span class='help_font'>".T_("Select the projects to be managed with CoDev Timetracking")."</span><br/>\n";
  echo "  <br/>\n";

  echo "<select name='projects[]' multiple size='5'>\n";
  foreach ($projectList as $id => $name) {
   echo "<option selected value='$id'>$name</option>\n";
  }
	echo "</select>\n";

  echo "  <br/>\n";
	echo "  <br/>\n";
	echo "<div  style='text-align: center;'>\n";
	echo "<input type=button style='font-size:150%' value='".T_("Prepare")." !' onClick='javascript: prepareProject()'>\n";
	echo "</div>\n";

  // ------
  echo "<input type=hidden name=action      value=noAction>\n";

	echo "</form>";
}

// ------------------------------------------------
/**
 * get all existing projects, except ExternalTasksProject & SideTasksProjects
 */
function getProjectList() {

    global $logger;

    $projectList = array();

	$extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);

	$query  = "SELECT id, name ".
                "FROM `mantis_project_table` ";
	#"WHERE mantis_project_table.id = $this->id ";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
       echo "<span style='color:red'>ERROR: Query FAILED</span>";
       exit;
    }
	while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
		// exclude ExternalTasksProject
		if ($extproj_id == $row->id) {
			$logger->debug("project $row->id: ExternalTasksProject is excluded");
			continue;
		}

		// exclude SideTasksProjects
       try {
         $p = ProjectCache::getInstance()->getProject($row->id);
			if ($p->isSideTasksProject()) {
				$logger->debug("project $row->id: sideTaskProjects are excluded");
				continue;
		   }
       } catch (Exception $e) {
	   	// could not determinate, so the project should be included in the list
	   	$logger->debug("project $row->id: Unknown type, project included anyway.");
	   	// nothing to do.
       }
       $projectList[$row->id] = $row->name;
	}
	return $projectList;
}

// ================ MAIN =================

global $admin_teamid;

$originPage = "prepare_project.php";

$action     = isset($_POST['action']) ? $_POST['action'] : '';

// Admins only
$session_user = new User($_SESSION['userid']);

if (!$session_user->isTeamMember($admin_teamid)) {
	echo T_("Sorry, you need to be in the admin-team to access this page.");
	exit;
}

/*
echo "<h2>".T_("Prepare Projects")."</h2>\n";
echo "<br/>";
echo T_("Prepare Mantis projects to be managed with CoDevTT")."<br/>";
echo "<br/>";
#echo T_("Note: adding RTTs is not a good idea, users may decide to work anyways and productionDaysForecast will be wrong.");
echo "<br/>";
echo "<br/>";
echo "<br/>";
*/

$projectList = getProjectList();

if ("prepareProject" == $action) {

	// Add custom fields to existing projects
	if(isset($_POST['projects']) && !empty($_POST['projects'])){
		$selectedProjects = $_POST['projects'];
		foreach($selectedProjects as $projectid){
			$project = ProjectCache::getInstance()->getProject($projectid);
			echo "preparing project: ".$project->name."<br/>";
			$project->prepareProjectToCodev();
		}
	}
	echo "done.<br/>";

}


// ----- DISPLAY PAGE

displayForm($originPage, $projectList);

?>
</div>


<?php include 'footer.inc.php'; ?>

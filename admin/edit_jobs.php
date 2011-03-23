<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
} 
?>

<?php
   $_POST[page_name] = T_("CoDev Administration : Jobs Edition"); 
   include 'header.inc.php'; 
?>
<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>


<script language="JavaScript">

function addJob() {
     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:\n\n"
         
     if ("" == document.forms["addJobForm"].job_name.value)      { msgString += "Job Name\n"; ++foundError; }
     if ("" == document.forms["addJobForm"].job_color.value)     { msgString += "Job Color\n"; ++foundError; }
                    
     if (0 == foundError) {
       document.forms["addJobForm"].action.value="addJob";
       document.forms["addJobForm"].submit();
     } else {
       alert(msgString);    
     }    
   }

function deleteJob(id, description){
   confirmString = "Desirez-vous vraiment supprimer definitivement le poste '" + description + "' ?";
   if (confirm(confirmString)) {
     document.forms["deleteJobForm"].action.value="deleteJob";
     document.forms["deleteJobForm"].job_id.value=id;
     document.forms["deleteJobForm"].submit();
   }
 }

function addJobProjectAssociation() {
     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:\n\n"
         
     if (0 == document.forms["addJobProjectAssociationForm"].project_id.value)  { msgString += "Project\n"; ++foundError; }
     if (0 == document.forms["addJobProjectAssociationForm"].job_id.value)      { msgString += "Job\n"; ++foundError; }
                    
     if (0 == foundError) {
       document.forms["addJobProjectAssociationForm"].action.value="addJobProjectAssociation";
       document.forms["addJobProjectAssociationForm"].submit();
     } else {
       alert(msgString);    
     }    
   }

function deleteJobProjectAssociation(id, description){
   confirmString = "Desirez-vous vraiment supprimer definitivement l'association '" + description + "' ?";
   if (confirm(confirmString)) {
     document.forms["deleteJobProjectAssociationForm"].action.value="deleteJobProjectAssociation";
     document.forms["deleteJobProjectAssociationForm"].asso_id.value=id;
     document.forms["deleteJobProjectAssociationForm"].submit();
   }
 }
</script>


<?php
include_once "constants.php";
include_once "tools.php";
include_once "user.class.php";
require_once('tc_calendar.php');


// ----------------------------------------------------
function getProjectList() {
	
	$plist = array();
	
   $query     = "SELECT id, name ".
                "FROM `mantis_project_table` ".
                "ORDER BY name";
   
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
   	$plist[$row->id] = $row->name;
   }
   
   return $plist;
}

// ----------------------------------------------------
function getJobList($type = NULL) {
   
   $jlist = array();
   
   $query     = "SELECT id, name ".
                "FROM `codev_job_table` ";
   if (NULL != $type) {
      $query .=  "WHERE type = $type ";
   }
   
   $query .=  "ORDER BY name";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      $jlist[$row->id] = $row->name;
   }
   
   return $jlist;
}

// ----------------------------------------------------
function addJobForm($originPage) {
   
	global $jobType_names;
	
   #echo "<div style='text-align: center;'>";
   echo "<div>\n";
   
   echo "<form id='addJobForm' name='addJobForm' method='post' Action='$originPage'>\n";

  echo("   ".T_("Job Name").": <input name='job_name' size='30' type='text' id='job_name'>\n");
      
   echo "   ".T_("Type").": <select name='job_type'>\n";
   foreach ($jobType_names as $jid => $jname) {
      echo "      <option value='$jid'>$jname</option>\n";
   }
   echo "   </select>\n";
      
   echo("   ".T_("Color").": <input name='job_color' type='text' id='job_color' value='#000000' size='6'>\n");

   echo "   <input type=button name='btAddJob' value='".T_("Add")."' onClick='javascript: addJob()'>\n";

   echo "   &nbsp;&nbsp;&nbsp;<a href='http://www.colorpicker.com' target='_blank' title='".T_("open a colorPicker in a new Tab")."'>ColorPicker</A>";
   
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";

   echo "</div>\n";
}

// ----------------------------------------------------
function displayJobTuples() {
	
   global $jobType_names;
   
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   //echo "<caption>Jobs</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("Job Name")."</th>\n";
   echo "<th>".T_("Type")."</th>\n";
   echo "<th>".T_("Color")."</th>\n";
   echo "</tr>\n";

   $query     = "SELECT * ".
                "FROM `codev_job_table` ".
                "ORDER BY name";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      
      // if job already used for TimeTracking, delete forbidden
      $query2 = "SELECT COUNT(jobid) ".
                "FROM `codev_timetracking_table` ".
                "WHERE jobid = $row->id";
      $result2    = mysql_query($query2) or die("Query failed: $query2");
      $nbTuples  = (0 != mysql_num_rows($result2)) ? mysql_result($result2, 0) : 0;
      
      if (0 == $nbTuples) {
         echo "<a title='".T_("delete Job")."' href=\"javascript: deleteJob('".$row->id."', '$row->name')\" ><img src='../images/b_drop.png'></a>\n";
      }
      echo "</td>\n";
      echo "<td title='$row->id'>".$row->name."</td>\n";
      echo "<td title='$row->type'>".$jobType_names[$row->type]."</td>\n";
      echo "<td style='background-color: ".$row->color."';>".$row->color."</td>\n";
      
      echo "</tr>\n";
   }
   echo "</table>\n";
   
   echo "<form id='deleteJobForm' name='deleteJobForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=job_id   value='0'>\n";
   echo "</form>\n";
   
   echo "<div>\n";
}

// ----------------------------------------------------
function addJobProjectAssociationForm($originPage) {
   global $assignedJobType;
   
   $plist = getProjectList();
   $jlist = getJobList($assignedJobType);
   
   #echo "<div style='text-align: center;'>";
   echo "<div>\n";
   
   echo "<form id='addJobProjectAssociationForm' name='addJobProjectAssociationForm' method='post' Action='$originPage'>\n";

   echo T_("Job").":   <select name='job_id'>\n";
   echo "   <option value='0'></option>\n";
   foreach($jlist as $jid => $jname) {
      echo "   <option value='".$jid."'>".$jname."</option>\n";
   }
   echo "</select>\n";
   
   echo T_("Project").": <select name='project_id'>\n";
   echo "   <option value='0'></option>\n";
   foreach($plist as $pid => $pname) {
      echo "   <option value='".$pid."'>".$pname."</option>\n";
   }
   echo "</select>\n";
   
   
   

   echo "<input type=button name='btAddAssociation' value='".T_("Add")."' onClick='javascript: addJobProjectAssociation()'>\n";

   echo "   <input type=hidden name=action  value=noAction>\n";
   echo "</form>\n";
   
   
   
   echo "</div>\n";
}


// ----------------------------------------------------
function displayAssignedJobTuples() {
   
   $plist = getProjectList();
	
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   //echo "<caption>Assigned Jobs</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("Job Name")."</th>\n";
   echo "<th>".T_("Project")."</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_project_job_table.id, codev_project_job_table.project_id, codev_project_job_table.job_id, codev_job_table.name AS job_name ".
                "FROM `codev_project_job_table`, `codev_job_table` ".
                "WHERE codev_project_job_table.job_id = codev_job_table.id ".
                "ORDER BY codev_project_job_table.project_id";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      // if SuiviOp do not allow tu delete
      $desc = $row->job_name." - ".$plist[$row->project_id];
      $desc = str_replace("'", "\'", $desc);
      $desc = str_replace('"', "\'", $desc);
      
      echo "<a title='".T_("delete Project Association")."' href=\"javascript: deleteJobProjectAssociation('$row->id','$desc')\" ><img src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td title='$row->job_id'>".$row->job_name."</td>\n";
      echo "<td title='$row->project_id'>".$plist[$row->project_id]."</td>\n";
   
      echo "</tr>\n";
   }
   echo "</table>\n";
   
   echo "<form id='deleteJobProjectAssociationForm' name='deleteJobProjectAssociationForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=asso_id   value='0'>\n";
   echo "</form>\n";
   
   
   echo "<div>\n";
}



// ================ MAIN =================

global $admin_teamid;

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die(T_("Could not select database"));


// Admins only
$session_user = new User($_SESSION['userid']);

if (!$session_user->isTeamMember($admin_teamid)) {
	echo T_("Sorry, you need to be in the admin-team to access this page.");
	exit;
}

echo "<h2>".T_("Jobs")."</h2>\n";
addJobForm("edit_jobs.php");
echo "<br/>";
displayJobTuples();

echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<hr align='left' width='20%'/>\n";
echo "<h2 title = 'Job-Projects Associations'>".T_("Job Assignations")."</h2>\n";
addJobProjectAssociationForm("edit_jobs.php");
echo "<br/>";
displayAssignedJobTuples();
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";


   // ----------- actions ----------
   if ($_POST[action] == "addJob") {
      
      $job_name = $_POST[job_name];
      $job_type = $_POST[job_type];
      $job_color = $_POST[job_color];
      
      // TODO check if not already in table !
    
      // save to DB
      $query = "INSERT INTO `codev_job_table`  (`name`, `type`, `color`) VALUES ('$job_name','$job_type','$job_color');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
    
      // reload page
      echo ("<script> parent.location.replace('edit_jobs.php'); </script>"); 
    
   } elseif ($_POST[action] == "deleteJob") {
      $job_id = $_POST[job_id];
   	
      // TODO delete Support job not allowed
      
      $query = "DELETE FROM `codev_project_job_table` WHERE job_id = $job_id;";
      mysql_query($query) or die("Query failed: $query");
      
   	$query = "DELETE FROM `codev_job_table` WHERE id = $job_id;";
      mysql_query($query) or die("Query failed: $query");
   	
      // reload page
      echo ("<script> parent.location.replace('edit_jobs.php'); </script>"); 
    
   } elseif ($_POST[action] == "addJobProjectAssociation") {
      
      $project_id = $_POST[project_id];
      $job_id     = $_POST[job_id];
    
      // TODO check if not already in table !
    
      // save to DB
      $query = "INSERT INTO `codev_project_job_table`  (`project_id`, `job_id`) VALUES ('$project_id','$job_id');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
    
      // reload page
      echo ("<script> parent.location.replace('edit_jobs.php'); </script>"); 
   
   } elseif ($_POST[action] == "deleteJobProjectAssociation") {
      $asso_id = $_POST[asso_id];
      
      $query = "DELETE FROM `codev_project_job_table` WHERE id = $asso_id;";
      mysql_query($query) or die("Query failed: $query");
      
      // reload page
      echo ("<script> parent.location.replace('edit_jobs.php'); </script>"); 
    
   }

?>

<?php include 'footer.inc.php'; ?>


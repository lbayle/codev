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
?>
<?php
   $_POST['page_name'] = T_("Uninstall");
   include 'header.inc.php';

   include 'uninstall_menu.inc.php';
?>


<script language="JavaScript">

function uninstall() {
	document.forms["form1"].action.value="uninstall";
   document.forms["form1"].is_modified.value= "true";
	document.forms["form1"].submit();
}

</script>

<div id="content">



<?php
include_once 'user.class.php';


// ------------------------------------------------
function displayForm($originPage, $is_modified,
                     $isPart1, $isPart2, $isPart3, $isPart4) {

	echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";

	// ------ 
	echo "  <br/>\n";
	echo "<h2>".T_("List")."</h2>\n";
	echo "<span class='help_font'>".T_("Select the parts to uninstall")."</span><br/>\n";
   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "  <br/>\n";
    
    

   // ---
   $isChecked = $isPart1 ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_part1' id='cb_part1'></input></td>\n";
   echo "    <td width='70'>remove DB tables: all but timetracks</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td><span class='help_font'>".T_("content")."</span></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";
      
   // ---
   $isChecked = $isPart2 ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_part2' id='cb_part2'></input></td>\n";
   echo "    <td width='70'>remove DB tables: timetracks</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td><span class='help_font'>".T_("content")."</span></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   // ---
   $isChecked = $isPart3 ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_part3' id='cb_part3'></input></td>\n";
   echo "    <td width='70'>remove SideTasks & ExternalTasks projects</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td><span class='help_font'>".T_("content")."</span></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";
         
   // ---
   $isChecked = $isPart4 ? "CHECKED" : "";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_part4' id='cb_part4'></input></td>\n";
   echo "    <td width='70'>mantis custom fields (Remaining, etc.)</td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td></td>";
   echo "    <td><span class='help_font'>".T_("content")."</span></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";
      
   // ----------
   echo "  <br/>\n";
	echo "  <br/>\n";
	echo "<div  style='text-align: center;'>\n";
	echo "<input type=button style='font-size:150%' value='".T_("Uninstall")." !' onClick='javascript: uninstall()'>\n";
	echo "</div>\n";

  // ------
	echo "<input type=hidden name=action      value=noAction>\n";
	echo "<input type=hidden name=is_modified value=$is_modified>\n";
	
	echo "</form>";
}




// ================ MAIN =================


// Admins only
global $admin_teamid;
$session_user = new User($_SESSION['userid']);
if (!$session_user->isTeamMember($admin_teamid)) {
	echo T_("Sorry, you need to be in the admin-team to access this page.");
	exit;
}

$originPage = "uninstall.php";
$action      = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";

// --- init
// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
if ("false" == $is_modified) {

	$isPart1 = true;
	$isPart2 = true;
	$isPart3 = true;
	$isPart4 = true;

} else {
	$isPart1   = $_POST['cb_part1'];
	$isPart2   = $_POST['cb_part2'];
	$isPart3   = $_POST['cb_part3'];
	$isPart4   = $_POST['cb_part4'];
}



// --- actions
if ("uninstall" == $action) {

	echo "TODO !<br/>";

}


// ----- DISPLAY PAGE

displayForm($originPage, $is_modified, 
            $isPart1, $isPart2, $isPart3, $isPart4);

?>

</div>

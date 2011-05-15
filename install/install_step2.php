<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install - Step 2");
   include 'install_header.inc.php';

   include 'install_menu.inc.php';
?>

<script language="JavaScript">

function setDatabaseInfo(){
   // check fields
   foundError = 0;
   msgString = "The following fields are missing:\n\n"

   if (0 == document.forms["databaseForm"].db_mantis_host.value)     { msgString += "Hostname\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_database.value)     { msgString += "Database\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_user.value)     { msgString += "User\n"; ++foundError; }
   //if (0 == document.forms["databaseForm"].db_mantis_password.value)     { msgString += Password"\n"; ++foundError; }

   if (0 == foundError) {
     document.forms["databaseForm"].action.value="setDatabaseInfo";
     document.forms["databaseForm"].submit();
   } else {
     alert(msgString);
   }
 }

</script>

<div id="content">


<?php

include_once 'install.class.php';

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "Step 1 finished";
   echo "<br/>";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>create CoDev Admin team</li>";
   echo "<li>set CoDev preferences</li>";
  echo "</ul>\n";
   echo "";
}


function displayForm($originPage, $adminTeamName, $adminTeamLeaderId,
            $g_eta_enum_string, $eta_balance_string,
            $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string) {

   echo "<form id='databaseForm' name='databaseForm' method='post' action='$originPage' >\n";

   echo "<hr align='left' width='20%'/>\n";
   echo "<h2>".T_("CoDev administration")."</h2>\n";

   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Admin team name")."</td>\n";
   echo "    <td><input size='20' type='text' name='adminTeamName'  id='adminTeamName' value='$adminTeamName'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Admin team leader")."</td>\n";
   echo "    <td><input size='20' type='text' name='adminTeamLeaderId'  id='adminTeamLeaderId' value='$adminTeamLeaderId'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   echo "<hr align='left' width='20%'/>\n";
   echo "<h2>".T_("Mantis ")."</h2>\n";

   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("ETA names")."</td>\n";
   echo "    <td><input size='150' type='text' name='g_eta_enum_string'  id='$g_eta_enum_string' value='$g_eta_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("ETA balance")."</td>\n";
   echo "    <td><input size='150' type='text' name='eta_balance_string'  id='eta_balance_string' value='$eta_balance_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Priority")."</td>\n";
   echo "    <td><input size='150' type='text' name='s_priority_enum_string'  id='s_priority_enum_string' value='$s_priority_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Resolve status")."</td>\n";
   echo "    <td><input size='150' type='text' name='s_resolution_enum_string'  id='s_resolution_enum_string' value='$s_resolution_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Status")."</td>\n";
   echo "    <td><input size='150' type='text' name='g_status_enum_string'  id='g_status_enum_string' value='$g_status_enum_string'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<input type=button value='".T_("Proceed Step 2")."' onClick='javascript: setDatabaseInfo()'>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step2.php";

$adminTeamName = T_("admin");
$adminTeamLeaderId = 1; // 1 is mantis administrator

// Values copied from:  mantis/lang/strings_english.txt
#$s_priority_enum_string   = '10:none,20:low,30:normal,40:high,50:urgent,60:immediate';
#$s_resolution_enum_string = '10:open,20:fixed,30:reopened,40:unable to reproduce,50:not fixable,60:duplicate,70:no change required,80:suspended,90:won\'t fix';
$s_priority_enum_string = '10:aucune,20:basse,30:normale,40:elevee,50:urgente,60:immediate';
$s_resolution_enum_string = '10:ouvert,20:resolu,30:rouvert,40:impossible a reproduire,50:impossible a corriger,60:doublon,70:pas un bogue,80:suspendu,90:ne sera pas resolu';

// Values copied from: mantis/config_inc.php
$g_eta_enum_string    = '10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days';
$eta_balance_string   = '10:1,20:1,30:3,40:5,50:10,60:15';

$g_status_enum_string = '10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed';

$codevReportsDir      = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";


$action      = $_POST[action];

displayStepInfo();

displayForm($originPage, $adminTeamName, $adminTeamLeaderId,
            $g_eta_enum_string, $eta_balance_string,
            $g_status_enum_string, $s_priority_enum_string, $s_resolution_enum_string);


if ("setDatabaseInfo" == $action) {

   $install = new Install();

   $adminTeamName = T_("admin");
   $adminTeamLeader = 1; // 1 is mantis administrator
   echo "DEBUG createAdminTeam  $adminTeamName  $adminTeamLeader<br/>";
   $install->createAdminTeam($adminTeamName, $adminTeamLeader);



}

?>


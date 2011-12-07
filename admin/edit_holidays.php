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

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}
?>

<?php
   $_POST['page_name'] = T_("CoDev Administration : Holidays");
   include 'header.inc.php';
?>
<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>


<script language="JavaScript">

function addHoliday() {

     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:\n\n"

     if ("" == document.forms["addHolidayForm"].hol_desc.value)     { msgString += "Holiday Description\n"; ++foundError; }

     if (0 == foundError) {
       document.forms["addHolidayForm"].action.value="addHoliday";
       document.forms["addHolidayForm"].submit();
     } else {
       alert(msgString);
     }

   }

function deleteHoliday(id, description){
   confirmString = "Desirez-vous vraiment supprimer definitivement cette date ?\n" + description;
   if (confirm(confirmString)) {
     document.forms["deleteHolidayForm"].action.value="deleteHoliday";
     document.forms["deleteHolidayForm"].hol_id.value=id;
     document.forms["deleteHolidayForm"].submit();
   }
 }

</script>


<?php
include_once "project.class.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "holidays.class.php";
require_once('tc_calendar.php');


// ----------------------------------------------------
function addHolidayForm($originPage, $defaultDate) {

   list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("../calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("../calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);


   #echo "<div style='text-align: center;'>";
   echo "<div>\n";

   echo "<form id='addHolidayForm' name='addHolidayForm' method='post' Action='$originPage'>\n";

   echo T_("Date").": ";
   $myCalendar->writeScript();

   echo("   ".T_("Description").": <input name='hol_desc' type='text' id='hol_desc'>\n");

   echo("   ".T_("Color").": <input name='hol_color' type='text' id='hol_color' value='".Holidays::$defaultColor."' title='format: ".Holidays::$defaultColor."' size='6'>\n");

   echo "   <input type=button name='btAddHoliday' value='".T_("Add")."' onClick='javascript: addHoliday()'>\n";

   echo "   &nbsp;&nbsp;&nbsp;<a href='http://www.colorschemer.com/online.html' target='_blank' title='".T_("open a colorPicker in a new Tab")."'>ColorPicker</A>";

   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "</form>\n";

   echo "</div>\n";
}

// ----------------------------------------------------
function displayHolidaysTuples() {

   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   //echo "<caption>Holidays</caption>\n";
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>".T_("Date")."</th>\n";
   echo "<th>".T_("Description")."</th>\n";
   echo "<th>".T_("Color")."</th>\n";
   echo "</tr>\n";

   #$holidays = new Holidays();

   $query     = "SELECT * ".
                "FROM `codev_holidays_table` ".
                "ORDER BY date DESC";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
   	$deleteDesc = date("d M Y", $row->date)." - ".$row->description;

      echo "<tr>\n";
      echo "<td>\n";
      echo "<a title='".T_("delete Holiday")."' href=\"javascript: deleteHoliday('".$row->id."', '$deleteDesc')\" ><img src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td title='$row->id'>".date("d M Y (D)", $row->date)."</td>\n";
      echo "<td title='$row->type'>".$row->description."</td>\n";
      echo "<td style='background-color: #".$row->color.";'>".$row->color."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";

   echo "<form id='deleteHolidayForm' name='deleteHolidayForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=hol_id   value='0'>\n";
   echo "</form>\n";

   echo "<div>\n";
}





// ================ MAIN =================

global $admin_teamid;

$originPage = "edit_holidays.php";

$defaultDate= date("Y-m-d", time());

$action     = isset($_POST['action']) ? $_POST['action'] : '';

// Admins only
$session_user = new User($_SESSION['userid']);

if (!$session_user->isTeamMember($admin_teamid)) {
   echo T_("Sorry, you need to be in the admin-team to access this page.");
   exit;
}

echo "<h2>".T_("Add fixed holidays")."</h2>\n";
echo "<br/>";
echo T_("In here you can set National Days, religious holidays, etc.")."<br/>";
echo "<br/>";
#echo T_("Note: adding RTTs is not a good idea, users may decide to work anyways and productionDaysForecast will be wrong.");
echo "<br/>";
echo "<br/>";
echo "<br/>";
addHolidayForm($originPage, $defaultDate);
#echo "<br/>";
#echo "<br/>";
#echo "<br/>";
echo "<br/>";
echo "<br/>";
displayHolidaysTuples();

echo "<br/>";


   // ----------- actions ----------
   if ($action == "addHoliday") {

      $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
      $timestamp = date2timestamp($formatedDate);

      $hol_date = $timestamp;
      $hol_desc = $_POST['hol_desc'];
      $hol_color = $_POST['hol_color'];

      // save to DB
      $query = "INSERT INTO `codev_holidays_table`  (`date`, `description`, `color`) VALUES ('$hol_date','$hol_desc','$hol_color');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");

      // reload page
      echo ("<script> parent.location.replace('$originPage'); </script>");

   } elseif ($action == "deleteHoliday") {
      $hol_id = $_POST['hol_id'];

      $query = "DELETE FROM `codev_holidays_table` WHERE id = $hol_id;";
      mysql_query($query) or die("Query failed: $query");

      // reload page
      echo ("<script> parent.location.replace('$originPage'); </script>");

   } elseif ($action == "addAstreinte") {
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

echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";

?>

<?php include 'footer.inc.php'; ?>


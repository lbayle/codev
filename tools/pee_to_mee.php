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

$_POST['page_name'] = T_("PrelEffortEstim to ManagerEffortEstim");
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
</script>

<div id="content">
<?php

include_once 'user.class.php';

$logger = Logger::getLogger("pee_to_mee");

function peeToMee() {
	Issue::getPrelEffortEstimValues();

	$prelEffortEstimCustomField = Config::getInstance()->getValue(Config::id_customField_PrelEffortEstim);
	$mgrEffortEstimCustomField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);

	$query  = "SELECT * FROM `mantis_custom_field_string_table` WHERE `field_id` = $prelEffortEstimCustomField";

	$result = mysql_query($query);
	$result = mysql_query($query);
    if (!$result) {
			echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
			$logger->error("Query FAILED: $query");
			$logger->error(mysql_error());
			exit;
	}
	while($row = mysql_fetch_object($result))
	{
		$meeValue = Issue::$PEE_balance[$row->value];
		echo "Issue $row->bug_id pee=<$row->value> mee=<$meeValue> <br>\n";

		$query2 = "INSERT INTO `mantis_custom_field_string_table`  (`field_id`, `bug_id`, `value`) VALUES ('".$mgrEffortEstimCustomField."','".$row->bug_id."','".$meeValue."');";
		$result2 = mysql_query($query2);
		if (!$result2) {
			echo "<span style='color:red'>ERROR: Query FAILED $query2</span>";
			$logger->error("Query FAILED: $query2");
			$logger->error(mysql_error());
			//exit;
		}
	}
}

// ================ MAIN =================

$originPage = "pee_to_mee.php";

peeToMee();

?>

</div>
<?php include 'footer.inc.php'; ?>

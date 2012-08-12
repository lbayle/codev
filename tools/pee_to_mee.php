<?php
require('../include/session.inc.php');

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

include_once('../path.inc.php');

include_once('i18n/i18n.inc.php');

include_once('classes/config.class.php');
include_once('classes/issue.class.php');
include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("pee_to_mee");

$_POST['page_name'] = T_("PrelEffortEstim to ManagerEffortEstim");
require_once('include/header.inc.php');

require_once('include/login.inc.php');
require_once('include/menu.inc.php');
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

   function peeToMee() {
      Issue::getPrelEffortEstimValues();

      $prelEffortEstimCustomField = Config::getInstance()->getValue(Config::id_customField_PrelEffortEstim);
      $mgrEffortEstimCustomField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);

      $query  = "SELECT * FROM `mantis_custom_field_string_table` WHERE `field_id` = $prelEffortEstimCustomField";

      $result = SqlWrapper::getInstance()->sql_query($query);
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $meeValue = Issue::$PEE_balance[$row->value];
         echo "Issue $row->bug_id pee=<$row->value> mee=<$meeValue> <br>\n";

         $query2 = "INSERT INTO `mantis_custom_field_string_table`  (`field_id`, `bug_id`, `value`) VALUES ('".$mgrEffortEstimCustomField."','".$row->bug_id."','".$meeValue."');";
         $result2 = SqlWrapper::getInstance()->sql_query($query2);
         if (!$result2) {
            echo "<span style='color:red'>ERROR: Query FAILED $query2</span>";
            //exit;
         }
      }
   }

   // ================ MAIN =================

   $originPage = "pee_to_mee.php";

   peeToMee();

   ?>

</div>
<?php include 'include/footer.inc.php'; ?>

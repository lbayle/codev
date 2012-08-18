<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

/*
 *
demander le projet, puis:

1) rename summary (except SideTasks Projects)
1.1) rename projects name (except SideTasks Projects)

2) rename ExtRef for all Issues
3) remove notes
4) remove attachments

5) rename users

 */

function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   return $result;
}

function create_fake_db($projectidList) {
   $i = 1;
   foreach($projectidList as $projid) {

      // change project name
      //$query  = "UPDATE SET `name`='Project_$projid' where `id`='$projid'";
      //$result = execQuery($query);

      $query  = "DELETE FROM `mantis_email_table` ";
      $result = execQuery($query);

      $query  = "SELECT * from `mantis_bug_table` WHERE `project_id`='$projid'";
      $result1 = execQuery($query);

      // clean project issues
      $i = 0;
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{

         $i++;

         echo "process project $projid issue $row->id <br>";


         $query  = "UPDATE `mantis_bug_table` SET `summary`='task p".$projid."_$i ' WHERE `id`='$row->id' ";
         $result = execQuery($query);

         $query  = "UPDATE `mantis_bug_text_table` SET `description`='this is a fake issue...' WHERE `id`='$row->bug_text_id' ";
         $result = execQuery($query);

         $query  = "DELETE FROM `mantis_bugnote_table` WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);

         $query  = "DELETE FROM `mantis_bug_file_table` WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);

         $query  = "UPDATE `mantis_bug_revision_table` SET `value` = 'revision on fake issue' WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);
      }
   }

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
   }
}

// ================ MAIN =================
$logger = Logger::getLogger("create_fake_db");

$projectidList = array(14,16,18,19,23,24,25,39);

create_fake_db($projectidList);

?>

<?php

include_once('../include/session.inc.php');

/*
  This file is part of CodevTT.

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




function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query<br/>" . mysql_error() . "</span>";
      exit;
   }
   return $result;
}



// =========== MAIN ==========
$logger = Logger::getLogger("versionUpdater");

// check removed issues
echo "<br>=================<br>Check issues to remove from Command<br>";
$query0 = "SELECT command_id, bug_id FROM codev_command_bug_table WHERE bug_id NOT IN (SELECT id FROM mantis_bug_table)";
$result0 = execQuery($query0);
while ($row = SqlWrapper::getInstance()->sql_fetch_object($result0)) {
   echo "ERROR issue $row->bug_id does not exist in Mantis but is still defined in Command $row->command_id<br>";

   // remove from Command
   $query = "DELETE FROM `codev_command_bug_table` WHERE bug_id = ".$row->bug_id.";";
   $result = execQuery($query);
}

// check removed issues
echo "<br>=================<br>Check issues to remove from WBS<br>";
$query0 = "SELECT root_id, bug_id FROM codev_wbs_table WHERE bug_id NOT IN (SELECT id FROM mantis_bug_table)";
$result0 = execQuery($query0);
while ($row = SqlWrapper::getInstance()->sql_fetch_object($result0)) {
   echo "ERROR issue $row->bug_id does not exist in Mantis but is still defined in WBS (root = $row->root_id)<br>";

   // remove from WBS
   $query = "DELETE FROM `codev_wbs_table` WHERE bug_id = ".$row->bug_id.";";
   $result = execQuery($query);
}


// check that all Command issues are declared in the Command WBS.

// 1) foreach command
$query = "SELECT id, name, wbs_id FROM `codev_command_table`;";
$result = execQuery($query);
while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

   $cmdid = $row->id;
   $wbsid = $row->wbs_id;
   echo "<br>=================<br>Check Command $cmdid ($row->name) with WBS $wbsid<br>";

   if (is_null($wbsid)) {
      echo "Command has no WBS<br>";
      continue;
   }

   // 2) get all issues declared in Cmd
   $query2 = "SELECT `bug_id` FROM `codev_command_bug_table` WHERE `command_id` = $cmdid;";
   $result2 = execQuery($query2);
   $cmdBugidList = array();
   while ($row2 = SqlWrapper::getInstance()->sql_fetch_object($result2)) {
      $cmdBugidList[] = $row2->bug_id;

   }

   // 3) get all issues declared in WBS
   $query3 = "SELECT `bug_id` FROM `codev_wbs_table` WHERE `root_id`  = $wbsid;";
   $result3 = execQuery($query3);
   $wbsBugidList = array();
   while ($row3 = SqlWrapper::getInstance()->sql_fetch_object($result3)) {
      $wbsBugidList[] = $row3->bug_id;
   }

   // 4) for each cmd issue, check if present in wbs
   foreach ($cmdBugidList as $bid) {
      if (!in_array($bid, $wbsBugidList)) {
         echo "<br>ERROR issue $bid missing in WBS !<br>";

         try {
            $issue = IssueCache::getInstance()->getIssue($bid);
         } catch (Exception $e) {
            echo "ERROR issue $bid does not exist in Mantis !</span><br>";
         }
      } else {
         echo "$bid, ";
      }
   }

}

echo "<br>===================<br>CHECK DONE.";

// "SELECT * FROM codev_command_bug_table WHERE bug_id NOT IN (SELECT id FROM mantis_bug_table)";



?>

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


$errors = array();
$logger = Logger::getLogger("checkWBS");

function logMessage($bugid, $errMsg) {
   global $errors;
   #echo "$errMsg<br>";
   $errors[] = array('bug_id' => $bugid, 'errMsg' => $errMsg);
}


// =========== MAIN ==========


if (Tools::isConnectedUser()) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {

   $sql = AdodbWrapper::getInstance();

   // check removed issues
   echo "<br>=================<br>Check issues to remove from Command (removed from mantis)<br>";
   $query0 = "SELECT command_id, bug_id FROM codev_command_bug_table WHERE bug_id NOT IN (SELECT id FROM {bug})";
   $result0 = $sql->sql_query($query0);
   while ($row = $sql->fetchObject($result0)) {
      $errMsg = "ERROR issue $row->bug_id does not exist in Mantis but is still defined in Command $row->command_id";
      logMessage($row->bug_id, $errMsg);

      // remove from Command
      $query = "DELETE FROM codev_command_bug_table WHERE bug_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($row->bug_id));
   }

   // check removed issues
   echo "Check issues to remove from WBS (removed from mantis)<br>";
   $query0 = "SELECT root_id, bug_id FROM codev_wbs_table WHERE bug_id NOT IN (SELECT id FROM {bug})";
   $result0 = $sql->sql_query($query0);
   while ($row = $sql->fetchObject($result0)) {
      $errMsg = "ERROR issue $row->bug_id does not exist in Mantis but is still defined in WBS (root = $row->root_id)";
      logMessage($row->bug_id, $errMsg);

      // remove from WBS
      $query = "DELETE FROM codev_wbs_table WHERE bug_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($row->bug_id));
   }


   // check that all Command issues are declared in the Command WBS.

      echo "<br>=================<br>Check Commands<br>";
   // 1) foreach command
   $query = "SELECT id, name, wbs_id FROM codev_command_table";
   $result = $sql->sql_query($query);
   while ($row = $sql->fetchObject($result)) {

      $cmdid = $row->id;
      $cmdName = $row->name;
      $wbsid = $row->wbs_id;
      #echo "<br>=================<br>Check Command $cmdid ($row->name) with WBS $wbsid<br>";

      if (is_null($wbsid)) {
         echo "Command $cmdid ($row->name) has no WBS<br>";
         continue;
      }

      // 2) get all issues declared in Cmd
      $query2 = "SELECT bug_id FROM codev_command_bug_table WHERE command_id = ".$sql->db_param();
      $result2 = $sql->sql_query($query2, array($cmdid));
      $cmdBugidList = array();
      while ($row2 = $sql->fetchObject($result2)) {
         $cmdBugidList[] = $row2->bug_id;

      }

      // 3) get all issues declared in WBS
      $query3 = "SELECT bug_id FROM codev_wbs_table WHERE root_id  = ".$sql->db_param();
      $result3 = $sql->sql_query($query3, array($wbsid));
      $wbsBugidList = array();
      while ($row3 = $sql->fetchObject($result3)) {
         $wbsBugidList[] = $row3->bug_id;
      }

      // 4) for each cmd issue, check if present in wbs
      foreach ($cmdBugidList as $bid) {
         if (!in_array($bid, $wbsBugidList)) {
            logMessage($bid, "ERROR issue $bid missing in WBS !");
            try {
               $issue = IssueCache::getInstance()->getIssue($bid);

               // try to fix (add issue to WBS)
               try {
                  $wbsChild = new WBSElement(NULL, $wbsid, $bid, $wbsid);
                  logMessage($bid, "INFO Add issue $bid to WBS $wbsid");
               } catch (Exception $e) {
                  logMessage($bid, "ERROR Could not add issue $bid to WBS $wbsid");
               }
            } catch (Exception $e) {
               logMessage($bid, "ERROR issue $bid does not exist in Mantis !");
            }
         } else {
            #echo "$bid, ";
         }
      }

      // 5) for each wbs issue, check if present in command
      if (0 !== count($wbsBugidList)) {
         foreach ($wbsBugidList as $wbs_bid) {
            if ( (NULL !== $wbs_bid) && (!in_array($wbs_bid, $cmdBugidList))) {
               // issue is declared in WBS but not in command: remove from WBS !
               logMessage($wbs_bid, "ERROR issue $wbs_bid is declared in WBS but not in command $cmdid : $cmdName");
               // remove from WBS
               $query4 = "DELETE FROM codev_wbs_table WHERE root_id = ".$sql->db_param().
                         " AND bug_id = ".$sql->db_param();
               $result4 = $sql->sql_query($query4, array($wbsid, $wbs_bid));

            }
         }
      }
   }

   echo "<br>===================<br>CHECK DONE.<br><br>";

   if (0 !== count($errors)) {
      $userid = $_SESSION['userid'];
      $nbErr = \count($errors);
      $formattedMsg = "=== checkWBS === (userid = $userid, nbErrors=$nbErr)\n";
      foreach($errors as $err) {
         $msg = '['.$err['bug_id'].'] '. $err['errMsg'];
         $formattedMsg .= $msg."\n";
         echo $msg."<br>";
      }
      $logger->error($formattedMsg);
   }
   

   // "SELECT * FROM codev_command_bug_table WHERE bug_id NOT IN (SELECT id FROM {bug})";
  } else {
     echo "Sorry, you're not identified as a CodevTT administrator.";
  }
} else {
     echo "Please login as CodevTT administrator.";
}



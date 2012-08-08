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

require('include/super_header.inc.php');

require_once('lib/log4php/Logger.php');

function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   return $result;
}


function move_st_cat_tuples() {
   global $logger;

   $mapping = array(
      "cat_inactivity" => Project::cat_st_inactivity,
      "cat_onduty"     => Project::cat_st_onduty,
      "cat_incident"   => Project::cat_st_incident,
      "cat_tools"      => Project::cat_st_tools,
      "cat_workshop"   => Project::cat_st_workshop,
      "cat_management" => Project::cat_mngt_regular
   );

   $query  = "SELECT * FROM `codev_sidetasks_category_table` ORDER BY project_id ASC";
   $result = execQuery($query);

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result))	{
      echo "project $row->project_id<br>";
      echo "   cat_inactivity = $row->cat_inactivity<br>";
      if (NULL != $row->cat_inactivity) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_inactivity','".Project::cat_st_inactivity."');";

         $logger->debug("$query2");
         $result2 = execQuery($query2);
      }

      echo "   cat_onduty = $row->cat_onduty<br>";
      if (NULL != $row->cat_onduty) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_onduty','".Project::cat_st_onduty."');";
         $logger->debug( "$query2");
         $result2 = execQuery($query2);
      }

      echo "   cat_incident = $row->cat_incident<br>";
      if (NULL != $row->cat_incident) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_incident','".Project::cat_st_incident."');";

         $logger->debug( "$query2");
         $result2 = execQuery($query2);
      }

      echo "   cat_tools = $row->cat_tools<br>";
      if (NULL != $row->cat_tools) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_tools','".Project::cat_st_tools."');";

         $logger->debug( "$query2");
         $result2 = execQuery($query2);
      }

      echo "   cat_workshop = $row->cat_workshop<br>";
      if (NULL != $row->cat_workshop) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_workshop','".Project::cat_st_workshop."');";

         $logger->debug( "$query2");
         $result2 = execQuery($query2);
      }

      echo "cat_management = $row->cat_management<br>";
      if (NULL != $row->cat_management) {
         $query2 = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) ".
            "VALUES ('$row->project_id','$row->cat_management','".Project::cat_mngt_regular."');";

         $logger->debug( "$query2");
         $result2 = execQuery($query2);
      }

   }

}

// ================ MAIN =================
$logger = Logger::getLogger("move_st_cat_tuples");

move_st_cat_tuples();

?>

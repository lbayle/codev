<?php

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

/**
 * This is a toolbox fot the plugin
 *
 */
class IssueMantisPluginHelper {

   const id_customField_backlog = "customField_backlog"; //  see codevtt/classes/config.class.php

   private $id;

   public function __construct($id) {

      if (is_null($id) || (0 == $id)) {
         $e = new Exception("Creating a Issue with id=0 is not allowed.");
         throw $e;
      }

      $this->id = $id;
   }

   /**
    * updates DB with new value
    * @param int $backlog
    * @throw exception on failure
    */
   public function setBacklog($backlog) {

      $old_backlog = NULL;
      
      $query = "SELECT value FROM `codev_config_table` WHERE config_id = '".IssueMantisPluginHelper::id_customField_backlog."'";
      $result = mysql_query($query);
      if (!$result) {
         $e = new Exception("Query FAILED: ".$query);
         throw $e;
      }
      $backlogCustomField    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;

      // TODO should be done only once...
      $query = "SELECT name FROM `mantis_custom_field_table` WHERE id = '$backlogCustomField'";
      $result = mysql_query($query);
      if (!$result) {
         $e = new Exception("Query FAILED: ".$query);
         throw $e;
      }
      $field_name    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "Backlog (BL)";

      // check if backlog already defined for this issue
      $query = "SELECT value FROM `mantis_custom_field_string_table` WHERE bug_id=$this->id AND field_id = '$backlogCustomField'";
      $result = mysql_query($query);
      if (!$result) {
         $e = new Exception("Query FAILED: ".$query);
         throw $e;
      }
      if (0 != mysql_num_rows($result)) {
         $old_backlog = mysql_result($result, 0);
         $query2 = "UPDATE `mantis_custom_field_string_table` SET value = '$backlog' WHERE bug_id=$this->id AND field_id = $backlogCustomField";
      } else {
         $old_backlog = '';
         $query2 = "INSERT INTO `mantis_custom_field_string_table` (`field_id`, `bug_id`, `value`) VALUES ('$backlogCustomField', '$this->id', '$backlog');";
      }
      $result2 = mysql_query($query2);
      if (!$result2) {
         $e = new Exception("Query FAILED: ".$query);
         throw $e;
      }
      // Add to history
      if ("$old_backlog" != "$backlog") {
         $userid = current_user_get_field( 'id' );
         $now = time();
         $query = "INSERT INTO `mantis_bug_history_table`  (`user_id`, `bug_id`, `field_name`, `old_value`, `new_value`, `type`, `date_modified`) ".
                  "VALUES ('$userid','$this->id','$field_name', '$old_backlog', '$backlog', '0', '".$now."');";
         $result = mysql_query($query);
         if (!$result) {
            $e = new Exception("Query FAILED: ".$query);
            throw $e;
         }
      }
      // no need to update lastUpdated field
   }



}

?>

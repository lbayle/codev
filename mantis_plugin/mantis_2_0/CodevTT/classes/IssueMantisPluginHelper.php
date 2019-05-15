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
 * This is a toolbox for the CodevTT plugin
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
      
      $query = "SELECT value FROM codev_config_table WHERE config_id = ". db_param();
      $result = db_query($query, array( IssueMantisPluginHelper::id_customField_backlog ));
	  $row = db_fetch_array( $result );

	  if( $row )
		$backlogCustomField    = $row['value'];
	  else
	    $backlogCustomField    = 0;

      // TODO should be done only once...
      $query = "SELECT name FROM {custom_field} WHERE id = " . db_param();
      $result = db_query($query, array($backlogCustomField));
      $row = db_fetch_array( $result );

	  if( $row )
        $field_name    = $row['name'];
      else
	    $field_name = "Backlog (BL)";

      // check if backlog already defined for this issue
      $query = "SELECT value FROM {custom_field_string} WHERE bug_id=" . db_param() . " AND field_id = " . db_param();
      $result = db_query($query, array( $this->id, $backlogCustomField ) );
      $row = db_fetch_array( $result );
	  
      if ($row) {
         $old_backlog = $row['value'];
         $query2 = "UPDATE {custom_field_string} SET value = " . db_param() . " WHERE bug_id=" . db_param() . " AND field_id = " . db_param();
		 $result2 = db_query($query2, array($backlog, $this->id,$backlogCustomField));
      } else {
         $old_backlog = '';
         $query2 = "INSERT INTO {custom_field_string} (field_id, bug_id, value) VALUES (" . db_param() . ", " . db_param() . ", " . db_param() . ")";
		 $result2 = db_query($query2, array( $backlogCustomField, $this->id, $backlog ));
      }
      

      // Add to history
      if ("$old_backlog" != "$backlog") {
         $userid = current_user_get_field( 'id' );
         $now = time();
         $query = "INSERT INTO {bug_history}  (user_id, bug_id, field_name, old_value, new_value, type, date_modified) ".
                  "VALUES (" . db_param() . "," . db_param() . "," . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ")";
         $result = db_query($query, array( $userid, $this->id, $field_name, $old_backlog, $backlog, 0, $now  ));
      }
      // no need to update lastUpdated field
   }
}



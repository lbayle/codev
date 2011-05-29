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

<?php

// ==============================================================

class ConfigMantisItem {

   public $config_id;
   public $project_id;
   public $user_id;
   public $access_reqd;
   public $type;
   public $value;

	public function __construct($config_id, $project_id, $user_id, $access_reqd, $type, $value)
   {
      $this->config_id    = $config_id;
      $this->project_id   = $project_id;
      $this->user_id      = $user_id;
      $this->access_reqd  = $access_reqd;
      $this->type         = $type;
      $this->value        = $value;
   }
} // class

/**
 * Easy access to Mantis configuration variables
 * mantis_config_table
 */
class ConfigMantis {

   // TODO: add Mantis config types & ids
   const  configType_int = 1;

   const id_database_version              = "database_version";
   const id_bug_resolved_status_threshold = "bug_resolved_status_threshold";

   private static $instance;    // singleton instance
   private static $configVariables;

   // --------------------------------------
   private function __construct()
   {
      self::$configVariables = array();

      $query = "SELECT * FROM `mantis_config_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	#echo "DEBUG: ConfigMantis:: $row->config_id = $row->value<br/>";
      	self::$configVariables["$row->config_id"] = new ConfigMantisItem($row->config_id, $row->project_id, $row->user_id, $row->access_reqd, $row->type, $row->value);
      }

        #echo "DEBUG: ConfigMantis ready<br/>";
   }

   // ------------------------------------
   /**
    * get Singleton instance
    */
   public static function getInstance()
   {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
   }

   // --------------------------------------
   public static function getValue($id) {

    	$value = NULL;
    	$variable = self::$configVariables[$id];

    	if (NULL != $variable) {
            $value = $variable->value;
    	} else {
    		echo "DEBUG: ConfigMantis::getValue($id): variable not found !<br/>";
    	}
    	return $value;
   }

   // --------------------------------------
   public static function getType($id) {

      $type = NULL;
      $variable = self::$configVariables[$id];

      if (NULL != $variable) {
         $type = $variable->type;
      } else {
         echo "DEBUG: ConfigMantis::getType($id): variable not found !<br/>";
      }
      return $type;
   }

} // class

?>
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
   const id_bugResolvedStatusThreshold    = "bug_resolved_status_threshold";

   private static $instance;    // singleton instance
   private static $configVariables;

   private $logger;

   // --------------------------------------
   private function __construct()
   {
      $this->logger = Logger::getLogger(__CLASS__);

      self::$configVariables = array();

      $query = "SELECT * FROM `mantis_config_table`";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
      	$key = $row->config_id."_".$row->project_id;
      	#echo "DEBUG: ConfigMantis:: $row->config_id = $row->value<br/>";
      	self::$configVariables["$key"] = new ConfigMantisItem($row->config_id, $row->project_id, $row->user_id, $row->access_reqd, $row->type, $row->value);
      }

        #echo "DEBUG: ConfigMantis ready<br/>";
        #print_r(array_keys(self::$configVariables));
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
   public static function getValue($id, $project_id = 0) {

    	$value = NULL;

      	$key = $id."_".$project_id;
    	$variable = self::$configVariables[$key];

    	if (NULL != $variable) {
            $value = $variable->value;
    	} else {
    		echo "<span class='error_font'>WARN: ConfigMantis::getValue($id, $project_id): variable not found !</span><br/>";
    	}
    	return $value;
   }

   // --------------------------------------
   public static function getType($id, $project_id = 0) {

      $type = NULL;
      $key = $id."_".$project_id;
      $variable = self::$configVariables[$key];

      if (NULL != $variable) {
         $type = $variable->type;
      } else {
         echo "<span class='error_font'>WARN: ConfigMantis::getType($id, $project_id): variable not found !</span><br/>";
      }
      return $type;
   }

   // --------------------------------------
   public static function isValueDefined($id, $project_id = 0) {

      $key = $id."_".$project_id;
      $variable = self::$configVariables[$key];

      return (NULL == $variable) ? FALSE : TRUE;
   }

   // --------------------------------------
   /**
    * Add Item in Cache and mantis DB (only if NOT exists).
    *
    * NOTE: the Mantis DB will NOT be modified if the value exists.
    */
   public static function setValue($config_id, $value, $type, $project_id = 0, $user_id = 0, $access_reqd = 90) {

      $query = "SELECT * FROM `mantis_config_table` WHERE config_id='$config_id' AND project_id='$project_id'";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      if (0 == mysql_num_rows($result)) {

         echo "DEBUG INSERT ConfigMantis::setValue $config_id: $value (type=$type)<br/>";

         // --- add to DB
         $query = "INSERT INTO `mantis_config_table` (`config_id`, `project_id`, `user_id`, `access_reqd`, `type`, `value`) ".
                  "VALUES ('$config_id', '$project_id', '$user_id', '$access_reqd', '$type', '$value');";
         $result = mysql_query($query);
	      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }


         // --- add/replace Cache
         $key = $config_id."_".$project_id;
         self::$configVariables["$key"] = new ConfigMantisItem($config_id, $project_id, $user_id, $access_reqd, $type, $value);
      } else {
         echo "<span class='error_font'>WARN: ConfigMantis::setValue($config_id, $project_id): variable already exists and will NOT be modified.</span><br/>";
      }

   }


} // class

?>
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


/**
 * Constants Singleton class
 * contains CoDev settings
 * @author lbayle
 *
 */

class ConfigItem {

   public $id;
   public $value;
   public $type;

	public function __construct($id, $value, $type)
   {
   	$this->id    = $id;
      $this->type  = $type;

      switch ($type) {
      	case Config::configType_keyValue :
      		$this->value = doubleExplode(':', ',', $value);
      		break;
         case Config::configType_array :
         	$this->value = explode(',', $value);
            break;
         default:
         	$this->value = $value;
      }
   }

}

class Config {

   const  configType_int      = 1;
   const  configType_string   = 2;
   const  configType_keyValue = 3;
   const  configType_array    = 4;

   // known Config ids
   const id_defaultSideTaskProject   = "defaultSideTaskProject";
   const id_jobSupport               = "job_support";
   const id_adminTeamId              = "adminTeamId";
   const id_statusNames              = "statusNames";
   const id_astreintesTaskList       = "astreintesTaskList";
   const id_codevReportsDir          = "codevReportsDir";
   const id_customField_TC           = "customField_TC";
   const id_customField_effortEstim  = "customField_effortEstim"; //  BI
   const id_customField_remaining    = "customField_remaining"; //  RAE
   const id_customField_deadLine     = "customField_deadLine";
   const id_customField_addEffort    = "customField_addEffort"; // BS
   const id_customField_deliveryId   = "customField_deliveryId"; // FDL (id of the associated Delivery Issue)
   const id_customField_deliveryDate = "customField_deliveryDate";

   private static $instance;    // singleton instance
   private static $configItems;

   // --------------------------------------
   private function __construct()
   {
      self::$configItems = array();

      $query = "SELECT * FROM `codev_config_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	#echo "DEBUG: Config:: $row->config_id<br/>";
      	self::$configItems["$row->config_id"] = new ConfigItem($row->config_id, $row->value, $row->type);
      }

        #echo "DEBUG: Config ready<br/>";
   }

   // --------------------------------------
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

    	$value == NULL;
    	$item = self::$configItems[$id];

    	if (NULL != $item) {
         $value = $item->value;
    	} else {
    		echo "DEBUG: Config::getValue($id): item not found !<br/>";
    	}
    	return $value;
   }

   // --------------------------------------
   public static function getType($id) {

      $type == NULL;
      $item = self::$configItems[$id];

      if (NULL != $item) {
         $type = $item->type;
      } else {
         echo "DEBUG: Config::getType($id): item not found !<br/>";
      }
      return $value;
   }

   // --------------------------------------
   /**
    * Add or update an Item (in DB and Cache)
    *
    * Note: update does not change the type.
    *
    * @param string $id
    * @param string $value
    * @param int    $type
    * @param string $desc
    */
   public static function setValue($id, $value, $type, $desc=NULL) {

   	  // add/update DB
      $query = "SELECT * FROM `codev_config_table` WHERE config_id='$id'";
      $result = mysql_query($query) or die("Query failed: $query");
      if (0 != mysql_num_rows($result)) {
         $query = "UPDATE `codev_config_table` SET value = '$value' WHERE config_id='$id'";
         #echo "DEBUG UPDATE Config::addValue $id: $value (t=$type) $desc<br/>";
      } else {
         $query = "INSERT INTO `codev_config_table` (`config_id`, `value`, `type`, `desc`) VALUES ('$id', '$value', '$type', '$desc');";
         #echo "DEBUG INSERT Config::addValue $id: $value (t=$type) $desc<br/>";
      }

      $result    = mysql_query($query) or die("Query failed: $query");

      // --- add/replace Cache
      self::$configItems["$id"] = new ConfigItem($id, $value, $type);

   }

   /**
    * removes a ConfigItem from DB and Cache
    */
   public static function deleteValue($id) {

	  if (NULL != self::$configItems[$id]) {

         // delete from DB
         $query = "DELETE FROM `codev_config_table` WHERE config_id = '$id';";
         mysql_query($query) or die("Query failed: $query");

         // remove from cache
	     unset(self::$configItems["$id"]);
	  } else {
	  	echo "DEBUG DELETE item not found in cache !<br/>";
	  }
   }
} // class

?>
<?php
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

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("default");
   $logger->info("LOG activated !");
}

/**
 * Constants Singleton class
 * contains CoDev settings
 * @author lbayle
 */
class ConfigItem {

   public $id;
   public $value;
   public $type;

   public function __construct($id, $value, $type) {
      $this->id = $id;
      $this->type = $type;

      switch ($type) {
         case Config::configType_keyValue :
            $this->value = (NULL != $value) ? $this->value = doubleExplode(':', ',', $value) : NULL;
            break;
         case Config::configType_array :
            $this->value = (NULL != $value) ? $this->value = explode(',', $value) : NULL;
            break;
         default:
            $this->value = $value;
      }
   }

   /**
    * @param $key
    * @return mixed
    */
   public function getArrayValueFromKey($key) {
      return (Config::configType_keyValue == $this->type) ? $this->value[$key] : NULL;
   }

   /**
    * @param $value
    * @return mixed
    */
   public function getArrayKeyFromValue($value) {
      return (Config::configType_keyValue == $this->type) ? array_search($value, $this->value) : NULL;
   }

}

/**
 * Example:
 * $admin_teamid = Config::getInstance()->getValue(Config::id_adminTeamId);
 */
class Config {

   const configType_int      = 1;
   const configType_string   = 2;
   const configType_keyValue = 3;
   const configType_array    = 4;

   // known Config ids
   const id_externalTasksProject     = "externalTasksProject";
   const id_externalTask_leave       = "externalTask_leave";
   const id_jobSupport               = "job_support";
   const id_adminTeamId              = "adminTeamId";
   const id_statusNames              = "statusNames";
   const id_astreintesTaskList       = "astreintesTaskList";
   const id_codevReportsDir          = "codevReportsDir";
   const id_customField_ExtId        = "customField_ExtId";
   const id_customField_MgrEffortEstim = "customField_MgrEffortEstim";  // ex ETA/PrelEffortEstim
   const id_customField_effortEstim  = "customField_effortEstim"; //  BI
   const id_customField_remaining    = "customField_remaining"; //  RAE
   const id_customField_deadLine     = "customField_deadLine";
   const id_customField_addEffort    = "customField_addEffort"; // BS
   const id_customField_deliveryId   = "customField_deliveryId"; // FDL (id of the associated Delivery Issue)
   const id_customField_deliveryDate = "customField_deliveryDate";
   const id_priorityNames            = "priorityNames";
   const id_resolutionNames          = "resolutionNames";
   const id_mantisFile_strings       = "mantisFile_strings";
   const id_mantisFile_custom_strings = "mantisFile_custom_strings";
   const id_mantisPath               = "mantisPath";
   const id_bugResolvedStatusThreshold = "bug_resolved_status_threshold";
   const id_timetrackingFilters      = "timetrackingFilters";
   const id_blogCategories           = "blogCategories";

   const id_ClientTeamid             = "client_teamid"; // FDJ_teamid

   /**
    * @var Config singleton instance
    */
   private static $instance;

   /**
    * @var ConfigItem[]
    */
   private static $configVariables;

   private static $quiet; // do not display any warning message (used for install procedures only)

   /**
    * @var Logger The logger
    */
   private $logger;

   /**
    * Private constructor to respect the singleton pattern
    */
   private function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

      self::$configVariables = array();

      $query = "SELECT * FROM `codev_config_table`";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = mysql_fetch_object($result)) {
         $this->logger->debug("id=$row->config_id, val=$row->value, type=$row->type");
         self::$configVariables[$row->config_id] = new ConfigItem($row->config_id, $row->value, $row->type);
      }

      $this->logger->trace("Config ready");
   }

   /**
    * The singleton pattern
    * @static
    * @return Config
    */
   public static function getInstance() {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }

   /**
    * If true, then no info/warning messages will be displayed.
    * this shall only be set during install procedures.
    * @static
    * @param bool $isQuiet
    */
   public static function setQuiet($isQuiet = false) {
      self::$quiet = $isQuiet;
   }

   /**
    * @static
    * @param $id
    * @return null
    */
   public static function getValue($id) {
      global $logger;

      $value = NULL;
      $variable = isset(self::$configVariables[$id]) ? self::$configVariables[$id] : NULL;

      if (NULL != $variable) {
         $value = $variable->value;
      } else {
         $logger->warn("getValue($id): variable not found !");
         if (!self::$quiet) {
            echo "<span class='warn_font'>WARN: Config::getValue($id): variable not found !</span><br/>";
         }
      }
      return $value;
   }

   /**
    * if the variable type is a configType_keyValue,
    * returns the key for a given value.
    * example: $status_new = Config::getVariableKeyFromValue(Config::id_statusNames, 'new');
    * @static
    * @param $id
    * @param $value
    * @return mixed
    */
   public static function getVariableKeyFromValue($id, $value) {
      global $logger;

      $key = NULL;
      $variable = self::$configVariables[$id];
      if (NULL != $variable) {
         $key = $variable->getArrayKeyFromValue($value);
      } else {
         $logger->warn("getVariableKeyFromValue($id, $value): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getVariableKeyFromValue($id, $value): variable not found !</span><br/>";
         }
      }
      return $key;
   }

   /**
    * if the variable type is a configType_keyValue,
    * returns the value for a given key.
    * @static
    * @param $id
    * @param $key
    * @return mixed
    */
   public static function getVariableValueFromKey($id, $key) {
      global $logger;

      $value = NULL;
      $variable = self::$configVariables[$id];

      if (NULL != $variable) {
         $value = $variable->getArrayValueFromKey($key);
      } else {
         $logger->warn("getVariableValueFromKey($id, $key): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getVariableValueFromKey($id, $key): variable not found !</span><br/>";
         }
      }
      return $value;
   }

   /**
    * @static
    * @param $id
    * @return string
    */
   public static function getType($id) {
      global $logger;
      $type = NULL;
      $variable = self::$configVariables[$id];

      if (NULL != $variable) {
         $type = $variable->type;
      } else {
         $logger->warn("getType($id): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getType($id): variable not found !</span><br/>";
         }
      }
      return $type;
   }

   /**
    * Add or update an Item (in DB and Cache)
    * Note: update does not change the type.
    * @static
    * @param string $id
    * @param string $value
    * @param int $type
    * @param string $desc
    * @param int $project_id
    * @param int $user_id
    * @param int $team_id
    */
   public static function setValue($id, $value, $type, $desc=NULL, $project_id=0, $user_id=0, $team_id=0) {
      global $logger;

      $formattedValue = mysql_real_escape_string($value);
      $formattedDesc = mysql_real_escape_string($desc);

      // add/update DB
      $query = "SELECT * FROM `codev_config_table` ".
         "WHERE config_id='$id' ".
         "AND project_id=$project_id ".
         "AND user_id=$user_id ".
         "AND team_id=$team_id ";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 != mysql_num_rows($result)) {
         $query = "UPDATE `codev_config_table` ".
            "SET value = '$formattedValue' ".
            "WHERE config_id='$id' ".
            "AND project_id=$project_id ".
            "AND user_id=$user_id ".
            "AND team_id=$team_id ";
         $logger->debug("UPDATE setValue $id: $value (t=$type) $desc");
         $logger->debug("UPDATE query = $query");
      } else {
         $query = "INSERT INTO `codev_config_table` ".
            "(`config_id`, `value`, `type`, `desc`, `project_id`, `user_id`, `team_id`) ".
            "VALUES ('$id', '$formattedValue', '$type', '$formattedDesc', '$project_id', '$user_id', '$team_id');";
         $logger->debug("INSERT Config::setValue $id: $value (t=$type) $desc");
         $logger->debug("INSERT query = $query");
      }

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // add/replace Cache
      self::$configVariables["$id"] = new ConfigItem($id, $value, $type);
   }

   /**
    * removes a ConfigItem from DB and Cache
    * @static
    * @param $id
    */
   public static function deleteValue($id) {
      global $logger;

      if (NULL != self::$configVariables[$id]) {

         // delete from DB
         $query = "DELETE FROM `codev_config_table` WHERE config_id = '$id';";
         $result = mysql_query($query);
         if (!$result) {
            $logger->error("Query FAILED: $query");
            $logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // remove from cache
         unset(self::$configVariables["$id"]);
      } else {
         $logger->warn("DELETE variable <$id> not found in cache !");
      }
   }
}

?>

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

include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

class ConfigMantisItem {

   public $config_id;
   public $project_id;
   public $user_id;
   public $access_reqd;
   public $type;
   public $value;

   public function __construct($config_id, $project_id, $user_id, $access_reqd, $type, $value) {
      $this->config_id = $config_id;
      $this->project_id = $project_id;
      $this->user_id = $user_id;
      $this->access_reqd = $access_reqd;
      $this->type = $type;
      $this->value = $value;
   }
}

/**
 * Easy access to Mantis configuration variables
 * mantis_config_table
 */
class ConfigMantis {
   // TODO: add Mantis config types & ids
   const  configType_int = 1;

   const id_database_version = "database_version";
   const id_bugResolvedStatusThreshold = "bug_resolved_status_threshold";

   private static $instance;    // singleton instance
   private static $configVariables;

   private static $quiet; // do not display any warning message

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   private function __construct() {
      self::$configVariables = array();
      self::$quiet = true;

      $query = "SELECT * FROM `mantis_config_table`";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         if (!self::$quiet) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
         }
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $key = $row->config_id."_".$row->project_id;
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("$row->config_id = $row->value");
         }
         self::$configVariables["$key"] = new ConfigMantisItem($row->config_id, $row->project_id, $row->user_id, $row->access_reqd, $row->type, $row->value);
      }

      if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
         self::$logger->trace("ConfigMantis ready");
      }
      #print_r(array_keys(self::$configVariables));
   }

   /**
    * get Singleton instance
    * @static
    * @return ConfigMantis
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

   public function getValue($id, $project_id = 0) {
      $value = NULL;

      $key = $id."_".$project_id;
      $variable = self::$configVariables[$key];

      if (NULL != $variable) {
         $value = $variable->value;
      } else {
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: ConfigMantis::getValue($id, $project_id): variable not found !</span><br/>";
         }
         self::$logger->warn("getValue($id, $project_id): variable not found !");
      }
      return $value;
   }

   public function getType($id, $project_id = 0) {
      $type = NULL;
      $key = $id."_".$project_id;
      $variable = self::$configVariables[$key];

      if (NULL != $variable) {
         $type = $variable->type;
      } else {
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: ConfigMantis::getType($id, $project_id): variable not found !</span><br/>";
         }
         self::$logger->warn("getType($id, $project_id): variable not found !");
      }
      return $type;
   }

   public function isValueDefined($id, $project_id = 0) {
      $key = $id."_".$project_id;
      $variable = self::$configVariables[$key];

      return (NULL == $variable) ? FALSE : TRUE;
   }

   /**
    * Add Item in Cache and mantis DB (only if NOT exists).
    *
    * NOTE: the Mantis DB will NOT be modified if the value exists.
    */
   public function setValue($config_id, $value, $type, $project_id = 0, $user_id = 0, $access_reqd = 90) {
      $query = "SELECT * FROM `mantis_config_table` WHERE config_id='$config_id' AND project_id='$project_id'";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         if (!self::$quiet) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
         }
         exit;
      }
      if (0 == SqlWrapper::getInstance()->sql_num_rows($result)) {

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("setValue $config_id: $value (type=$type)");
         }

         // add to DB
         $query = "INSERT INTO `mantis_config_table` (`config_id`, `project_id`, `user_id`, `access_reqd`, `type`, `value`) ".
                  "VALUES ('$config_id', '$project_id', '$user_id', '$access_reqd', '$type', '$value');";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            if (!self::$quiet) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
            }
            exit;
         }

         // add/replace Cache
         $key = $config_id."_".$project_id;
         self::$configVariables["$key"] = new ConfigMantisItem($config_id, $project_id, $user_id, $access_reqd, $type, $value);
      } else {
         self::$logger->warn("setValue($config_id, $project_id): variable already exists and will NOT be modified.");
      }
   }


}

ConfigMantis::staticInit();

?>

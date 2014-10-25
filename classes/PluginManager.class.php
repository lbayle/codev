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
 * 
 * This class is a singleton responsible for:
 * - discover available plugins
 * - activate/deactivate plugins
 * - return a list of plugins for Dashboards, depending on the context
 * - update the DB codev_plugin_table.
 *
 * @author lbayle
 */
class PluginManager {

   // plugin status
   const PLUGIN_STATUS_DISABLED = 0;
   const PLUGIN_STATUS_ENABLED = 1;
   const PLUGIN_STATUS_REMOVED = 2; // TODO define what this implies

   // literal names for plugin status
   public static $statusNameList;
   
   /**
    * Singleton
    * @var PluginManager
    */
   private static $instance;

   private static $logger;



   // an image of the DB
   private $plugins;

   /**
    * The singleton pattern
    * @static
    * @return IssueCache
    */
   public static function getInstance() {
      if (NULL == self::$instance) {
         self::$instance = new PluginManager();
      }
      return self::$instance;
   }
   
   public static function getStatusName($status) {
      return self::$statusNameList[$status];
   }

   /**
    * Private constructor to respect the singleton pattern
    * @param string $cacheName The cache name
    */
   private function __construct() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$statusNameList = array(
          self::PLUGIN_STATUS_ENABLED => T_('Enabled'),
          self::PLUGIN_STATUS_DISABLED => T_('Disabled'),
          self::PLUGIN_STATUS_REMOVED => T_('Removed'),
         );
   }



   /**
    * Parse plugin direcories to find plugins and update the database.
    *
    * Note: Directory name must be SAME AS plugin className
    *       Plugin must implement IndicatorPlugin interface
    *
    * removed plugins must be marked too.
    */
   public function discoverNewPlugins() {
      $pluginsDir = Constants::$codevRootDir . DIRECTORY_SEPARATOR . IndicatorPluginInterface::INDICATOR_PLUGINS_DIR;

      $validPlugins = array();

      // foreach directory
      $dirContent = array_diff(scandir($pluginsDir), array('..', '.'));
      foreach($dirContent as $file) {
         // remove files
         if (!is_dir($pluginsDir . DIRECTORY_SEPARATOR . $file)) {
            continue;
         }
         // remove Dir that do not contain a Plugin class implementing IndicatorPluginInterface
         $pluginClassFilename = $pluginsDir . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . $file.'.class.php';
         if (!is_file($pluginClassFilename)) {
            // remove & warn
            #echo "plugin class not found -------- $pluginClassFilename<br>";
            continue;
         } else {
            $interfaceList = class_implements($file);
            #echo "interfaces: ".var_export($interfaceList, true).'<br>';
            if ((NULL == $interfaceList) || 
                (!in_array('IndicatorPluginInterface', $interfaceList))) {
               // remove & warn
               #echo "no plugin interface -------- ".$file."<br>";
               continue;
            }
         }
         $validPlugins[$file] = 0; // '0' means not yet checked with DB
      }
      self::$logger->debug("validPlugins: ".var_export($validPlugins, true));

      // compare with DB list
      $query = "SELECT * FROM `codev_plugin_table`;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $hasChanged = false;
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // if not found in validPlugins, set as REMOVED
         if (!array_key_exists($row->name, $validPlugins)) {
            if (self::PLUGIN_STATUS_REMOVED != $row->status) {
               #echo "must set as removed: $row->name<br>";
               $query2 = "UPDATE `codev_plugin_table` SET `status`=".self::PLUGIN_STATUS_REMOVED." WHERE `name` = '".$row->name."';";
               $result2 = SqlWrapper::getInstance()->sql_query($query2);
               if (!$result2) {
                  echo "<span style='color:red'>ERROR: Query FAILED</span>";
                  exit;
               }
               $hasChanged = true;
            }
         } else {
            // if found, 'REMOVED' => 'DISABLED' & update other fields.
            #echo "must be updated: $row->name<br>";

            // do not disable an already enabled plugin
            $pStatus = (self::PLUGIN_STATUS_REMOVED == $row->status) ? self::PLUGIN_STATUS_DISABLED : $row->status;

            $reflectionMethod = new ReflectionMethod($row->name, 'getDesc');
            $pDesc = $reflectionMethod->invoke(NULL);
            $reflectionMethod = new ReflectionMethod($row->name, 'getDomains');
            $pDomains = implode(',', $reflectionMethod->invoke(NULL));
            $reflectionMethod = new ReflectionMethod($row->name, 'getCategories');
            $pCat = implode(',', $reflectionMethod->invoke(NULL));
            $reflectionMethod = new ReflectionMethod($row->name, 'getVersion');
            $pVersion = $reflectionMethod->invoke(NULL);

            $query3 = "UPDATE `codev_plugin_table` SET ".
               "`status`='$pStatus', ".
               "`domains`='$pDomains', ".
               "`categories`='$pCat', ".
               "`version`='$pVersion', ".
               "`description`='$pDesc' ".
               "WHERE `name` = '".$row->name."';";
            $result3 = SqlWrapper::getInstance()->sql_query($query3);
            if (!$result3) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            
            // DB was updated, but the classmap does not need an update
            // (unless the plugin Dir has changed...)
            //$hasChanged = true;
         }
            $validPlugins[$row->name] = 1; // checked with DB
      }
      // if not found in DB, add new as DISABLED
      foreach($validPlugins as $pName => $checkedWithDB) {
         if (0 == $checkedWithDB) {
            #echo "new plugin found: $pName<br>";
            $reflectionMethod = new ReflectionMethod($pName, 'getDesc');
            $pDesc = $reflectionMethod->invoke(NULL);
            $reflectionMethod = new ReflectionMethod($pName, 'getDomains');
            $pDomains = implode(',', $reflectionMethod->invoke(NULL));
            $reflectionMethod = new ReflectionMethod($pName, 'getCategories');
            $pCat = implode(',', $reflectionMethod->invoke(NULL));
            $reflectionMethod = new ReflectionMethod($pName, 'getVersion');
            $pVersion = $reflectionMethod->invoke(NULL);

            $query4 = "INSERT  INTO `codev_plugin_table` (`name`, `description`, `status`, `domains`, `categories`, `version`) ".
               "VALUES ('$pName', '$pDesc', '".self::PLUGIN_STATUS_DISABLED."', '$pDomains', '$pCat', '$pVersion');";
            #echo "new plugin query: $query4<br>";
            $result4 = SqlWrapper::getInstance()->sql_query($query4);
            if (!$result4) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            $hasChanged = true;
         }
      }

      // if plugin status changed, re-generate the classmap.ser
      if (true == $hasChanged) {
         //$this->updateClassmap();
      }
   }



   /**
    * re-generates the classmap.ser file
    * to include new plugins classes
    */
   private function updateClassmap() {
      #echo "updateClassmap<br>";
      if (FALSE == Tools::createClassMap()) {
         self::$logger->error("updateClassmap failed !");
      }
   }

   /**
    * plugins must be granted by the admin before
    * beeing available for the Dashboards
    * 
    * @param string  $className
    * @param boolean $isActivated true to activate, false to disable
    */
   public function enablePlugin($className) {

      $status = self::PLUGIN_STATUS_ENABLED;

      $query2 = "UPDATE `codev_plugin_table` SET `status`=".$status." WHERE `name` = '".$className."';";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

   }

   /**
    * plugins must be granted by the admin before
    * beeing available for the Dashboards
    * 
    * @param string  $className
    */
   public function disablePlugin($className) {

      $status = self::PLUGIN_STATUS_DISABLED;

      $query2 = "UPDATE `codev_plugin_table` SET `status`=".$status." WHERE `name` = '".$className."';";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

   }
   
   /**
    * Return all plugins defined in DB
    * 
    * @return array of plugin descriptions
    */
   public function getPlugins() {

      if (NULL == $this->plugins) {

         $plugins = array();

         $query = "SELECT * FROM `codev_plugin_table`;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

            $plugin = array(
               'name' => $row->name,
               'status' => intval($row->status),
               'domains' => explode(',', $row->domains),
               'categories' => explode(',', $row->categories),
               'version' => $row->version,
               'description' => $row->description,
            );
            $plugins[$row->name] = $plugin;
         }
         ksort($plugins);
         $this->plugins = $plugins;
      }
      return $this->plugins;
   }

   /**
    * De pending on where the Dashboard is displayed, not all plugins should be displayed.
    * You may want to display only the quality (category) plugins available for commands (domain).
    *
    * only activated plugins will be returned
    *
    * @param string $domain
    * @param array $categories
    *
    * @return array(string) list of plugin classNames
    */
   public function getPluginCandidates($domain, $categories) {

      if (NULL == $this->plugins) { $this->getPlugins(); }

      $candidates = array();
      foreach ($this->plugins as $key => $plugin) {
         if ((self::PLUGIN_STATUS_ENABLED == $plugin['status']) &&
            (in_array($domain, $plugin['domains']))) {
            // check categ: one match is enough
            foreach ($plugin['categories'] as $cat) {
               if (in_array($cat, $categories)) {
                  $candidates[] = $plugin['name'];
                  break;
               }
            }
         }
      }
      return $candidates;
   }

}

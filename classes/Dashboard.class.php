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
 * Dashboards can be inserted in many places, each place beeing defined as a Domain.
 * So a Dashboard is associated to (one and only) domain and can display
 * IndicatorPlugins from (one or more) categories.
 * The Dashboard queries the PluginManager singleton for available Plugins.
 *
 * The dashboard saves Indicator specific settings in the database, as well as
 * it's own settings (collapsed, color, ....).
 * These settings are saved in codevtt_config_table and can be specific to [team,user].
 *
 * A Dashboard must have a unique id depending on its domain/categories in order
 * to save the settings in codevtt_config_table with [id,team,user] as key components.
 * the id should be hardcodded in the page Controler (which will also set domain & cat).
 *
 * the Dashboards has a dialogbox to let the user choose the plugins to be displayed.
 * (simple combobox with pluginCandidates).
 *
 * A Dashboard uses a SAMRTY template file (dashboard.html), and provides some smartyVariables.
 * WARN: There can be several dashboards in a same page
 *
 * include/dashboard_ajax.php will allow to save settings.
 *
 *
 * @author lob
 */
class Dashboard {

   const SETTINGS_DISPLAYED_PLUGINS = 'displayedPlugins'; // 
   const SETTINGS_DASHBOARD_TITLE = 'dashboardTitle'; //
   
   private static $logger;

   private $id;
   private $domain;
   private $categories;
   private $userid;
   private $teamid;
   private $settings;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * WARN: $id must be unique in ALL CodevTT !
    * the id is hardcoded in the CodevTT pages.
    * 
    * @param type $id
    */
   public function __construct($id) {
      $this->id = $id;
   }

   public function setDomain($domain) {
      $this->domain = $domain;
   }
   public function getDomain() {
      $this->domain;
   }
   public function setCategories($categories) {
      $this->categories = $categories;
   }
   public function getCategories() {
      return $this->categories;
   }
   public function setUserid($userid) {
      $this->userid = $userid;
   }
   public function getUserid() {
      return $this->userid;
   }
   public function setTeamid($teamid) {
      $this->teamid = $teamid;
   }
   public function getTeamid() {
      return $this->teamid;
   }



   /**
    * called by include/dashboard_ajax.php
    * 
    * save dashboard settings for [team, user]
    * 
    * Note: the dashboard can contain the same plugin
    * multiple times, each one having specific attributes.
    * ex: ProgressHistoryIndic for Cmd1, Cmd2, Cmd2 
    * 
    *  settings = array (
    *     'dashboardTitle' => 'dashboard title'
    *     'displayedPlugins' => array(
    *        array(
    *           'pluginClassName' => <pluginClassName>,
    *           'plugin_attr1' => 'val',
    *           'plugin_attr2' => 'val',
    *        )
    *     )
    *  )
    *
    * @param array $settings containing dashboard & plugin attributes.
    * @param int $teamid
    * @param int $userid if NULL, default settings for team will be saved.
    */
   public function saveSettings($settings, $teamid, $userid = NULL) {

      if (!is_array($settings)) {
         self::$logger->error("saveSettings: not an array !");
         return false;
      }
      if (!array_key_exists(self::SETTINGS_DISPLAYED_PLUGINS, $settings)) {
         self::$logger->error("saveSettings: missing key: ".self::SETTINGS_DISPLAYED_PLUGINS);
         return false;
      }
      if (!array_key_exists(self::SETTINGS_DASHBOARD_TITLE, $settings)) {
         self::$logger->error("saveSettings: missing key: ".self::SETTINGS_DASHBOARD_TITLE);
         return false;
      }
      
      $jsonSettings = json_encode($settings);
      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug("saveSettings: save ok: " . $jsonSettings);
      }
      Config::setValue(Config::id_dashboard.$this->id, $jsonSettings, Config::configType_string, NULL, 0, $userid, $teamid);
   }

   /**
    * get dashboard settings from DB
    * 
    * if user has saved some settings, return them.
    * if none, return team settings.
    * if none, return default settings
    */
   private function getSettings() {

   /*
      settings = array (
        'dashboardTitle' => 'dashboard title'
        'displayedPlugins' => array(
           array(
              'pluginClassName' => <pluginClassName>,
              'plugin_attr1' => 'val',
              'plugin_attr2' => 'val',
           )
        )
     )
   */   
      if (NULL == $this->settings) {
         // get [team, user] specific settings
         $json = Config::getValue(Config::id_dashboard.$this->id, array($this->userid, 0, $this->teamid, 0, 0, 0), true);

         // if not found, get [team] specific settings
         if (NULL == $json) {
            $json = Config::getValue(Config::id_dashboard.$this->id, array(0, 0, $this->teamid, 0, 0, 0), true);
         }
         // if no specific settings, use default values (from config.ini)
         if (NULL == $json) {
            
            $defaultPlugins = Constants::$dashboardDefaultPlugins[$this->domain];
            
            $pluginAttributes = array();
            foreach ($defaultPlugins as $pluginClassName) {
               $pluginAttributes[] = array('pluginClassName' => $pluginClassName);
               //self::$logger->error($this->domain." default plugin: ".$pluginClassName);
            }
            $this->settings = array(
               self::SETTINGS_DASHBOARD_TITLE   => 'Dashboard Title',
               self::SETTINGS_DISPLAYED_PLUGINS => $pluginAttributes,
            );
         } else {
            // convert json to array
            $this->settings = json_decode($json, true);
            if (is_null($this->settings)) {
               self::$logger->error("Dashboard settings: json could not be decoded !");
               $this->settings = array(
                  self::SETTINGS_DASHBOARD_TITLE => 'ERROR on dashboard settings',
                  self::SETTINGS_DISPLAYED_PLUGINS => array(),
               ); // failover
            }
            // TODO check that expected keys exists ?
            //self::$logger->error("settings= " . var_export($this->settings, true));
         }
      }
      return $this->settings;
   }


   public function getSmartyVariables($smartyHelper) {

      // dashboard settings
      $pm = PluginManager::getInstance();
      $candidates = $pm->getPluginCandidates($this->domain, $this->categories);

      // user specific dashboard settings
      if (NULL == $this->settings) { $this->getSettings(); }

      // insert widgets
      $pluginDataProvider = PluginDataProvider::getInstance();
      $idx = 1;
      $dashboardPluginCssFiles = array();
      $dashboardPluginJsFiles = array();
      foreach ($this->settings[self::SETTINGS_DISPLAYED_PLUGINS] as $pluginAttributes) {

         $pClassName = $pluginAttributes['pluginClassName'];
         try {
            // check that this plugin is allowed to be displayed in this dashboard
            if (!in_array($pClassName, $candidates)) {
               self::$logger->error("Dashboard user settings: ".$pClassName.' is not a candidate !');
               continue;
            }

            $widget = self::getWidget($pluginDataProvider, $smartyHelper, $pluginAttributes, $idx);
            $dashboardWidgets[$pClassName] = $widget;

            // get all mandatory CSS files
            foreach ($pClassName::getCssFiles() as $cssFile) {
               if (!in_array($cssFile, $dashboardPluginCssFiles)) {
                  array_push($dashboardPluginCssFiles, $cssFile);
               }
            }
            // get all mandatory JS files
            foreach ($pClassName::getJsFiles() as $jsFile) {
               if (!in_array($jsFile, $dashboardPluginJsFiles)) {
                  array_push($dashboardPluginJsFiles, $jsFile);
               }
            }

            $idx += 1;
         } catch (Exception $e) {
            self::$logger->error('Could not display plugin '.$pClassName.': '.$e->getMessage());
         }
      }

      // TODO as long as adding multiple times the same plugin fails,
     //  the dilplayedPlugins should be removed from candidates
      $dashboardPluginCandidates = array();
      foreach ($candidates as $cClassName) {
         if (class_exists($cClassName)) {
            $categories = $cClassName::getCategories();
            $dashboardPluginCandidates[] = array(
               'pluginClassName' => $cClassName,
               'title' => $cClassName::getName(),
               'category' => $categories[0],
            );
         } else {
            self::$logger->error('Could not display plugin '.$cClassName.': class not found');
         }
      }

      return array(
         'dashboardId' => $this->id,
         'dashboardTitle' => 'title', // TODO use SETTINGS_DASHBOARD_TITLE
         'dashboardPluginCandidates' => $dashboardPluginCandidates,
         'dashboardWidgets' =>  $dashboardWidgets,
         'dashboardPluginCssFiles' => $dashboardPluginCssFiles,
         'dashboardPluginJsFiles' => $dashboardPluginJsFiles,
              );
   }

   /**
    * 
    * @param PluginDataProvider $pluginDataProvider
    * @param SmartyHelper $smartyHelper
    * @param array $pluginAttributes
    * @param int $idx
    * @return array
    */
   public static function getWidget($pluginDataProvider, $smartyHelper, $pluginAttributes, $idx) {

      $pluginClassName = $pluginAttributes['pluginClassName'];

      // Plugin may not exist...
      if (!class_exists($pluginClassName)) {
         $e = new Exception('getWidget: class '.$pluginClassName.' not found !');
         throw $e;
      }

      $r = new ReflectionClass($pluginClassName);
      $indicator = $r->newInstanceArgs(array($pluginDataProvider));

      // examples: isGraphOnly, dateRange(defaultRange|currentWeek|currentMonth|noDateLimit), ...
      $indicator->setPluginSettings($pluginAttributes);
      $indicator->execute();

      $data = $indicator->getSmartyVariables();
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }

      //self::$logger->error("Indic classname: ".$pluginClassName);
      //self::$logger->error("Indic SmartyFilename: ".$pluginClassName::getSmartyFilename());
      $indicatorHtmlContent = $smartyHelper->fetch($pluginClassName::getSmartyFilename());

      // update inettuts attributes from pluginAttributes
      $widgetTitle = (array_key_exists('widgetTitle', $pluginAttributes)) ? $pluginAttributes['widgetTitle'] : $pluginClassName::getName();
      $color =       (array_key_exists('color',       $pluginAttributes)) ? $pluginAttributes['color'] : 'color-white';
      unset($pluginAttributes['widgetTitle']);
      unset($pluginAttributes['color']);

      // set indicator result in a dashboard widget
      $widget = array(
         'id' => 'w_'.$idx, // TODO WARN must be unique (if 2 dashboards in same page)
         'color' => $color,
         'title' => $widgetTitle,
         'desc' => $pluginClassName::getDesc(),
         'category' => implode(',', $pluginClassName::getCategories()),
         'attributesJsonStr' => json_encode($pluginAttributes),
         'jsFiles' => $pluginClassName::getJsFiles(),
         'cssFiles' => $pluginClassName::getCssFiles(),
         'content' => $indicatorHtmlContent,
      );
      return $widget;
   }

}

// Initialize static variables
Dashboard::staticInit();


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
    * @param type $settings json containing dashboard & plugins settings.
    * @param type $userid
    * @param type $teamid
    */
   public function saveSettings($settings, $userid, $teamid) {
      // if any, save to codevtt_config_table with [id,team,user] as key components.
   }

   /**
    * if user has saved some settings, return them.
    * - list of plugins to display
    * - widgetAttributes (collapsed, color, ...)
    *
    *
    */
   private function getDashboardSettings() {

      // TODO get user specific settings

      // if no specific settings, default values:
      $this->settings = array();
      $pm = PluginManager::getInstance();
      $candidates = $pm->getPluginCandidates($this->domain, $this->categories);
      $this->settings['plugins'] = $candidates;

      return $this->settings;
   }

   /**
    * if user has saved specific settings, get them. if not return NULL
    * @param type $pluginClassName
    * @return array
    */
   private function getPluginSettings($pluginClassName) {
      // TODO get from $this->settings
      return NULL;
   }

   public function getSmartyVariables($smartyHelper) {

      // dashboard settings
      $pm = PluginManager::getInstance();
      $candidates = $pm->getPluginCandidates($this->domain, $this->categories);

      // user specific dashboard settings
      if (NULL == $this->settings) { $this->getDashboardSettings(); }

      // insert widgets
      $pluginDataProvider = PluginDataProvider::getInstance();
      $idx = 1;
      foreach ($this->settings['plugins'] as $pClassName) {

         if (!in_array($pClassName, $candidates)) {
            self::$logger->error("Dashboard user settings: ".$pClassName.' is not a candidate !');
            continue;
         }

         // TODO check, Plugin may not exist...
         $r = new ReflectionClass($pClassName);
         $indicator = $r->newInstanceArgs(array($pluginDataProvider));


         // examples: isGraphOnly, dateRange(defaultRange|currentWeek|currentMonth|noDateLimit), ...
         $indicator->setPluginSettings($this->getPluginSettings($pClassName));
         $indicator->execute();

         $data = $indicator->getSmartyVariables();
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         
         $indicatorHtmlContent = $smartyHelper->fetch($pClassName::getSmartyFilename());

         // set indicator result in a dashboard widget
         $widget = array(
            'id' => 'w_'.$idx, // TODO WARN must be unique (if 2 dashboards in same page)
            'color' => 'color-white',
            'title' => $pClassName::getName(),
            'desc' => $pClassName::getDesc(),
            'category' => implode(',', $pClassName::getCategories()),
            'content' => $indicatorHtmlContent,
         );

         $dashboardWidgets[] = $widget;
         $idx += 1;
      }

      return array(
         'dashboardId' => $this->id,
         'dashboardTitle' => 'title',
         'dashboardPluginCandidates' => $candidates, // TODO json ? implode ?
         'dashboardWidgets' =>  $dashboardWidgets
         );
   }
}

// Initialize static variables
Dashboard::staticInit();


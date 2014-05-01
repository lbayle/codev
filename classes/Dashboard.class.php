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

   private $domain;
   private $categories;
   private $userid;
   private $teamid;
   private $settings;

   public function __construct($id) {
      
      // TODO REMOVE TEST
      $this->settings = array();
      $this->settings['plugins'] = array('LoadPerJobIndicator2');
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
    *
    * @param type $settings json containing dashboard & plugins settings.
    * @param type $userid
    * @param type $teamid
    */
   public function saveSettings($settings, $userid, $teamid) {

   }

   private function getPluginSettings($pluginClassName) {
      return $pluginSettings;
   }

   /**
    * - list of plugins to display
    * - widgetAttributes (collapsed, color, ...)
    * 
    * 
    * @param type $userid
    * @param type $teamid
    */
   private function getDashboardSettings() {

   }

   public function getSmartyVariables($smartyHelper) {

      // dashboard settings
      $pm = PluginManager::getInstance();
      $candidates = $pm->getPluginCandidates($this->domain, $this->categories);

      // insert widgets
      $pluginDataProvider = PluginDataProvider::getInstance();
      $idx = 1;
      foreach ($this->settings['plugins'] as $pClassName) {


         $r = new ReflectionClass($pClassName);
         $indicator = $r->newInstanceArgs(array($pluginDataProvider));



         //$indicator->setPluginSettings($this->getPluginSettings($pClassName));
         $indicator->execute();

         $data = $indicator->getSmartyVariables();
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $indicatorHtmlContent = $smartyHelper->fetch(LoadPerJobIndicator::getSmartyFilename());

         // set indicator result in a dashboard widget
         $widget = array(
            'id' => 'w_'.$idx,
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
         'dashboardId' => 'id',
         'dashboardTitle' => 'title',
         'dashboardPluginCandidates' => $candidates, // TOSO json ? implode ?
         'dashboardWidgets' =>  $dashboardWidgets
         );
   }
}

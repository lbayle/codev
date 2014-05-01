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
 * the Dashboards uses a selectItemsDialogbox (*) to let the user choose the plugins to
 * be displayed.
 * (*) : mabe a simple checkbox Dialogbox is enough.
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

   public function __construct($id) {
      
   }

   public function setDomain($domain) {
      $this->domain = $domain;
   }
   public function getDomain() {

   }
   public function setCategories($categories) {
      $this->categories = $categories;
   }
   public function getCategories() {

   }

   /**
    *
    * @param type $settings json containing dashboard & plugins settings.
    * @param type $userid
    * @param type $teamid
    */
   public function saveSettings($settings, $userid, $teamid) {

   }

   public function getPluginSettings($pluginClassName, $userid, $teamid) {
      return $pluginSettings;
   }

   public function getSmartyVariables() {

      return array(
         'dashboardId' => 'id',
         'dashboardTitle' => 'title',
         );
   }
}

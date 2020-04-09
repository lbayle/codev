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
 *
 * For each Task, return the sum of the elapsed time.
 *
 * @author lob
 */
class AdminTools extends IndicatorPluginAbstract {


   const OPTION_SELECTED_TEAMID = 'selectedTeamid';
   const OPTION_SELECTED_USERID = 'selectedUserid';


   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $selectedTeamid;
   private $selectedUserid;

   // config options from Dashboard

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_ADMIN,
      );
      self::$categories = array (
         self::CATEGORY_ADMIN,
      );
   }

   public static function getName() {
      return T_('Administration tools');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('CodevTT administration tools');
      if (!$isShortDesc) {
         $desc .= '<br><br>'.T_('TODO');
      }
      return $desc;
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
      );
   }
   public static function getJsFiles() {
      return array(
         //'js_min/datepicker.min.js',
         //'js_min/table2csv.min.js',
         //'js_min/progress.min.js',
         //'js_min/tooltip.min.js',
         //'js_min/datatable.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->selectedTeamid = NULL;
      $this->selectedUserid = NULL;
   }

   /**
    * settings are saved by the Dashboard
    *
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_SELECTED_TEAMID, $pluginSettings)) {
            $this->selectedTeamid = $pluginSettings[self::OPTION_SELECTED_TEAMID];
         }
         if (array_key_exists(self::OPTION_SELECTED_USERID, $pluginSettings)) {
            $this->selectedUserid = $pluginSettings[self::OPTION_SELECTED_USERID];
         }
      }
   }


   /**
    * Restore Message plugin on homepage for ALL users
    *
    * @param type $force if dashboardDefaultPlugins does not contain BlogPlugin, force it !
    */
   public function restoreBlogPlugin($force=FALSE) {

      // reset homepage dashboard to contain only the Message plugin
      $sql = AdodbWrapper::getInstance();

      $query = 'UPDATE codev_config_table'.
         ' SET value='.$sql->db_param().
         ' WHERE config_id LIKE '.$sql->db_param().
         ' AND value NOT LIKE '.$sql->db_param();

      if (NULL != $this->selectedTeamid) {
         $team= TeamCache::getInstance()->getTeam($this->selectedTeamid);
         $members = $team->getActiveMembers();
         $formatted_members = implode(',', array_keys($members));
         $query .= ' AND userid IN ('.$formatted_members.')';
      }

      $q_params=array();
      $q_params[]='{"dashboardTitle":"title","displayedPlugins":[{"pluginClassName":"BlogPlugin"}]}';
      $q_params[]=Config::id_dashboard.'homepage%';
      $q_params[]='%BlogPlugin%';
      $sql->sql_query($query, $q_params);
/*
      $homepageDefaultPlugins = Constants::$dashboardDefaultPlugins[IndicatorPluginInterface::DOMAIN_HOMEPAGE];
      if ((TRUE == $force) && (FALSE == strpos($homepageDefaultPlugins, 'BlogPlugin'))) {

         // TODO identify all users with no dashboard settings
         // and insert a BlogPlugin to the dashboard

      }
 */
   }

   private function removeDashboardSettings($user, $domain) {

   }


   /**
    *
    * returns an array of
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $formatted_members = NULL;
      if (NULL != $this->selectedTeamid) {
         $team= TeamCache::getInstance()->getTeam($this->selectedTeamid);
         $members = $team->getActiveMembers();
         $formatted_members = implode(',', array_keys($members));
      }
      if (NULL != $this->selectedUserid) {
         // Overrides selectedTeamid
         //$user = UserCache::getInstance()->getUser($this->selectedUserid);
         $formatted_members = $this->selectedUserid;
      }

      $sql = AdodbWrapper::getInstance();

      // count people having removed the BlogPlugin
      // Note: this assumes [dashboardDefaultPlugins]contains BlogPlugin !
      $query = 'SELECT count(1) FROM `codev_config_table` '.
         ' WHERE config_id LIKE '.$sql->db_param().
         ' AND value NOT LIKE '.$sql->db_param();

      if (NULL != $formatted_members) {
         $query .= ' AND userid IN ('.$formatted_members.')';
      }
      $result = $sql->sql_query($query, array(Config::id_dashboard.'homepage%', '%BlogPlugin%'));
      $nbUsersNoBlogPlugin = $sql->sql_result($result);

      // TODO:
      // if BlogPlugin has been removed from [dashboardDefaultPlugins] Homepage in config.ini
      // then no user will have it (unless they have add it manualy in the homepage dashboard)
      // so we need to count people with no config too ...

      // -------------------------





      $this->execData['nbUsersNoBlogPlugin'] = $nbUsersNoBlogPlugin;
      $this->execData['teamMembers'] = $members;
      $this->execData['allUsers'] = User::getUsers(TRUE);
      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $prefix='AdminTools_';
      $smartyVariables = array();
      foreach ($this->execData as $key => $val) {
         $smartyVariables[$prefix.$key] = $val;
      }

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }

//self::$logger->error($smartyVariables);

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
AdminTools::staticInit();

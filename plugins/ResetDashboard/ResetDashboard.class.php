
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
 * Description of HelloWorldIndicator
 *
 * @author lob
 */
class ResetDashboard extends IndicatorPluginAbstract {

   const OPTION_DISPLAYED_USERID = 'displayedUserid';
   const OPTION_DISPLAYED_TEAMID = 'displayedTeamid';

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from Dashboard
   private $displayedUserid;
   private $displayedTeamid;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_ADMIN,
         //self::DOMAIN_TEAM,
         //self::DOMAIN_USER,
      );
      self::$categories = array (
         self::CATEGORY_ADMIN
      );
   }

   public static function getName() {
      return T_("Remove dashboard plugins");
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Remove all plugins from a dashboard. This is usefull if a plugin crashes the page');
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
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datatable.min.js',
         'lib/select2/select2.min.js'
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->displayedTeamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_TEAM_ID);
      }

      if (IndicatorPluginInterface::DOMAIN_USER === $this->domain) {
         if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID)) {
            $this->displayedUserid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         } else {
            throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         }
      } else {
         if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
             $this->displayedUserid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
         } else {
            $this->displayedUserid = 0; // none selected
         }
      }

   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DISPLAYED_USERID, $pluginSettings)) {
            $this->displayedUserid = $pluginSettings[self::OPTION_DISPLAYED_USERID];
         }
         if (array_key_exists(self::OPTION_DISPLAYED_TEAMID, $pluginSettings)) {
            $this->displayedTeamid = $pluginSettings[self::OPTION_DISPLAYED_TEAMID];
         }
      }
   }

   /**
    * this should not be here...
    */
   private function getDashboardData($dashboardId) {
      $data = array();

      // DOMAIN_COMMAND_SET
      if (str_starts_with($dashboardId, 'dashboard_CommandSet')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_COMMAND_SET);
         $id = intval(substr($dashboardId, strlen('dashboard_CommandSet')));
         $data['subId'] = $id;
         if (CommandSet::exists($id)) {
            $cset = CommandSetCache::getInstance()->getCommandSet($id);
            $data['subName'] = $cset->getName();
         } else {
            $data['subName'] = $id.' (not found)';
         }
         return $data;
      }
      // DOMAIN_COMMAND
      if (str_starts_with($dashboardId, 'dashboard_Command')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_COMMAND);
         $id = intval(substr($dashboardId, strlen('dashboard_Command')));
         $data['subId'] = $id;
         if (Command::exists($id)) {
            $cmd = CommandCache::getInstance()->getCommand($id);
            $data['subName'] = $cmd->getName();
         } else {
            $data['subName'] = $id.' (not found)';
         }
         return $data;
      }
      // DOMAIN_SERVICE_CONTRACT
      if (str_starts_with($dashboardId, 'dashboard_ServiceContract')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT);
         $id = intval(substr($dashboardId, strlen('dashboard_ServiceContract')));
         $data['subId'] = $id;
         if (ServiceContract::exists($id)) {
            $cmd = ServiceContractCache::getInstance()->getServiceContract($id);
            $data['subName'] = $cmd->getName();
         } else {
            $data['subName'] = $id.' (not found)';
         }
         return $data;
      }
      // DOMAIN_ADMIN
      if (str_starts_with($dashboardId, 'dashboard_Admin')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_ADMIN);
         $data['subId'] = '';
         $data['subName'] = '';
         return $data;
      }
      // DOMAIN_HOMEPAGE
      if (str_starts_with($dashboardId, 'dashboard_homepage')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_HOMEPAGE);
         $data['subId'] = '';
         $data['subName'] = '';
         return $data;
      }
      // DOMAIN_TEAM_ADMIN
      if (str_starts_with($dashboardId, 'dashboard_EditTeam')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_TEAM_ADMIN);
         $data['subId'] = '';
         $data['subName'] = '';
         return $data;
      }
      // DOMAIN_TEAM
      if (str_starts_with($dashboardId, 'dashboard_Team')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_TEAM);
         $id = intval(substr($dashboardId, strlen('dashboard_Team')));
         $data['subId'] = $id;
         if (Team::exists($id)) {
            $cmd = TeamCache::getInstance()->getTeam($id);
            $data['subName'] = $cmd->getName();
         } else {
            $data['subName'] = $id.' (not found)';
         }
         return $data;
      }
      // DOMAIN_IMPORT_EXPORT
      if (str_starts_with($dashboardId, 'dashboard_User')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_USER);
         $data['subId'] = '';
         $data['subName'] = '';
         return $data;
      }
      // DOMAIN_IMPORT_EXPORT
      if (str_starts_with($dashboardId, 'dashboard_Import')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_IMPORT_EXPORT);
         $data['subId'] = '';
         $data['subName'] = '';
         return $data;
      }
      // DOMAIN_PROJECT
      if (str_starts_with($dashboardId, 'dashboard_Project')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_PROJECT);
         $id = intval(substr($dashboardId, strlen('dashboard_Project')));
         $data['subId'] = $id;
         if (Project::exists($id)) {
            $cmd = ProjectCache::getInstance()->getProject($id);
            $data['subName'] = $cmd->getName();
         } else {
            $data['subName'] = $id.' (not found)';
         }
         return $data;
      }
      // DOMAIN_TASK
      if (str_starts_with($dashboardId, 'dashboard_Tasks_prj')) {
         $data['domain'] = T_(IndicatorPluginInterface::DOMAIN_TASK);
         $id = intval(substr($dashboardId, strlen('dashboard_Tasks_prj')));
         $data['subId'] = $id;
         if (Project::exists($id)) {
            $cmd = ProjectCache::getInstance()->getProject($id);
            $data['subName'] = $cmd->getName();
         } else {
            $data['subName'] = T_('Project').' '.$id;
         }
         return $data;
      }

      // if not found (internal error !)
      return array (
         'domain' => $dashboardId,
         'subId' => '',
         'subName' => '',
      );
   }

   private function getUserDashboards($userid, $teamid) {

      $userDashboards = array(); // dashboardName => { domain, pluginList}

      // get all dashboards settings for ($userid, $teamid)
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT config_id, value FROM codev_config_table" .
               " WHERE config_id LIKE '".Config::id_dashboard."%' ".
               " AND user_id = ".$sql->db_param().
               " AND team_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($userid, $teamid));

      // foreach dashboard:
      while ($row = $sql->fetchObject($result)) {
         /*
          * {"dashboardTitle":"title",
          *  "displayedPlugins":[
          *     {"pluginClassName":"UserTeamList"},
          *     {"pluginClassName":"TimetrackDetailsIndicator"}]}

            settings = array (
              'dashboardTitle' => 'dashboard title'
              'displayedPlugins' => array(
                    'pluginClassName' => <pluginClassName>,
                    'plugin_attr1' => 'val',
                    'plugin_attr2' => 'val',
                  )
           )
         */
         $settings = json_decode($row->value, true);
         $displayedPlugins = $settings['displayedPlugins'];
         $pluginNameList = array();
         foreach ($displayedPlugins as $p) {
            // TODO get Name (not className)
            $pluginNameList[] = $p['pluginClassName'];
         }
         $dashboardData = $this->getDashboardData($row->config_id);
         $userDashboards[$row->config_id] = array (
               'dashboardId' => $row->config_id,
               'domain' => $dashboardData['domain'],
               'subName' => $dashboardData['subName'],
               'subId' => $dashboardData['subId'],
               'pluginList' => implode(', ', $pluginNameList),
            );
      }
      //self::$logger->error("dashboardData = ".var_export($userDashboards, true));
      return $userDashboards;
   }

   public function removeDashboardSettings($dashboardId) {
      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_config_table" .
               " WHERE config_id = ".$sql->db_param().
               " AND user_id = ".$sql->db_param().
               " AND team_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($dashboardId, $this->displayedUserid, $this->displayedTeamid));

   }

  /**
    *
    */
   public function execute() {

      $sql = AdodbWrapper::getInstance();

      // all CodevTT users
      $query = "SELECT id, realname FROM {user} ORDER BY realname;";
      $result = $sql->sql_query($query);
      while ($row = $sql->fetchObject($result)) {
         $formattedId = sprintf('%03d', $row->id);
         $userList[$row->id] = "$formattedId | $row->realname";
      }

      // find teamList for selected user
      $userTeams = array();
      if (0 != $this->displayedUserid) {
         $displayedUser = UserCache::getInstance()->getUser($this->displayedUserid);
         $userTeams = $displayedUser->getTeamList();
      } else {
         $userTeams = array();
      }

      if ((0 != $this->displayedTeamid) &&
          (!array_key_exists ($this->displayedTeamid, $userTeams))) {
         //self::$logger->error("user ".$this->displayedUserid.' NOT in team '.$this->displayedTeamid." !");
         $this->displayedTeamid = 0;
      }

      // get data for each Dashboard
      if ((0 != $this->displayedUserid) && (0 != $this->displayedTeamid)) {
         $userDashboards = $this->getUserDashboards($this->displayedUserid, $this->displayedTeamid);
      }


      $this->execData = array (
         'userList' => $userList,
         'userTeams' => $userTeams,
         'userDashboards' => $userDashboards,
      );

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $availableUsers = SmartyTools::getSmartyArray($this->execData['userList'],$this->displayedUserid);
      $userTeams = SmartyTools::getSmartyArray($this->execData['userTeams'],$this->displayedTeamid);

      $prefix='ResetDashboard_';
      $smartyVariables = array(
         $prefix.'availableUsers' => $availableUsers,
         $prefix.'availableTeams' => $userTeams,
         $prefix.'userDashboards' => $this->execData['userDashboards'],

         // add pluginSettings (if needed by smarty)
         $prefix.self::OPTION_DISPLAYED_USERID => $this->displayedUserid,
      );

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
ResetDashboard::staticInit();

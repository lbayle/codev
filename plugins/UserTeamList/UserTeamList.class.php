
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
class UserTeamList extends IndicatorPluginAbstract {

   const OPTION_DISPLAYED_USERID = 'displayedUserid';
   
   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   //private $inputIssueSel;
   private $startTimestamp;

   // config options from Dashboard
   private $displayedUserid;

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
      );
      self::$categories = array (
         self::CATEGORY_ADMIN
      );
   }

   public static function getName() {
      return T_("User team list");
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Display a history of all the teams for a given user');
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

      // nothing to initialize
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
      }
   }


  /**
    *
    */
   public function execute() {

      $query = "SELECT id, realname FROM `mantis_user_table` ORDER BY realname;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $formattedId = sprintf('%03d', $row->id);
         $userList[$row->id] = "[$formattedId] $row->realname";
      }

      $teamList = array();
      if (0 != $this->displayedUserid) {
         
         if (!empty($userList)) {

            $query = "SELECT `codev_team_table`.id team_id, `codev_team_table`.name team_name, `codev_team_user_table`.user_id, "
               . "`codev_team_user_table`.`arrival_date`, `codev_team_user_table`.`departure_date`, `codev_team_user_table`.`access_level` "
               . "FROM `codev_team_user_table` "
               . "LEFT OUTER JOIN `codev_team_table` ON `codev_team_user_table`.team_id = `codev_team_table`.id "
               . "WHERE `codev_team_user_table`.user_id = $this->displayedUserid;";

            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }

            $jobs = new Jobs();
            while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
               $teamList[$row->team_id] = array(
                  'user_id' => $row->user_id,
                  'team_id' => $row->team_id,
                  'team_name' => $row->team_name,
                  'arrival_date' => date('Y-m-d', $row->arrival_date),
                  'departure_date' => (0 == $row->departure_date) ? '' : date('Y-m-d', $row->departure_date),
                  'access_level' => Team::$accessLevelNames[$row->access_level],
               );
            }
         }
      }

      $this->execData = array (
         'userList' => $userList, // available users
         'user_id' => $this->displayedUserid,
         'teamList' => $teamList, // the teams of displayedUserid
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

      $smartyVariables = array(
         'userTeamList_availableUsers' => $availableUsers,
         'userTeamList_userTeams' => $this->execData['teamList'],

         // add pluginSettings (if needed by smarty)
         'userTeamList_'.self::OPTION_DISPLAYED_USERID => $this->displayedUserid,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['userTeamList_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['userTeamList_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      self::$logger->error($smartyVariables);
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
UserTeamList::staticInit();

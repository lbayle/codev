
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
class CustomUserData extends IndicatorPluginAbstract {

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $teamid;
   private $domain;
   private $sessionUserid;
   private $isManager;


   // config options from Dashboard
   //private $displayedUserid;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM_ADMIN,
         self::DOMAIN_TEAM,
         self::DOMAIN_USER,
      );
      self::$categories = array (
         self::CATEGORY_ADMIN,
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Custom user data');
   }
   
   
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Allows to set some user specific data such as EmployeeId, userId in other DB/Softwares, phoneNumber, etc.')."<br>".
         T_('The initial goal is to ease the export of CodevTT data to other tools.');
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
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datatable.min.js',
         'plugins/CustomUserData/CustomUserData.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->sessionUserid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         $this->sessionUserid = 0;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         $this->domain = 'unknown';
      }

      try {
         $sessionUser = UserCache::getInstance()->getUser($this->sessionUserid);
         $this->isManager = $sessionUser->isTeamManager($this->teamid);
      } catch (Exception $e) {
         $this->isManager = NULL;
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
         //if (array_key_exists(self::OPTION_DISPLAYED_USERID, $pluginSettings)) {
         //   $this->displayedUserid = $pluginSettings[self::OPTION_DISPLAYED_USERID];
         //}
      }
   }

   public function setUserField($userid, $sqlFieldName, $fieldValue) {

      if (((IndicatorPluginInterface::DOMAIN_USER == $this->domain)) &&
          ($userid != $this->sessionUserid)) {
         $e = new Exception("SECURITY user $this->sessionUserid is NOT ALLOWED TO update user $userid !");
         throw $e;
      }

      if (!array_key_exists($sqlFieldName, Constants::$customUserData)) {
         $e = new Exception("Unknown field: $sqlFieldName !");
         throw $e;
      }
      if (!User::existsId($userid)) {
         $e = new Exception("Unknown userid not found in Mantis DB: $userid !");
         throw $e;
      }

      $sql = AdodbWrapper::getInstance();

      // check if exists
      $query  = "SELECT COUNT(user_id) FROM codev_custom_user_data_table WHERE user_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($userid));
      $nbTuples  = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;

      if (0 == $nbTuples) {
         $query  = 'INSERT INTO codev_custom_user_data_table (user_id,'.$sqlFieldName.')'.
                     ' VALUES ( ' . $sql->db_param() . ','
                                  . $sql->db_param() . ')';
         $result = $sql->sql_query($query, array($userid, $fieldValue));

      } else {
         $query = 'UPDATE codev_custom_user_data_table SET ' . $sqlFieldName . ' = '.$sql->db_param().
            ' WHERE user_id = '.$sql->db_param();
         $result = $sql->sql_query($query, array($fieldValue, $userid));
      }
   }


  /**
    *
    */
   public function execute() {

      $sql = AdodbWrapper::getInstance();

//      if (IndicatorPluginInterface::DOMAIN_USER == $this->domain) {
//         $formatedTeamMembers = $this->sessionUserid;
//      } else {
         $team = TeamCache::getInstance()->getTeam($this->teamid);
         $members =$team->getMembers();
         $formatedTeamMembers = implode(', ', array_keys($members));
//      }

      $query = "SELECT codev_custom_user_data_table.*, "
         . " {user}.id userid, {user}.username, {user}.realname, {user}.email "
         . "FROM {user} "
         . "LEFT OUTER JOIN codev_custom_user_data_table ON codev_custom_user_data_table.user_id = {user}.id "
         . "WHERE {user}.id IN (".$formatedTeamMembers.") ";

      $result = $sql->sql_query($query);

      while ($row = $sql->fetchArray($result)) {

         $curUserid = $row['userid'];
         
         // if (CodevTT administrator) or if (DOMAIN_TEAM & teamAdmin) or if (DOMAIN_USER & it's me)
         $isUserEditable = true;
         if (((IndicatorPluginInterface::DOMAIN_USER == $this->domain) || 
              (IndicatorPluginInterface::DOMAIN_TEAM == $this->domain)) &&
             ($curUserid != $this->sessionUserid)) {
            $isUserEditable = false;
         }
         $userData[$curUserid] = array(
            'isEditable' => $isUserEditable,
            'user_id' => $curUserid, # $row->userid,
            'user_login' => $row['username'], # $row->username,
            'user_realname' => $row['realname'], # $row->realname,
            'user_email' => $row['email'], # $row->email,
         );

         // not always 5 fields, depends on config.ini
         foreach (array_keys(Constants::$customUserData) as $sqlFieldName) {
            $userData[$curUserid][$sqlFieldName] = (null == $row[$sqlFieldName]) ? '' : $row[$sqlFieldName];
         }
      }

      $this->execData = array (
         'userData' => $userData,
      );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables = array(
         'CustomUserData_fieldNames' => Constants::$customUserData,
         'CustomUserData_users' => $this->execData['userData'],

         // add pluginSettings (if needed by smarty)
         //'CustomUserData_'.self::OPTION_DISPLAYED_USERID => $this->displayedUserid,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['CustomUserData_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['CustomUserData_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      //self::$logger->error(var_export($smartyVariables, true));
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
CustomUserData::staticInit();

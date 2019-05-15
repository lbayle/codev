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
 * Description of BlogPlugin
 *
 * @author fr20648
 */
class BlogPlugin extends IndicatorPluginAbstract {

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $teamid;
   private $sessionUserId;

   // config options from Dashboard
   private $userSettings;

   protected $execData;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      // A plugin can be displayed in multiple domains
      self::$domains = array (
         self::DOMAIN_HOMEPAGE,
      );
      // A plugin should have only one category
      self::$categories = array (
         self::CATEGORY_INTERNAL
      );
   }

   public static function getName() {
      return T_('Messages');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Display a message wall on the homepage')."<br>".
              T_('Allows Administrators & team members to send messages that will be displayed on other users\' wall');
      if (!$isShortDesc) {
         $desc .= "<br><br>";
      }
      return $desc;   }
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
         'js_min/datepicker.min.js',
      );
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
   }


   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      //self::$logger->error("pluginSettings".var_export($pluginSettings, true));

      if (NULL != $pluginSettings) {
         // override default with user preferences
      }
   }

   public function execute() {

      $blogManager = new BlogManager();
      $session_user = UserCache::getInstance()->getUser($this->sessionUserId);
      $isAdministrator = $session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId));

      $postList   = $blogManager->getPosts($session_user->getId(), $this->teamid);
      $categories = $blogManager->getCategoryList();
      $severities = $blogManager->getSeverityList();

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $userCandidates = $team->getActiveMembers(NULL, NULL, TRUE);

      $blogPosts = array();
      foreach ($postList as $id => $bpost) {
         $item = $bpost->getSmartyStruct($this->sessionUserId);
         $blogPosts[$id] = $item;
      }

      $this->execData = array(
          'isCodevAdmin' => $isAdministrator,
          'blogPosts' => $blogPosts,
          'categoryList' => $categories,
          'severityList' => $severities,
          'userCandidates' => $userCandidates,
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
         'blogPlugin_isCodevAdmin' => $this->execData['isCodevAdmin'],
         'blogPlugin_blogPosts'    => $this->execData['blogPosts'],
         'blogPlugin_categoryList' => $this->execData['categoryList'],
         'blogPlugin_severityList' => $this->execData['severityList'],
         'blogPlugin_userCandidateList' => $this->execData['userCandidates'],
         // add pluginSettings (if needed by smarty)
      );

      if (false == $isAjaxCall) {
         $smartyVariables['blogPlugin_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['blogPlugin_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}
BlogPlugin::staticInit();
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
         'js_min/tabs.min.js',

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
      return 'SUCCESS : restoreBlogPlugin';
   }

   /**
    * Permet de changer l'id d'une fiche.
    * Si l'on supprime une fiche par inadvertance, et que l'id de la fiche avait une importance (visibilité client), 
    * vous pouvez recréer une fiche et lui donner l'id de la fiche supprimée.
    * 
    * Allows you to change the id of an issue.
    * If an issue is inadvertently deleted, and the id of the issue was important (customer visibility), 
    * you can recreate a record and give it the id of the deleted isue.
    * @param type $srcIssueId
    * @param type $destIssueId
    */
   public function changeIssueId($srcIssueId, $destIssueId) {

      
      self::$logger->error("srcIssueId =".$srcIssueId);
      self::$logger->error("destIssueId =".$destIssueId);
      $statusMsg = 'SUCCESS';
      $strActionLogs = '';
      
      //update all tables
      try {
         $sql = AdodbWrapper::getInstance();
         
         // check $sourceIssueId exists
         if (!Issue::exists($srcIssueId)) {
            $str = 'ERROR changeIssueId: Could not find source issue in the DB : '.$srcIssueId;
            $strActionLogs .= $str;
            throw new Exception($str);
         }
         // check $destIssueId does NOT exists
         if (Issue::exists($destIssueId)) {
            $str =  'ERROR changeIssueId: The destination issueId already exists in the DB : '.$destIssueId;
            $strActionLogs .= $str;
            throw new Exception($str);
         }
         // check destIssueId should not exceed AUTO_INCREMENT
         if ($sql->isMysql()) {
            $dbName = $sql->getDatabaseName();

            $query = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES ".
                     " WHERE TABLE_SCHEMA = ".$sql->db_param()." AND TABLE_NAME = '{bug}'";
            $result = $sql->sql_query($query, array($dbName));
            $autoIncVal = $sql->sql_result($result, 0);
            
            if ($destIssueId >= $autoIncVal) {
               $str =  'ERROR changeIssueId: The destination issueId cannot exceed AUTO_INCREMENT value : '.$autoIncVal;
               $strActionLogs .= $str;
               throw new Exception($str);
            }
         }
         

         $table = '{bug}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET id=".$sql->db_param().' WHERE id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bugnote}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_file}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_history}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_monitor}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_relationship}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET source_bug_id=".$sql->db_param().' WHERE source_bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));
         $query = "UPDATE $table SET destination_bug_id=".$sql->db_param().' WHERE destination_bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_revision}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{bug_tag}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{custom_field_string}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = '{sponsorship}';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = 'codev_command_bug_table';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $table = 'codev_wbs_table';
         $strActionLogs .= "changeIssueId: update table $table\n";
         $query = "UPDATE $table SET bug_id=".$sql->db_param().' WHERE bug_id='.$sql->db_param();
         $sql->sql_query($query, array($destIssueId, $srcIssueId));

         $strActionLogs .= "SUCCESS : ChangeIssueId";

      } catch (Exception $e) {
         self::$logger->error("srcIssueId=$srcIssueId, destIssueId=$destIssueId : " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         $statusMsg = 'ERROR : ChangeIssueId';
      }
      $data = array (
         'statusMsg' => $statusMsg,
         'actionLogs' => htmlentities($strActionLogs),
         );
      return $data;
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

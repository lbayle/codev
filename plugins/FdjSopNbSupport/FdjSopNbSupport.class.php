
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
 * Description of FdjSopNbSupport
 *
 * @author lob
 */
class FdjSopNbSupport extends IndicatorPluginAbstract {

   const OPTION_REGEX_TASK_SUPPORT_HIA = 'regEx_taskSupportHIA'; // '/Support HIA/i'
   const OPTION_REGEX_TASK_SUPPORT_USER   = 'regEx_taskSupportUser'; // "/Support Utilisateur/i"
   const OPTION_USER_SETTINGS = 'userSettings';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $regEx_taskSupportHIA;
   private $regEx_taskSupportUser;

   // internal
   protected $execData;
   private $userSettings;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
//         self::DOMAIN_USER,
         self::DOMAIN_TEAM,
         self::DOMAIN_COMMAND,
//         self::DOMAIN_COMMAND_SET,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );

   }

   public static function getName() {
      return '== FDJ == SOP - Consommé sur tâches de support';
   }
   public static function getDesc($isShortDesc = true) {
      return 'Calcule le consommé sur tâches de support';
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
         'js_min/datepicker.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->regEx_taskSupportHIA = '/Support HIA/i';
      $this->regEx_taskSupportUser = '/Support Utilisateur/i';

      $this->setDefaultUserSettings();
   }

   /**
    * get from DB, if not found activate for all users
    */
   private function setDefaultUserSettings() {
      $this->userSettings = array();
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $users = $team->getActiveMembers($this->startTimestamp,$this->endTimestamp,TRUE); // TRUE=realNames

      foreach ($users as $uid => $uname) {
         $this->userSettings[$uid] = array(
             'name' => $uname,
             'enabled' => true,
         );
         #self::$logger->error("team member: $uname ");
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
         if (array_key_exists(self::OPTION_REGEX_TASK_SUPPORT_HIA, $pluginSettings)) {
            // WARN: regex has ben stored with htmlentities !!
            $this->regEx_taskSupportHIA = html_entity_decode($pluginSettings[self::OPTION_REGEX_TASK_SUPPORT_HIA]);
         }
         if (array_key_exists(self::OPTION_REGEX_TASK_SUPPORT_USER, $pluginSettings)) {
            // WARN: regex has ben stored with htmlentities !!
            $this->regEx_taskSupportUser = html_entity_decode($pluginSettings[self::OPTION_REGEX_TASK_SUPPORT_USER]);
         }
         if (array_key_exists(self::OPTION_USER_SETTINGS, $pluginSettings)) {
            // override each user values, do not replace the complete block
            $newUserSettings = $pluginSettings[self::OPTION_USER_SETTINGS];
            foreach(array_keys($this->userSettings) as $uid) {
               if (array_key_exists($uid, $newUserSettings)) {
                  $this->userSettings[$uid]['enabled'] = $newUserSettings[$uid]['enabled'] == 0 ? false : true;
               }
            }
            //self::$logger->error(var_export($this->userSettings, true));
         }
      }
   }

   /**
    *
    * @return array execData
    */
   public function execute() {

      //self::$logger->error(var_export($this->userSettings, true));

      // filter timetracks by user
      $uidList = array();
      foreach ($this->userSettings as $uid => $uSettings) {
         if ($uSettings['enabled']) {
            $uidList[] = $uid;
         }
      }

      if (!empty($uidList)) {
         $timetracks = $this->inputIssueSel->getTimetracks($uidList, $this->startTimestamp, $this->endTimestamp);
      } else {
         $timetracks = array();
      }
      $userActivity = array();
      $tasksSupportHIA = array();
      $tasksSupportUser = array();
      $totalElapsedSupportHIA = 0;
      $totalElapsedSupportUser = 0;
      $totalElapsedOther = 0;
      foreach ($timetracks as $track) {

         $userId = $track->getUserId();

         // add new user
         if (!array_key_exists($userId, $userActivity)) {
            $user = UserCache::getInstance()->getUser($userId);
            $userActivity[$userId] = array(
               'userName' => $user->getRealname(),
               'elapsedSupportHIA' => 0,
               'elapsedSupportUser' => 0,
               'elapsedOther' => 0,
            );
         }

         // check TaskType : SupportHIA,SupportUser,Other
         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $summary = $issue->getSummary();
         if (preg_match($this->regEx_taskSupportHIA, $summary)) {
            $userActivity[$userId]['elapsedSupportHIA'] += $track->getDuration();
            $totalElapsedSupportHIA += $track->getDuration();
            if (!array_key_exists($issue->getId(), $tasksSupportHIA)) {
               $tasksSupportHIA[$issue->getId()] = array(
                  'taskId' => Tools::issueInfoURL($issue->getId(), NULL, true),
                  'summary' => $summary,
                  'elapsed' => 0,
               );
            }
            $tasksSupportHIA[$issue->getId()]['elapsed'] += $track->getDuration();
         } else if (preg_match($this->regEx_taskSupportUser, $summary)) {
            $userActivity[$userId]['elapsedSupportUser'] += $track->getDuration();
            $totalElapsedSupportUser += $track->getDuration();
            if (!array_key_exists($issue->getId(), $tasksSupportUser)) {
               $tasksSupportUser[$issue->getId()] = array(
                  'taskId' => Tools::issueInfoURL($issue->getId(), NULL, true),
                  'summary' => $summary,
                  'elapsed' => 0,
               );
            }
            $tasksSupportUser[$issue->getId()]['elapsed'] += $track->getDuration();
         } else {
            $userActivity[$userId]['elapsedOther'] += $track->getDuration();
            $totalElapsedOther += $track->getDuration();
         }
      }

      $totalElapsed = $totalElapsedSupportHIA + $totalElapsedSupportUser + $totalElapsedOther;

      $this->execData = array();
      $this->execData['userActivityArray'] = $userActivity;
      $this->execData['totalElapsed'] = $totalElapsed;
      $this->execData['totalElapsedSupportHIA'] = $totalElapsedSupportHIA;
      $this->execData['totalElapsedSupportUser'] = $totalElapsedSupportUser;
      $this->execData['totalElapsedOther'] = $totalElapsedOther;
      $this->execData['pcentSupportHIA'] = round($totalElapsedSupportHIA * 100 / $totalElapsed, 2);
      $this->execData['pcentSupportUser'] = round($totalElapsedSupportUser * 100 / $totalElapsed, 2);

      //self::$logger->error("HIA: $totalElapsedSupportHIA * 100 / $totalElapsed", true);
      //self::$logger->error("User: $totalElapsedSupportUser * 100 / $totalElapsed", true);

      // option dialogBox
      $this->execData['userSettings'] = $this->userSettings;

      // Help dialogbox
      $this->execData['taskListSupportHIA'] = $tasksSupportHIA;
      $this->execData['taskListSupportUser'] = $tasksSupportUser;

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables= array();
      $prefix = 'fdjSopNbSupport_';

      foreach ($this->execData as $key => $val) {
         $smartyVariables[$prefix.$key] = $val;
      }
      $smartyVariables[$prefix.'startDate'] = Tools::formatDate("%Y-%m-%d", $this->startTimestamp);
      $smartyVariables[$prefix.'endDate'] = Tools::formatDate("%Y-%m-%d", $this->endTimestamp);

      // add pluginSettings (if needed by smarty)
      $smartyVariables[$prefix.self::OPTION_REGEX_TASK_SUPPORT_HIA] = $this->regEx_taskSupportHIA;
      $smartyVariables[$prefix.self::OPTION_REGEX_TASK_SUPPORT_USER] = $this->regEx_taskSupportUser;

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
FdjSopNbSupport::staticInit();

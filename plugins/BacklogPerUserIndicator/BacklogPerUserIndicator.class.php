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
 * Description of BacklogPerUserIndicator
 *
 * For each user, return the sum of the backlog of its assigned tasks.
 * 
 * @author lob
 */
class BacklogPerUserIndicator extends IndicatorPluginAbstract {

   const OPTION_IS_EXT_REF = 'isExtRef';
   
   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $teamid;
   private $sessionUserid;
   private $isManager;

   // config options from Dashboard
   private $isExtRef;

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM,
         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Backlog per User');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Check all the tasks and return the backlog per User');
      if (!$isShortDesc) {
         $desc .= '<br><br>';
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
         'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
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
      try {
         $sessionUser = UserCache::getInstance()->getUser($this->sessionUserid);
         $this->isManager = $sessionUser->isTeamManager($this->teamid);
      } catch (Exception $e) {
         $this->isManager = NULL;
      }
      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isExtRef = false;
      
   }

   /**
    * settings are saved by the Dashboard
    * 
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_EXT_REF, $pluginSettings)) {
            $this->isExtRef = $pluginSettings[self::OPTION_IS_EXT_REF];
         }
      }
   }


   /**
    *
    * returns an array of [user][activity]
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $members = $team->getActiveMembers(null, null, true);

      $issueList = $this->inputIssueSel->getIssueList();

      // ---
      $userList=array();
      $formattedTaskListPerUser = array();
      $iSelPerUser = array();
      $iSelOpenTasks = new IssueSelection('nonResolved');
      /* @var $issue Issue */
      foreach ($issueList as $issue) {

         try {
            // for each issue that is not resolved, add reestimated to handler.

            if (!$issue->isResolved()) {

               $userId = $issue->getHandlerId();
               if (0 != $userId) {
                  $user = UserCache::getInstance()->getUser($userId);
                  $userList[$userId] = $user->getRealname();
               } else {
                  $userList[0] = '(unknown 0)';
               }
               
               if (!$this->isExtRef) {
                  $displayedTaskId = NULL;
               } else {
                   $displayedTaskId = (NULL != $issue->getTcId() && false != trim($issue->getTcId())) ? $issue->getTcId() : 'm-'.$issue->getId();
               }
               
               $tooltipAttr = $issue->getTooltipItems($this->teamid, $this->sessionUserid, $this->isManager);
               // add task summary in front
               $tooltipAttr = array(T_('Summary') => $issue->getSummary()) + $tooltipAttr;
               $formattedTaskListPerUser[$userId][] = Tools::issueInfoURL($issue->getId(), $tooltipAttr, FALSE, $displayedTaskId);
               
               if (!array_key_exists($userId, $iSelPerUser)) {
                  $iSelPerUser[$userId] = new IssueSelection('user_'.$userId);
               }
               $iSelPerUser[$userId]->addIssue($issue->getId());
               $iSelOpenTasks->addIssue($issue->getId());
            }

         } catch (Exception $e) {
            self::$logger->error("BacklogPerUserIndicator: ".$e->getMessage());
         }
      }

      // sort by name, keep key-val association
      asort($userList);
      asort($members);

      // team members
      $usersActivity = array();
      foreach ($members as $userId => $userName) {
         if (array_key_exists($userId, $iSelPerUser)) {
            $isel = $iSelPerUser[$userId];
            $progress = round($isel->getProgress() * 100);
            $backlog = $isel->duration;
            $taskList = implode(', ', $formattedTaskListPerUser[$userId]);
            $nbTasks = count($formattedTaskListPerUser[$userId]);
         } else {
            $progress = 0;
            $backlog = '';
            $taskList = '';
            $nbTasks = '';
         }
         $usersActivity[$userId] = array(
            'handlerName' => $userName,
            'backlog' => $backlog,
            'nbTasks' => $nbTasks,
            'progress' => $progress,
            'taskList' => $taskList,
         );
      }
      // users not in team
      foreach ($userList as $userId => $userName) {
         if ((!array_key_exists($userId, $members)) && (0 != $userId)){
            $isel = $iSelPerUser[$userId];
            $usersActivity[$userId] = array(
               'handlerName' => '<span class="warn_font">'.$userName.'</span>',
               'backlog' => $isel->duration,
               'nbTasks' => count($formattedTaskListPerUser[$userId]),
               'progress' => round($isel->getProgress() * 100),
               'taskList' => implode(', ', $formattedTaskListPerUser[$userId]),
            );
         }
      }
      // unassigned tasks
      if (array_key_exists(0, $userList)) {
         $isel = $iSelPerUser[0];
         $usersActivity[0] = array(
            'handlerName' => '<span class="error_font">'.T_('(unknown 0)').'</span>',
            'backlog' => $isel->duration,
            'nbTasks' => count($formattedTaskListPerUser[0]),
            'progress' => round($isel->getProgress() * 100),
            'taskList' => implode(', ', $formattedTaskListPerUser[0]),
         );
      }
      // Total
      $totalArray = array(
         'handlerName' => T_('TOTAL'),
         'backlog' => $iSelOpenTasks->duration,
         'nbTasks' => count($iSelOpenTasks->getIssueList()),
         'progress' => round($iSelOpenTasks->getProgress() * 100),
         'taskList' => '',
      );


      #var_dump($usersActivity);
      $this->execData = array();
      $this->execData['userArray'] = $usersActivity;
      $this->execData['totalArray'] = $totalArray;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = array(
         'backlogPerUserIndicator_userArray' => $this->execData['userArray'],
         'backlogPerUserIndicator_totalArray' => $this->execData['totalArray'],

          // add pluginSettings (if needed by smarty)
         'backlogPerUserIndicator_'.self::OPTION_IS_EXT_REF => $this->isExtRef,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['backlogPerUserIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['backlogPerUserIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
BacklogPerUserIndicator::staticInit();

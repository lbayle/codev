
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
class FillPeriodWithTimetracks extends IndicatorPluginAbstract {

   const OPTION_START_TIMESTAMP = 'startTimestamp';
   const OPTION_END_TIMESTAMP = 'endTimestamp';
   const OPTION_MANAGED_USERID = 'managedUserid';
   const OPTION_ISSUE_ID = 'issueId';
   const OPTION_JOB_ID = 'jobId';
   const OPTION_ELAPSED_TARGET = 'elapsedTarget';
   const OPTION_FINAL_BACKLOG = 'finalBacklog';
   const OPTION_TIMETRACK_NOTE = 'ttNote';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $teamId;
   private $issueId;
   private $managedUserId;
   private $jobId;
   private $elapsedTarget;
   private $finalBacklog;
   private $ttNote;

   // internal
   private $sessionUserId;
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM_ADMIN,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Fill period with timetracks');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Add multiple timetracks at once');
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
         'lib/select2/select2.min.js',
         'js_min/tabs.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      // --- set default values
      $weekDates = Tools::week_dates(date('W'),date('Y'));
      $this->startTimestamp = $weekDates[1];
      $this->endTimestamp = $weekDates[5];
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_START_TIMESTAMP, $pluginSettings)) {
            $this->startTimestamp = $pluginSettings[self::OPTION_START_TIMESTAMP];
         }
         if (array_key_exists(self::OPTION_END_TIMESTAMP, $pluginSettings)) {
            $this->endTimestamp = $pluginSettings[self::OPTION_END_TIMESTAMP];
         }
         if (array_key_exists(self::OPTION_MANAGED_USERID, $pluginSettings)) {
            $this->managedUserId = $pluginSettings[self::OPTION_MANAGED_USERID];
         }
         if (array_key_exists(self::OPTION_ISSUE_ID, $pluginSettings)) {
            $this->issueId = $pluginSettings[self::OPTION_ISSUE_ID];
         }
         if (array_key_exists(self::OPTION_JOB_ID, $pluginSettings)) {
            $this->jobId = $pluginSettings[self::OPTION_JOB_ID];
         }
         if (array_key_exists(self::OPTION_ELAPSED_TARGET, $pluginSettings)) {
            $this->elapsedTarget = $pluginSettings[self::OPTION_ELAPSED_TARGET];
         }
         if (array_key_exists(self::OPTION_FINAL_BACKLOG, $pluginSettings)) {
            $this->finalBacklog = $pluginSettings[self::OPTION_FINAL_BACKLOG];
         }
         if (array_key_exists(self::OPTION_TIMETRACK_NOTE, $pluginSettings)) {
            $this->ttNote = $pluginSettings[self::OPTION_TIMETRACK_NOTE];
         }
      }
   }

   /*
    * used by Ajax to display information when user changes the dates and user
    */
   public function getAvailableOnPeriod() {

      $managedUser = UserCache::getInstance()->getUser($this->managedUserId);
      $timestamp = mktime(0, 0, 0, date("m", $this->startTimestamp), date("d", $this->startTimestamp), date("Y", $this->startTimestamp));
      $availableOnPeriod = 0;

      while ($timestamp <= $this->endTimestamp) {
         $availableOnPeriod += $managedUser->getAvailableTime($timestamp);
         $timestamp = strtotime("+1 day",$timestamp);
      }
      return round($availableOnPeriod,2);
   }

   /*
    * used by Ajax when user selects a task in the list
    */
   public function getIssueInfo() {

      $team = TeamCache::getInstance()->getTeam($this->teamId);
      $issue = IssueCache::getInstance()->getIssue($this->issueId);
      $projectid = $issue->getProjectId();
      $project = ProjectCache::getInstance()->getProject($projectid);

      $teamProjTypes = $team->getProjectsType();
      $jobList = $project->getJobList($teamProjTypes[$projectid]);

      $data = array (
         'jobList' => $jobList,
         'backlog' => $issue->getBacklog(),
         );
      return $data;
   }

   /**
    *
    * @return type
    */
   public function addTimetracks() {

      $managedUser = UserCache::getInstance()->getUser($this->managedUserId);
      $team = TeamCache::getInstance()->getTeam($this->teamId);

      $strActionLogs = "----------- ".date("Y-m-d H:i:s")." -----------\n";
      $realElapsed = 0;
      $timestamp = mktime(0, 0, 0, date("m", $this->startTimestamp), date("d", $this->startTimestamp), date("Y", $this->startTimestamp));

      while (($timestamp <= $this->endTimestamp) &&
             ($realElapsed < $this->elapsedTarget)) {

         $stillToDo = round($this->elapsedTarget - $realElapsed,2);
         $availToday = round($managedUser->getAvailableTime($timestamp),2);

         if (0 == $availToday) {
            //$strActionLogs .= date("Y-m-d (D)", $timestamp)." : No time available. stillToDo=$stillToDo\n";
            $timestamp = strtotime("+1 day",$timestamp);
            continue;
         }

         // not more than necessary
         $ttDuration = ($availToday <= $stillToDo) ? $availToday : $stillToDo;
         $realElapsed += $ttDuration;
         $trackid = TimeTrack::create($this->managedUserId, $this->issueId, $this->jobId, $timestamp, $ttDuration, $this->sessionUserId, $this->teamId);

         if (1 == $team->getGeneralPreference('useTrackNote') && strlen($this->ttNote)!=0) {
            TimeTrack::setNote($this->issueId, $trackid, $this->ttNote, $this->managedUserId);
         }

         // TODO : add SUCCESS / ERROR
         $strActionLogs .= date("Y-m-d", $timestamp)." : $ttDuration day on task $this->issueId for user ".$managedUser->getRealname()."\n";

         $timestamp = strtotime("+1 day",$timestamp);
      }

      // TODO : add SUCCESS / ERROR
      $strActionLogs .= "STATUS : add $realElapsed days on task $this->issueId for user ".$managedUser->getRealname()."\n";

      // set backlog for the task
      $issue = IssueCache::getInstance()->getIssue($this->issueId);
      if (!$issue->isResolved()) {
         $issue->setBacklog($this->finalBacklog);
      }

      $data = array (
         'elapsedTarget' => $this->elapsedTarget,
         'realElapsed' => $realElapsed,
         'availableOnPeriod' => $this->getAvailableOnPeriod(),
         'actionLogs' => htmlentities($strActionLogs),
         );
      return $data;
   }


  /**
    *
    */
   public function execute() {

      $sessionUser = UserCache::getInstance()->getUser($this->sessionUserId);
      $team = TeamCache::getInstance()->getTeam($this->teamId);
      $teamMembers= $team->getMembers(true);

      // get all tasks (regularProjects + sidetasksProjects)

      $hideStatusAndAbove = 0; // may be usefull to add as option, for huge projects
      $isHideResolved = false;

      $issueList = array();
      $teamProjects = $team->getProjects();
      foreach ($teamProjects as $projectid => $pname) {
         $project = ProjectCache::getInstance()->getProject($projectid);
         $prjIssueList = $project->getIssues(0, $isHideResolved, $hideStatusAndAbove);
         $issueList = array_merge($issueList, $prjIssueList);
      }
      //ksort($issueList);
      $taskList = array();
      foreach ($issueList as $issue) {
         $taskList[$issue->getId()] = $issue->getId().' : '.$issue->getSummary();
      }

      $this->execData = array (
         'teamMembers' => $teamMembers,
         'taskList' => $taskList,
         'ttNote' => T_("Timetrack added by ").$sessionUser->getRealname(),
         );
      return $this->execData;

   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $prefix='FillPeriodWithTimetracks_';

      $availableTeamMembers = SmartyTools::getSmartyArray($this->execData['teamMembers'],$this->managedUserId);
      $taskList = SmartyTools::getSmartyArray($this->execData['taskList'],$this->issueId);


      $smartyVariables = array(
         $prefix.'teamMembers' => $availableTeamMembers, // $this->execData['teamMembers'],
         $prefix.'startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         $prefix.'endDate'   => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         $prefix.'taskList' => $taskList,

         // add pluginSettings (if needed by smarty)
         $prefix.self::OPTION_TIMETRACK_NOTE => $this->execData['ttNote'],
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
FillPeriodWithTimetracks::staticInit();

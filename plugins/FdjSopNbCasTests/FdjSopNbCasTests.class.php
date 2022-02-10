
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
 * Description of FdjSopNbCasTests
 *
 * @author lob
 */
class FdjSopNbCasTests extends IndicatorPluginAbstract {

   const OPTION_REGEX_TESTS = 'regEx_tests'; // "/<([0-9]+)>/"
   const OPTION_TASK_TYPE   = 'taskType'; // 1,2,3 => REGEX_TASK_INSTALL_x
   const OPTION_USER_SETTINGS = 'userSettings';

   const REGEX_TASK_CAS_TEST = '/Rédaction.*cas de tests/i'; // S1 - Rédaction de Cas de tests
   const REGEX_TASK_CAMPAGNE_TEST = '/Exécution.*campagne de tests/i'; // S5 - Exécution de campagne de tests

   const TASK_LABEL_1 = 'S1 - Rédaction de Cas de tests';
   const TASK_LABEL_2 = 'S5 - Exécution de campagne de tests';

   private static $logger;
   private static $domains;
   private static $categories;

   private static $typeToUO; // array
   private static $typeToRegEx; // array
   private static $taskTypes; // array
   private static $uoInManDaysRef; // TODO:  options from Dashboard

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $regEx_test;
   private $taskType;
   private $userSettings;

   // internal
   private $timetracks;
   private $UOs;
   protected $execData;

   private $filteredIssueSel; // $inputIssueSel + regEx_taskType

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
      // depending on task type, one FDL has != UO
      self::$typeToUO = array (
         '1' => (1/20),    // 20 cas de tests exécutés = 1 UO  --- Cas test
         '2' => (1/100),   // 100 cas tests = 1 UO             --- Campagne tests
      );

      self::$typeToRegEx = array (
         '1' => self::REGEX_TASK_CAS_TEST ,
         '2' => self::REGEX_TASK_CAMPAGNE_TEST,
      );

      self::$taskTypes = array (
         '1' => self::TASK_LABEL_1,
         '2' => self::TASK_LABEL_2,
      );

      # calcul de rentabilité
      # cet ABAC donne le nbre de jours necessaires a la resolution d'un UO (estimation prise lors du devis)
      self::$uoInManDaysRef = array (
         '1' => 2, // S1: 1 UO = 2 days
         '2' => 8, // S5: 1 UO = 8 days
      );
   }

   public static function getName() {
      return '== FDJ == SOP - Nb tests effectués';
   }
   public static function getDesc($isShortDesc = true) {
      return 'Parse les Notes d\'imputations et calcule le nbre de tests effectués';
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
      $this->regEx_test = '/_([0-9]+)_/';

      $this->taskType = '1'; // default value

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
         if (array_key_exists(self::OPTION_REGEX_TESTS, $pluginSettings)) {
            // WARN: regex has ben stored with htmlentities !!
            $this->regEx_test = html_entity_decode($pluginSettings[self::OPTION_REGEX_TESTS]);
         }
         if (array_key_exists(self::OPTION_TASK_TYPE, $pluginSettings)) {
            $this->taskType = $pluginSettings[self::OPTION_TASK_TYPE];
         }
         if (array_key_exists(self::OPTION_USER_SETTINGS, $pluginSettings)) {
            // override each user values, do not replace the complete block
            $newUserSettings = $pluginSettings[self::OPTION_USER_SETTINGS];
            foreach(array_keys($this->userSettings) as $uid) {
               if (array_key_exists($uid, $newUserSettings)) {
                  $this->userSettings[$uid]['enabled'] = $newUserSettings[$uid]['enabled'] == 0 ? false : true;
               }
            }
         }
      }
   }

   private function getTimetrackUO($timetrackId) {

      if (NULL === $this->UOs) {
         $this->UOs = array();
         $timetrackidList = array_keys($this->timetracks);
         $formated_trackids = implode(', ', $timetrackidList);
         if($formated_trackids != "") {

            $sql = AdodbWrapper::getInstance();
            $query2 = "SELECT timetrackid, value FROM codev_uo_table WHERE timetrackid IN ($formated_trackids)";
            $result2 = $sql->sql_query($query2);
            if($sql->getNumRows($result2) != 0) {
               while($row2 = $sql->fetchObject($result2)) {
                  $this->UOs[$row2->timetrackid] = $row2->value;
               }
            }
         }
      }
      return $this->UOs[$timetrackId];
   }

    /**
    * returns a subset of inputIssueSel, filtered by REGEX_TASK_xxx
    * The regEx is applied on issue summary
    *
    * @return an array of TimeTrack
    */
   private function getFilteredISel($taskType) {

      //return $this->inputIssueSel;

      $inputIssueList = $this->inputIssueSel->getIssueList();
      $filteredISel = new IssueSelection('filtered_'.$taskType);
      foreach ($inputIssueList as $issue) {
         $summary = $issue->getSummary();
         if (preg_match(self::$typeToRegEx[$taskType], $summary)) {
            $filteredISel->addIssue($issue->getId());
         }
      }
      return $filteredISel;
   }

   /**
    *
    * @return array execData
    */
   public function execute() {

      $this->filteredIssueSel = $this->getFilteredISel($this->taskType);

      // filter timetracks by user
      $uidList = array();
      foreach ($this->userSettings as $uid => $uSettings) {
         if ($uSettings['enabled']) {
            $uidList[] = $uid;
         }
      }

      if (!empty($uidList)) {
         $this->timetracks = $this->filteredIssueSel->getTimetracks($uidList, $this->startTimestamp, $this->endTimestamp); // warn: also used by getTimetrackUO()
      } else {
         $this->timetracks = array();
      }

      $nbTimetracks = count($this->timetracks);
      $nbTestsTotal = 0;

      foreach ($this->timetracks as $trackid => $track) {

         $user = UserCache::getInstance()->getUser($track->getUserId());
         $note = $track->getNote();

         // search for "bla bla <3> bla bla" where '3' is nbTests
         $matches = array();
         preg_match($this->regEx_test, $note, $matches);

         if (array_key_exists('1', $matches)) {
            $nbTestsTotal += $matches[1];
            $nbTimetracksWithTests += 1;
            $nbTimetrackTests = str_replace('.', ',',round($matches[1], 2));
         } else {
            $nbTimetrackTests = "No match !";
         }

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $timetracksArray[$trackid] = array(
            'user' => $user->getRealname(),
            'issueId' => Tools::issueInfoURL($track->getIssueId(), $issue->getSummary(), true),
            'ttDate' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
            'elapsed' => str_replace('.', ',',round($track->getDuration(), 2)),
            //'ttUO' => str_replace('.', ',',$this->getTimetrackUO($track->getId())),
            'ttCalculatedUO' => str_replace('.', ',',$nbTimetrackTests * self::$typeToUO[$this->taskType]),
            'nbTests' => $nbTimetrackTests,
            'ttNote' => nl2br(htmlspecialchars($note)),
            'id' => $track->getId(),
         );
      }

      // --- show issue selection (depends on RegEx)
      $selectedTasks = array();
      $selectedIssueList = $this->filteredIssueSel->getIssueList();
      foreach ($selectedIssueList as $issue) {
         $selectedTasks[] = array(
            'taskId' => Tools::issueInfoURL($issue->getId(), NULL, true),
            'summary' => $issue->getSummary(),
         );
      }

      $smartyTaskTypes = SmartyTools::getSmartyArray(self::$taskTypes,$this->taskType);

      $totalUO = $nbTestsTotal * self::$typeToUO[$this->taskType];
      $totalElapsed = $this->filteredIssueSel->getElapsed();

      $this->execData = array();
      $this->execData['taskTypes'] = $smartyTaskTypes; // --- Combobox
      $this->execData['totalUO'] = $totalUO;
      $this->execData['totalElapsed'] = $totalElapsed;
      $this->execData['testValueInUO'] = self::$typeToUO[$this->taskType];
      $this->execData['nbTests'] = $nbTestsTotal;
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['nbTimetracksWithTests'] = $nbTimetracksWithTests;
      $this->execData['nbTimetracksNoTests'] = $nbTimetracks - $nbTimetracksWithTests;
      $this->execData['timetracksArray'] = $timetracksArray;
      $this->execData['selectedTaskType'] = $this->taskType;
      $this->execData['selectedTaskTypeStr'] = self::$taskTypes[$this->taskType]; // overview title
      $this->execData['selectedTasks'] = $selectedTasks;

      // rentabilite UO
      $this->execData['uoInManDaysRef'] = self::$uoInManDaysRef[$this->taskType];
      if ((0 != $totalElapsed) && (0 != $totalUO)) {
         $this->execData['uoInManDaysReal'] = round($totalElapsed / $totalUO, 2);
         $this->execData['uoPerf'] = round(self::$uoInManDaysRef[$this->taskType] * $totalUO / $totalElapsed, 2);  // uoInManDaysRef / ($totalElapsed / $totalUO)
         $this->execData['uoPerfColor'] = ($this->execData['uoPerf'] < 1) ? 'red' : 'green';
      }

      // option dialogBox
      $this->execData['userSettings'] = $this->userSettings;

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables= array();
      foreach ($this->execData as $key => $val) {
         $smartyVariables['fdjSopNbCasTests_'.$key] = $val;
      }
      $smartyVariables['fdjSopNbCasTests_startDate'] = Tools::formatDate("%Y-%m-%d", $this->startTimestamp);
      $smartyVariables['fdjSopNbCasTests_endDate'] = Tools::formatDate("%Y-%m-%d", $this->endTimestamp);

      // add pluginSettings (if needed by smarty)
      $smartyVariables['fdjSopNbCasTests_'.self::OPTION_REGEX_TESTS] = $this->regEx_test;
      $smartyVariables['fdjSopNbCasTests_regEx_taskType'] = self::$typeToRegEx[$this->taskType];

      if (false == $isAjaxCall) {
         $smartyVariables['fdjSopNbCasTests_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjSopNbCasTests_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
FdjSopNbCasTests::staticInit();

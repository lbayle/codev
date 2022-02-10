
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
 * Description of FdjSopNbInstall
 *
 * @author lob
 */
class FdjSopNbInstall extends IndicatorPluginAbstract {

   const OPTION_REGEX_INSTALL = 'regEx_install'; // "/_([0-9]+)_/"  was: '/([a-zA-Z0-9]+_FDL[0-9]+)/'
   const OPTION_USER_SETTINGS = 'userSettings';
   const OPTION_VALUE_IN_UO = 'valueInUO'; // how many UO for one install

   const TASK_LABEL_1 = 'S3 - Première install < 0,5 jour';
   const TASK_LABEL_2 = 'S3 - Première install > 2 jours';
   const TASK_LABEL_3 = 'S3 - Reproduction d\'une install';


   // S3 - Première Installation - De moins de 0,5 jours
   // S3 - Première Installation - De plus de 2 jours
   // S3 - Reproduction d'une installation
   const REGEX_TASK_INSTALL_ALL = '/installation/i';

   private static $logger;
   private static $domains;
   private static $categories;

   private static $taskTypes; // array
   private static $uoInManDaysRef;  // TODO:  options from Dashboard

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $regEx_nbInstall;
   private $taskType;
   private $userSettings;
   private $valueInUO; // 1 install => 1 UO

   // internal
   private $teamid;
   private $commandId;
   private $timetracks;
   protected $execData;

   private $filteredIssueSel; // $inputIssueSel + regEx_task


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         #self::DOMAIN_TASK,
         self::DOMAIN_COMMAND,
         #self::DOMAIN_COMMAND_SET,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );

      self::$taskTypes = array (
         '1' => self::TASK_LABEL_1, //  Première install < 0,5 jour
         '2' => self::TASK_LABEL_2, //  Première install > 2 jours
         '3' => self::TASK_LABEL_3, //  Reproduction d'une install
      );

      # calcul de rentabilité
      # cet ABAC donne le nbre de jours necessaires a la resolution d'un UO (estimation prise lors du devis)
      self::$uoInManDaysRef = array (
         '1' => 0.5, // S3 install 0.5j : 1 UO = 0.5 days
         '2' => 2, // S5 install > 2j   : 1 UO  = 2 days
         '3' => 0.5, // S5 repro = 0.5  : 1 UO  days
      );
   }

   public static function getName() {
      return '== FDJ == SOP - Nombre d\'installations v2';
   }
   public static function getDesc($isShortDesc = true) {
      return 'Parse les Notes d\'imputations et calcule le nbre d\'installations';
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      // 1.1.0 include FDI
      // 1.2.0 count nbInstall <ENV>_<FDI>_<nbInstall>
      return '1.2.0';
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

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID)) {
         $this->commandId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID);
      } else {
         // TODO could be DOMAIN_TASK or DOMAIN_COMMAND_SET...
         $this->commandId = NULL;
      }

      // v1.0.0 tokens to find: <ENV>_<FDL00000>
      //$this->regEx_FDL = '/([a-zA-Z0-9]+_FDL[0-9]+)/';

      // v1.1.0 tokens to find: <ENV>_<FDL00000> AND <ENV>_<FDI00000>
      //$this->regEx_FDL = '/([a-zA-Z0-9]+_FD[LI][0-9]+)/';

      // v1.2.0 tokens to find: <ENV>_<FDL00000> AND <ENV>_<FDI00000> AND <ENV>_<FDI00000>_<NbInstall>
      //$this->regEx_FDL = '/([a-zA-Z0-9]+_FD[LI][0-9]+)(_([0-9]+))?/'; // _nbInstall is optional
      //$this->regEx_FDL = '/([a-zA-Z0-9]+_FD[LI][0-9]+)(_[0-9]+)?/'; // _nbInstall is optional
      $this->regEx_nbInstall ="/_([0-9]+)_/";

      $this->taskType = '1'; // Unique value !! all others are DEPRECATED
      $this->valueInUO = 1; // 1 install = 1 UO

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
         if (array_key_exists(self::OPTION_REGEX_INSTALL, $pluginSettings)) {
            // WARN: regex has ben stored with htmlentities !!
            $this->regEx_nbInstall = html_entity_decode($pluginSettings[self::OPTION_REGEX_INSTALL]);
         }
         if (array_key_exists(self::OPTION_VALUE_IN_UO, $pluginSettings)) {
            $this->valueInUO = $pluginSettings[self::OPTION_VALUE_IN_UO];
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

   /**
    * returns a subset of inputIssueSel, filtered by REGEX_TASK_INSTALL_x
    * The regEx is applied on issue summary
    *
    * @return an array of TimeTrack
    */
   private function getFilteredISel($isel) {

      $inputIssueList = $isel->getIssueList();
      $filteredISel = new IssueSelection('install_tasks');
      foreach ($inputIssueList as $issue) {
         $summary = $issue->getSummary();

         if (preg_match(self::REGEX_TASK_INSTALL_ALL, $summary)) {
            $filteredISel->addIssue($issue->getId());
         }
      }
      return $filteredISel;
   }

   /**
    * on doit checker les doublons dans les imputations des trimestres précédents.
    * pour cela, on récupère les tâches dann les commandes dont
    * le status < 'closed'
    *
    * @return array cmdId => cmdName
    */
   private function getCommands($cmdStateThreshold = Command::state_closed) {
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamCommands = $team->getCommands();

      $cmds = array();
      foreach ($teamCommands as $cmdId => $cmd) {
         if ($this->commandId == $cmdId) { continue; }
         if ($cmd->getState() < $cmdStateThreshold) {
           $cmds[$cmdId] = $cmd->getName();
         }
      }
      return $cmds;
   }

   /**
    *
    * @return array execData
    */
   public function execute() {

      $this->filteredIssueSel = $this->getFilteredISel($this->inputIssueSel);

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
      $nbInstallTotal = 0;

      foreach ($this->timetracks as $trackid => $track) {

         $user = UserCache::getInstance()->getUser($track->getUserId());
         $note = $track->getNote();
         $nbFDLinTimetrack = 0;


         // search for "bla bla _3_ bla bla" where '3' is nbInstall
         $matches = array();
         preg_match($this->regEx_nbInstall, $note, $matches);

         if (array_key_exists('1', $matches)) {
            $nbInstallTotal += $matches[1];
            $nbTimetracksWithFDL += 1;
            $nbFDLinTimetrack = str_replace('.', ',',round($matches[1], 2));
         } else {
            $nbFDLinTimetrack = "No match !";
         }

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $this->timetracksArray[$trackid] = array(
            'user' => $user->getRealname(),
            'issueId' => Tools::issueInfoURL($track->getIssueId(), $issue->getSummary(), true),
            'ttDate' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
            'nbFDL' => $nbFDLinTimetrack,
            'ttNote' => nl2br(htmlspecialchars($note)),
            'ttCalculatedUO' => $nbFDLinTimetrack * $this->valueInUO,
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

      $totalUO = $nbInstallTotal * $this->valueInUO;

      // ----------
      $this->execData = array();
      $this->execData['totalUO'] = $totalUO;
      $this->execData['fdlValueInUO'] = $this->valueInUO;
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['nbNotesNoFDL'] = $nbTimetracks - $nbTimetracksWithFDL;
      $this->execData['nbFDL'] = $nbInstallTotal;
      $this->execData['timetracksArray'] = $this->timetracksArray;
      $this->execData['selectedTasks'] = $selectedTasks;

      // rentabilite UO
      $totalElapsed = $this->filteredIssueSel->getElapsed();
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
         $smartyVariables['fdjSopNbInstall_'.$key] = $val;
      }
      $smartyVariables['fdjSopNbInstall_startDate'] = Tools::formatDate("%Y-%m-%d", $this->startTimestamp);
      $smartyVariables['fdjSopNbInstall_endDate'] = Tools::formatDate("%Y-%m-%d", $this->endTimestamp);

      // add pluginSettings (if needed by smarty)
      $smartyVariables['fdjSopNbInstall_'.self::OPTION_REGEX_INSTALL] = $this->regEx_nbInstall;
      $smartyVariables['fdjSopNbInstall_'.self::OPTION_VALUE_IN_UO] = $this->valueInUO;
      $smartyVariables['fdjSopNbInstall_regEx_taskType'] = self::REGEX_TASK_INSTALL_ALL;
      $smartyVariables['fdjSopNbInstall_regEx_FDL'] = $this->regEx_nbInstall;

      if (false == $isAjaxCall) {
         $smartyVariables['fdjSopNbInstall_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjSopNbInstall_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
FdjSopNbInstall::staticInit();

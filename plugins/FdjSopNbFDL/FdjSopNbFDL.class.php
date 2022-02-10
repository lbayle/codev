
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
 * Description of FdjSopNbFDL
 *
 * @author lob
 */
class FdjSopNbFDL extends IndicatorPluginAbstract {

   const OPTION_REGEX_FDL = 'regEx_FDL'; // '/([a-zA-Z0-9]+_FDL[0-9]+)/'
   const OPTION_TASK_TYPE = 'taskType'; // 1,2,3 => REGEX_TASK_INSTALL_x
   const OPTION_USER_SETTINGS = 'userSettings';

   const REGEX_TASK_INSTALL_1 = '/Première install.*0,5 jour/i'; // S3 - Première Installation - De moins de 0,5 jours
   const REGEX_TASK_INSTALL_2 = '/Première install.*2 jour/i'; // S3 - Première Installation - De plus de 2 jours
   const REGEX_TASK_INSTALL_3 = '/Repro.*install/i'; // S3 - Reproduction d'une installation

   const TASK_LABEL_1 = 'S3 - Première install < 0,5 jour';
   const TASK_LABEL_2 = 'S3 - Première install > 2 jours';
   const TASK_LABEL_3 = 'S3 - Reproduction d\'une install';

   private static $logger;
   private static $domains;
   private static $categories;

   private static $typeToUO; // array
   private static $typeToRegEx; // array
   private static $taskTypes; // array
   private static $uoInManDaysRef;  // TODO:  options from Dashboard

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $regEx_FDL;
   private $taskType;
   private $userSettings;

   // internal
   private $teamid;
   private $commandId;
   private $timetracks;
   private $UOs;
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

      // depending on task type, one FDL has != UO
      self::$typeToUO = array (
         '1' => 1,    // 1 FDL = 1 UO     --- Première install < 0,5 jour
         '2' => 1,    // 1 FDL = 1 UO     --- Première install > 2 jours
         '3' => 0.25, // 1 FDL = 0.25 UO  --- Reproduction d'une install
      );

      self::$typeToRegEx = array (
         '1' => self::REGEX_TASK_INSTALL_1, //  Première install < 0,5 jour
         '2' => self::REGEX_TASK_INSTALL_2, //  Première install > 2 jours
         '3' => self::REGEX_TASK_INSTALL_3, //  Reproduction d'une install
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
      return '== FDJ == SOP - Nombre d\'installations';
   }
   public static function getDesc($isShortDesc = true) {
      return 'Parse les Notes d\'imputations et calcule le nbre de FDL/FDI installées';
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
      $this->regEx_FDL = '/([a-zA-Z0-9]+_FD[LI][0-9]+)(_[0-9]+)?/'; // _nbInstall is optional

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
    * returns a subset of inputIssueSel, filtered by REGEX_TASK_INSTALL_x
    * The regEx is applied on issue summary
    *
    * @return an array of TimeTrack
    */
   private function getFilteredISel($taskType, $isel) {

      $inputIssueList = $isel->getIssueList();
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
    * a partir des commandes identifiées, construit un tableau des FDL par Environnement
    * => a comparer avec la commande courante pour identifier les doublons
    *
    * @param type $cmdId
    * @param type $aExistingFdlList append to an existing list
    */
   private function getFDLPerEnv($cmdId, $aExistingFdlList) {

      // append to an existing list, or create
      if (NULL == $aExistingFdlList) { $aExistingFdlList = array(); }

      $cmd = CommandCache::getInstance()->getCommand($cmdId);
      $filteredCmdISel = $this->getFilteredISel($this->taskType, $cmd->getIssueSelection());
      $cmdTimetracks = $filteredCmdISel->getTimetracks(); // no timestamp specification

      foreach ($cmdTimetracks as $trackid => $track) {
         $note = $track->getNote();
         $user = UserCache::getInstance()->getUser($track->getUserId());
         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());

         // search for "ENV_FDL0000" ex: TR3_FDL0023654
         $matches = array();
         preg_match_all($this->regEx_FDL, $note, $matches);
         if (0 != count($matches[0])) {
            foreach ($matches[0] as $env_fdl) {
               // overwrite if existing
               $aExistingFdlList[$env_fdl] = array(
                   'cmdId' => $cmdId,
                   'issueId' => Tools::issueInfoURL($track->getIssueId(), $issue->getSummary(), true),
                   'timetrackId' => $trackid,
                   'ttDate' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
                   'cmdName' => $cmd->getName(),
                   'user' => $user->getRealname(),
                   'ttNote' => $note,
               );
            }
         }
      }
      return $aExistingFdlList;
   }

   /**
    *
    * @return array execData
    */
   public function execute() {

      $this->filteredIssueSel = $this->getFilteredISel($this->taskType, $this->inputIssueSel);

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
      $nbFdlWithDuplicates = 0;
      $aFdlPerEnv = array(); // key=ENV, value = string-list of "FDL, FDL, FDL"

      foreach ($this->timetracks as $trackid => $track) {

         $user = UserCache::getInstance()->getUser($track->getUserId());
         $note = $track->getNote();
         $nbFDLinTimetrack = 0;

         // search for "ENV_FDL0000" ex: INT3_FDL0023654
         $matches = array();
         preg_match_all($this->regEx_FDL, $note, $matches);

         if (0 != count($matches[0])) {
            $nbTimetracksWithFDL += 1;

            foreach ($matches[0] as $env_fdl) {
               $a = explode('_', $env_fdl);
               if (null != $a[2]) {
                  // TR3_FDIxxxxx_3 (3 components installed on this FDI)
                  //self::$logger->error("found: $env_fdl $a[0],$a[1],$a[2]<br>");
                  $nbInstall = $a[2];
               } else {
                  // TR3_FDIxxxxx => only one component installed on this FDI
                  $nbInstall = 1;
               }
               $aFdlPerEnv["$a[0]"] .= "$a[1], "; // (including duplicates)
               $nbFDLinTimetrack += $nbInstall;
               $nbFdlWithDuplicates += $nbInstall;
            }
         } else {
            $nbFDLinTimetrack = "No match !";
         }

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $this->timetracksArray[$trackid] = array(
            'user' => $user->getRealname(),
            'issueId' => Tools::issueInfoURL($track->getIssueId(), $issue->getSummary(), true),
            'ttDate' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
            'nbFDL' => $nbFDLinTimetrack,
            'listFDL' => implode(', ', $matches[0]),
            'ttNote' => nl2br(htmlspecialchars($note)),
            //'ttUO' => str_replace('.', ',',$this->getTimetrackUO($track->getId())),
            'ttCalculatedUO' => $nbFDLinTimetrack * self::$typeToUO[$this->taskType],
            'id' => $track->getId(),
         );
      }

      // ----- search for duplicates ----

      $prevCommands = $this->getCommands();
      $aExistingFdlList = array();
      foreach ($prevCommands as $cmdId => $cmdName) {
         $aExistingFdlList = $this->getFDLPerEnv($cmdId, $aExistingFdlList);
      }

      $nbTotalFDL = 0;
      $overviewArray = array();
      $listDuplicates = array();
      foreach ($aFdlPerEnv as $env => $fdlString) {
         $fdlString = substr($fdlString, 0, -2); // remove trailing ', '
         $listFdlWithDuplicates = explode(', ', $fdlString);
         $listFDL = array();

         foreach ($listFdlWithDuplicates as $fdl) {
            // search if duplicate in current command
            if (in_array($fdl, $listFDL)) {
               $listDuplicates[] = $env.'_'.$fdl;
               continue;
            }
            // search if dupplicate in previous commands
            if (array_key_exists($env.'_'.$fdl, $aExistingFdlList)) {
               $listDuplicates[] = $env.'_'.$fdl;
               // TOTO remember $aExistingFdlList[$env.'_'.$fdl]
               $prevDupTimetracks[] = $aExistingFdlList[$env.'_'.$fdl];
               continue;
            }

            // first occurence
            $listFDL[] = $fdl;
         }
         sort($listFDL);
         $overviewArray[$env] = array(
           'env' => $env,
           'nbFDL' => count($listFDL),
           'nbCalculatedUO' => count($listFDL) * self::$typeToUO[$this->taskType],
           'listFDL' => implode(', ', $listFDL),
         );
         $nbTotalFDL += count($listFDL); // without duplicates
      }
      sort($listDuplicates);

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

      // total UO depends on task type:
      // if != 3, then duplicated must be omitted, if 3 (Repro) then count all
      // v1.2.0: Plus maintenant, on compte aussi les doublons, car on fait une FDI pour plusieurs
      // //      composants, donc il peut y avoir plusieurs imputations pour une meme FDI
      #if ('3' == $this->taskType) {
         $totalUO = $nbFdlWithDuplicates * self::$typeToUO[$this->taskType];
      #} else {
      #   $totalUO = $nbTotalFDL * self::$typeToUO[$this->taskType];
      #}

      // ----------
      $this->execData = array();
      $this->execData['taskTypes'] = $smartyTaskTypes; // --- Combobox
      $this->execData['totalUO'] = $totalUO;
      $this->execData['fdlValueInUO'] = self::$typeToUO[$this->taskType];
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['nbNotesNoFDL'] = $nbTimetracks - $nbTimetracksWithFDL;
      $this->execData['nbFdlWithDuplicates'] = $nbFdlWithDuplicates; // with duplicates
      $this->execData['nbFDL'] = $nbTotalFDL;  // without duplicates
      $this->execData['nbDuplicatedFDL'] = count($listDuplicates);
      $this->execData['listDuplicatedFDL'] = implode(', ', $listDuplicates);
      $this->execData['overviewArray'] = $overviewArray;
      $this->execData['timetracksArray'] = $this->timetracksArray;
      $this->execData['selectedTaskType'] = $this->taskType;
      $this->execData['selectedTaskTypeStr'] = self::$taskTypes[$this->taskType]; // overview title
      $this->execData['selectedTasks'] = $selectedTasks;
      $this->execData['prevCommands'] = $prevCommands;
      $this->execData['prevDupTimetracks'] = $prevDupTimetracks;

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
         $smartyVariables['fdjSopNbFDL_'.$key] = $val;
      }
      $smartyVariables['fdjSopNbFDL_startDate'] = Tools::formatDate("%Y-%m-%d", $this->startTimestamp);
      $smartyVariables['fdjSopNbFDL_endDate'] = Tools::formatDate("%Y-%m-%d", $this->endTimestamp);

      // add pluginSettings (if needed by smarty)
      $smartyVariables['fdjSopNbFDL_'.self::OPTION_REGEX_FDL] = $this->regEx_FDL;
      $smartyVariables['fdjSopNbFDL_regEx_taskType'] = self::$typeToRegEx[$this->taskType];

      if (false == $isAjaxCall) {
         $smartyVariables['fdjSopNbFDL_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjSopNbFDL_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
FdjSopNbFDL::staticInit();

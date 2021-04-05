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
class LoadPerUserGroups extends IndicatorPluginAbstract {

   const OPTION_IS_ONLY_TEAM_MEMBERS = 'isOnlyActiveTeamMembers';
   const OPTION_IS_DISPLAY_INVOLVED_USERS = 'isDisplayInvolvedUsers';
   const OPTION_IS_DISPLAY_TASKS = 'isDisplayTasks';

   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;
   private $userGroups;
   private $userDataArray;

   // config options from Dashboard
   private $isOnlyActiveTeamMembers;
   private $isDisplayInvolvedUsers;
   private $isDisplayTasks;

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_PROJECT,
         self::DOMAIN_TEAM,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return T_('Load per user groups');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Check all the timetracks of the period and return their repartition per User groups');
//      if (!$isShortDesc) {
//         $desc .= '<br><br>'.T_('bla bla');
//      }
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
         'js_min/datepicker.min.js',
         'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
         'js_min/datatable.min.js',
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

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         // WARN: no start date can return loads of results and eventualy overload the server
         $this->startTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = NULL;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isOnlyActiveTeamMembers= true;
      $this->isDisplayInvolvedUsers= false;
      $this->isDisplayTasks= false;

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $this->userGroups = $team->getUserGroups();

      $this->updateUserDataArray();

   }

   /**
    * settings are saved by the Dashboard
    *
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_ONLY_TEAM_MEMBERS, $pluginSettings)) {
            $this->isOnlyActiveTeamMembers = $pluginSettings[self::OPTION_IS_ONLY_TEAM_MEMBERS];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_INVOLVED_USERS, $pluginSettings)) {
            $this->isDisplayInvolvedUsers = $pluginSettings[self::OPTION_IS_DISPLAY_INVOLVED_USERS];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_TASKS, $pluginSettings)) {
            $this->isDisplayTasks = $pluginSettings[self::OPTION_IS_DISPLAY_TASKS];
         }
      }
   }

    /**
     *
     * @return string the filename of the uploaded CSV file.
     * @throws Exception
     */
    public static function getSourceFile() {

        if (isset($_FILES['uploaded_csv'])) {
            $filename = $_FILES['uploaded_csv']['name'];
            $tmpFilename = $_FILES['uploaded_csv']['tmp_name'];

            $err_msg = NULL;

            if ($_FILES['uploaded_csv']['error']) {
                $err_id = $_FILES['uploaded_csv']['error'];
                switch ($err_id) {
                    case 1:
                        $err_msg = "UPLOAD_ERR_INI_SIZE ($err_id) on file : " . $filename;
                        //echo"Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !";
                        break;
                    case 2:
                        $err_msg = "UPLOAD_ERR_FORM_SIZE ($err_id) on file : " . $filename;
                        //echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
                        break;
                    case 3:
                        $err_msg = "UPLOAD_ERR_PARTIAL ($err_id) on file : " . $filename;
                        //echo "L'envoi du fichier a été interrompu pendant le transfert !";
                        break;
                    case 4:
                        $err_msg = "UPLOAD_ERR_NO_FILE ($err_id) on file : " . $filename;
                        //echo "Le fichier que vous avez envoyé a une taille nulle !";
                        break;
                }
                self::$logger->error($err_msg);
            } else {
                // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
                // ce qui signifie qu'il n'y a eu aucune erreur
            }

            $extensions = array('.csv', '.CSV');
            $extension = strrchr($filename, '.');
            if (!in_array($extension, $extensions)) {
                $err_msg = T_('Please upload files with the following extension: ') . implode(', ', $extensions);
                self::$logger->error($err_msg);
            }
        } else {
            $err_msg = "no file to upload.";
            self::$logger->error($err_msg);
            self::$logger->error('$_FILES=' . var_export($_FILES, true));
        }
        if (NULL !== $err_msg) {
            throw new Exception($err_msg);
        }
        return $tmpFilename;
    }

     /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return mixed[]
     */
   public function getUserGroupsFromCSV($filename, $delimiter = ';', $enclosure = '"', $escape = '"') {

      $file = new SplFileObject($filename);
      $file->setFlags(SplFileObject::READ_CSV);
      $file->setCsvControl($delimiter, $enclosure, $escape);

      $newUserGroups = array();
      $row = 0;
      while (!$file->eof()) {
         while ($data = $file->fgetcsv($delimiter, $enclosure)) {
            $row++;
            if (1 == $row) {
               continue;
            } // skip column names
            // two columns: username ; groupName
            if ('' != $data[0] && '' != $data[1]) {

               $username = $data[0];
               $groupName = $data[1];

               if (!User::exists($username)) {
                  self::$logger->error("User '$username' not found in Mantis DB !");
               } else {
                  $userid = User::getUserId($username);
                  $newUserGroups[$userid] = $groupName;
               }
            } else {
               self::$logger->error("Row $row: Missing fields ['$data[0]','$data[1]']");
            }
         }
      }
      //self::$logger->error($newUserGroups);
      return $newUserGroups;
   }

   public function setUserGroups($uGroups) {
      $this->userGroups = $uGroups;
   }

   public function updateUserDataArray() {

      $this->userDataArray = array();

      foreach ($this->userGroups as $uid => $groupName) {
         $user = UserCache::getInstance()->getUser($uid);
         $this->userDataArray[$uid] = array(
           'userId' => $uid,
           'userName' => $user->getName(),
           'userRealname' => $user->getRealname(),
           'groupName' => $groupName,
           'color' => ($user->isTeamMember($this->teamid)) ? 'blue' : 'darkgrey',
           'message' => '',
         );
      }
      // add team members not found in userGroups
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamMembers = $team->getActiveMembers($this->startTimestamp, $this->endTimestamp);
      foreach ($teamMembers as $uid => $uname) {
         if (!array_key_exists($uid, $this->userGroups)) {
            $user = UserCache::getInstance()->getUser($uid);
            $this->userGroups[$uid] = '--undefined--';
            $this->userDataArray[$uid] = array(
               'userId' => $uid,
               'userName' => $uname,
               'userRealname' => $user->getRealname(),
               'groupName' => '--undefined--',
               'color' => 'red',
               'message' => T_("No group defined for team member"),
             );
         }
      }
      return $this->userDataArray;
   }

   private function getGroupName($userId) {

      if (NULL == $this->userDataArray) {
         $this->updateUserDataArray();
      }

      if (!array_key_exists($userId, $this->userDataArray)) {
         //self::$logger->warn("No userGroup defined for user $userId in team $this->teamid");
         $user = UserCache::getInstance()->getUser($userId);
         $this->userGroups[$userId] = '--undefined--';
         $this->userDataArray[$userId] = array(
            'userId' =>  $userId,
            'userName' =>  $user->getName(),
            'userRealname' =>  $user->getRealname(),
            'groupName' =>  '--undefined--',
            'color' => 'orange',
            'message' =>  T_("No group defined"),
          );
      }
      return $this->userDataArray[$userId]['groupName'];
   }

   /**
    *
    * returns an array of
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      // === get timetracks for each Issue
      $team = TeamCache::getInstance()->getTeam($this->teamid);

      if ($this->isOnlyActiveTeamMembers) {
         $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
      } else {
         // include also timetracks of users not in the team (relevant on ExternalTasksProjects)
         $useridList = NULL;
      }
      $timetracks = $this->inputIssueSel->getTimetracks($useridList, $this->startTimestamp, $this->endTimestamp);
      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly

      $totalElapsedOnRegularPrj = 0;
      $totalElapsedOnSidetasksPrj = 0;
      $totalElapsedOnPeriod = 0;

      $groupDataArray = array(); // key = groupName
      foreach ($timetracks as $track) {

         // find real date range
         if ( (NULL == $realStartTimestamp) || ($track->getDate() < $realStartTimestamp)) {
            $realStartTimestamp = $track->getDate();
         }
         if ( (NULL == $realEndTimestamp) || ($track->getDate() > $realEndTimestamp)) {
            $realEndTimestamp = $track->getDate();
         }
         $user = UserCache::getInstance()->getUser($track->getUserId());
         $currentUserGroupName = $this->getGroupName($user->getId());
         $prjType = $team->getProjectType($track->getProjectId());

         if ((Project::type_regularProject == $prjType) ||  // regular project
             (Project::type_sideTaskProject == $prjType)) {

            if (!array_key_exists($currentUserGroupName, $groupDataArray)) {
               $groupDataArray[$currentUserGroupName] = array(
                  'groupName' => $currentUserGroupName,
                  'elapsedOnRegularPrj' => 0,
                  'elapsedOnSidetasksPrj' => 0,
                  'groupTotalElapsed' => 0,
                  'involvedUsers' => $user->getName(),
                  'tasks' => $track->getIssueId(),
               );
            }
            switch ($prjType) {
               case Project::type_regularProject: // regular project
                  $groupDataArray[$currentUserGroupName]['elapsedOnRegularPrj'] += $track->getDuration();
                  $totalElapsedOnRegularPrj += $track->getDuration();
                  break;
               case Project::type_sideTaskProject:
                  $groupDataArray[$currentUserGroupName]['elapsedOnSidetasksPrj'] += $track->getDuration();
                  $totalElapsedOnSidetasksPrj += $track->getDuration();
                  break;
            }
            $groupDataArray[$currentUserGroupName]['groupTotalElapsed'] += $track->getDuration();
            $totalElapsedOnPeriod += $track->getDuration();

            if ($this->isDisplayInvolvedUsers) {
               $involvedUsers =  $groupDataArray[$currentUserGroupName]['involvedUsers'];
               if (FALSE === strpos($involvedUsers, $user->getName())) {
                  $groupDataArray[$currentUserGroupName]['involvedUsers'] = $involvedUsers.', '.$user->getName();
               }
            }
            if ($this->isDisplayTasks) {
               $tasks =  $groupDataArray[$currentUserGroupName]['tasks'];
               if (FALSE === strpos($tasks, $user->getName())) {
                  $groupDataArray[$currentUserGroupName]['tasks'] = $tasks.', '.$track->getIssueId();
               }
            }
         }
      }

      foreach($groupDataArray as $uid => $userData){
         $groupDataArray[$uid]['elapsedOnRegularPrj'] = round($groupDataArray[$uid]['elapsedOnRegularPrj'], 2);
         $groupDataArray[$uid]['elapsedOnSidetasksPrj'] = round($groupDataArray[$uid]['elapsedOnSidetasksPrj'], 2);
         $groupDataArray[$uid]['groupTotalElapsed'] = round($groupDataArray[$uid]['groupTotalElapsed'], 2);
      }

      $this->execData = array();
      $this->execData['realStartTimestamp'] = $realStartTimestamp;
      $this->execData['realEndTimestamp'] = $realEndTimestamp;
      $this->execData['groupDataArray'] = $groupDataArray;
      $this->execData['totalElapsedOnRegularPrj'] = round($totalElapsedOnRegularPrj, 2);
      $this->execData['totalElapsedOnSidetasksPrj'] = round($totalElapsedOnSidetasksPrj, 2);
      $this->execData['totalElapsedOnPeriod'] = round($totalElapsedOnPeriod, 2);

      // Sort the multidimensional array
      uasort($this->userDataArray, "f_customGroupSort");

      $this->execData['userDataArray'] = $this->userDataArray;

      //self::$logger->error($this->execData);
      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $prefix='LoadPerUserGroups_';
      $smartyVariables = array(
         $prefix.'isOnlyActiveTeamMembers' => $this->isOnlyActiveTeamMembers,
         $prefix.'isDisplayInvolvedUsers' => $this->isDisplayInvolvedUsers,
         $prefix.'isDisplayTasks' => $this->isDisplayTasks,
      );
      foreach ($this->execData as $key => $val) {
         $smartyVariables[$prefix.$key] = $val;
      }

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;
      $smartyVariables[$prefix.'startDate'] = Tools::formatDate("%Y-%m-%d", $startTimestamp);
      $smartyVariables[$prefix.'endDate']   = Tools::formatDate("%Y-%m-%d", $endTimestamp);

      //self::$logger->error($smartyVariables);
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

/**
 * Sort blue/red/grey then by name
 */
function f_customGroupSort($a,$b) {

   if ($a['color'] == $b['color']) {
      return $a['userName'] > $b['userName'];
   }
   $keyVal = array('blue' => 0, 'red' => 1, 'darkgrey' => 2);
   return $keyVal[$a['color']] > $keyVal[$b['color']];
}

// Initialize complex static variables
LoadPerUserGroups::staticInit();

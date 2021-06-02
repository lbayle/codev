
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
class LoadPerCustomfieldValues extends IndicatorPluginAbstract {

   const OPTION_DISPLAY_TASKS   = 'isDisplayTasks';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $selectedCustomfieldId;
   private $managedUserId; // DOMAIN_USER only

   // config options from Dashboard
   private $isDisplayTasks;

   // internal
   protected $execData;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_USER,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Load per customfield values');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Choose a customfield, return the elapsed time for each customField value');
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
          'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
         'js_min/tooltip.min.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js',
         'js_min/chart.min.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_CUSTOMFIELD_ID)) {
         $this->selectedCustomfieldId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_CUSTOMFIELD_ID);
      } else {
         $this->selectedCustomfieldId = 0;
      }

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }
      if (IndicatorPluginInterface::DOMAIN_USER === $this->domain) {
         if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID)) {
            $this->managedUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         } else {
            throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         }
      } else {
         $this->managedUserId = NULL; // consider complete team
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isDisplayTasks = false;
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DISPLAY_TASKS, $pluginSettings)) {
            $this->isDisplayTasks = $pluginSettings[self::OPTION_DISPLAY_TASKS];
         }
         if (array_key_exists(PluginDataProviderInterface::PARAM_CUSTOMFIELD_ID, $pluginSettings)) {
            $this->selectedCustomfieldId = $pluginSettings[PluginDataProviderInterface::PARAM_CUSTOMFIELD_ID];
         }
      }
   }


   /**
    * List all available customFields
    * @return type
    */
   private function getCustomfieldList() {
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamProjects = $team->getProjects(false, true, true);

      $customfieldList = array();
      foreach ($teamProjects as $projectid => $pname) {
         $project = ProjectCache::getInstance()->getProject($projectid);
         $fields = $project->getCustomFieldsList();
         foreach($fields as $id => $name) {
            if (!array_key_exists($id, $customfieldList)) {
               $customfieldList[$id] = $name;
            }
         }
      }
      if (0 == $this->selectedCustomfieldId) {
         $this->selectedCustomfieldId = array_key_first($customfieldList);
      }

      self::$logger->error('selectedCustomField='.$this->selectedCustomfieldId);
      //self::$logger->error('getCustomfieldList='.var_export($customfieldList, true));

      return $customfieldList;
   }

      /**
    * returns $durationPerCustomfieldVal[CategoryName]['duration'] = duration
    *
    * @param array $timeTracks
    * @return array[] $durationPerCustomFieldValues[] = array (duration, bugidList),
    */
   private function getRawInfoPerCustomfieldVal($timeTracks) {
      $durationPerCustomfieldVal = array();
      $bugsPerCustomfieldVal = array();

      $totalDuration = 0;
      foreach($timeTracks as $timeTrack) {
         try {
            $bugid = $timeTrack->getIssueId();
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $duration = $timeTrack->getDuration();
            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
            $prjCustomfieldList = $project->getCustomFieldsList();

            if (!array_key_exists($this->selectedCustomfieldId, $prjCustomfieldList)) {
               $key = '(no_customField)';
            } else {
               $cfieldData = $issue->getCustomfieldData($this->selectedCustomfieldId);
               if ((null == $cfieldData) || ('' == $cfieldData['value'])) {
                  $key = '(empty)';
               } else {
                  $key =  $cfieldData['value'];
               }
            }

            $totalDuration += $duration;
            if (array_key_exists($key, $durationPerCustomfieldVal)) {
               $durationPerCustomfieldVal[$key] += $duration;
            } else {
               $durationPerCustomfieldVal[$key] = $duration;
            }

            // save bugid list per category
            if ($this->isDisplayTasks) {
               if (!array_key_exists($key, $bugsPerCustomfieldVal)) {
                  $bugsPerCustomfieldVal[$key] = array();
               }
               if(array_key_exists($bugid,$bugsPerCustomfieldVal[$key])) {
                  $bugsPerCustomfieldVal[$key][$bugid] += $duration;
               } else {
                  $bugsPerCustomfieldVal[$key][$bugid] = $duration;
               }
            }

         } catch (Exception $e) {
            self::$logger->warn("getRawInfoPerCustomfieldVal() issue ".$timeTrack->getIssueId()." not found in Mantis DB (duration = ".$timeTrack->getDuration()." on ".date('Y-m-d', $timeTrack->getDate()).')');
         }
      }
      $ret =  array(
         'durationPerCustomfieldVal' => $durationPerCustomfieldVal,
         'totalDuration' => $totalDuration);

      if ($this->isDisplayTasks) {
         $ret['bugidsPerCustomfieldValues'] = $bugsPerCustomfieldVal;
      }
      //self::$logger->error('rawInfo='.var_export($ret, true));
      return $ret;
   }

  /**
    *
    */
   public function execute() {

      // === get timetracks for each Issue,
      $team = TeamCache::getInstance()->getTeam($this->teamid);

      // === get timetracks for each Issue
      if (NULL !== $this->managedUserId) {
         $useridList = array($this->managedUserId);

      } else {
         //if ($this->isOnlyActiveTeamMembers) {
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
         //} else {
            // include also timetracks of users not in the team (relevant on ExternalTasksProjects)
         //   $useridList = NULL;
         //}
      }
      $timeTracks = $this->inputIssueSel->getTimetracks($useridList, $this->startTimestamp, $this->endTimestamp);

      $customfieldList = $this->getCustomfieldList();

      // === process timetracks
      $rawInfoPerCustomfieldValues = $this->getRawInfoPerCustomfieldVal($timeTracks);
      $totalElapsed = $rawInfoPerCustomfieldValues['totalDuration'];

      // === build $infoPerCat
      $infoPerCfieldValue = array();
      foreach ($rawInfoPerCustomfieldValues['durationPerCustomfieldVal'] as $customfieldValue => $duration) {

         $cfieldValInfo = array(
            'customfieldValue' => $customfieldValue,
            'duration' => $duration,
            'pcent' => round(($duration*100/$totalElapsed), 2),
         );

         // create formatedBugList (with tooltips)
         if ($this->isDisplayTasks) {
            $bugList = $rawInfoPerCustomfieldValues['bugidsPerCustomfieldValues'][$customfieldValue];
            $formatedBugs = '';
            foreach ($bugList as $bugid => $bugDuration) {
               $issue = IssueCache::getInstance()->getIssue($bugid);
               $tooltipAttributes = array(
                     T_('Project') => $issue->getProjectName(),
                     T_('Summary') => $issue->getSummary(),
                     T_('Elapsed') => $bugDuration,
               );
               if (!empty($formatedBugs)) {
                  $formatedBugs .= ', '.Tools::issueInfoURL($bugid, $tooltipAttributes);
               } else {
                  $formatedBugs = Tools::issueInfoURL($bugid, $tooltipAttributes);
               }
            }
            $cfieldValInfo['formatedBugsPerCustomfieldValues'] = $formatedBugs;
         }

         $infoPerCfieldValue[$customfieldValue] = $cfieldValInfo;
      }

      $this->execData = array (
         'customfieldList' => $customfieldList,
         'infoPerCfieldValue' => $infoPerCfieldValue,
         );

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $data = array();
      foreach ($this->execData['infoPerCfieldValue'] as $cfieldValInfo) {
         if (0 != $cfieldValInfo['duration']) {
            $data[$cfieldValInfo['customfieldValue']] = $cfieldValInfo['duration'];
         }
      }

      $jqplotData = empty($data) ? NULL : Tools::array2plot($data);
      $fields = SmartyTools::getSmartyArray(
         $this->execData['customfieldList'],
         $this->selectedCustomfieldId);

      $prefix='LoadPerCustomfieldValues_';
      $smartyVariables = array(
         $prefix.'startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         $prefix.'endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         $prefix.'fields' => $fields,

         $prefix.'tableData' => $this->execData['infoPerCfieldValue'],
         $prefix.'jqplotData' => $jqplotData,

         // add pluginSettings (if needed by smarty)
         $prefix.self::OPTION_DISPLAY_TASKS => $this->isDisplayTasks,
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
LoadPerCustomfieldValues::staticInit();

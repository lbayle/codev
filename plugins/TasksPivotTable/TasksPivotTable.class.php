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
class TasksPivotTable extends IndicatorPluginAbstract {

   const OPTION_SELECTED_FILTERS = "selectedFilters"; // string of comma-sep FilterClassNames

   private static $logger;
   private static $domains;
   private static $categories;

   private static $allFilters;

   private $domain;
   private $inputIssueSel;
   private $teamid;
   private $sessionUserid;
   private $isManager;
   private $maxTooltipsPerPage;

   // internal
   protected $execData;

   private $filterList;         // selected filters classNames = array_keys($selectedFilterList)
   private $availFilterList;    // for dialogbox: key=className, value=displayName
   private $selectedFilterList; // for dialogbox: key=className, value=displayName
   private $selectedFiltersStr; // comma-sep filter classNames
   private $filterDisplayNames; // selected filters displayNames = array_values($this->selectedFilterList)

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

      // TODO: this list should be dynamicaly set by parsing the filters directory !
      self::$allFilters = "ProjectFilter,ProjectVersionFilter,ProjectCategoryFilter,IssueIdFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

   }

   public static function getName() {
      return T_('Tasks pivot table');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Group tasks by adding multiple filters');
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
         'plugins/TasksPivotTable/TasksPivotTable.css',
      );
   }
   public static function getJsFiles() {
      return array(
         //'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
         'js_min/tabs.min.js',
         'js_min/FileSaver.min.js',
      );
   }

   public static function getSmartySubFilename2() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax2.html";
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
         $isManager = $sessionUser->isTeamManager($this->teamid);
         $isObserver = $sessionUser->isTeamObserver($this->teamid);
         $this->isManager = ($isManager || $isObserver);
      } catch (Exception $e) {
         // hmm... sessionUser should exist, this case should never happen !
         $this->isManager = false;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->maxTooltipsPerPage = Constants::$maxTooltipsPerPage;
      $this->filterList = array();
   }

   /**
    * Apply settings saved by the Dashboard in attributesJsonStr.
    * (this method is called by the dashboard)
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_SELECTED_FILTERS, $pluginSettings)) {
            $tmpSelectedFilters = $pluginSettings[self::OPTION_SELECTED_FILTERS];

            // cleanup selected filters (remove empty lines)
            $this->filterList = explode(',', $tmpSelectedFilters);
            $this->filterList = array_filter($this->filterList, create_function('$a','return $a!="";'));
         }
      }
   }

   private function setDialogboxFilters() {

      // cleanup allFilters (remove empty lines)
      $tmpList = explode(',', self::$allFilters);
      $tmpList = array_filter($tmpList, create_function('$a','return $a!="";'));

      $allFilterList = array();
      foreach ($tmpList as $class_name) {
         if (is_null($class_name)) { continue; } // skip trailing commas ','
         $filter = new $class_name("fake_id");
         $allFilterList[$class_name] = $filter->getDisplayName();
      }

      // init dialogbox lists: $availFilterList & $selectedFilterList
      $this->availFilterList = $allFilterList;
      $this->selectedFilterList = array();
      foreach ($this->filterList as $className) {
         $this->selectedFilterList[$className] = $allFilterList[$className];
         unset($this->availFilterList[$className]);
      }
      $this->filterDisplayNames = array_values($this->selectedFilterList);
   }


   public function execute() {

      // initialize $availFilterList & $selectedFilterList
      $this->setDialogboxFilters();

      // call the filters, create FilterNode tree, and get displayable array of ISel
      $filterMgr = new FilterManager($this->inputIssueSel, $this->filterList);
      $resultList = $filterMgr->execute();
      $this->execData = $filterMgr->explodeResults($resultList);

      // TODO return dynatree data...
   }

   public function getSmartyVariables($isAjaxCall = false) {

      $prefix = 'taskPivotTable_';

      $smartyVariables[$prefix.'isManager'] = $this->isManager;

      // set smarty objects
      $smartyVariables[$prefix.'availFilterList'] = $this->availFilterList;
      $smartyVariables[$prefix.'selectedFilterList'] = $this->selectedFilterList;
      $smartyVariables[$prefix.'selectedFilters'] = $this->selectedFiltersStr;
      $smartyVariables[$prefix.'nbFilters'] = count($this->filterList); // nb selected filters

      $smartyVariables = $this->getDetailed($this->execData, $this->filterDisplayNames, $smartyVariables);
      $smartyVariables = $this->getIssues($this->execData, $this->filterDisplayNames, $smartyVariables);
      #var_dump($smartyVariables);

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxFile2'] = self::getSmartySubFilename2();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

   /**
    * explodeResults() returns a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param type $explodeResults
    * @param type $filterDisplayNames
    */
   private function getDetailed($explodeResults, $filterDisplayNames, $smartyVariables) {
      $prefix = 'taskPivotTable_';

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         $values = $isel->getDrift();
         $smartyElem = array(
            #'name' => $isel->name,
            'progress' => round(100 * $isel->getProgress()),
            'effortEstim' => $isel->effortEstim,
            'reestimated' => $isel->getReestimated(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round($values['nbDays'],2)
         );

         if ($this->isManager) {
            $valuesMgr = $isel->getDriftMgr();
            $smartyElem['effortEstimMgr'] = $isel->mgrEffortEstim;
            $smartyElem['driftMgr'] = round($valuesMgr['nbDays'],2);
            $smartyElem['driftColorMgr'] = IssueSelection::getDriftColor($valuesMgr['percent']);
         }
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("Progress");
      if ($this->isManager) {$titles[] = T_("MgrEffortEstim");}
      $titles[] = T_("EffortEstim");
      $titles[] = T_("Reestimated");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      if ($this->isManager) {$titles[] = T_("Drift Mgr");}
      $titles[] = T_("Drift");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $smartyVariables[$prefix.'detailedMgrTitles'] = $titles;
      $smartyVariables[$prefix.'detailedMgrLines'] = $smartyObj;
      $smartyVariables[$prefix.'detailedMgrTotal'] = $totalLine;

      return $smartyVariables;
   }

   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param type $explodeResults
    * @param type $filterDisplayNames
    */
   private function getIssues($explodeResults, $filterDisplayNames, $smartyVariables) {
      $prefix = 'taskPivotTable_';

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      // find out which issues will have a tooltip
      if (0 != $this->maxTooltipsPerPage) {
         #$bugWithTooltipList = @array_keys($this->inputIssueSel->getLastUpdatedList($this->maxTooltipsPerPage));
         $bugWithTooltipList = array_keys($this->inputIssueSel->getLastUpdatedList($this->maxTooltipsPerPage));
         #echo "bugWithTooltipList = ".implode(',', $bugWithTooltipList).'<br>';
      }

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         // format Issues list
         $formatedResolvedList = '';
         $formatedOpenList = '';
         $formatedNewList = '';
         foreach ($isel->getIssueList() as $bugid => $issue) {

            if ((0 != $this->maxTooltipsPerPage) && (!in_array($issue->getId(), $bugWithTooltipList))) {
               // display default one-line tooltip
               $tooltipAttr = NULL;
            } else {
               if (0 != $this->userid) {
                  $tooltipAttr = $issue->getTooltipItems($this->teamid, $this->userid);
               } else {
                  $tooltipAttr = $issue->getTooltipItems($this->teamid, 0, $this->isManager);
               }

               if (!array_key_exists(T_('Summary'), $tooltipAttr)) {
                  // insert in front
                  $tooltipAttr = array(T_('Summary') => $issue->getSummary()) + $tooltipAttr;
               }
            }

            if (Constants::$status_new == $issue->getCurrentStatus()) {
               if (!empty($formatedNewList)) {
                  $formatedNewList .= ', ';
               }
               $formatedNewList .= Tools::issueInfoURL($bugid, $tooltipAttr);;

            } elseif ($issue->getCurrentStatus() >= $issue->getBugResolvedStatusThreshold()) {
               if (!empty($formatedResolvedList)) {
                  $formatedResolvedList .= ', ';
               }
               $formatedResolvedList .= Tools::issueInfoURL($bugid, $tooltipAttr);
            } else {
               if (!empty($formatedOpenList)) {
                  $formatedOpenList .= ', ';
               }
               $formatedOpenList .= Tools::issueInfoURL($bugid, $tooltipAttr);
            }
         }

         $smartyElem = array(
            #'name' => $isel->name,
            'newList' => $formatedNewList,
            'openList' => $formatedOpenList,
            'resolvedList' => $formatedResolvedList
         );

         // ---
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("New Tasks");
      $titles[] = T_("Current Tasks");
      $titles[] = T_("Resolved Tasks");

      // set Smarty
      if (1 == count($explodeResults)) {
         $totalLine = $smartyObj[0]; // first line is rootElem (TOTAL)
      } else {
         $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)
      }

      $smartyVariables[$prefix.'issuesTitles'] = $titles;
      $smartyVariables[$prefix.'issuesLines'] = $smartyObj;
      $smartyVariables[$prefix.'issuesTotal'] = $totalLine;

      return $smartyVariables;
   }

}

// Initialize complex static variables
TasksPivotTable::staticInit();

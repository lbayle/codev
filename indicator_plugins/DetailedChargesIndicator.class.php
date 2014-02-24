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
 * Propose IssueSelectionFilters and display results in
 * 3 tabs "Overview|Detailed|Tasks".
 *
 */
class DetailedChargesIndicator implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
   private static $logger;

   protected $inputIssueSel;

   protected $execData;

   private $isManager;
   private $teamid;
   private $userid;

   private $availFilterList;
   private $selectedFilterList;
   private $filterList;
   private $filterDisplayNames;

   /**
    *
    * @var bool if true, use Constants::$maxTooltipsPerPage to reduce the number of displayed tooltips. (PERF)
    */
   private $maxTooltipsPerPage;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {

   }

   public function getDesc() {
      return "Display filtered charges";
   }
   public function getName() {
      return __CLASS__;
   }
   public static function getSmartyFilename() {
      return "plugin/detailed_charges_indicator.html";
   }


   private function setFilters($allFilters) {

      $tmpList = explode(',', $allFilters);
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
      $this->filterDisplayNames = array();
      foreach ($this->filterList as $id) {
         $this->selectedFilterList[$id] = $this->availFilterList[$id];
         $this->filterDisplayNames[]    = $allFilterList[$id];
         unset($this->availFilterList[$id]);
      }

   }

   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (is_null($inputIssueSel)) {
         throw new Exception("Missing IssueSelection");
      }
      if (is_null($params)) {
         throw new Exception("Missing parameters: isManager, allFilters, selectedFilters, teamid, userid, maxTooltipsPerPage");
      }

      if (array_key_exists('isManager', $params)) {
         $this->isManager = $params['isManager'];
      } else {
         $this->isManager = false;
      }

      if (array_key_exists('teamid', $params)) {
         $this->teamid = $params['teamid'];
      } else {
         $this->teamid = 0;
      }
      if (array_key_exists('userid', $params)) {
         $this->userid = $params['userid'];
      } else {
         $this->userid = 0;
      }

      if (array_key_exists('maxTooltipsPerPage', $params)) {
         $this->maxTooltipsPerPage = $params['maxTooltipsPerPage'];
      } else {
         // display all tooltips
         $this->maxTooltipsPerPage = 0;
      }

      if (array_key_exists('selectedFilters', $params)) {
         $tmpSelectedFilters = $params['selectedFilters'];

         // cleanup filters (remove empty lines)
         $this->filterList = explode(',', $tmpSelectedFilters);
         $this->filterList = array_filter($this->filterList, create_function('$a','return $a!="";'));
         $this->selectedFilters = implode(',', $this->filterList);

      } else {
         $this->filterList = array();
         $this->selectedFilters='';
      }

      if (array_key_exists('allFilters', $params)) {
         $this->setFilters($params['allFilters']);
      } else {
         throw new Exception("Missing parameter: allFilters");
      }


   }


   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {
      $this->checkParams($inputIssueSel, $params);

      $this->inputIssueSel = $inputIssueSel;

      // do the work ...
      $filterMgr = new FilterManager($inputIssueSel, $this->filterList);
      $resultList = $filterMgr->execute();
      $this->execData = $filterMgr->explodeResults($resultList);

   }



   public function getSmartyObject() {

      $smartyVariables = array();

      // should be set by the controler, but just in case...
      $smartyVariables['isManager'] = $this->isManager;

      // set smarty objects
      $smartyVariables['availFilterList'] = $this->availFilterList;
      $smartyVariables['selectedFilterList'] = $this->selectedFilterList;
      $smartyVariables['selectedFilters'] = $this->selectedFilters;
      $smartyVariables['nbFilters'] = count($this->filterList);
      $smartyVariables = $this->getOverview($this->execData, $this->filterDisplayNames, $this->isManager, $smartyVariables);
      if ($this->isManager) {
         $smartyVariables = $this->getDetailed($this->execData, $this->filterDisplayNames, $smartyVariables);
      }
      $smartyVariables = $this->getIssues($this->execData, $this->filterDisplayNames, $smartyVariables);

      #var_dump($smartyVariables);
      return $smartyVariables;
   }

   /**
    * $explodeResults contains a list of filterNames + an IssueSelection on the last column.
    * This function will replace the IssueSelection with a smarty comprehensible array
    * containing the info to be displayed.
    *
    * @param mixed[] $explodeResults
    * @param string[] $filterDisplayNames
    */
   private function getOverview(array $explodeResults, array $filterDisplayNames, $isManager, $smartyVariables) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         // ---
         $values = $isel->getDrift();

         // TODO show date only if ProjectVersion
         /*
         $date = "";
         if ('ProjectVersion' == get_class($isel)) {
            $vdate =  $isel->getVersionDate();
            if (is_numeric($vdate)) {
               $date = date(T_("Y-m-d"),$vdate);
            }
         }
         */

         $smartyElem = array(
            #'name' => $isel->name,
            #'date' => $date,
            'progress' => round(100 * $isel->getProgress()),
            #'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round(100 * $values['percent'])
         );
         if ($isManager) {
            $valuesMgr = $isel->getDriftMgr();
            $smartyElem['reestimated'] = $isel->getReestimated();
            $smartyElem['driftMgrColor'] = IssueSelection::getDriftColor($valuesMgr['percent']);
            $smartyElem['driftMgr'] = round(100 * $valuesMgr['percent']);

         }

         // ---
         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      #$titles[] = T_("Date");
      $titles[] = T_("Progress");
      if ($isManager) { $titles[] = T_("Reestimated"); }
      #$titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      if ($isManager) { $titles[] = T_("Drift Mgr"); }
      $titles[] = T_("Drift");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $smartyVariables['overviewTitles'] = $titles;
      $smartyVariables['overviewLines'] = $smartyObj;
      $smartyVariables['overviewTotal'] = $totalLine;

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
   private function getDetailed($explodeResults, $filterDisplayNames, $smartyVariables) {

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      foreach($explodeResults as $line) {
         $isel = $line[$iselIdx];

         $valuesMgr = $isel->getDriftMgr();
         $values = $isel->getDrift();
         $smartyElem = array(
            #'name' => $isel->name,
            'progress' => round(100 * $isel->getProgress()),
            'effortEstimMgr' => $isel->mgrEffortEstim,
            'effortEstim' => ($isel->effortEstim + $isel->effortAdd),
            'reestimated' => $isel->getReestimated(),
            'elapsed' => $isel->elapsed,
            'backlog' => $isel->duration,
            'driftColorMgr' => IssueSelection::getDriftColor($valuesMgr['percent']),
            'driftMgr' => round($valuesMgr['nbDays'],2),
            'driftColor' => IssueSelection::getDriftColor($values['percent']),
            'drift' => round($values['nbDays'],2)
         );

         $line[$iselIdx] = $smartyElem;
         $smartyObj[] = $line;
      }

      // add TitleLine
      $titles = $filterDisplayNames;
      $titles[] = T_("Progress");
      $titles[] = T_("MgrEffortEstim");
      $titles[] = T_("EffortEstim");
      $titles[] = T_("Reestimated");
      $titles[] = T_("Elapsed");
      $titles[] = T_("Backlog");
      $titles[] = T_("Drift Mgr");
      $titles[] = T_("Drift");

      // set Smarty
      $totalLine = array_shift($smartyObj); // first line is rootElem (TOTAL)

      $smartyVariables['detailedMgrTitles'] = $titles;
      $smartyVariables['detailedMgrLines'] = $smartyObj;
      $smartyVariables['detailedMgrTotal'] = $totalLine;

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

      $iselIdx = count($explodeResults[0]) -1;

      $smartyObj = array();

      // find out which issues will have a tooltip
      if (0 != $this->maxTooltipsPerPage) {
         $bugWithTooltipList = @array_keys($this->inputIssueSel->getLastUpdatedList($this->maxTooltipsPerPage));
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

      $smartyVariables['issuesTitles'] = $titles;
      $smartyVariables['issuesLines'] = $smartyObj;
      $smartyVariables['issuesTotal'] = $totalLine;

      return $smartyVariables;
   }

}

// Initialize complex static variables
DetailedChargesIndicator::staticInit();
?>

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
 * Description of LoadPerJobIndicator
 *
 * @author lob
 */
class LoadPerJobIndicator2 implements IndicatorPlugin2  {

   private static $logger;
   private static $domains;
   private static $categories;

   // params
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // options
   // TODO: graphOnly, sidetasksDetail, ...

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_COMMAND,
         self::DOMAIN_TEAM,
         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_MACRO_COMMAND,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_QUALITY
      );
   }

   public static function getName() {
      return 'Load per Job';
   }
   public static function getDesc() {
      return 'Check all the timetracks of the period and return their repartition per Job';
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
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

   public static function getSmartyFilename() {
      return Constants::$codevRootDir.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__.".html";
   }
   public static function getSmartySubFilename() {
   	  return Constants::$codevRootDir.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__."_ajax.html";
   }
   public static function getAjaxPhpFilename() {
   	  return Constants::$codevURL.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__."_ajax.php";
   }


   /**
    *
    * @param \PluginManagerFacadeInterface $pluginMgr
    * @throws Exception if initialization failed
    */
   public function __construct(PluginManagerFacadeInterface $pluginMgr) {
      $this->startTimestamp     = NULL;
      $this->endTimestamp       = NULL;

      $this->initialize($pluginMgr);
   }

   /**
    *
    * @param \PluginManagerFacadeInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginManagerFacadeInterface $pluginMgr) {

      // TODO Deprecated, for transistion only
      $this->inputIssueSel = $pluginMgr->getParam(PluginManagerFacadeInterface::PARAM_ISSUE_SELECTION);
      $params = array(
         'teamid' => $pluginMgr->getParam(PluginManagerFacadeInterface::PARAM_TEAM_ID),
      );
      $this->checkParams($this->inputIssueSel, $params);

   }

   /**
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    * @throws Exception
    */
   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
      if (NULL == $params) {
         throw new Exception("Missing parameters: teamid");
      }

      if (NULL != $params) {

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("checkParams() ISel=".$inputIssueSel->name.' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
         }
         #echo "start ".date('Y-m-d', $params['startTimestamp']). " end ".date('Y-m-d', $params['endTimestamp'])."<br>";

         if (array_key_exists('teamid', $params)) {
            $this->teamid = $params['teamid'];
         } else {
            throw new Exception("Missing parameter: teamid");
         }

         if (array_key_exists('startTimestamp', $params)) {
            $this->startTimestamp = $params['startTimestamp'];
         }

         if (array_key_exists('endTimestamp', $params)) {
            $this->endTimestamp = $params['endTimestamp'];
         }

      }
   }


  /**
    *
    */
   public function execute() {
      

      $issueList = $this->inputIssueSel->getIssueList();
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamMembers = $team->getMembers();
      $jobs = new Jobs();

      $loadPerJobs = array();
      foreach($issueList as $issue) {
#echo "issue ".$issue->getId()."<br>";
         $issueTimetracks = $issue->getTimeTracks(NULL, $this->startTimestamp, $this->endTimestamp);
         foreach ($issueTimetracks as $tt) {

            // check if user in team
            if (!array_key_exists($tt->getUserId(), $teamMembers)) { continue; }

#echo "tt user=".$tt->getUserId()." job=".$tt->getJobId()." dur=".$tt->getDuration()."<br>";
            // check if sidetask
            if ($team->isSideTasksProject($issue->getProjectId())) {
               // TODO check category (detail all sidetasks categories)

               $jobid = '999_SideTasks';
               if (!array_key_exists($jobid, $loadPerJobs)) {
                  // create job if not exist in jobList
                  $loadPerJobs[$jobid] = array(
                     'name' => T_('SideTasks'),
                     'color' => 'C2C2C2', // TODO hardcoded !
                     'nbDays' => floatval($tt->getDuration()),
                     );
               } else {
                  $loadPerJobs[$jobid]['nbDays'] += floatval($tt->getDuration());
               }
            } else {
               $jobid = $tt->getJobId();
               if (!array_key_exists($jobid, $loadPerJobs)) {
                  // create job if not exist in jobList
                  $loadPerJobs[$jobid] = array(
                     'name' => htmlentities($jobs->getJobName($jobid), ENT_QUOTES | ENT_HTML401, "UTF-8"),
                     'color' => $jobs->getJobColor($jobid),
                     'nbDays' => floatval($tt->getDuration()),
                     );
               } else {
                  $loadPerJobs[$jobid]['nbDays'] += floatval($tt->getDuration());
               }
            }
         }
      }
      // array sort to put sideTasks categories at the bottom
      ksort($loadPerJobs);

      $this->execData = $loadPerJobs;
      return $loadPerJobs;
   }


   public function getSmartyVariables() {

      $loadPerJobs = $this->execData;
      $data = array();
      $formatedColors = array();
      foreach ($loadPerJobs as $jobItem) {
         $data[$jobItem['name']] = $jobItem['nbDays'];
         $formatedColors[] = '#'.$jobItem['color'];
      }
      $seriesColors = '["'.implode('","', $formatedColors).'"]';  // ["#FFCD85","#C2DFFF"]

      return array(
         'loadPerJobIndicator_tableData' => $loadPerJobs,
         'loadPerJobIndicator_jqplotData' => Tools::array2json($data),
         'loadPerJobIndicator_colors' => $formatedColors,
         'loadPerJobIndicator_jqplotSeriesColors' => $seriesColors, // TODO get rid of this
         'loadPerJobIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'loadPerJobIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         #'loadPerJobIndicatorFile' => LoadPerJobIndicator::getSmartyFilename(), // added in controller
         'loadPerJobIndicator_ajaxFile' => LoadPerJobIndicator::getSmartySubFilename(),
         'loadPerJobIndicator_ajaxPhpFile' => LoadPerJobIndicator::getAjaxPhpFilename(),
      );
   }

}

// Initialize complex static variables
LoadPerJobIndicator2::staticInit();

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
class LoadPerJobIndicator implements IndicatorPlugin  {

   private static $logger;

   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   protected $execData;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {
      $this->startTimestamp     = NULL;
      $this->endTimestamp       = NULL;

   }

   public function getDesc() {
      return "Load per Job";
   }
   public function getName() {
      return __CLASS__;
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
    *
    * 
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {
      $this->checkParams($inputIssueSel, $params);

      $issueList = $inputIssueSel->getIssueList();
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


   public function getSmartyObject() {

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
         'loadPerJobIndicator_jqplotSeriesColors' => $seriesColors,
         'loadPerJobIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'loadPerJobIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         #'loadPerJobIndicatorFile' => LoadPerJobIndicator::getSmartyFilename(), // added in controller
         'loadPerJobIndicator_ajaxFile' => LoadPerJobIndicator::getSmartySubFilename(),
         'loadPerJobIndicator_ajaxPhpFile' => LoadPerJobIndicator::getAjaxPhpFilename(),
      );
   }
}

// Initialize complex static variables
LoadPerJobIndicator::staticInit();

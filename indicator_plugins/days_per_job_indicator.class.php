<?php

/*
  This file is part of CodevTT.

  CodevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CodevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Logger.php');

include_once('classes/indicator_plugin.interface.php');

require_once ('user_cache.class.php');
require_once ('issue_cache.class.php');
require_once ('issue_selection.class.php');
require_once ('jobs.class.php');
require_once ('team.class.php');



/**
 * Description of days_per_job
 *
 */
class DaysPerJobIndicator implements IndicatorPlugin {

   private $logger;

   protected $workingDaysPerJob;

   public function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->initialize();
   }

   public function initialize() {

      // get info from DB
   }

   public function getName() {
      return __CLASS__;
   }

   public function getSmartyFilename() {
      return 'days_per_job_indicator.html';
   }

   public function getDesc() {
      return T_("Working days per Job");
   }


   /**
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params {teamid, startTimestamp, endTimestamp}
    *
    * @exception on missing parameters or other error
    * @return float[] workingDaysPerJob[jobid] = duration
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->logger->debug("execute() ISel=".$inputIssueSel->name.' teamid='.$params['teamid'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);

      $startTimestamp      = NULL;
      $endTimestamp        = NULL;
      $teamid              = NULL;
      $formattedMemberList = NULL;

      $issueList = $inputIssueSel->getIssueList();
      if (0 == count($issueList)) {
         throw Exception("IssueSelection is empty !");
      }
      $formattedIssueList = implode(',', array_keys($issueList));

      if (array_key_exists('startTimestamp', $params)) {
         $startTimestamp = $params['startTimestamp'];
      }

      if (array_key_exists('endTimestamp', $params)) {
         $endTimestamp = $params['endTimestamp'];
      }

      if (array_key_exists('teamid', $params)) {
         $teamid  = $params['teamid'];
         $team = TeamCache::getInstance()->getTeam($teamid);

         $memberList = $team->getActiveMembers($startTimestamp, $endTimestamp);
         $formattedMemberList = implode(',', $memberList);

         // do not get timetracks prior to team creation date
         if ((NULL != $startTimestamp) && ($team->date > $startTimestamp)) {
            $startTimestamp = $team->date;
         }
      }

      // if defined, endTimestamp must be > startTimestamp
      if ((NULL != $startTimestamp) &&
          (NULL != $endTimestamp) &&
          ($endTimestamp < $startTimestamp)) {
         throw Exception("endTimeStamp < startTimestamp !");
      }


      // select memberList timetracks on issueList whthin the period
      $query = "SELECT * FROM `codev_timetracking_table` ".
               "WHERE bugid  IN ($formattedIssueList) ";
      
      if (NULL != $formattedMemberList) { $query .= "AND   userid IN ($formattedMemberList) "; }

      if (NULL != $startTimestamp) { $query .=  "AND codev_timetracking_table.date >= $startTimestamp "; }
      if (NULL != $endTimestamp)   { $query .=  "AND codev_timetracking_table.date < $endTimestamp "; }

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         $this->logger->error("execute() Query FAILED: ".$query);
         throw Exception("Query FAILED !");
      }

      $this->workingDaysPerJob = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $this->workingDaysPerJob[$row->jobid] += $row->duration;

         // ---- DEBUG
         if ($this->logger->isDebugEnabled()) {
            $u = UserCache::getInstance()->getUser($row->userid);
            $issue = IssueCache::getInstance()->getIssue($row->bugid);
            $this->logger->debug("execute() : team $teamid job $job_id user $row->userid ".$u->getName()." bug $row->bugid duration $row->duration");
         }
      }

      return $this->workingDaysPerJob;
   }

   /**
    *
    * $smartyHelper->assign('daysPerJobIndicator', $myIndic->getSmartyObject());
    *
    * @return array
    */
   public function getSmartyObject() {

      if (NULL != $this->workingDaysPerJob) {

         $jobs = new Jobs();
         $smartyData = array();

         foreach ($this->workingDaysPerJob as $id => $duration) {

            $smartyData[] = array(
               "name"   => $jobs->getJobName($id),
               "nbDays" => $duration,
               "color"  => $jobs->getJobColor($id)
            );
         }
      } else {
         throw Exception("the execute() method must be called before assignInSmarty().");
      }
      return $smartyData;
   }

}

?>

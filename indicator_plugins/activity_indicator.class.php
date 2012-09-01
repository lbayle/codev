<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ActivityIndicator
 *
 * @author lob
 */
class ActivityIndicator implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
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
      return "";
   }
   public function getName() {
      return __CLASS__;
   }
   public function getSmartyFilename() {
      return "plugin/activity_indicator.html";
   }


   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
      if (NULL == $params) {
         throw new Exception("Missing parameters: teamid");
      }

      if (NULL != $params) {

         self::$logger->debug("execute() ISel=".$inputIssueSel->name.' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
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
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      $team = TeamCache::getInstance()->getTeam($this->teamid);

      $members = $team->getActiveMembers($this->startTimestamp, $this->endTimestamp);
      $formatedUseridString = implode( ', ', array_keys($members));

      $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $leaveTaskId = Config::getInstance()->getValue(Config::id_externalTask_leave);

      // get timetracks for each Issue,
      $issueList = $inputIssueSel->getIssueList();
      $bugidList = array_keys($issueList);
      //$formatedBugidString = implode( ', ', array_keys($issueList));

      $query = "SELECT * FROM `codev_timetracking_table` ".
               "WHERE userid IN (".$formatedUseridString.") ";

      if (isset($this->startTimestamp)) { $query .= "AND date >= $this->startTimestamp "; }
      if (isset($this->endTimestamp))   { $query .= "AND date <= $this->endTimestamp "; }
      $query .= " ORDER BY bugid";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $timeTracks = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $timeTracks[$row->id] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
      }

      // ---
      // un tablean de users avec repartition temps en categories: regular,external,sidetask
      $teams = array($this->teamid);

      $usersActivity = array();
      foreach ($timeTracks as $tt) {

         $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());
         $userid = $tt->getUserId();
         
         if (!isset($usersActivity[$userid])) { $usersActivity[$userid] = array(); }
         //$activityList = $usersActivity[$userid];

         if ($extProjId == $tt->getProjectId()) {
            #echo "external ".$tt->getIssueId()."<br>";
            if ($leaveTaskId == $tt->getIssueId()) {
               $usersActivity[$userid]['leave'] += $tt->getDuration();
            } else {
               $usersActivity[$userid]['external'] += $tt->getDuration();
            }

         } else if ($issue->isSideTaskIssue($teams)) {
            #echo "sidetask ".$tt->getIssueId()."<br>";
            #$usersActivity[$userid]['sidetask'] += $tt->getDuration();
            $usersActivity[$userid]['elapsed'] += $tt->getDuration();

         }else if (in_array($tt->getIssueId(), $bugidList)) {
            #echo "selection ".$tt->getIssueId()."<br>";
            $usersActivity[$userid]['elapsed'] += $tt->getDuration();

         } else {
            #echo "other ".$tt->getIssueId()."<br>";
            $usersActivity[$userid]['other'] += $tt->getDuration();
         }
      }
      #var_dump($usersActivity);
      $this->execData = $usersActivity;

   }

   /**
    *
    */
   public function getSmartyObject() {

      $smartyObj = array();
      $totalActivity= array();
      $usersActivities= array();


      foreach ($this->execData as $userid => $userActivity) {
          $user = UserCache::getInstance()->getUser($userid);
          $usersActivities[$user->getName()] = $userActivity;

          $totalActivity['leave'] += $userActivity['leave'];
          $totalActivity['external'] += $userActivity['external'];
          $totalActivity['elapsed'] += $userActivity['elapsed'];
          $totalActivity['other'] += $userActivity['other'];
      }

      ksort($usersActivities);

      // pieChart data
      $jqplotData = array(
            T_('Elapsed') => $totalActivity['elapsed'],
            T_('Other') => $totalActivity['other'],
            T_('External') => $totalActivity['external'],
            T_('Inactivity') => $totalActivity['leave']
      );

      $smartyObj['usersActivities'] = $usersActivities;
      $smartyObj['totalActivity'] = $totalActivity;
      $smartyObj['jqplotData'] = Tools::array2plot($jqplotData);



      return $smartyObj;
   }
}


// Initialize complex static variables
ActivityIndicator::staticInit();

?>

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
class ActivityIndicator extends Plugin implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private $startTimestamp;
   private $endTimestamp;
   private $teamid;
   private $showSidetasks;

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


      // if false, sidetasks (found in IssueSel) will be included in 'elapsed'
      // if true, sidetasks (found in IssueSel) will be displayed as 'sidetask'
      $this->showSidetasks = false;
   }

   public function getDesc() {
      return "";
   }
   public function getName() {
      return __CLASS__;
   }
   public static function getSmartyFilename() {
      return Constants::$codevRootDir.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__.".html";
   }
   
   public static function getSmartySubFilename() {
   	  return Constants::$codevRootDir.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__."_ajax1.html";
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
            self::$logger->debug("execute() ISel=".$inputIssueSel->name.' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
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

         if (array_key_exists('showSidetasks', $params)) {
            // if false, sidetasks (found in IssueSel) will be included in 'elapsed'
            // if true, sidetasks (found in IssueSel) will be displayed as 'sidetask'
            $this->showSidetasks = $params['showSidetasks'];
         }
      }
   }



   /**
    *
    * returns an array of [user][activity]
    * activity in (elapsed, sidetask, other, external, leave)
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
      $extTasksCatLeave = Config::getInstance()->getValue(Config::id_externalTasksCat_leave);

      // get timetracks for each Issue,
      $issueList = $inputIssueSel->getIssueList();
      $bugidList = array_keys($issueList);
      //$formatedBugidString = implode( ', ', array_keys($issueList));

      if ($formatedUseridString != NULL && count($formatedUseridString)>0) {
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
	
	         $issueId = $tt->getIssueId();
	         try {
	            $issue = IssueCache::getInstance()->getIssue($issueId);
	         } catch (Exception $e) {
	            self::$logger->error("execute() skip issue $issueId : ".$e->getMessage());
	            continue;
	         }
	         $userid = $tt->getUserId();
	
	         if (!array_key_exists($userid, $usersActivity)) {
	            $usersActivity[$userid] = array();
	         }
	         //$activityList = $usersActivity[$userid];
	
	         $duration = $tt->getDuration();
	         if ($extProjId == $tt->getProjectId()) {
	            #echo "external ".$tt->getIssueId()."<br>";
	            if ($extTasksCatLeave == $issue->getCategoryId()) {
	               if(array_key_exists('leave',$usersActivity[$userid])) {
	                  $usersActivity[$userid]['leave'] += $duration;
	               } else {
	                  $usersActivity[$userid]['leave'] = $duration;
	               }
	            } else {
	               if(array_key_exists('external',$usersActivity[$userid])) {
	                  $usersActivity[$userid]['external'] += $duration;
	               } else {
	                  $usersActivity[$userid]['external'] = $duration;
	               }
	            }
	
	         } else if ($issue->isSideTaskIssue($teams)) {
	            #echo "sidetask ".$tt->getIssueId()."<br>";
	
	            // if sideTask is in the IssueSelection, then it is considered as 'normal',
	            // else it should not be included
	            if (in_array($issueId, $bugidList)) {
	               $cat = $this->showSidetasks ? 'sidetask' : 'elapsed';
	               if(array_key_exists($cat,$usersActivity[$userid])) {
	                  $usersActivity[$userid][$cat] += $duration;
	               } else {
	                  $usersActivity[$userid][$cat] = $duration;
	               }
	            } else {
	               // all sideTasks are in 'other' except inactivity tasks.
	               $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
	               if ($project->getCategory(Project::cat_st_inactivity) == $issue->getCategoryId()) {
	                  if(array_key_exists('leave',$usersActivity[$userid])) {
	                     $usersActivity[$userid]['leave'] += $duration;
	                  } else {
	                     $usersActivity[$userid]['leave'] = $duration;
	                  }
	               } else {
	                  if(array_key_exists('other',$usersActivity[$userid])) {
	                     $usersActivity[$userid]['other'] += $duration;
	                  } else {
	                     $usersActivity[$userid]['other'] = $duration;
	                  }
	               }
	            }
	         } else if (in_array($issueId, $bugidList)) {
	            #echo "selection ".$tt->getIssueId()."<br>";
	            if(array_key_exists('elapsed',$usersActivity[$userid])) {
	               $usersActivity[$userid]['elapsed'] += $duration;
	            } else {
	               $usersActivity[$userid]['elapsed'] = $duration;
	            }
	         } else {
	            #echo "other ".$tt->getIssueId()."<br>";
	            if(array_key_exists('other',$usersActivity[$userid])) {
	               $usersActivity[$userid]['other'] += $duration;
	            } else {
	               $usersActivity[$userid]['other'] = $duration;
	            }
	         }
	      }
      }
      #var_dump($usersActivity);
      $this->execData = $usersActivity;
   }

   public function getSmartyObject() {
      if ($this->execData != NULL) {
      	
	      $usersActivities = array();
	      	
	      $totalLeave = 0;
	      $totalExternal = 0;
	      $totalElapsed = 0;
	      $totalOther = 0;
	      $totalSidetask = 0;
	      
	      foreach ($this->execData as $userid => $userActivity) {
	         $user = UserCache::getInstance()->getUser($userid);
	         $usersActivities[$user->getName()] = $userActivity;
	
	         if(array_key_exists('leave',$userActivity)) {
	            $totalLeave += $userActivity['leave'];
	         }
	         if(array_key_exists('external',$userActivity)) {
	            $totalExternal += $userActivity['external'];
	         }
	         if(array_key_exists('elapsed',$userActivity)) {
	            $totalElapsed += $userActivity['elapsed'];
	         }
	         if(array_key_exists('other',$userActivity)) {
	            $totalOther += $userActivity['other'];
	         }
	         if ($this->showSidetasks && array_key_exists('sidetask',$userActivity)) {
	            $totalSidetask += $userActivity['sidetask'];
	         }
	      }
	      
	      ksort($usersActivities);
      }

      $totalActivity = array();
      $totalActivity['leave'] = $totalLeave;
      $totalActivity['external'] = $totalExternal;
      $totalActivity['elapsed'] = $totalElapsed;
      $totalActivity['other'] = $totalOther;
      $totalActivity['activity_indicator_ajax1_html'] = $this->getSmartySubFilename();
      if ($this->showSidetasks) {
         $totalActivity['sidetask'] += $totalSidetask;
      }

      // pieChart data
      $jqplotData = array(
         T_('Elapsed') => $totalActivity['elapsed'],
         T_('Other') => $totalActivity['other'],
         T_('External') => $totalActivity['external'],
         T_('Inactivity') => $totalActivity['leave']
      );

      if ($this->showSidetasks) {
         $jqplotData[T_('SideTask')] = $totalActivity['sidetask'];
      }

      return array(
         'usersActivities' => $usersActivities,
         'totalActivity' => $totalActivity,
         'jqplotData' => Tools::array2json($jqplotData)
      );
   }
}

// Initialize complex static variables
ActivityIndicator::staticInit();

?>

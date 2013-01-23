<?php

/*
 * Get a normalized backlog evolution on a group of issues
 */

// internal class
/**
 * normalize the backlogHistory of an issue
 */
class NormalizedBacklog {


   /**
    * @var Logger The logger
    */
   private static $logger;


   public $bugid;

   private $normalizedTotalElapsed;

   /**
    *
    * @var array #day => backlog
    */
   private $normalizedBacklogHistory;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct(Issue $issue, $normValue) {
      $this->bugid = $issue->getId();
      #echo "=== NormalizedBacklog $this->bugid normValue=$normValue<br>";
      $this->normalize($issue, $normValue);
   }

   /**
    * 
    * @param int $timestamp1
    * @param int $timestamp2
    * @return int interval in days
    */
   private static function getDateInterval($timestamp1, $timestamp2) {
      // diff and convert seconds to days
      $days = ($timestamp2 - $timestamp1) / 86400;
      echo "interval (".date('Y-m-d H:i:s', $timestamp1)." to ".date('Y-m-d H:i:s', $timestamp2)." = ".$days." days<br>";
      return $days;
   }

   public function getNormalizedTotalElapsed() {
      return $this->normalizedTotalElapsed;
   }

   private function normalize(Issue $issue, $normValue) {

      // 1) get known backlogs
      $blHistory = $issue->getBacklogHistory();
      $issueEE = $issue->getEffortEstim() + $issue->getEffortAdd();

      $backlogs = array('0' => $issueEE);
      #echo "origin: ".date('Y-m-d', $issue->getDateSubmission())." elapsed=0  backlog=$issueEE<br>";
      foreach ($blHistory as $timestamp => $backlog) {

         $index = $issue->getElapsed(NULL, NULL, $timestamp);

         #echo "origin: ".date('Y-m-d', $timestamp)." elapsed=$index  backlog=$backlog<br>";

         $backlogs["$index"] = floatval($backlog);
      }

      // 2) stretch out to normalValue (changement d'echelle absices et ordonnees)
      // note: keys are float values
      $factor = $normValue / $issueEE;
      foreach ($backlogs as $index => $backlog) {
         $key = round(($index * $factor), 3);
         $this->normalizedBacklogHistory["$key"] = ($backlog * $factor);
         #echo "normalized: elapsed=$key  backlog=".$this->normalizedBacklogHistory["$key"]."<br>";
      }
      $this->normalizedTotalElapsed = floatval($key);
      // WARN normalizedBacklogHistory keys must be sorted !
   }

   /**
    *
    * @param int $relativeDate
    * @return float backlog
    */
   public function getBacklogEval($relativeDate) {

      if (array_key_exists("$relativeDate", $this->normalizedBacklogHistory)) {
         return $this->normalizedBacklogHistory["$relativeDate"];
      }
      // 1) find prev and next keys in stretchedBacklog table
      $indexes = array_keys($this->normalizedBacklogHistory);
      foreach ($indexes as $idx) {
         if ($idx < $relativeDate) {
            $prev = $idx;
         } else {
            $next = $idx;
            #echo "issue $this->bugid: FOUND: prev = $prev next = $next for relativeDate=$relativeDate<br>";
            break;
         }
      }

      if (is_null($next)) {
         #echo "issue $this->bugid: backlogEval[$relativeDate] = 0 => NOT FOUND (max found = $prev)<br>";
         return 0; // return currentBacklog  (but issue is resolved, so '0')
      }

      // 2) compute backlog evaluation
      // Y = m * X + p
      $Xa = $prev;
      $Ya = $this->normalizedBacklogHistory[$prev];
      $Xb = $next;
      $Yb = $this->normalizedBacklogHistory[$next];
      $m = ($Yb - $Ya) / ($Xb - $Xa);
      $p = $Ya - $m * $Xa;

      $backlogEval = $m * $relativeDate + $p;

      #echo "issue $this->bugid: backlogEval[$relativeDate] = $backlogEval<br>";
      return $backlogEval;

   }

}


/**
 * Description of BacklogVariationIndicator
 *
 *
 * graph X = elapsed
 *       Y = backlog
 *
 * @author lob
 */
class BacklogVariationIndicator implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private $inputIssueSel;
   protected $execData;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {
      // nothing to do
   }


   public function getDesc(){

   }

   public function getName(){
      
   }

   public static function getSmartyFilename(){
      
   }

   /**
    * return the Max Elapsed found in iSel
    */
   private function getMaxElapsed($normalizedBacklogList) {
      $maxElapsed = 0;

      foreach ($normalizedBacklogList as $normalizedBacklog) {

         $elapsed = $normalizedBacklog->getNormalizedTotalElapsed();

         if ($elapsed > $maxElapsed) {
            $maxElapsed = $elapsed;
         }
      }
      return $maxElapsed;
   }

   /**
    * return the Max EE found in iSel
    */
   private function getNormValue() {
      $normValue = 0;

      $issueList = $this->inputIssueSel->getIssueList();
      foreach ($issueList as $issue) {

         $issueEE = $issue->getEffortEstim() + $issue->getEffortAdd();

         if ($issueEE > $normValue) {
            $normValue = $issueEE;
         }
      }
      return $normValue;
   }

   /**
    * pour chaque issue, cree un tableau de backlog normalisés.
    * 
    * normValue prend la valeur 
    * 
    * @param type $normValue
    */
   private function getNormalizedBacklog($normValue) {

      $normalizedBacklogList = array();

      $issueList = $this->inputIssueSel->getIssueList();
      foreach ($issueList as $issue) {
         $normalizedBacklogList[$issue->getId()] = new NormalizedBacklog($issue, $normValue);
      }
      return $normalizedBacklogList;
   }


   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (is_null($inputIssueSel)) {
         throw new Exception("Missing IssueSelection");
      }
   }

   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);
      $this->inputIssueSel = $inputIssueSel;

      #$normValue = $this->getNormValue();
      $normValue = 20;
      $normalizedBacklogList = $this->getNormalizedBacklog($normValue);

      $maxDays = intval(1 + $this->getMaxElapsed($normalizedBacklogList));
      $step = round(($maxDays / 20), 1);
      #$step = $maxDays / $normValue;
      if ($step < 0.1) { $step = 0.1; }
      #echo "maxDays = $maxDays step = $step<br>";

      $finalData = array();
      for ($i = 0; $i <= $maxDays; $i += $step) {
         $totalBacklog = 0;
         foreach ($normalizedBacklogList as $bugid => $normalizedBacklog) {

            $totalBacklog += $normalizedBacklog->getBacklogEval($i);
         }
         $finalData["$i"] = $totalBacklog;
      }

      $nbIssues = $inputIssueSel->getNbIssues();
      for ($i = 0; $i <= $maxDays; $i += $step) {
         $finalData["$i"] = round(($finalData["$i"] / $nbIssues), 2);
         #echo "final[$i] = ".$finalData["$i"].'<br>';
      }

      $this->execData = array();
      $this->execData['normValue'] = $normValue;
      $this->execData['step'] = $step;
      $this->execData['finalData'] = $finalData;
   }

   /**
    *
    * @return array smartyVariables
    */
   public function getSmartyObject(){
      $smartyVariables = array();

      $smartyVariables['backlogVariation_graphTitle'] = T_('Average backlog');
      $smartyVariables['backlogVariation_legendLabels'] = '["average backlog"]';


      $smartyVariables['backlogVariation_graphData'] = '['.Tools::array2plot($this->execData['finalData']).']';


      return $smartyVariables;
   }

}

// Initialize complex static variables
NormalizedBacklog::staticInit();
BacklogVariationIndicator::staticInit();

?>

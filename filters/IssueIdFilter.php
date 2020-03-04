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
 * Description of IssueIdFilter
 *
 */
class IssueIdFilter implements IssueSelectionFilter {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   private $filterCriteria; // array of issueId
   private $outputList;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct($id) {

      $this->id = $id;
   }

   public function getDesc() {
      return T_("Sort issues per ID");
   }

   public function getName() {
      return "IssueIdFilter";
   }

   public function getDisplayName() {
      return T_("Issue ID");
   }

   public function getId() {
      return $this->id;
   }

   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (is_null($inputIssueSel)) {
         throw new Exception("Missing IssueSelection");
      }

      if (!is_null($params)) {
         if (array_key_exists('filterCriteria', $params)) {

            if (!is_array($params['filterCriteria'])) {
               throw new Exception("Parameter 'filterCriteria' must be an array of issueId");
            }
            if (0 == count($params['filterCriteria'])) {
               // filterCriteria skipped if empty...
               self::$logger->warn("Parameter 'filterCriteria' skipped: empty array !");
            } else {
               $this->filterCriteria = $params['filterCriteria'];
               //self::$logger->debug("Return only issues in projects: ".implode(',', $this->filterCriteria));
            }
         }

      }
   }

   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      if (NULL == $this->outputList) {

         $this->outputList = array();

         $issueList = $inputIssueSel->getIssueList();
         foreach ($issueList as $issue) {
            $issueId = $issue->getId();
            $issueDisplayName = $issueId;

            // if no criteria defined, or ProjectId found in filterCriteria
            if (is_null($this->filterCriteria) ||
                in_array("$issueId", $this->filterCriteria)) {

               $tag = 'TASK_'.$issueId;
               if (!array_key_exists($tag, $this->outputList)) {
                  $this->outputList[$tag] = new IssueSelection($issueDisplayName);
               }
               $this->outputList[$tag]->addIssue($issue->getId());
            }
         }
         ksort($this->outputList);
      }
      return $this->outputList;
   }


}

// Initialize complex static variables
IssueIdFilter::staticInit();


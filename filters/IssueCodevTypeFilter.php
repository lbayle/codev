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
 * Description of IssueCodevType
 *
 */
class IssueCodevTypeFilter implements IssueSelectionFilter {

   const tag_Bug  = 'Bug';
   const tag_Task = 'Task';
   const tag_None = 'NO_TYPE';

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   private $filterCriteria; // array of projectId
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
      return T_("Sort issues per CustomField: CodevTT_Type");
   }

   public function getName() {
      return "IssueCodevTypeFilter";
   }

   public function getDisplayName() {
      return T_("Issue Type");
   }

   public function getId() {
      return $this->id;
   }

   public function addFilterCriteria($tag) {
      if (is_null($this->filterCriteria)) {
         $this->filterCriteria = array();
      }
      $this->filterCriteria[] = $tag;

      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug("Return only issues types: ".implode(',', $this->filterCriteria));
      }

   }

   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (is_null($inputIssueSel)) {
         throw new Exception("Missing IssueSelection");
      }

      if (!is_null($params)) {
         if (array_key_exists('filterCriteria', $params)) {

            if (!is_array($params['filterCriteria'])) {
               throw new Exception("Parameter 'filterCriteria' must be an array of CodevTT_Type");
            }
            if (empty($params['filterCriteria'])) {
               // filterCriteria skipped if empty...
               self::$logger->warn("Parameter 'filterCriteria' skipped: empty array !");
            } else {
               $this->filterCriteria = $params['filterCriteria'];
               if (self::$logger->isDebugEnabled()) {
                  self::$logger->debug("Return only issues types: ".implode(',', $this->filterCriteria));
               }
            }
         }
      }
   }

   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      if (is_null($this->outputList)) {

         $this->outputList = array();

         $issueList = $inputIssueSel->getIssueList();
         foreach ($issueList as $issue) {
            $type = $issue->getType();

            // if no criteria defined, or ProjectId found in filterCriteria
            if (is_null($this->filterCriteria) ||
                in_array("$type", $this->filterCriteria)) {

               if (empty($type)) {
                  $tag = IssueCodevTypeFilter::tag_None;
                  $displayName = "(no type)";
               } else {
                  $tag = $type;
                  $displayName = $type;
               }
               if (self::$logger->isDebugEnabled()) {
                  self::$logger->trace('execute: Issue '.$issue->getId().' Type = '.$tag);
               }

               if (!array_key_exists($tag, $this->outputList)) {
                  $this->outputList["$tag"] = new IssueSelection($displayName);
               }
               $this->outputList["$tag"]->addIssue($issue->getId());
            }
         }
         ksort($this->outputList);
      }
      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug('input Nb Issues ='.$inputIssueSel->getNbIssues());
         foreach ($this->outputList as $tag => $iSel) {
            self::$logger->debug('Type {'.$tag.'} Nb Issues ='.$iSel->getNbIssues());
         }
      }
      return $this->outputList;
   }


}

// Initialize complex static variables
IssueCodevTypeFilter::staticInit();
?>

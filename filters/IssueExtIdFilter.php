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
 * split input in two groups: with / without externalID
 */
class IssueExtIdFilter implements IssueSelectionFilter {

   const tag_with_extRef = 'withExtRef';
   const tag_no_extRef = 'noExtRef';

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   private $filterCriteria; // array of tags
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
      return T_("Sort issues: with/without External ID");
   }

   public function getName() {
      return "extIdFilter";
   }

   public function getDisplayName() {
      return T_("Issue External ID");
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
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
      if (!is_null($params)) {
         if (array_key_exists('filterCriteria', $params)) {

            if (!is_array($params['filterCriteria'])) {
               throw new Exception("Parameter 'filterCriteria' must be an array of tags");
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

            $extId = $issue->getTcId();

            if (!is_null($this->filterCriteria)) {

               if ((in_array(IssueExtIdFilter::tag_with_extRef, $this->filterCriteria)) &&
                (empty($extId))) {
                  continue;
               }
               if ((in_array(IssueExtIdFilter::tag_no_extRef, $this->filterCriteria)) &&
                (!empty($extId))) {
                  continue;
               }
            }

            if (!empty($extId)) {

               if (!array_key_exists(IssueExtIdFilter::tag_with_extRef, $this->outputList)) {
                  $displayName = IssueExtIdFilter::tag_with_extRef;
                  $this->outputList[IssueExtIdFilter::tag_with_extRef] = new IssueSelection($displayName);
               }
               $this->outputList[IssueExtIdFilter::tag_with_extRef]->addIssue($issue->getId());

               if (self::$logger->isDebugEnabled()) {
                  self::$logger->trace('execute: Issue '.$issue->getId().' extId = '.$extId);
               }
            } else {

               if (!array_key_exists(IssueExtIdFilter::tag_no_extRef, $this->outputList)) {
                  $displayName = IssueExtIdFilter::tag_no_extRef;
                  $this->outputList[IssueExtIdFilter::tag_no_extRef] = new IssueSelection($displayName);
               }
               $this->outputList[IssueExtIdFilter::tag_no_extRef]->addIssue($issue->getId());

               if (self::$logger->isDebugEnabled()) {
                  self::$logger->trace('execute: Issue '.$issue->getId().' extId Not defined');
               }
            }
         }

         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug('input Nb Issues ='.$inputIssueSel->getNbIssues());
            foreach ($this->outputList as $tag => $iSel) {
               self::$logger->debug('Tag {'.$tag.'} Nb Issues ='.$iSel->getNbIssues());
            }
         }
      }

      return $this->outputList;
   }

}

// Initialize complex static variables
IssueExtIdFilter::staticInit();
?>

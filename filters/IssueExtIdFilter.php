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
   const tag_without_extRef = 'noExtRef';

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

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

   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
   }

   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      if (NULL == $this->outputList) {
         $withExtIdIssueSel = new IssueSelection(IssueExtIdFilter::tag_with_extRef);
         $withoutExtIdIssueSel = new IssueSelection(IssueExtIdFilter::tag_without_extRef);
         $issueList = $inputIssueSel->getIssueList();
         foreach ($issueList as $issue) {

            $extId = $issue->getTcId();
            if (isset($extId)) {
               $withExtIdIssueSel->addIssue($issue->getId());
            } else {
               $withoutExtIdIssueSel->addIssue($issue->getId());
            }
         }
         $this->outputList = array();
         $this->outputList[IssueExtIdFilter::tag_with_extRef] = $withExtIdIssueSel;
         $this->outputList[IssueExtIdFilter::tag_without_extRef] = $withoutExtIdIssueSel;
      }
      return $this->outputList;
   }

}

// Initialize complex static variables
IssueExtIdFilter::staticInit();
?>

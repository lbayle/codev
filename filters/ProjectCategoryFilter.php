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

require_once('lib/log4php/Logger.php');

/* INSERT INCLUDES HERE */

/**
 * Description of ProjectCategoryFilter
 *
 */
class ProjectCategoryFilter implements IssueSelectionFilter {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   private $categoryList;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct($id) {

   }

   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
   }   
   
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      if (NULL == $this->categoryList) {
         $this->categoryList = array();
         $issueList = $inputIssueSel->getIssueList();
         foreach ($issueList as $issue) {
            $tag = "CATEGORY_".$issue->getCategoryId();

            if (!array_key_exists($tag, $this->categoryList)) {
               $this->categoryList[$tag] = new IssueSelection($issue->getCategoryId());
            }
            $this->categoryList[$tag]->addIssue($issue->getId());
         }
         ksort($this->categoryList);
      }
      return $this->categoryList;
   }

   public function getDesc() {
      return "sort issues per project categories";
   }

   public function getName() {
      return "ProjectCategoryFilter";
   }

}

// Initialize complex static variables
ProjectCategoryFilter::staticInit();
?>

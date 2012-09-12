<?php
/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/


class FilterNode {

   private $name;
   private $tagList;  // array("INDE","V4.1", "OPMNT", "withExtRef");

   /**
    *
    * @var String class name of the filter to apply
    */
   private $filterName;

   /**
    * @var IssueSelection The parent issueSel (result of previous filter)
    */
   private $issueSelection;

   /**
    *
    * @var FilterNode[] The result of the current filter
    */
   private $childList; // array of IssueSelection


   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   function __construct($name, IssueSelection $issueSel, array $filterList) {

      $this->name = $name;
      $this->issueSelection = $issueSel;
      //$this->tagList =

      // if not a leaf
      if (!empty($filterList)) {

         // extract & remove 1st element from $filterList
         $class_name = array_shift($filterList);
         $this->filterName = $class_name;

         echo "FILTER ".$class_name." NAME ".$this->name." IS_NAME ".$this->issueSelection->name." TAG".implode(',', $this->tagList)."<br>";

         // execute filter
         $filter = new $class_name("param1");
         $resultList = $filter->execute($issueSel, NULL);

         // execute on children
         foreach ($resultList as $tag => $iSel) {
            $this->childList[$tag] = new FilterNode($this->issueSelection->name, $iSel, $filterList);
         }
      } else {
         // self::$logger
         echo "NAME ".$this->name." IS_NAME ".$this->issueSelection->name." nbIssues = ".$this->issueSelection->getNbIssues()." - ".$this->issueSelection->getFormattedIssueList()."<br>";
      }
   }

   public function getName() {
      return $this->name;
   }
   public function getFilterName() {
      return $this->filterName;
   }
   public function getIssueSelection() {
      return $this->issueSelection;
   }
   public function getChildList() {
      return $this->childList;
   }

}

/**
 * Description of FilterManager
 */
class FilterManager {

   private $rootIssueSelection;
   private $filterList;
   private $rootNode;

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   function __construct(IssueSelection $issueSel, array $filterList = NULL) {
      $this->rootIssueSelection = $issueSel;
      $this->filterList = $filterList;
   }

   public function appendFilter($filterClassName) {
      if (NULL != $this->filterList) {
         $this->filterList = array();
      }
      $this->filterList[] = $filterClassName;
   }

   public function execute() {
      $this->rootNode = new FilterNode("root", $this->rootIssueSelection, $this->filterList);;
   }

   public function getRootNode() {
      return $this->rootNode;
   }

   public function getFilterList() {
      return $this->filterList;
   }

}

FilterNode::staticInit();
FilterManager::staticInit();

?>

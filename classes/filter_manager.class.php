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

/**
 * A FilterNode is a tree element containing an array of FilterNode
 * resusting from the applyance of the first filter of the given filterList.
 *
 * the leaf FilterNode (result of the last filter) contains
 * only an IssueSelection (no other FilterNode)
 */
class FilterNode {

   /**
    * this is the unique path of the tree resulting of previous filters applyance
    * ex: on filters Project,Version,Category => "root","myProject1","V4.1","myCat1"
    * @var array
    */
   private $tagList;  // array("INDE","V4.1", "OPMNT", "withExtRef");

   /**
    * @var String FilterClassName to apply
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
   private $childList; // array of FilterNode


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
   
   function __construct($tagList, IssueSelection $issueSel, array $filterList) {

      $this->issueSelection = $issueSel; // parent list (initial)
      $this->tagList = $tagList;

      // if not a leaf => more filters to apply
      if (!empty($filterList)) {

         // extract & remove 1st element from $filterList
         $class_name = array_shift($filterList);
         $this->filterName = $class_name;

         #echo "FILTER ".$class_name." TAG ".implode(',', $this->tagList)."<br>";

         // execute filter
         // the result of a filter is an array of isel
         $filter = new $class_name("param1");
         $resultList = $filter->execute($issueSel, NULL);

         // as we have more filters to apply,
         // create a FilterNode for each ISel and recursively apply the remaining filters
         $this->childList = array();
         foreach ($resultList as $tag => $iSel) {
            $nodeTagList = $tagList; // my own path resulting on previous filters
            $nodeTagList[] = $iSel->name; // add level to unique tree-path
            $this->childList[$tag] = new FilterNode($nodeTagList, $iSel, $filterList);
         }
      } // else {
         //echo "TAG ".implode(',', $this->tagList)." nbIssues = ".$this->issueSelection->getNbIssues()." - ".$this->issueSelection->getFormattedIssueList()."<br>";
      // }
   }

   public function getTagList() {
      return $this->tagList;
   }
   public function getFilterName() {
      return $this->filterName;
   }
   public function getIssueSelection() {
      return $this->issueSelection;
   }
   /**
    *
    * @return array array of FilterNode
    */
   public function getChildList() {
      return $this->childList;
   }

}

/**
 * Description of FilterManager
 */
class FilterManager {

   private $rootIssueSelection;
   private $filterList; // array of Filter class names (ProjectVersionFilter, IssueTagFilter, ...)
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

   /**
    *
    * @param IssueSelection $issueSel  initial isel
    * @param array $filterList array of Filter class names
    */
   function __construct(IssueSelection $issueSel, array $filterList = NULL) {
      $this->rootIssueSelection = $issueSel;
      $this->filterList = $filterList;
   }

   public function getRootNode() {
      return $this->rootNode;
   }

   public function getFilterList() {
      return $this->filterList;
   }

   public function appendFilter($filterClassName) {
      if (NULL != $this->filterList) {
         $this->filterList = array();
      }
      $this->filterList[] = $filterClassName;
   }

   public function getFlattened() {
      if ($this->rootNode) {
         $res = self::node_flatten($this->rootNode, array(), false);
      } else {
         $res = array(); // hmm...throw exception ?
      }
      return $res;
   }
   
   public function execute() {
      $this->rootNode = new FilterNode(array($this->rootIssueSelection->name), 
                                       $this->rootIssueSelection,
                                       $this->filterList);

      $flatIssueSelList = $this->getFlattened();

      return $flatIssueSelList;

   }

   /**
    * convert a FilterNode tree in an one-level array of ISel
    *
    *
    * @param FilterNode $node
    * @param array $return  the existing flattened array of Isel
    * @param type $isLeafOnly
    * @return array of Isel (existing flattened ISel + current Node Isels)
    */
   private static function node_flatten(FilterNode $node, array $return, $isLeafOnly = false) {

      // TODO $isLeafOnly

      $childList = $node->getChildList();

      // return data
      $tag = implode(',', $node->getTagList());
      $return[$tag] = $node->getIssueSelection();

      //echo "node [$tag] nbIssues = ".$node->getIssueSelection()->getNbIssues()."<br>";

      // check if leaf
      if (NULL != $childList) {

         foreach ($childList as $childNode) {
            $return = self::node_flatten($childNode, $return, $isLeafOnly);
         }
      }
      return $return;
   }

   /**
    * this method will explode the tags, to indent them when displayed in an HTML array
    *
    * Input: The result of execute() as an array of {key="tag1,tag2,tag3" value=IssueSel}
    *
    * @param IssueSelection[] $resultList array of key="tag1,tag2,tag3" value=IssueSel
    * @param boolean $isLeafOnly
    *
    * @return array  array(array(root,filter1,filter2,filter3, issueSel))
    */
   public function explodeResults(array $resultList, $isLeafOnly=true) {

      $nbLevels = count($this->filterList) + 1; // REM: the rootNode is included in the results at level '0'

      // array (root,filter1,filter2,filter3, issueSel)

      $resultArray = array();
      foreach ($resultList as $tag => $issueSel) {

         $tagList = explode(',',$tag);
         $nbTags = count($tagList);

         // display empty cells before&after the nodeName.
         $line = array();
         $nodeName = array_pop ($tagList);
         for ($i=0; $i < ($nbTags-1); $i++) { $line[] = ""; }
         $line[] = $nodeName;
         for ($i=0; $i < ($nbLevels - $nbTags); $i++) { $line[] = ""; }

         //if (($isLeafOnly) && (($nbTags-1) != $nbLevels)) {
         //   $line[] = NULL;
         //} else {
            $line[] = $issueSel;
         //}

         $resultArray[] = $line;
      }

      return $resultArray;
   }



}

FilterNode::staticInit();
FilterManager::staticInit();


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
 * Description of ProjectFilter
 *
 */
class ProjectFilter implements IssueSelectionFilter {

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
      return T_("Sort issues per Project");
   }

   public function getName() {
      return "ProjectFilter";
   }

   public function getDisplayName() {
      return T_("Project");
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
               throw new Exception("Parameter 'filterCriteria' must be an array of projectId");
            }
            if (0 == count($params['filterCriteria'])) {
               // filterCriteria skipped if empty...
               self::$logger->warn("Parameter 'filterCriteria' skipped: empty array !");
            } else {
               $this->filterCriteria = $params['filterCriteria'];
               self::$logger->debug("Return only issues in projects: ".implode(',', $this->filterCriteria));
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
            $projectId = $issue->getProjectId();
            $projectName = $issue->getProjectName();

            // if no criteria defined, or ProjectId found in filterCriteria
            if (is_null($this->filterCriteria) ||
                array_key_exists("$projectId", $this->filterCriteria)) {

               $tag = 'PROJ_'.$projectId;
               if (!array_key_exists($tag, $this->outputList)) {
                  $this->outputList[$tag] = new IssueSelection($projectName);
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
ProjectFilter::staticInit();
?>

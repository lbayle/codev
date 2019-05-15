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
 * Sort issues per ProjectVersion
 */
class ProjectVersionFilter implements IssueSelectionFilter {

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
      return T_("Sort issues per Project TargetVersion");
   }

   public function getName() {
      return "ProjectVersionFilter";
   }

   public function getDisplayName() {
      return T_("Project TargetVersion");
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

         $this->outputList = array();

         $issueList = $inputIssueSel->getIssueList();
         foreach ($issueList as $issue) {
            $tagVersion = "VERSION_".$issue->getTargetVersion();
               $versionName = (NULL == $issue->getTargetVersion()) ? "(no version)" : $issue->getTargetVersion();

            if (!array_key_exists($tagVersion, $this->outputList)) {
               $this->outputList[$tagVersion] = new ProjectVersion($this->id, $versionName);
            }
            $this->outputList[$tagVersion]->addIssue($issue->getId());
         }
         ksort($this->outputList);
      }
      return $this->outputList;
   }

}


// Initialize complex static variables
ProjectVersionFilter::staticInit();
?>

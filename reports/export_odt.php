<?php

require('../include/session.inc.php');

/*
  This file is part of CodevTT.

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

require('../path.inc.php');

// Make sure you have Zip extension or PclZip library loaded
// First : include the librairy
require_once('lib/odtphp/library/odf.php');

class ExportODTController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger('export_odt');
   }

   private function logException(Exception $e) {
      self::$logger->error("EXCEPTION " . $e->getMessage());
      self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
   }


   /**
    * get SMARTY table with ODT template files
    *
    * @param string $odtTemplateDir
    */
   private function getTemplates($odtTemplateDir = NULL, $selectedFile = 'none') {

      if (is_null($odtTemplateDir)) {
         $odtTemplateDir = Constants::$codevRootDir . '/odt_templates';
      }

      $files = glob($odtTemplateDir.'/*.odt');
      $templates = array();
      foreach($files as $id => $f) {
         $templates[] = array(
            'id' => $id,
            'name' => basename($f),
            'selected' => (basename($f) == $selectedFile)
         );
      }
      return $templates;
   }

   /**
    *
    * @param type $projectid
    * @param array $selectedCategoryList
    * @return type
    */
   private function getProjectCategories($projectid, array $selectedCategoryList = array(0)) {

      $categories = array();
      $categories[0] = array(
            'id' => 0,
            'name' => T_('(all)'),
            'selected' => in_array(0, $selectedCategoryList)
         );
      if (0 != $projectid) {
         $project = ProjectCache::getInstance()->getProject($projectid);
         $categoryList = $project->getCategories();

         foreach($categoryList as $id => $name) {
            $selected = in_array($id, $selectedCategoryList);
            $categories[] = array(
               'id' => $id,
               'name' => $name,
               'selected' => $selected
            );
         }
      }
      return $categories;
   }

   /**
    * get only statuses defined for this project
    *
    * @return array statusId => statusName
    */
   private function getProjectStatusList($projectid, $selectedStatusList = array(0)) {


      $statusList = array();
      $statusList[0] = array(
            'id' => 0,
            'name' => T_('(all)'),
            'selected' => in_array(0, $selectedStatusList)
         );

      if (0 != $projectid) {
         // get only statuses defined for this project
         $project = ProjectCache::getInstance()->getProject($projectid);
         $wfTrans = $project->getWorkflowTransitionsFormatted();

         if (!is_null($wfTrans)) {
            $statusNames = $wfTrans[0];
         } else {
            // if none defined, get all mantis statuses
            $statusNames = Constants::$statusNames;
            ksort($statusNames);
         }
      }

      $statusList[Constants::$status_new] = array(
            'id' => Constants::$status_new,
            'name' => Constants::$statusNames[Constants::$status_new],
            'selected' => in_array(Constants::$status_new, $selectedStatusList)
         );

      foreach ($statusNames as $id => $name) {
         if (Constants::$status_new != $id) {
            $selected = in_array($id, $selectedStatusList);
            $statusList[] = array(
               'id' => $id,
               'name' => $name,
               'selected' => $selected
            );
         }
      }

      return $statusList;
   }

   /**
    *
    * @param array $selectedUseridList
    * @return type
    */
   private function getTeamMembers(array $selectedUseridList = array(0)) {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $memberList = $team->getActiveMembers();
      $members = array();
      $members[0] = array(
            'id' => 0,
            'name' => T_('(all)'),
            'selected' => in_array(0, $selectedUseridList)
         );
      foreach($memberList as $id => $name) {
         $selected = in_array($id, $selectedUseridList);
         $members[] = array(
            'id' => $id,
            'name' => $name,
            'selected' => $selected
         );
      }
      return $members;
   }

   /**
    *
    * @param type $projectid
    * @param String $categories imploded category_id list
    * @param String $reporteridList imploded userid list
    * @param String $handleridList imploded userid list
    */
   private function getIssueSelection($projectid, $categories = NULL, $formattedReporters = NULL, $formattedHandlers = NULL, $formattedStatuses = false) {

      $query = "SELECT id from `mantis_bug_table` WHERE project_id = $projectid ";

      if (!empty($categories)) {
         $query .= "AND category_id IN ($categories) ";
      }
      if (!empty($formattedReporters)) {
         $query .= "AND reporter_id IN ($formattedReporters) ";
      }
      if (!empty($formattedHandlers)) {
         $query .= "AND handler_id IN ($formattedHandlers) ";
      }
      if (!empty($formattedStatuses)) {
         $query .= "AND status IN ($formattedStatuses) ";
      }
      #if (!$withResolved) {
      #   $query .= "AND status < get_project_resolved_status_threshold(project_id) ";
      #}

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $iSel = new IssueSelection('exportODT');
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $iSel->addIssue($row->id);
      }
      #$iSel->addIssue(694);

      #echo implode(',', array_keys($iSel->getIssueList())).'<br>';

      return $iSel;
   }

   /**
    *
    * @param IssueSelection $iSel
    * @param type $projectid
    * @param type $odtTemplate
    * @return string filepath complete path to generated ODT file
    */
   private function generateODT(IssueSelection $iSel, $projectid, $odtTemplate) {

      self::$logger->debug("genProjectODT(): project $projectid template $odtTemplate");

      $project = ProjectCache::getInstance()->getProject($projectid);
      $odf = new odf($odtTemplate);

      try { $odf->setVars('today', date('Y-m-d')); } catch (Exception $e) { };
      try { $odf->setVars('selectionName', $project->getName()); } catch (Exception $e) { };
      try {
         $session_user = UserCache::getInstance()->getUser($this->session_userid);
         $odf->setVars('sessionUser', $session_user->getRealname());
      } catch (Exception $e) { };

      $issueList = $iSel->getIssueList();
      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug("nb issues = " . count($issueList));
      }

      $q_id = 0;
      try {
         $issueSegment = $odf->setSegment('issueSelection');
      } catch (Exception $e) {
         self::$logger->error("generateODT: TAG 'issueSelection'");
         self::$logger->error("generateODT: "+$e->getMessage());
         return "FAILED: error on segment 'issueSelection'.";
      }

      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug('XML=' . $issueSegment->getXml());
      }

      foreach ($issueList as $issue) {
         $q_id += 1;

         if (0 == $issue->getHandlerId()) {
            $userName = T_('unknown');
         } else {
            $user = UserCache::getInstance()->getUser($issue->getHandlerId());
            $userName = utf8_decode($user->getRealname());
            if (empty($userName)) {
               $userName = "user_" . $issue->getHandlerId();
            }
         }
         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue " . $issue->getId() . ": handlerName = " . $userName);
         }

         if (0 == $issue->getReporterId()) {
            $reporterName = T_('unknown');
         } else {
            $reporter = UserCache::getInstance()->getUser($issue->getReporterId());
            $reporterName = utf8_decode($reporter->getRealname());
            if (empty($reporterName)) {
               $reporterName = "user_" . $issue->getReporterId();
            }
         }
         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue " . $issue->getId() . ": reporterName = " . $reporterName);
         }

         // add issue
         try { $issueSegment->setVars('q_id', $q_id); } catch (Exception $e) { };

         try { $issueSegment->setVars('bugId', $issue->getId()); } catch (Exception $e) { $this->logException($e); };
         try { $issueSegment->setVars('summary', utf8_decode($issue->getSummary())); } catch (Exception $e) { $this->logException($e); };

         try { $issueSegment->setVars('dateSubmission', date('d/m/Y', $issue->getDateSubmission())); } catch (Exception $e) { };
         try {
            $timestamp = $issue->getLatestStatusOccurrence($issue->getBugResolvedStatusThreshold());
            if (is_null($timestamp)) {
               $issueSegment->setVars('dateResolved', ''); 
            } else {
               $issueSegment->setVars('dateResolved', date('d/m/Y', $timestamp));
            }

         } catch (Exception $e) { };
         try {
            $timestamp = $issue->getDeadLine();
            $deadline = (0 == $timestamp) ? '' : date('d/m/Y', $issue->getDeadLine());
            $issueSegment->setVars('deadline', $deadline);
         } catch (Exception $e) { $this->logException($e); };
         try { $issueSegment->setVars('currentStatus', Constants::$statusNames[$issue->getCurrentStatus()]); } catch (Exception $e) { };
         try { $issueSegment->setVars('handlerId', $userName); } catch (Exception $e) { };
         try { $issueSegment->setVars('reporterId', $reporterName); } catch (Exception $e) { };
         try { $issueSegment->setVars('reporterName', $reporterName); } catch (Exception $e) { };
         try { $issueSegment->setVars('description', utf8_decode($issue->getDescription())); } catch (Exception $e) { };
         #try { $issueSegment->setVars('description', utf8_decode(Tools::convertToUTF8($issue->getDescription()))); } catch (Exception $e) { };
         try { $issueSegment->setVars('category', $issue->getCategoryName()); } catch (Exception $e) { };
         try { $issueSegment->setVars('severity', $issue->getSeverityName()); } catch (Exception $e) { };
         try { $issueSegment->setVars('status', Constants::$statusNames[$issue->getStatus()]);}  catch (Exception $e) { };
         try { $issueSegment->setVars('extId', $issue->getTcId()); } catch (Exception $e) { };

         // add issueNotes
         $issueNotes = $issue->getIssueNoteList();
         $noteId = 0;
         foreach ($issueNotes as $id => $issueNote) {
            $noteId += 1;
            if (self::$logger->isDebugEnabled()) {
               self::$logger->debug("issue " . $issue->getId() . ": note $id = $issueNote->note");
            }

            $noteReporter = UserCache::getInstance()->getUser($issueNote->reporter_id);
            try { $noteReporterName = utf8_decode($noteReporter->getRealname()); } catch (Exception $e) { };
            try { $issueSegment->bugnotes->noteId($noteId); } catch (Exception $e) { };
            try { $issueSegment->bugnotes->noteReporter($noteReporterName); } catch (Exception $e) { };
            try { $issueSegment->bugnotes->noteDateSubmission(date('d/m/Y', $issueNote->date_submitted)); } catch (Exception $e) { };
            try { $issueSegment->bugnotes->note(utf8_decode($issueNote->note)); } catch (Exception $e) { };
            try { $issueSegment->bugnotes->merge(); } catch (Exception $e) { };
         }
         $issueSegment->merge();
      }
      $odf->mergeSegment($issueSegment);

      // INFO: the following line is MANDATORY and fixes the following error:
      // "wrong .odt file encoding"
      #ob_end_clean();
      #$odf->exportAsAttachedFile();


      // 2nd solution : show link in page
      $odtFilename = basename($odtTemplate, ".odt").'_'.$project->getName().'_'.date('Ymd').'.odt';
      $filepath = Constants::$codevOutputDir . '/reports/' . $odtFilename ;
      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug("save odt file " . $filepath);
      }
      $odf->saveToDisk($filepath);

      return $filepath;
   }

   /**
    * Display HTML page
    */
   protected function display() {
      if (Tools::isConnectedUser()) {

         if (0 != $this->teamid) {

            #$isManager = $this->session_user->isTeamManager($this->teamid);
            #$this->smartyHelper->assign('isManager', $isManager);

            $tmpTeamList = array($this->teamid => $this->teamList[$this->teamid]);
            $projList = $this->session_user->getProjectList($tmpTeamList, true, false);
            reset($projList);
            $projectid = key($projList);

            $odtTemplate = NULL;

            $action = Tools::getSecurePOSTStringValue('action', '');
            
            if ('downloadODT' == $action) {

               $projectid = Tools::getSecurePOSTIntValue('projectid', NULL);
               $formattedCategories = Tools::getSecurePOSTStringValue('categoryList', NULL);
               $formattedStatuses = Tools::getSecurePOSTStringValue('statusList', NULL);
               $formattedReporters = Tools::getSecurePOSTStringValue('reporterList', NULL);
               $formattedHandlers = Tools::getSecurePOSTStringValue('handlerList', NULL);

               $odtBasename = Tools::getSecurePOSTStringValue('templateFile', NULL);
               $odtTemplate = Constants::$codevRootDir.'/odt_templates/'.$odtBasename;
            }

            $this->smartyHelper->assign('odtTemplates', $this->getTemplates(NULL, basename($odtTemplate)));
            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList, $projectid));

            $selectedCategories = empty($formattedCategories) ? array(0) :  explode(',', $formattedCategories);
            $this->smartyHelper->assign('categories', $this->getProjectCategories($projectid, $selectedCategories));

            $selectedStatusList = empty($formattedStatuses) ? array(0) :  explode(',', $formattedStatuses);
            $this->smartyHelper->assign('statuses', $this->getProjectStatusList($projectid, $selectedStatusList));

            $selectedReporters = empty($formattedReporters) ? array(0) :  explode(',', $formattedReporters);
            $this->smartyHelper->assign('reporters', $this->getTeamMembers($selectedReporters));

            $selectedHandlers = empty($formattedHandlers) ? array(0) :  explode(',', $formattedHandlers);
            $this->smartyHelper->assign('handlers',  $this->getTeamMembers($selectedHandlers));


            if ('downloadODT' == $action) {

               $iSel = $this->getIssueSelection($projectid, $formattedCategories, $formattedReporters, $formattedHandlers, $formattedStatuses);
               #echo implode(',', array_keys($iSel->getIssueList())).'<br>';

               $odfFilepath = $this->generateODT($iSel, $projectid, $odtTemplate);
               $this->smartyHelper->assign('odtFilename',  basename($odfFilepath));

            }
         }
      }
   }

}

// ========== MAIN ===========
ExportODTController::staticInit();
$controller = new ExportODTController('../', 'Export Issues to ODT', 'ImportExport');
$controller->execute();
?>

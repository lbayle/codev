<?php
require('../include/session.inc.php');

require('../path.inc.php');

include_once('i18n/i18n.inc.php');

$page_name = T_("Test: export to ODT");

// Make sure you have Zip extension or PclZip library loaded
// First : include the librairy
require_once('lib/odtphp/library/odf.php');

$logger = Logger::getLogger("odt_test");

function displayProjectSelectionForm($originPage, $projList, $defaultProjectid = 0) {
   global $logger;

   // Display form
   echo "<div style='text-align: center;'>";
   echo "<form name='form1' method='post' action='$originPage'>\n";

   // Project list
   echo "Project ";
   echo "<select id='projectid' name='projectid' title='".T_("Project")."'>\n";
   foreach ($projList as $pid => $pname) {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type='submit' value='".T_("Select")."'>\n";
   echo "<input type='hidden' name='action' value='genODT' />\n";

   echo "</form>\n";

   echo "</div>";
}

/**
 *
 * @global type $logger
 * @param Project $project
 * @param type $odtTemplate
 * @param type $userid
 * @param type $session_userid
 */
function genProjectODT(Project $project, $odtTemplate, $session_userid, $userid = 0) {
   global $logger;

   $logger->debug("genProjectODT(): project ".$project->getName()." template $odtTemplate user $userid");

   $odf = new odf($odtTemplate);

   try { $odf->setVars('today',  date('Y-m-d')); } catch (Exception $e) {};
   try { $odf->setVars('selectionName', $project->getName()); } catch (Exception $e) {};
   try {
      $session_user = UserCache::getInstance()->getUser($session_userid);
      $odf->setVars('sessionUser', $session_user->getRealname()); } catch (Exception $e) {};

   $isHideResolved = true;
   $issueList = $project->getIssues($userid, $isHideResolved);

   $q_id = 0;
   $issueSegment = $odf->setSegment('issueSelection');

   foreach($issueList as $issue) {
      $q_id += 1;

      if (0 == $issue->getHandlerId()) {
         $userName = T_('unknown');
      } else {
         $user = UserCache::getInstance()->getUser($issue->getHandlerId());
         $userName = utf8_decode($user->getRealname());
         if (empty($userName)) {
            $userName = "user_".$issue->getHandlerId();
         }
      }

      if (0 == $issue->getReporterId()) {
         $reporterName = T_('unknown');
      } else {
         $reporter = UserCache::getInstance()->getUser($issue->getReporterId());
         $reporterName = utf8_decode($reporter->getRealname());
         if (empty($reporterName)) {
            $reporterName = "user_".$issue->getReporterId();
         }
      }

      // add issue
      try { $issueSegment->setVars('q_id', $q_id); } catch (Exception $e) { $logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString()); };

      try { $issueSegment->setVars('bugId', $issue->getId()); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('summary', utf8_decode($issue->getSummary())); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};

      try { $issueSegment->setVars('dateSubmission', date('d/m/Y',$issue->getDateSubmission())); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try {
         $timestamp = $issue->getDeadLine();
         $deadline = (0 == $timestamp) ? '' : date('d/m/Y',$issue->getDeadLine());
         $issueSegment->setVars('deadline', $deadline); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('currentStatus', Constants::$statusNames[$issue->getCurrentStatus()]); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('handlerId', $userName); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('reporterId', $reporterName); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('reporterName', $reporterName); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('description', utf8_decode($issue->getDescription())); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('category', $issue->getCategoryName()); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('severity', $issue->getSeverityName()); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('status', Constants::$statusNames[$issue->getStatus()]); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};
      try { $issueSegment->setVars('extId', $issue->getTcId()); } catch (Exception $e) {$logger->error("EXCEPTION ".$e->getMessage()); $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());};

      // add issueNotes
      $issueNotes = $issue->getIssueNoteList();
      $noteId = 0;
      foreach ($issueNotes as $id => $issueNote) {
         $noteId += 1;

         $noteReporter     = UserCache::getInstance()->getUser($issueNote->reporter_id);
         try { $noteReporterName = utf8_decode($noteReporter->getRealname()); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->noteId($noteId); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->noteReporter($noteReporterName); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->noteDateSubmission(date('d/m/Y',$issueNote->date_submitted)); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->note(utf8_decode($issueNote->note)); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->merge(); } catch (Exception $e) {};
      }
      $issueSegment->merge();
   }
   $odf->mergeSegment($issueSegment);

   // INFO: the following line is MANDATORY and fixes the following error:
   // "wrong .odt file encoding"
   //ob_end_clean();
   //$odf->exportAsAttachedFile();

   $myFile = Constants::$codevOutputDir.'/reports/odtphp_test_'.time().'.odt';
   $odf->saveToDisk($myFile);
   echo "<br>";
   echo "<br>";
   echo "<span style='padding-left: 3em'><a tatget='blank' href='../include/download.php?f=".basename($myFile)."'>".basename($myFile)."</a></span>";
}

#==== MAIN =====
$session_userid = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];


if (isset($session_userid)) {
   $session_user = UserCache::getInstance()->getUser($session_userid);
   // exclude disabled projects
   $projList = $session_user->getProjectList(NULL, true, false);

   if (0 == count($projList)) {
      echo "<div style='text-align: center'>";
      echo T_("Sorry, there are no projects defined for your team.");
      echo "</div>";
   } else {
      $defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
      $projectid = Tools::getSecurePOSTIntValue('projectid', $defaultProject);
      $_SESSION['projectid'] = $projectid;

      displayProjectSelectionForm("odt.php", $projList, $projectid);

      $action = Tools::getSecurePOSTStringValue('action', 'none');
      if ('genODT' == $action) {
         $project = ProjectCache::getInstance()->getProject($projectid);

         genProjectODT($project, "../odt_templates/atos_dinf_01.odt", $session_userid, 0);
         #genProjectODT($project, "../odt_templates/test_01.odt", $session_userid, 0);
      }
   }

}

?>

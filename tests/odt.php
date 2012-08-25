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

   echo "<input type='submit' value='".T_("Jump")."'>\n";
   echo "</form>\n";

   echo "</div>";
}

function genODT(User $user) {
   $odf = new odf("odtphp_template.odt");

   $odf->setVars('today',  date('Y-m-d H:i:s'));
   $odf->setVars('selectionName',  $user->getRealname());

   $issueList = $user->getAssignedIssues();

   $issueSegment = $odf->setSegment('issueSelection');
   foreach($issueList AS $issue) {

      $user     = UserCache::getInstance()->getUser($issue->getHandlerId());
      $reporter = UserCache::getInstance()->getUser($issue->getReporterId());

      $issueSegment->bugId($issue->getId());
      $issueSegment->summary(utf8_decode($issue->getSummary()));
      $issueSegment->dateSubmission(date('d/m/Y',$issue->getDateSubmission()));
      $issueSegment->currentStatus(Constants::$statusNames[$issue->getCurrentStatus()]);
      $issueSegment->handlerId(utf8_decode($user->getRealname()));
      $issueSegment->reporterId(utf8_decode($reporter->getRealname()));
      $issueSegment->description(utf8_decode($issue->getDescription()));
      $issueSegment->merge();
   }
   $odf->mergeSegment($issueSegment);

   $odf->exportAsAttachedFile();
   //$odf->saveToDisk('/tmp/odtphp_test.odt');
}

function genProjectODT(Project $project, $odtTemplate, $userid = 0) {
   global $logger;

   $odf = new odf($odtTemplate);

   try { $odf->setVars('today',  date('Y-m-d H:i:s')); } catch (Exception $e) {};
   try { $odf->setVars('selectionName',  $project->name); } catch (Exception $e) {};

   $isHideResolved = true;
   $issueList = $project->getIssues($userid, $isHideResolved);
   $logger->debug("nb issues = ".count($issueList));

   $q_id = 0;
   $issueSegment = $odf->setSegment('issueSelection');
   foreach($issueList as $issue) {
      $q_id += 1;

      if (0 == $issue->getHandlerId()) {
         $userName = T_('unknown');
      } else {
         $user = UserCache::getInstance()->getUser($issue->getHandlerId());
         $userName = utf8_decode($user->getRealname());
      }
      $logger->debug("issue ".$issue->getId().": userName = ".$userName);

      if (0 == $issue->getReporterId()) {
         $reporterName = T_('unknown');
      } else {
         $reporter = UserCache::getInstance()->getUser($issue->getReporterId());
         $reporterName = utf8_decode($reporter->getRealname());
      }
      $logger->debug("issue ".$issue->getId().": reporterName = ".$reporterName);

      // add issue
      try { $issueSegment->q_id($q_id); } catch (Exception $e) {};
      try { $issueSegment->bugId($issue->getId()); } catch (Exception $e) {};
      try { $issueSegment->summary(utf8_decode($issue->getSummary())); } catch (Exception $e) {};
      try { $issueSegment->dateSubmission(date('d/m/Y',$issue->getDateSubmission())); } catch (Exception $e) {};
      try { $issueSegment->currentStatus(Constants::$statusNames[$issue->getCurrentStatus()]); } catch (Exception $e) {};
      try { $issueSegment->handlerId($userName); } catch (Exception $e) {};
      try { $issueSegment->reporterId($reporterName); } catch (Exception $e) {};
      try { $issueSegment->description(utf8_decode($issue->getDescription())); } catch (Exception $e) {};
      try { $issueSegment->category($issue->getCategoryName()); } catch (Exception $e) {};

      // add issueNotes
      $issueNotes = $issue->getIssueNoteList();
      foreach ($issueNotes as $id => $issueNote) {

         $logger->debug("issue ".$issue->getId().": note $id = $issueNote->note");

         $reporter     = UserCache::getInstance()->getUser($issueNote->reporter_id);
         try { $reporterName = utf8_decode($user->getRealname()); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->noteReporter($reporterName); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->noteDateSubmission(date('d/m/Y',$issueNote->date_submitted)); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->note(utf8_decode($issueNote->note)); } catch (Exception $e) {};
         try { $issueSegment->bugnotes->merge(); } catch (Exception $e) {};
      }
      $issueSegment->merge();
   }
   $odf->mergeSegment($issueSegment);

   $odf->exportAsAttachedFile();
   //$odf->saveToDisk('/tmp/odtphp_test.odt');
}

#==== MAIN =====
$session_userid = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];

$logger->debug ("session_userid = $session_userid");

if (isset($session_userid)) {
   $session_user = UserCache::getInstance()->getUser($session_userid);
   $projList = $session_user->getProjectList();

   if (0 == count($projList)) {
      echo "<div style='text-align: center'>";
      echo T_("Sorry, there are no projects defined for your team.");
      echo "</div>";
   } else {
      $defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
      $projectid = Tools::getSecureGETIntValue('projectid', $defaultProject);
      $_SESSION['projectid'] = $projectid;

      $logger->debug ("projectid = $projectid");

      displayProjectSelectionForm("odt.php", $projList, $projectid);

      if (isset($_GET['projectid'])) {
         $project = ProjectCache::getInstance()->getProject($projectid);

         // INFO: the following line is MANDATORY and fixes the following error:
         // "wrong .odt file encoding"
         ob_end_clean();

         genProjectODT($project, "../odt_templates/questions.odt");
         #genProjectODT($project, "../odt_templates/questions2.odt");
         #genProjectODT($project, "odtphp_template.odt");
      }
   }

}

?>

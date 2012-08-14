<?php
include_once('../include/session.inc.php');

include_once '../path.inc.php';

include_once 'i18n/i18n.inc.php';

$page_name = T_("Test: export to ODT");
require_once 'include/header.inc.php';

// Make sure you have Zip extension or PclZip library loaded
// First : include the librairy
require_once('lib/odtphp/library/odf.php');

include_once('classes/user.class.php');
include_once "classes/issue.class.php";
include_once "classes/issue_note.class.php";
include_once "classes/project.class.php";

require_once('constants.php');

?>

<script language="JavaScript">

  function submitForm() {
    document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
    document.forms["form1"].action.value = "displayProject";
    document.forms["form1"].submit();
  }


</script>


<?php

$logger = Logger::getLogger("odt_test");

// ---------------------------------------------------------------
function displayProjectSelectionForm($originPage, $projList, $defaultProjectid = 0) {

	global $logger;

	// Display form
	echo "<div style='text-align: center;'>";
	echo "<form name='form1' method='post' Action='$originPage'>\n";

	// Project list
	echo "Project ";
	echo "<select id='projectidSelector' name='projectidSelector' title='".T_("Project")."'>\n";
	echo "<option value='0'></option>\n";
	foreach ($projList as $pid => $pname)
	{
		if ($pid == $defaultProjectid) {
			echo "<option selected value='".$pid."'>$pname</option>\n";
		} else {
			echo "<option value='".$pid."'>$pname</option>\n";
		}
	}
	echo "</select>\n";

	echo "<input type=button value='".T_("Jump")."' onClick='javascript: submitForm()'>\n";

	echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
	echo "<input type=hidden name=action       value=noAction>\n";
	echo "</form>\n";

	echo "</div>";
}

// ------------------------------------
function genODT($user) {
	$odf = new odf("odtphp_template.odt");

	$odf->setVars('today',  date('Y-m-d H:i:s'));
	$odf->setVars('selectionName',  $user->getRealname());

	$issueList = $user->getAssignedIssues();

	$issueSegment = $odf->setSegment('issueSelection');
	foreach($issueList AS $issue) {

		$user     = UserCache::getInstance()->getUser($issue->handlerId);
		$reporter = UserCache::getInstance()->getUser($issue->reporterId);

		$issueSegment->bugId($issue->bugId);
		$issueSegment->summary(utf8_decode($issue->summary));
		$issueSegment->dateSubmission(date('d/m/Y',$issue->dateSubmission));
		$issueSegment->currentStatus(Constants::$statusNames["$issue->currentStatus"]);
		$issueSegment->handlerId(utf8_decode($user->getRealname()));
		$issueSegment->reporterId(utf8_decode($reporter->getRealname()));
		$issueSegment->description(utf8_decode($issue->getDescription()));
		$issueSegment->merge();
	}
	$odf->mergeSegment($issueSegment);


	$odf->exportAsAttachedFile();
	//$odf->saveToDisk('/tmp/odtphp_test.odt');


}

// ------------------------------------
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

		if (0 == $issue->handlerId) {
			$userName = T_('unknown');
		} else {
			$user = UserCache::getInstance()->getUser($issue->handlerId);
			$userName = utf8_decode($user->getRealname());
		}
		$logger->debug("issue $issue->bugId: userName = ".$userName);

		if (0 == $issue->reporterId) {
			$reporterName = T_('unknown');
		} else {
			$reporter = UserCache::getInstance()->getUser($issue->reporterId);
			$reporterName = utf8_decode($reporter->getRealname());
		}
		$logger->debug("issue $issue->bugId: reporterName = ".$reporterName);

		// add issue
		try { $issueSegment->q_id($q_id); } catch (Exception $e) {};
		try { $issueSegment->bugId($issue->bugId); } catch (Exception $e) {};
		try { $issueSegment->summary(utf8_decode($issue->summary)); } catch (Exception $e) {};
		try { $issueSegment->dateSubmission(date('d/m/Y',$issue->dateSubmission)); } catch (Exception $e) {};
		try { $issueSegment->currentStatus(Constants::$statusNames["$issue->currentStatus"]); } catch (Exception $e) {};
		try { $issueSegment->handlerId($userName); } catch (Exception $e) {};
		try { $issueSegment->reporterId($reporterName); } catch (Exception $e) {};
		try { $issueSegment->description(utf8_decode($issue->getDescription())); } catch (Exception $e) {};
		try { $issueSegment->category($issue->getCategoryName()); } catch (Exception $e) {};

      // add issueNotes
      $issueNotes = $issue->getIssueNoteList();
      foreach ($issueNotes as $id => $issueNote) {

      	$logger->debug("issue $issue->bugId: note $id = $issueNote->note");

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

$originPage = "odt.php";

$action           = isset($_POST['action']) ? $_POST['action'] : '';
$session_userid   = isset($_POST['userid']) ? $_POST['userid'] : $_SESSION['userid'];

$defaultProject = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
$projectid        = isset($_POST['projectid']) ? $_POST['projectid'] : $defaultProject;
$_SESSION['projectid'] = $projectid;

$logger->debug ("session_userid = $session_userid");

if (isset($session_userid))
{

	$session_user = UserCache::getInstance()->getUser($session_userid);
	$projList = $session_user->getProjectList();

	if (0 == count($projList)) {
		echo "<div id='content'' class='center'>";
		echo T_("Sorry, there are no projects defined for your team.");
		echo "</div>";

	} else {


		$logger->debug ("projectid = $projectid");

		echo "<br/>";
		echo "<br/>";
		echo "<br/>";
		displayProjectSelectionForm($originPage, $projList, $projectid);

		if ("displayProject" == $action) {

			$project = ProjectCache::getInstance()->getProject($projectid);

			// INFO: the following line is MANDATORY and fixes the following error:
			// "wrong .odt file encoding"
			ob_end_clean();

			genProjectODT($project, "../odt_templates/questions.odt");
			#genProjectODT($project, "../odt_templates/questions2.odt");
			#genProjectODT($project, "odtphp_template.odt");

		} elseif ("setProjectid" == $action) {

			// pre-set form fields
			$projectid  = $_POST['projectid'];

		} elseif ("notAllowed" == $action) {
			echo "<br/>";
			echo "<br/>";
			echo "<br/>";
			echo "<br/>";
			echo T_("Sorry, you are not allowed to view the details of this project")."<br/>";
		}


	}


}


?>

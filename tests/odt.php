<?php
include_once('../include/session.inc.php');

include_once '../path.inc.php';

include_once 'i18n.inc.php';

$page_name = T_("Tools: export to LibreOffice");
include 'header.inc.php';

include_once('user.class.php');

// Make sure you have Zip extension or PclZip library loaded
// First : include the librairy
require_once('odf.php');


// ------------------------------------
function genODT($user) {

   global $statusNames;

	$odf = new odf("odtphp_template.odt");

   $odf->setVars('today',  date('Y-m-d H:i:s'));
   $odf->setVars('userName',  $user->getRealname());

   $issueList = $user->getAssignedIssues();

   $issueSegment = $odf->setSegment('assignedIssues');
   foreach($issueList AS $issue) {

   	$user = UserCache::getInstance()->getUser($issue->handlerId);

	   $issueSegment->bugId($issue->bugId);
	   $issueSegment->summary($issue->summary);
	   $issueSegment->dateSubmission(date('d/m/Y',$issue->dateSubmission));
	   $issueSegment->currentStatus($statusNames["$issue->currentStatus"]);
	   $issueSegment->handlerId($user->getRealname());
	   $issueSegment->description($issue->getDescription());
	   $issueSegment->merge();
   }
   $odf->mergeSegment($issueSegment);


   $odf->exportAsAttachedFile();
   //$odf->saveToDisk('/tmp/odtphp_test.odt');


}

#==== MAIN =====


if (isset($_SESSION['userid']))
{

    // Admins only
    $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
// INFO: the following 1 line are MANDATORY and fix the following error:
// “The image <name> cannot be displayed because it contains errors”
ob_end_clean();

   genODT($session_user);



}


?>

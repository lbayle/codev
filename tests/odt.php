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

	$odf = new odf("odtphp_template.odt");

   $odf->setVars('today',  date('Y-m-d H:i:s'));
   $odf->setVars('userName',  $user->getRealname());

   $odf->exportAsAttachedFile();
   //$odf->saveToDisk('/tmp/odtphp_test.odt');


}

#==== MAIN =====


if (isset($_SESSION['userid']))
{

    // Admins only
    $session_user = new User($_SESSION['userid']);

// INFO: the following 1 line are MANDATORY and fix the following error:
// “The image <name> cannot be displayed because it contains errors”
ob_end_clean();

   genODT($session_user);



}


?>

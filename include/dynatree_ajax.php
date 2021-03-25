<?php
require('../include/session.inc.php');
require('../path.inc.php');
include_once('i18n/i18n.inc.php');

if (Tools::isConnectedUser() && (isset($_POST['action']))) {

	$logger = Logger::getLogger("dynatreeAjax");
   if (isset($_POST['action'])) {

      if($_POST['action'] == 'saveWBS') {

         #$root_id          = Tools::getSecurePOSTIntValue('wbsRootId');
         #$jsonDynatreeDict = Tools::getSecurePOSTStringValue('jsonDynatreeDict');
         #$nodesToDelete    = Tools::getSecurePOSTStringValue('nodesToDelete', '');
         $root_id          = $_POST['wbsRootId'];
         $jsonDynatreeDict = $_POST['jsonDynatreeDict'];
         $nodesToDelete    = $_POST['nodesToDelete'];

         // dynatree returns a 'container' element
         $dynatreeDict = json_decode($jsonDynatreeDict);
         $rootArray = get_object_vars($dynatreeDict[0]);

         if (!is_null($nodesToDelete)) {
            foreach ($nodesToDelete as $folder_id) {
               $f = new WBSElement($folder_id, $root_id);
               try {
                  $f->delete($root_id);
               } catch (Exception $e) {
                  // happens if user moved children AND deleted the node.
                  // The node will not be deleted, but at least the rest of the WBS changes
                  // have a chance to be proceeded.
                  $logger->error("Node $folder_id not deleted : ".$e->getMessage());
                  $logger->warn("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
               }
            }
         }
         WBSElement::updateFromDynatree($rootArray, $root_id);

         echo $jsonDynatreeDict;

      } else if($_POST['action'] == 'loadWBS') {

         try {
            $root_id = Tools::getSecurePOSTIntValue('wbsRootId');
            $hasDetail = (1 === Tools::getSecurePOSTIntValue('hasDetail')) ? true : false;

            $userid = $_SESSION['userid'];
            $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
            $session_user = UserCache::getInstance()->getUser($userid);

            // Managers & Observers have the same view (MEE,Reestimated, ...)
            $isManager = $session_user->isTeamManager($teamid);
            $isObserver = $session_user->isTeamObserver($teamid);
            $isManager = ($isManager || $isObserver);

            $rootElement = new WBSElement($root_id);
            $dynatreeDict = $rootElement->getDynatreeData($hasDetail, $isManager, $teamid);

            $jsonDynatreeDict = json_encode($dynatreeDict);
            echo $jsonDynatreeDict;
         } catch (Exception $e) {
            $logger->error("loadWBS: ".$e->getMessage());
            Tools::sendNotFoundAccess();
         }

      } else {
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}
?>

<?php
require('../include/session.inc.php');

require('../path.inc.php');

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

         if ($logger->isDebugEnabled()) {
            $aa = var_export($rootArray, true);
            $logger->debug("saveWBS (nodesToDelete=".implode(',', $nodesToDelete).")");
            $logger->debug("saveWBS (root=$root_id) : \n$aa");
         }

         file_put_contents('/tmp/tree.txt', "=== NEW ".time()."\n");

         if (!is_null($nodesToDelete)) {
            foreach ($nodesToDelete as $folder_id) {
               $f = new WBSElement2($folder_id, $root_id);
               $f->delete($root_id);
            }
         }
         WBSElement2::updateFromDynatree($rootArray, $root_id);

         echo $jsonDynatreeDict;

      } else if($_POST['action'] == 'loadWBS') {

         $root_id = Tools::getSecurePOSTIntValue('wbsRootId');
         $hasDetail = (1 === Tools::getSecurePOSTIntValue('hasDetail')) ? true : false;

			//file_put_contents('/tmp/loadWBS.txt', "=== ".date('Ymd')."\n");
			//file_put_contents('/tmp/loadWBS.txt', "root_id = $root_id \n", FILE_APPEND);

			$rootElement = new WBSElement2($root_id);
			$dynatreeDict = $rootElement->getDynatreeData($hasDetail);

         if ($logger->isDebugEnabled()) {
            $aa = var_export($dynatreeDict, true);
            $logger->debug("loadWBS (root=$root_id, hasDetail=".$_POST['hasDetail'].") : \n$aa");
         }

			$jsonDynatreeDict = json_encode($dynatreeDict);
			echo $jsonDynatreeDict;


      } else {
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}
?>

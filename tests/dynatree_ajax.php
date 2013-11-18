<?php
require('../include/session.inc.php');

require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_GET['action']))) {

	$logger = Logger::getLogger("dynatreeAjax");
   if (isset($_GET['action'])) {

      if($_GET['action'] == 'saveWBS') {

         $root_id          = $_GET['wbsRootId'];
         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];
         $nodesToDelete    = $_GET['nodesToDelete'];

         // dynatree returns a 'container' element
         $dynatreeDict = json_decode($jsonDynatreeDict);
         $rootArray = get_object_vars($dynatreeDict[0]);

         if ($logger->isDebugEnabled()) {
            $aa = var_export($rootArray, true);
            $logger->debug("saveWBS (nodesToDelete=".implode(',', $nodesToDelete).")");
            $logger->debug("saveWBS (root=$root_id) : \n$aa");
         }

         file_put_contents('/tmp/tree.txt', "=== NEW ".time()."\n");

         foreach ($nodesToDelete as $folder_id) {
            $f = new WBSElement2($folder_id, $root_id);
            $f->delete($root_id);
         }
         WBSElement2::updateFromDynatree($rootArray, $root_id);

         echo $jsonDynatreeDict;

      } else if($_GET['action'] == 'loadWBS') {

         $root_id = $_GET['wbsRootId'];
         $hasDetail = ('1' == $_GET['hasDetail']) ? true : false;

			//file_put_contents('/tmp/loadWBS.txt', "=== ".date('Ymd')."\n");
			//file_put_contents('/tmp/loadWBS.txt', "root_id = $root_id \n", FILE_APPEND);

			$rootElement = new WBSElement2($root_id);
			$dynatreeDict = $rootElement->getDynatreeData($hasDetail);

         if ($logger->isDebugEnabled()) {
            $aa = var_export($dynatreeDict, true);
            $logger->debug("loadWBS (root=$root_id, hasDetail=".$_GET['hasDetail'].") : \n$aa");
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

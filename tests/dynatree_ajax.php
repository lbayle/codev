<?php
require('../include/session.inc.php');

require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_GET['action']))) {

   if (isset($_GET['action'])) {

      if($_GET['action'] == 'saveWBS') {

         $root_id          = $_GET['wbsRootId'];
         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];
         $nodesToDelete    = $_GET['nodesToDelete'];

         $dynatreeDict = json_decode($jsonDynatreeDict);
         //file_put_contents('/tmp/dynatree.txt', serialize($dynatreeDict));

         file_put_contents('/tmp/tree.txt', "=== NEW ".time()."\n");
         WBSElement2::createTreeFromDynatreeData($dynatreeDict, 1, $root_id, $root_id);

         echo $jsonDynatreeDict;

      } else if($_GET['action'] == 'loadWBS') {

         $root_id = $_GET['wbsRootId'];
         $hasDetail = ('1' == $_GET['hasDetail']) ? true : false;

			//file_put_contents('/tmp/getDynatreeData.txt', "=== ".date('Ymd')."\n");
			$rootElement = new WBSElement2($root_id);
			$dynatreeDict = $rootElement->getDynatreeData($hasDetail);
			//file_put_contents('/tmp/getDynatreeData.txt', serialize($dynatreeDict)."\n");
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

<?php
require('../include/session.inc.php');

require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_GET['action']))) {

   if (isset($_GET['action'])) {

      if($_GET['action'] == 'saveWBS') {

         $root_id          = $_GET['wbsRootId'];
         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];
         $nodesToDelete    = $_GET['nodesToDelete'];

         // dynatree returns a 'container' element
         $dynatreeDict = json_decode($jsonDynatreeDict);
         $rootArray = get_object_vars($dynatreeDict[0]);

         file_put_contents('/tmp/tree.txt', "=== NEW ".time()."\n");
         WBSElement2::updateFromDynatree($rootArray, $root_id);

         echo $jsonDynatreeDict;

      } else if($_GET['action'] == 'loadWBS') {

         $root_id = $_GET['wbsRootId'];
         $hasDetail = ('1' == $_GET['hasDetail']) ? true : false;

			//file_put_contents('/tmp/loadWBS.txt', "=== ".date('Ymd')."\n");
			//file_put_contents('/tmp/loadWBS.txt', "root_id = $root_id \n", FILE_APPEND);

			$rootElement = new WBSElement2($root_id);
			$dynatreeDict = $rootElement->getDynatreeData($hasDetail);

         //file_put_contents('/tmp/loadWBS.txt', serialize($dynatreeDict)."\n", FILE_APPEND);
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

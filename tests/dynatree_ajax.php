<?php
require('../include/session.inc.php');

require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_GET['action']))) {

   if (isset($_GET['action'])) {

      if($_GET['action'] == 'saveWBS') {

         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];

         $dynatreeDict = json_decode($jsonDynatreeDict);
         file_put_contents('/tmp/dynatree.txt', serialize($dynatreeDict));

         file_put_contents('/tmp/tree.txt', "=== NEW ".date('Ymd')."\n");
         //$root_id = WBSElement2::create(NULL, NULL, NULL, NULL, "root_".date('Ymd'));
			$root_id = 1;
         $myarray = get_object_vars($dynatreeDict);
         $rootArray = get_object_vars($myarray['children'][0]);
         WBSElement2::createTreeFromDynatreeData($rootArray, 1, $root_id, $root_id);

         echo $jsonDynatreeDict;

      } else if($_GET['action'] == 'loadWBS') {

         $root_id = $_GET['wbsElementId'];
			//$root_id = 1;

/*
         $serializedDynatreeDict = file_get_contents('/tmp/dynatree.txt');

         if (FALSE == $serializedDynatreeDict) {
            //echo "file not found!!!";
            Tools::sendNotFoundAccess();
         } else {
            $dynatreeDict = unserialize($serializedDynatreeDict);
            $jsonDynatreeDict = json_encode($dynatreeDict);
            echo $jsonDynatreeDict;
         }
*/
			file_put_contents('/tmp/getDynatreeData.txt', "=== ".date('Ymd')."\n");
			$rootElement = new WBSElement2($root_id);
			$dynatreeDict = $rootElement->getDynatreeData(true);
			file_put_contents('/tmp/getDynatreeData.txt', serialize($dynatreeDict)."\n");
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

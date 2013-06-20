<?php
require('../include/session.inc.php');

require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_GET['action']))) {

   if (isset($_GET['action'])) {
      
      if($_GET['action'] == 'saveWBS') {

         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];

         $dynatreeDict = json_decode($jsonDynatreeDict);
         file_put_contents('/tmp/dynatree.txt', serialize($dynatreeDict));

         echo $jsonDynatreeDict;

      } else if($_GET['action'] == 'loadWBS') {
 
         $wbsElementId = $_GET['wbsElementId'];

         $serializedDynatreeDict = file_get_contents('/tmp/dynatree.txt');
	 
         if (FALSE == $serializedDynatreeDict) { 
            //echo "file not found!!!";
            Tools::sendNotFoundAccess(); 
         } else {
            $dynatreeDict = unserialize($serializedDynatreeDict);
            $jsonDynatreeDict = json_encode($dynatreeDict);
            echo $jsonDynatreeDict;
         }

      } else {
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}
?>

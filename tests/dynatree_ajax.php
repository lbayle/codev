<?php
require('../include/session.inc.php');

require('../path.inc.php');
if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   if(isset($_GET['action'])) {
      if($_GET['action'] == 'saveWBS') {
         $jsonDynatreeDict = $_GET['jsonDynatreeDict'];

         $dynatreeDict = json_decode($jsonDynatreeDict);
         file_put_contents('/tmp/dynatree.txt', serialize($dynatreeDict));

         echo $jsonDynatreeDict;
      } else {
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}
?>

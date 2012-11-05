<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/
require('../path.inc.php');

if(Tools::isConnectedUser() && (isset($_POST['action']) || isset($_POST['action']))) {
   if(isset($_POST['action'])) {
      $smartyHelper = new SmartyHelper();
      if($_POST['action'] == 'saveProvisionChanges') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {

               // <provid>:<isInCheckBudget>,provid:<isInCheckBudget>
               $imploded = Tools::getSecurePOSTStringValue("isInCheckBudgetImploded");

               echo "imploded = $imploded<br>";
               $provisions = Tools::doubleExplode(':', ',', $imploded);


               // save Provision changes
               foreach ($provisions as $provid => $isInCheckBudget) {
                  $prov = new CommandProvision($provid);

                  echo "prov $provid = $isInCheckBudget<br>";

/*
                  // securityCheck: does provid belong to this command ?
                  if ($cmdid == $prov->getCommandId()) {
                     $prov->setIsInCheckBudget($isInCheckBudget);
                  } else {
                     // LOG SECURITY ERROR !!
                  }
 */
               }

            } else {
               Tools::sendBadRequest("Invalid CommandId: 0");
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

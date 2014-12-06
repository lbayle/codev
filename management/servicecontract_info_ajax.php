<?php
require('../include/session.inc.php');

/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/
require('../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();
      if ($_GET['action'] == 'updateDetailedCharges') {

         $servicecontractid = Tools::getSecureGETIntValue('selectFiltersSrcId');
         $selectedFilters = Tools::getSecureGETStringValue('selectedFilters', '');


         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $session_user->setServiceContractFilters($selectedFilters, $servicecontractid);

         $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
         $isManager = $session_user->isTeamManager($servicecontract->getTeamid());
         $isObserver = $session_user->isTeamObserver($servicecontract->getTeamid());

         // DetailedChargesIndicator
         $data = ServiceContractTools::getDetailedCharges($servicecontract, ($isManager || $isObserver), $selectedFilters);
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $smartyHelper->display(DetailedChargesIndicator::getSmartySubFilename());

      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

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

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();

      if ($_GET['action'] == 'updateDetailedCharges') {

         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $teamid = $_SESSION['teamid'];

         $projectid = Tools::getSecureGETIntValue('selectFiltersSrcId');

         $isManager = $user->isTeamManager($teamid);
         $isObserver = $user->isTeamObserver($teamid);

         $selectedFilters = Tools::getSecureGETStringValue('selectedFilters', '');

         // save user preferances
         $user->setProjectFilters($selectedFilters, $projectid);

         // DetailedChargesIndicator
         $data = ProjectInfoTools::getDetailedCharges($projectid, ($isManager || $isObserver), $selectedFilters);
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

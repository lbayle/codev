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

   //$teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;

   if(isset($_GET['action'])) {
      //$smartyHelper = new SmartyHelper();
      if ($_GET['action'] == 'markIssueNoteAsRead') {

         $bugid = Tools::getSecureGETIntValue('bugid');
         $issueNote = IssueNote::getTimesheetNote($bugid);
         if (!is_null($issueNote)) {
            $userid = $_SESSION['userid'];
            $issueNote->markAsRead($userid);
            $data = 'OK';

         } else {
            $data = 'ERROR';
            // TODO return ERROR
            Tools::sendBadRequest("Could not mark as read.");
         }

         // return data
         echo $data;
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

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

if(Tools::isConnectedUser() && (isset($_POST['action']))) {

	//$logger = Logger::getLogger("TeamActivityReportAjax");

   //$teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;

   if(isset($_POST['action'])) {
      //$smartyHelper = new SmartyHelper();
      if ($_POST['action'] == 'markIssueNoteAsRead') {

         $bugid = Tools::getSecurePOSTIntValue('bugid');
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

      } else if ($_POST['action'] == 'deleteNote') {

         $userid = $_SESSION['userid'];
         $bugid = Tools::getSecurePOSTIntValue('bugid');
         $bugnoteid = Tools::getSecurePOSTIntValue('bugnote_id');

         // note could be deleted, but it will be transformed in a 'normal' note (remove codevtt tags)
         //$retCode = IssueNote::delete($bugnoteid, $bugid, $userid);
         $issueNote = new IssueNote($bugnoteid);
         $noteText = $issueNote->getText(); // remove tags
         $retCode = $issueNote->setText($noteText, $issueNote->getReporterId());

         // return data
         echo $data;
      }

   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>

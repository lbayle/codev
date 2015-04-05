<?php
require(realpath(dirname(__FILE__)).'/../include/session.inc.php');
require(realpath(dirname(__FILE__)).'/../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

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



// =========== MAIN ==========
$logger = Logger::getLogger("sendTimetrackEmails");

# Make sure this script doesn't run via the webserver
if( php_sapi_name() != 'cli' ) {
	echo "send_timesheet_emails.php is not allowed to run through the webserver.";
   $logger->error("send_timesheet_emails.php is not allowed to run through the webserver.");
	exit( 1 );
}


if (1 == Constants::$emailSettings['enable_email_notification']) {

   if ( count($argv) > 1) {
      if (is_numeric($argv[1])) {
         $team_id = intval($argv[1]);
      } else {
         echo 'cmd line arg "'.$argv[1].'" is not a team_id !'."\n";
         $logger->error('cmd line arg "'.$argv[1].'" is not a team_id !');
         exit( 2 );
      }
   }

   //$startT = strtotime("first day of last month");
   //$endT = strtotime("-1 days", time());
   $endT = time();
   $endT = mktime(0, 0, 0, date('m', $endT), date('d',$endT), date('Y', $endT));

   if (is_null($team_id)) {
      $query = "SELECT id FROM `codev_team_table` WHERE enabled = 1;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $team = TeamCache::getInstance()->getTeam($row->id);
         $team->sendTimesheetEmails($startT, $endT);
      }
   } else {
      $team = TeamCache::getInstance()->getTeam($team_id);
      $team->sendTimesheetEmails($startT, $endT);

   }

}

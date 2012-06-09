<?php

include_once('../include/session.inc.php');

/*
  This file is part of CodevTT.

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

require('super_header.inc.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "service.class.php";

include "service_tools.php";

include_once "smarty_tools.php";

$logger = Logger::getLogger("service_edit");


// your functions here
// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Service (edit)'));

if (isset($_SESSION['userid'])) {

   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if (isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if (isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }
   $_SESSION['teamid'] = $teamid;

   // ---
   // use the serviceid set in the form, if not defined (first page call) use session serviceid
   $serviceid = 0;
   if(isset($_POST['serviceid'])) {
      $serviceid = $_POST['serviceid'];
   } else if(isset($_SESSION['serviceid'])) {
      $serviceid = $_SESSION['serviceid'];
   }
   $_SESSION['serviceid'] = $serviceid;


   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------
   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));

   $smartyHelper->assign('serviceid', $serviceid);
   $smartyHelper->assign('services', getServices($teamid, $serviceid));


   if (0 != $serviceid) {
      $service = new Service($serviceid);


      // ------ Actions

      if ("addEngagement" == $action) {
echo "addEngagement<br>";

         $_SESSION['engid'] = 0;
         header('Location:engagement_edit.php?engid=0');

      } else if ("updateServiceInfo" == $action) {
echo "updateServiceInfo<br>";
      } else if ("removeEngagement" == $action) {
echo "removeEngagement<br>";

      }

      // ------ Display Service

      displayService($smartyHelper, $service);

   }
   
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>

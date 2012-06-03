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

include_once "service.class.php";
include_once "engagement.class.php";
include_once "user.class.php";
include_once "team.class.php";

include_once "smarty_tools.php";

$logger = Logger::getLogger("service_info");


function getServices($teamid, $selectedServiceId) {

   $services = array();
if (0 != $teamid) {

   $team = TeamCache::getInstance()->getTeam($teamid);
   $serviceList = $team->getServices();

   foreach ($serviceList as $id => $service) {
      $services[] = array(
         'id' => $id,
         'name' => $service->getName(),
         'selected' => ($id == $selectedServiceId)
      );
   }
}
   return $services;
}

function getServiceEngagements($serviceid, $type) {
   
   $engagements = array();

   if (0 != $serviceid) {
      $service = new Service($serviceid); // TODO use cache

      $engList = $service->getEngagements($type);
      foreach ($engList as $id => $eng) {
         $engagements[] = array(
            'id' => $id,
            'name' => $eng->getName(),
         );
      }
   }
   return $engagements;
}


// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Service'));

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

   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));

   $smartyHelper->assign('serviceid', $serviceid);
   $smartyHelper->assign('services', getServices($teamid, $serviceid));

   if (0 != $serviceid) {
      $service = new Service($serviceid);
      $smartyHelper->assign('serviceDate', date("Y-m-d", $service->getDate()));

      $smartyHelper->assign('devEngagements', getServiceEngagements($serviceid, Service::engType_dev));
      $smartyHelper->assign('mngtEngagements', getServiceEngagements($serviceid, Service::engType_mngt));
   }









   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------


   // your actions here
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>

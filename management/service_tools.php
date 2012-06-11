<?php
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



/**
 *
 * @param int $teamid
 * @param int $selectedServiceId
 * @return array
 */
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

/**
 *
 * @param int $serviceid
 * @param int $type
 * @return array
 */
function getServiceEngagements($serviceid, $type) {

   $engagements = array();

   if (0 != $serviceid) {
      $service = new Service($serviceid); // TODO use cache

      $engList = $service->getEngagements($type);
      foreach ($engList as $id => $eng) {

         $issueSelection = $eng->getIssueSelection();
         $engDetailedMgr = getIssueSelectionDetailedMgr($issueSelection);

         $engDetailedMgr['name'] = $eng->getName();
         $engDetailedMgr['description'] = $eng->getDesc();

         $engagements[$id] = $engDetailedMgr;
      }
   }
   return $engagements;
}

/**
 *
 * @param type $smartyHelper
 * @param Service $service
 */
function displayService($smartyHelper, $service) {

   #$smartyHelper->assign('serviceId', $service->getId());
   $smartyHelper->assign('serviceName', $service->getName());
   $smartyHelper->assign('serviceDesc', $service->getDesc());
   $smartyHelper->assign('serviceDate', date("Y-m-d", $service->getDate()));

   $smartyHelper->assign('engList', getServiceEngagements($service->getId(), Service::engType_dev));

   // DEPRECATED, a service has only one type of engagement
   #$smartyHelper->assign('mngtEngagements', getServiceEngagements($serviceid, Service::engType_mngt));

}

?>

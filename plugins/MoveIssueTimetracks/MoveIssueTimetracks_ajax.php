<?php

require('../../include/session.inc.php');

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
require('../../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if (Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

    $logger = Logger::getLogger("MoveIssueTimetracks_ajax");
    $action = Tools::getSecurePOSTStringValue('action');
    $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');

    if (!isset($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId])) {
        $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
        Tools::sendBadRequest("PluginDataProvider not set");
    }
    $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId]);
    if (FALSE == $pluginDataProvider) {
        $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
        Tools::sendBadRequest("PluginDataProvider unserialize error");
    }

    $smartyHelper = new SmartyHelper();
    if ('getUsersFromTeam' == $action) {

        $team = Tools::getSecurePOSTIntValue('moveIssueTimetracks_displayedTeam', 0);
        $teamUserList = array();
        $teamUserListSmarty = null;

        if ($team != 0) {
            $teamUserList = TeamCache::getInstance()->getTeam($team)->getMembers();
        }

        $data['moveIssueTimetracks_teamUserList'] = $teamUserList;
        $jsonData = json_encode($data);
        echo $jsonData;
    } else if ('getMoveIssueTimetracks' == $action) {

        $statusMsg = null;
        $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("moveIssueTimetracks_startdate"));
        $endTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("moveIssueTimetracks_enddate"));

        $users = Tools::getSecurePOSTStringValue('moveIssueTimetracks_displayedUser', 0);
        $users = json_decode(stripslashes($users), true);

        $originTaskId = Tools::getSecurePOSTIntValue('moveIssueTimetracks_bugidOrigin', 0);
        $destinationTaskId = Tools::getSecurePOSTIntValue('moveIssueTimetracks_bugidDestination', 0);


        // if all fields are not filled
        if ($startTimestamp == null || $endTimestamp == null || $users == null || $originTaskId == 0 || $destinationTaskId == 0) {
            $data['statusMsg'] = T_("Please, fill all fields");
        } else { 
            // if origin task is the same as destination task
            if ($originTaskId == $destinationTaskId) {
                $data['statusMsg'] = T_("Destination task must be different from origin");
            } else {
                $isOriginTaskExisting = true;
                $originTask = null;
                try {
                    // get origin task
                    $originTask = new Issue($originTaskId);
                    $originTaskSummary = $originTask->getSummary();
                    $originTaskCreationDate = $originTask->getDateSubmission();
                } catch (Exception $ex) {
                    $data['statusMsg'] = T_("This origin task doesn't exist");
                    $isOriginTaskExisting = false;
                }

                if ($isOriginTaskExisting) {
                    // Get timetracks   
                    $indicator = new MoveIssueTimetracks($pluginDataProvider);
                    $timetracks = $indicator->getTimetracks($users, $startTimestamp, $endTimestamp, $originTaskId);

                    // check if destination task creation date is posterior to one of timetracks creation date
                    $isDestinationTaskCreationDatePosterior = false;

                    $destinationTaskSummary = null;
                    $destinationTask = null;
                    // If timetracks exist
                    if (null != $timetracks) {
                        try {
                            // Get issue
                            $destinationTask = new Issue($destinationTaskId);
                            $destinationTaskSummary = $destinationTask->getSummary();
                            $destinationTaskDate = $destinationTask->getDateSubmission();
                            
                            // For all timetracks
                            foreach ($timetracks as $timetrack) {
                                // If destination task date is posterior to timetrack date
                                if (Tools::date2timestamp($timetrack['date']) < $destinationTaskDate) {
                                    $isDestinationTaskCreationDatePosterior = true;
                                }
                            }

                            $statusMsg = "SUCCESS";
                        } catch (Exception $ex) {
                            $statusMsg = T_("This destination task doesn't exist");
                        }
                    } else {
                        $statusMsg = T_("No timetracks for this search");
                    }

                    $originTaskSummary = Tools::issueInfoURL($originTaskId, NULL, TRUE) . ' - ' . $originTaskSummary;
                    $destTaskSummary = Tools::issueInfoURL($destinationTaskId, NULL, TRUE) . ' - ' . $destinationTaskSummary;

                    $data = array(
                        'statusMsg' => $statusMsg,
                        'moveIssueTimetracks_selectedOriginTask' => $originTaskId,
                        'moveIssueTimetracks_selectedOriginTaskSummary' => $originTaskSummary,
                        'moveIssueTimetracks_selectedDestinationTask' => $destinationTaskId,
                        'moveIssueTimetracks_selectedDestinationTaskSummary' => $destTaskSummary,
                        'moveIssueTimetracks_selectedBeginDate' => $startTimestamp,
                        'moveIssueTimetracks_selectedEndDate' => $endTimestamp,
                        'moveIssueTimetracks_timetracks' => $timetracks,
                        'moveIssueTimetracks_isDestinationTaskCreationDatePosterior' => $isDestinationTaskCreationDatePosterior,
                        'moveIssueTimetracks_originTaskCreationDate' => date('Y-m-d', $originTaskCreationDate)
                    );

                    // construct the html table
                    foreach ($data as $smartyKey => $smartyVariable) {
                        $smartyHelper->assign($smartyKey, $smartyVariable);
                    }
                    $html = $smartyHelper->fetch(MoveIssueTimetracks::getSmartySubFilename());
                    $data['moveIssueTimetracks_htmlContent'] = $html;
                }
            }
        }


        // set JS libraries that must be load
        $data['moveIssueTimetracks_jsFiles'] = TimetrackDetailsIndicator::getJsFiles();
        $data['moveIssueTimetracks_cssFiles'] = TimetrackDetailsIndicator::getCssFiles();

        // return data (just an integer value)
        $jsonData = json_encode($data);
        echo $jsonData;
    } else if ('moveIssueTimetracks' == $action) {

        $timetracksIds = Tools::getSecurePOSTStringValue('moveIssueTimetracks_timetracksIds', 0);
        $timetracksIds = json_decode(stripslashes($timetracksIds), true);

        $destinationTaskId = Tools::getSecurePOSTIntValue('bugidDestination', 0);

        if ($timetracksIds != null && $destinationTaskId != 0) {
            // Move timetracks   
            $indicator = new MoveIssueTimetracks($pluginDataProvider);
            $timetracks = $indicator->moveTimetracks($timetracksIds, $destinationTaskId);

            $data["statusMsg"] = "SUCCESS";
        } else {
            $data["statusMsg"] = T_("Please, display timetracks before moving them");
        }

        $jsonData = json_encode($data);

        echo $jsonData;
    } else {
        Tools::sendNotFoundAccess();
    }
} else {
    Tools::sendUnauthorizedAccess();
}



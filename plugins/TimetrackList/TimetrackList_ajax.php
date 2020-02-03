<?php
require('../../include/session.inc.php');

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
require('../../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $logger = Logger::getLogger("TimetrackList_ajax");
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

      // ================================================================
      if('getTimetrackList' == $action) {
         $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("timetrackList_startdate"));
         $endTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("timetrackList_enddate"));

         // get dataProvider
         $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');

         if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
            $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
            Tools::sendBadRequest("PluginDataProvider not set");
         }
         $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
         if (FALSE == $pluginDataProvider) {
            $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
            Tools::sendBadRequest("PluginDataProvider unserialize error");
         }

         $attributesJsonStr = Tools::getSecurePOSTStringValue('attributesJsonStr');
         $attributesArray = json_decode(stripslashes($attributesJsonStr), true);

         $isDisplayCommands = ('on' !== $attributesArray[TimetrackList::OPTION_IS_DISPLAY_COMMANDS]) ? false : true;
         $isDisplayProject = ('on' !== $attributesArray[TimetrackList::OPTION_IS_DISPLAY_PROJECT]) ? false : true;
         $isDisplayCategory = ('on' !== $attributesArray[TimetrackList::OPTION_IS_DISPLAY_CATEGORY]) ? false : true;
         $isDisplayTaskSummary = ('on' !== $attributesArray[TimetrackList::OPTION_IS_DISPLAY_SUMMARY]) ? false : true;

         //$logger->error("attributesArray = ".var_export($attributesArray, true));

         // update dataProvider
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

         $indicator = new TimetrackList($pluginDataProvider);

         // override plugin settings with current attributes
         $indicator->setPluginSettings(array(
             TimetrackList::OPTION_IS_DISPLAY_COMMANDS => $isDisplayCommands,
             TimetrackList::OPTION_IS_DISPLAY_PROJECT => $isDisplayProject,
             TimetrackList::OPTION_IS_DISPLAY_CATEGORY => $isDisplayCategory,
             TimetrackList::OPTION_IS_DISPLAY_SUMMARY => $isDisplayTaskSummary,
         ));

         $indicator->execute();
         $data = $indicator->getSmartyVariablesForAjax();

         // construct the html table
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
            #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
         }
         $html = $smartyHelper->fetch(TimetrackList::getSmartySubFilename());
         $data['timetrackList_htmlTable'] = $html;

         // set JS libraries that must be load
         $data['timetrackList_jsFiles'] = TimetrackDetailsIndicator::getJsFiles();
         $data['timetrackList_cssFiles'] = TimetrackDetailsIndicator::getCssFiles();

         // return html & chart data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if('getEditTimetrackData' == $action) {

         $timetrackId = Tools::getSecurePOSTStringValue('timetrackId');
         try {
            $tt = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);
            $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());
            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
            $teamProjTypes = $team->getProjectsType();
            $availableJobs = $project->getJobList($teamProjTypes[$issue->getProjectId()]);

            // return data
            $data = array(
               'statusMsg' => 'SUCCESS',
               'note' => $tt->getNote(),
               'issueSummary' => $issue->getSummary(),
               'noteIsMandatory' => $team->getGeneralPreference('isTrackNoteMandatory'),
               'availableJobs' => $availableJobs,
               'jobId' => $tt->getJobId(),
            );
         } catch (Exception $e) {
            $data = array(
               'statusMsg' => 'Could not get timetrack data',
            );
         }
         $jsonData = json_encode($data);
         // return data
         echo $jsonData;

      } else if('setEditTimetrackData' == $action) {

            //////////////// UPDATE //////////////////
            $timetrackId = Tools::getSecurePOSTIntValue('timetrackId');
            $jobId = Tools::getSecurePOSTIntValue('jobId');
            $team = TeamCache::getInstance()->getTeam($teamid);
            $tt = TimeTrackCache::getInstance()->getTimeTrack($timetrackId);

            // Info: no need to sql_real_escape_string, it is applied in the setNote method...
            $note = filter_input(INPUT_POST, 'note'); // filter_input to handle escaped chars (quotes, \n, ...)

            $updateDone = $tt->update($tt->getDate(), $tt->getDuration(), $jobId, $note);

            $statusMsg = ($updateDone) ? "SUCCESS" : "timetrack update failed.";

            $jobs = new Jobs();

            $data = array(
               'statusMsg' => $statusMsg,
               'note' => nl2br(htmlspecialchars($tt->getNote())),
               'jobName' => $jobs->getJobName($jobId),
            );
            $jsonData = json_encode($data);

            // return data
            echo $jsonData;

      } else {
         $logger->error("Unknown action: $action");
         Tools::sendNotFoundAccess();
      }
   } else {
      Tools::sendUnauthorizedAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}



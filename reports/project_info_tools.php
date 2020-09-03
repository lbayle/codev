<?php

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

/**
 * Description of ProjectInfoTools
 *
 */
class ProjectInfoTools {

   /**
    *
    * @param SmartyHelper $smartyHelper
    * @param Command $prj
    * @param int $userid
    */
   public static function dashboardSettings(SmartyHelper $smartyHelper, Project $prj, $userid, $teamid, $startT, $endT) {

      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $prj->getIssueSelection());
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $teamid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $userid);

      // Calculate a nice day interval
      $nbWeeks = ($endT - $startT) / 60 / 60 / 24;
      $interval = ceil($nbWeeks / 20);

      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startT);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endT);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $interval);

      $dashboardName = 'Project'.$prj->getId();
      $dashboardDomain = IndicatorPluginInterface::DOMAIN_PROJECT;

      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_DOMAIN, $dashboardDomain);

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard($dashboardName);
      $dashboard->setDomain($dashboardDomain);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
         ));
      $dashboard->setTeamid($teamid);
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
   }

}


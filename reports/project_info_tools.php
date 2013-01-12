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

   public static function getDetailedCharges($projectid, $isManager, $selectedFilters) {

      $project = ProjectCache::getInstance()->getProject($projectid);
      $issueSel = $project->getIssueSelection();

      $allFilters = "ProjectVersionFilter,ProjectCategoryFilter,IssueExtIdFilter,IssuePublicPrivateFilter,IssueTagFilter,IssueCodevTypeFilter";

      $params = array(
         'isManager' => $isManager,
         #'teamid' => $teamid,
         'selectedFilters' => $selectedFilters,
         'allFilters' => $allFilters,
         'maxTooltipsPerPage' => Constants::$maxTooltipsPerPage
      );


      $detailedChargesIndicator = new DetailedChargesIndicator();
      $detailedChargesIndicator->execute($issueSel, $params);

      $smartyVariable = $detailedChargesIndicator->getSmartyObject();
      $smartyVariable['selectFiltersSrcId'] = $projectid;

      return $smartyVariable;
   }



}

?>

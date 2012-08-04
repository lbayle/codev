<?php
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

require_once('classes/issue_selection.class.php');

include_once('classes/sqlwrapper.class.php');

class ProjectVersion extends IssueSelection {

   public $projectId;
   protected $versionDate; // mantis_project_version_table.date_order

   public function __construct($projectId, $version) {
      parent::__construct($version);
      $this->projectId = $projectId;
   }

   public function getVersionDate() {
      if (NULL == $this->versionDate) {
         $query = "SELECT date_order ".
                  "FROM `mantis_project_version_table` ".
                  "WHERE project_id = $this->projectId ".
                  "AND version = '$this->name';";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->versionDate = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : "(none)";

         if ($this->versionDate <= 1) { $this->versionDate = "(none)"; }
      }

      return $this->versionDate;
   }

}

?>

<?php /*
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
*/ ?>

<?php

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
	Logger::configure(dirname(__FILE__).'/../log4php.xml');
	$logger = Logger::getLogger("default");
	$logger->info("LOG activated !");
}


include_once "issue_selection.class.php";


// ===================================================
class ProjectVersion extends IssueSelection {

	public    $projectId;
	protected $versionDate; // mantis_project_version_table.date_order
	
	
	public function __construct($projectId, $version) {

		parent::__construct($version);
		
		$this->logger = Logger::getLogger(__CLASS__);
		
		$this->projectId = $projectId;
		

	}
	
	public function getVersionDate() {
		
		if (NULL == $this->versionDate) {
			$query = "SELECT date_order ".
					"FROM  `mantis_project_version_table` ".
					"WHERE  project_id = $this->projectId ".
					"AND    version    = '$this->name' ";
			
			$result = mysql_query($query);
			if (!$result) {
				$this->logger->error("Query FAILED: $query");
				$this->logger->error(mysql_error());
				echo "<span style='color:red'>ERROR: Query FAILED</span>";
				exit;
			}
			
			$this->versionDate = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "(none)";
			
			if ($this->versionDate <= 1) { $this->versionDate = "(none)"; }
		}
		
		return $this->versionDate;
	}

}

?>
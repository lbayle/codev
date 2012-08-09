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

include_once('classes/config.class.php');

// CoDevTT project started on: 17 May 2010

/*
* The Variables in here are not expected to be changed in any way.
* most of them are initialyzed from the 'codev_config_table'.
*/
class InternalConfig {

   public static $codevVersion = "v0.99.17 (29 Jun 2012)";

   public static $codevVersionHistory = array(
      "v0.01.0"  => "(17 May 2010) - CodevTT project creation",
      "v0.99.0" => "(09 Sept 2010) - team management complete",
      "v0.99.1" => "(28 Sept 2010) - jobs management",
      "v0.99.2" => "(08 Dec  2010) - Project Management",
      "v0.99.3" => "(03 Jan  2011) - fix new year problems",
      "v0.99.4" => "(13 Jan  2011) - ConsistencyCheck",
      "v0.99.5" => "(21 Jan  2011) - Update directory structure & Apache config",
      "v0.99.6" => "(16 Feb  2011) - i18n (internationalization)",
      "v0.99.7" => "(25 Feb  2011) - Graph & Statistics",
      "v0.99.8" => "(25 Mar  2011) - Add Job and specificities for 'support' + createTeam enhancements",
      "v0.99.9" => "(11 Apr  2011) - Planning + enhance global performances",
      "v0.99.10" => "(28 May  2011) - Install Procedure (unpolished)",
      "v0.99.11" => "(16 Jun  2011) - Replace ETA with Preliminary Est. Effort",
      "v0.99.12" => "(25 Aug  2011) - bugfix release & Install Procedure (unpolished)",
      "v0.99.13" => "(27 Oct  2011) - GANTT chart + ExternalTasksProject",
      "v0.99.14" => "(2 Feb  2012) - JQuery,Log4php, ForecastingReport, uninstall",
      "v0.99.15" => "(28 Feb  2012) - MgrEffortEstim, install, timetrackingFilters",
      "v0.99.16" => "(11 Apr  2012) - Smarty+Ajax, install, ProjectInfo, Https, Sessions, Doxygen, Observers view all pages, greasemonkey, ConsistencyChecks",
      "v0.99.17" => "(29 Jun  2012) - Smarty+Ajax, install, Management section, datatables, GUI enhancements, 'Leave' task moved to ExternalTasks, ConsistencyChecks"
   );

   public static $default_timetrackingFilters = "onlyAssignedTo:0,hideResolved:0,hideDevProjects:0";

}

// TODO Move to Download
// used by tools/download.php
$_POST['codevReportsDir'] = Config::getInstance()->getValue(Config::id_codevReportsDir);

?>

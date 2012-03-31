<?php
# WARNING: Never ever put an 'echo' in this file, the graph won't be displayed !

/*
    This file is part of CoDevTT.

    CoDevTT is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

# WARN: this avoids the display of some PHP errors...
#error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

# ================
# NOTE: header.inc.php is not loaded, so some config must be done.
error_reporting(0); // no logs displayed in page (page is a generated image)
date_default_timezone_set('Europe/Paris');

require_once '../path.inc.php';
require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("gantt_graph");
   //$logger->debug("LOG activated !");
}

include_once "tools.php";
#include_once "mysql_connect.inc.php";
#include_once "internal_config.inc.php";
#include_once "constants.php";
# ================

require_once ('jpgraph.php');
require_once ('jpgraph_gantt.php');

require_once ('gantt_manager.class.php');

# ###########################"
$teamid = isset($_GET['teamid']) ? $_GET['teamid'] : 2;
$startT = isset($_GET['startT']) ? $_GET['startT'] : date2timestamp("2011-08-01");
$endT = isset($_GET['endT']) ? $_GET['endT'] : date2timestamp("2011-12-30");
$projectid = isset($_GET['projects']) ? $_GET['projects'] : 0;
if(0 != $projects) {
   $projectList = explode(':', $projects);
   $logger->debug("team <$teamid> projects = <$projects>");
} else {
   $logger->debug("team <$teamid> display all projects");
   $projectList = NULL;
}

$gantManager = new GanttManager($teamid, $startT, $endT);
if (NULL != $projectList) {
   $gantManager->setProjectFilter($projectList);
}
$graph = $gantManager->getGanttGraph();

// INFO: the following 1 line are MANDATORY and fix the following error:
// “The image <name> cannot be displayed because it contains errors”
ob_end_clean();

// display graph
$graph->Stroke();

?>

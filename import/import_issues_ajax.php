<?php

include_once('../include/session.inc.php');
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

require '../path.inc.php';

require('super_header.inc.php');

require_once "project_cache.class.php";
require_once "team_cache.class.php";
#require_once "../management/command_tools.php";

$logger = Logger::getLogger("import_issues_ajax");

/*
 * NOTE: this file provides the content of the comboboxes displayed
 * when clicking on a datatable field.
 *
 */


$teamid    = isset($_GET['teamid'])    ? $_GET['teamid'] : '0';
$projectid = isset($_GET['projectid']) ? $_GET['projectid'] : '0';
$column    = isset($_GET['column'])    ? $_GET['column'] : 'unknown_column';


if ('command' == $column) {


   $team = TeamCache::getInstance()->getTeam($teamid);
   $cmdList = $team->getCommands();
   
   $commands = array();
   foreach ($cmdList as $id => $cmd) {
      $commands[$id] = $cmd->getName();
   }

   if (isset($_GET['selected'])) {
      $commands['selected'] = $_GET['selected'];
   }

   print json_encode($commands);
}

if ('category' == $column) {

   $projectid = isset($_GET['projectid']) ? $_GET['projectid'] : '0';
   $prj = ProjectCache::getInstance()->getProject($projectid);

   $categories = $prj->getCategories();

   if (isset($_GET['selected'])) {
      $categories['selected'] = $_GET['selected'];
   }
   print json_encode($categories);
}

if ('targetVersion' == $column) {

   $projectid = isset($_GET['projectid']) ? $_GET['projectid'] : '0';
   $prj = ProjectCache::getInstance()->getProject($projectid);

   $versions = $prj->getProjectVersions();

   if (isset($_GET['selected'])) {
      $versions['selected'] = $_GET['selected'];
   }

   print json_encode($versions);
}

if ('userName' == $column) {

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'userName 1';
   $array['2'] = 'userName 2';
   $array['3'] = 'userName 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   print json_encode($array);
}

?>
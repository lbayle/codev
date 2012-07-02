<?php

require_once('../include/session.inc.php');
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

require_once ('../path.inc.php');

require_once ('super_header.inc.php');

require_once ('user_cache.class.php');


$logger = Logger::getLogger("import_row_ajax");


if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);


   $action = isset($_POST['action']) ? $_POST['action'] : '';


   if ("importRow" == $action) {

      $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : '0';

      $extRef = isset($_POST['extRef']) ? $_POST['extRef'] : NULL;
      $summary = isset($_POST['summary']) ? $_POST['summary'] : NULL;
      $mgrEffortEstim = isset($_POST['mgrEffortEstim']) ? $_POST['mgrEffortEstim'] : NULL;
      $effortEstim = isset($_POST['effortEstim']) ? $_POST['effortEstim'] : NULL;
      $commandName = isset($_POST['command']) ? $_POST['command'] : NULL;
      $categoryName = isset($_POST['category']) ? $_POST['category'] : NULL;
      $targetVersion = isset($_POST['targetVersion']) ? $_POST['targetVersion'] : NULL;
      $userName = isset($_POST['userName']) ? $_POST['userName'] : NULL;


      $logger->debug("Import $extRef - $summary - $mgrEffortEstim - $effortEstim - $commandName - $categoryName - $targetVersion - $userName");

      $categoryid = Project::getCategoryId($categoryName);
      $logger->debug("Import category $categoryName (id = $categoryid)");

      $commandid = Command::getCommandId($commandName);
      $logger->debug("Import command $commandName (id = $commandid)");

      $userid = User::getUserId($userName);
      $logger->debug("Import user $userName (id = $userid)");



   }
}

?>
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

      $extRef = isset($_POST['extRef']) ? $_POST['extRef'] : '0';
      $summary = isset($_POST['summary']) ? $_POST['summary'] : '0';
      $mgrEffortEstim = isset($_POST['mgrEffortEstim']) ? $_POST['mgrEffortEstim'] : '0';
      $effortEstim = isset($_POST['effortEstim']) ? $_POST['effortEstim'] : '0';
      $command = isset($_POST['command']) ? $_POST['command'] : '0';
      $category = isset($_POST['category']) ? $_POST['category'] : '0';
      $targetVersion = isset($_POST['targetVersion']) ? $_POST['targetVersion'] : '0';
      $userName = isset($_POST['userName']) ? $_POST['userName'] : '0';


      $logger->debug("Import $extRef - $summary - $mgrEffortEstim - $effortEstim - $command - $category - $targetVersion - $userName");
   }
}

?>
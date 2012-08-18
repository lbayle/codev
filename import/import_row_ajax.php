<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

$logger = Logger::getLogger("import_row_ajax");

// ================ MAIN =================
if (isset($_SESSION['userid'])) {

   $action = Tools::getSecurePOSTStringValue('action', '');

   if ("importRow" == $action) {

      $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : '0';

      $extRef = isset($_POST['extRef']) ? $_POST['extRef'] : NULL;
      $summary = isset($_POST['summary']) ? $_POST['summary'] : NULL;
      $mgrEffortEstim = isset($_POST['mgrEffortEstim']) ? $_POST['mgrEffortEstim'] : NULL;
      $effortEstim = isset($_POST['effortEstim']) ? $_POST['effortEstim'] : NULL;
      $commandid = isset($_POST['commandid']) ? $_POST['commandid'] : NULL;
      $categoryid = isset($_POST['categoryid']) ? $_POST['categoryid'] : NULL;
      $targetversionid = isset($_POST['targetversionid']) ? $_POST['targetversionid'] : NULL;
      $userid = isset($_POST['userid']) ? $_POST['userid'] : NULL;
      $description = isset($_POST['description']) ? $_POST['description'] : NULL;
      $formatedDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : NULL;

      $proj = ProjectCache::getInstance()->getProject($projectid);
      $bugid = $proj->addIssue($categoryid, $summary, $description, Constants::$status_new);

      $issue = IssueCache::getInstance()->getIssue($bugid);

      if ($extRef)          { $issue->setExternalRef($extRef); }
      if ($mgrEffortEstim)  { $issue->setMgrEffortEstim($mgrEffortEstim); }
      if ($effortEstim)     { $issue->setEffortEstim($effortEstim); }
      if ($targetversionid) { $issue->setTargetVersion($targetversionid); }
      if ($userid)          { $issue->setHandler($userid); }
      if ($formatedDeadline) {
         $timestamp = Tools::date2timestamp($formatedDeadline);
         $issue->setDeadline($timestamp);
      }

      if ($commandid) {
         $command = CommandCache::getInstance()->getCommand($commandid);
         $command->addIssue($bugid, true); // DBonly
      }

      $logger->debug("Import bugid=$bugid $extRef - $summary - $mgrEffortEstim - $effortEstim - $commandid - $categoryid - $targetversionid - $userid");

      // RETURN VALUE
      echo Tools::mantisIssueURL($bugid, NULL, TRUE)." ".Tools::issueInfoURL($bugid, NULL);
   }
}

?>

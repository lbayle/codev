<?php

include_once('../include/session.inc.php');

/*
  This file is part of CodevTT.

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

require('super_header.inc.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";

include_once "smarty_tools.php";

include "engagement_tools.php";

$logger = Logger::getLogger("engagement_edit");


// your functions here
// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Engagement (edition)'));

if (isset($_SESSION['userid'])) {

   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if (isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if (isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }
   $_SESSION['teamid'] = $teamid;

   // use the engid set in the form, if not defined (first page call) use session engid
   $engagementid = 0;
   if(isset($_POST['engid'])) {
      $engagementid = $_POST['engid'];
   } else if(isset($_SESSION['engid'])) {
      $engagementid = $_SESSION['engid'];
   }
   $_SESSION['engid'] = $engagementid;


   $action = isset($_POST['action']) ? $_POST['action'] : '';

   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));
   $smartyHelper->assign('engagementid', $engagementid);


   // ------ Display Engagement

   if (0 != $engagementid) {

      $eng = new Engagement($engagementid);

      $smartyHelper->assign('engid', $engagementid);
      $smartyHelper->assign('engName', $eng->getName());
      $smartyHelper->assign('engDesc', $eng->getDesc());
      $smartyHelper->assign('engBudjetDev', $eng->getBudjetDev());
      $smartyHelper->assign('engBudjetMngt', $eng->getBudjetMngt());
      $smartyHelper->assign('engBudjetGarantie', $eng->getBudjetGarantie());
      $smartyHelper->assign('engBudjetTotal', $eng->getBudjetDev() + $eng->getBudjetMngt() + $eng->getBudjetGarantie());
      $smartyHelper->assign('engStartDate', date("Y-m-d", $eng->getStartDate()));
      $smartyHelper->assign('engDeadline', date("Y-m-d", $eng->getDeadline()));
      $smartyHelper->assign('engAverageDailyRate', $eng->getAverageDailyRate());

      // set Eng Details
      $engIssueSel = $eng->getIssueSelection();
      $smartyHelper->assign('engNbIssues', $engIssueSel->getNbIssues());


      // set EngagementList (for selected the team)
      $issueList = getEngagementIssues($eng);
      $smartyHelper->assign('engIssues', $issueList);

      // ---------------

      if ("addEngIssue" == $action) {
         $bugid = $_POST['bugid'];
         $logger->debug("add Issue $bugid on Engagement $engagementid team $teamid<br>");

         $eng = new Engagement($engagementid);
         $eng->addIssue($bugid);
      } else if ("updateEng == $action") {

         # TODO
      }
   }







}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>

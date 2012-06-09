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

/**
 *
 * @param Engagement $eng 
 */
function updateEngInfo($eng) {

   // security check
   $formattedValue = mysql_real_escape_string($_POST['engName']);
   $eng->setName($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['engDesc']);
   $eng->setDesc($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['engStartDate']);
   $eng->setStartDate(date2timestamp($formattedValue));

   $formattedValue = mysql_real_escape_string($_POST['engDeadline']);
   $eng->setDeadline(date2timestamp($formattedValue));


   $eng->setState(checkNumericValue($_POST['engState'], true));
   $eng->setBudjetDev(checkNumericValue($_POST['engBudjetDev'], true));
   $eng->setBudjetMngt(checkNumericValue($_POST['engBudjetMngt'], true));
   $eng->setBudjetGarantie(checkNumericValue($_POST['engBudjetGarantie'], true));
   $eng->setAverageDailyRate(checkNumericValue($_POST['engAverageDailyRate'], true));


}

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

   // TODO check if $teamid is set and != 0


   // use the engid set in the form, if not defined (first page call) use session engid
   $engagementid = 0;
   if(isset($_POST['engid'])) {
      $engagementid = $_POST['engid'];
   } else if(isset($_GET['engid'])) {
      $engagementid = $_GET['engid'];
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


   if (0 == $engagementid) {

      // -------- CREATE ENG -------

      // ------ Actions
      if ("createEng" == $action) {
         $bugid = $_POST['bugid'];
         $logger->debug("create new Engagement for team $teamid<br>");

         $engName = mysql_real_escape_string($_POST['engName']);

         $engagementid = Engagement::create($engName, $teamid);
         $smartyHelper->assign('engagementid', $engagementid);

         $eng = EngagementCache::getInstance()->getEngagement($engagementid);

         // set all fields
         updateEngInfo($eng);

      }
      // ------ Display Engagement
      // Note: this will be overridden by the 'update' section if the 'createEng' action has been called.
      $smartyHelper->assign('engInfoFormBtText', 'Create');
      $smartyHelper->assign('engInfoFormAction', 'createEng');
   }


   if (0 != $engagementid) {
      // -------- UPDATE ENG -------

      $eng = EngagementCache::getInstance()->getEngagement($engagementid);

      // ------ Actions

      if ("addEngIssue" == $action) {
         $bugid = $_POST['bugid'];
         $logger->debug("add Issue $bugid on Engagement $engagementid team $teamid<br>");

         $eng->addIssue($bugid);

      } else if ("updateEngInfo" == $action) {

         updateEngInfo($eng);

      } else if ("removeEngIssue" == $action) {

         $eng->removeIssue($_POST['bugid']);
      }

      // ------ Display Engagement
      $smartyHelper->assign('engInfoFormBtText', 'Save');
      $smartyHelper->assign('engInfoFormAction', 'updateEngInfo');

      displayEngagement($smartyHelper, $eng);

   }



   







}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>

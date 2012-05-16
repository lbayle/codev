<?php
include_once('../include/session.inc.php');

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

require('../path.inc.php');

require('super_header.inc.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "engagement.class.php";
#include_once "time_tracking.class.php";

#include_once "smarty_tools.php";

$logger = Logger::getLogger("engagement_info");






/**
 *
 */
function getIssueList($engagement) {

   $issueArray = array();

   $issues = $engagement->getIssueSelection()->getIssueList();
   foreach ($issues as $id => $issue) {

      $issueInfo = array();
      $issueInfo["bugid"] = $issue->bugId;
      $issueInfo["project"] = $issue->getProjectName();
      $issueInfo["target"] = $issue->getTargetVersion();
      $issueInfo["status"] = $issue->getCurrentStatusName();
      $issueInfo["progress"] = round(100 * $issue->getProgress());
      $issueInfo["elapsed"] = $issue->elapsed;
      $issueInfo["driftMgr"] = $issue->getDriftMgrEE();
      $issueInfo["durationMgr"] = $issue->getDurationMgr();
      $issueInfo["summary"] = $issue->summary;

      $issueArray[$id] = $issueInfo;
   }
   return $issueArray;
}


// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Engagement'));
$smartyHelper->assign('menu2', "menu/management_menu.html");

if (isset($_SESSION['userid'])) {


   $engagementid = 1;
   $eng = new Engagement($engagementid);

   $issueList = getIssueList($eng);

   $smartyHelper->assign('issueList', $issueList);



}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);


?>

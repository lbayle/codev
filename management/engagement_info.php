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
include_once('consistency_check2.class.php');

#include_once "time_tracking.class.php";


include_once "smarty_tools.php";

include "engagement_tools.php";

$logger = Logger::getLogger("engagement_info");


function getEngagements($teamid, $selectedEngId) {

   $engagements = array();
if (0 != $teamid) {

   $team = TeamCache::getInstance()->getTeam($teamid);
   $engList = $team->getEngagements();

   foreach ($engList as $id => $eng) {
      $engagements[] = array(
         'id' => $id,
         'name' => $eng->getName(),
         'selected' => ($id == $selectedEngId)
      );
   }
}

   return $engagements;


}


/**
 * Get consistency errors
 * @param Engagement $eng
 */
function getConsistencyErrors($eng) {
   global $statusNames;

   $consistencyErrors = array(); // if null, array_merge fails !

   $issueSel = $eng->getIssueSelection();
   $issueList = $issueSel->getIssueList();
    $ccheck = new ConsistencyCheck2($issueList);

    $cerrList = $ccheck->check();

    if (count($cerrList) > 0) {
        $i = 0;
        foreach ($cerrList as $cerr) {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            $consistencyErrors[] = array('issueURL' => issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
                'status' => $statusNames[$cerr->status],
                'desc' => $cerr->desc);
        }
        $i++;
    }

    return $consistencyErrors;
}





// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Engagement'));

if (isset($_SESSION['userid'])) {


   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if(isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if(isset($_SESSION['teamid'])) {
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


   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));

   $smartyHelper->assign('engagementid', $engagementid);
   $smartyHelper->assign('engagements', getEngagements($teamid, $engagementid));

   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------ Display Engagement

   if (0 != $engagementid) {

      $eng = EngagementCache::getInstance()->getEngagement($engagementid);

      displayEngagement($smartyHelper, $eng);


      // ConsistencyCheck
      $consistencyErrors = getConsistencyErrors($eng);

      if(count($consistencyErrors) > 0) {
         $smartyHelper->assign('consistencyErrorsTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('consistencyErrors', $consistencyErrors);
      }

   }



}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);


?>

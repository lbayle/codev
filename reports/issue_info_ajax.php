<?php
require('../include/session.inc.php');

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

if(isset($_SESSION['userid']) && (isset($_GET['action']) || isset($_POST['action']))) {

   require('../path.inc.php');
   require('super_header.inc.php');
   require('../smarty_tools.php');
   require('issue_info_tools.php');

   if(isset($_GET['action'])) {
      require('display.inc.php');

      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'getGeneralInfo') {
         include_once('issue_cache.class.php');
         require_once('user_cache.class.php');

         $issue = IssueCache::getInstance()->getIssue(getSecureGETIntValue('bugid'));
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $managedTeamList = $user->getManagedTeamList();
         $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList) : array();
         $isManager = (array_key_exists($issue->projectId, $managedProjList)) ? true : false;
         $smartyHelper->assign('issueGeneralInfo', getIssueGeneralInfo($issue, $isManager));
         $smartyHelper->display('ajax/generalInfo');
      }
      else {
         sendNotFoundAccess();
      }
   }
}
else {
   sendUnauthorizedAccess();
}

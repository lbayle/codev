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
#require_once ('display.inc.php');

/* INSERT INCLUDES HERE */
require_once ('user_cache.class.php');
require_once ('issue_cache.class.php');
require_once ('issue_selection.class.php');

require_once ('days_per_job_indicator.class.php');




/* INSERT FUNCTIONS HERE */


// ================ MAIN =================

$logger = Logger::getLogger("indicators");

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);




   $issueList = $session_user->getAssignedIssues(NULL, true);

   $issueSel = new IssueSelection("Assigned Issues");
   $issueSel->addIssueList($issueList);


   $daysPerJobIndicator = new DaysPerJobIndicator();

   echo 'Testing '.$daysPerJobIndicator->getName().'<br>';

   $daysPerJobIndicator->execute($issueSel);
}

?>
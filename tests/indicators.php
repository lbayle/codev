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

require_once ('include/super_header.inc.php');

function testDaysPerJobIndicator($session_user) {
/*
   $issueList = $session_user->getAssignedIssues(NULL, true);
   $issueSel = new IssueSelection("Assigned Issues");
   $issueSel->addIssueList($issueList);
*/

/*
   $proj = ProjectCache::getInstance()->getProject(14);
   $issueSel = $proj->getIssueSelection();
*/
   $proj = ProjectCache::getInstance()->getProject(14);
   $issueSel = new IssueSelection("Project $proj->name user ".$session_user->getName());
   $issueList = $proj->getIssues($session_user->id);
   $issueSel->addIssueList($issueList);


   $daysPerJobIndicator = new DaysPerJobIndicator();

   echo 'Testing '.$daysPerJobIndicator->getName().'<br>';

   $daysPerJob = $daysPerJobIndicator->execute($issueSel);

   $jobs = new Jobs();
   $totalElapsed = 0;
   echo "<table>";
   echo "<caption>".$issueSel->name."</caption>";
   echo "<tr><th>name</th><th>nbDays</th><th>color</th><tr>";
   foreach ($daysPerJob as $id => $duration) {
      echo "<tr>";
      echo '<td>'.$jobs->getJobName($id).'</td>';
      echo '<td>'.$duration.'</td>';
      echo '<td>'.$jobs->getJobColor($id).'</td>';
      echo "</tr>";
      $totalElapsed += $duration;
   }
   echo "</table>";
   echo "totalElapsed = $totalElapsed<br>";

}



// ================ MAIN =================

$logger = Logger::getLogger("indicators");

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);

   testDaysPerJobIndicator($session_user);


   $issue = IssueCache::getInstance()->getIssue(69);

   $date = "2012-01-16 23:59:59";
   $timestamp = Tools::datetime2timestamp($date);
   echo "Issue 69 date $date RAF = ".$issue->getBacklog($timestamp).'<br><br>';

   $date = "2012-01-17 23:59:59";
   $timestamp = Tools::datetime2timestamp($date);
   echo "Issue 69 date $date RAF = ".$issue->getBacklog($timestamp).'<br><br>';

   $date = "2012-06-21 23:59:59";
   $timestamp = Tools::datetime2timestamp($date);
   echo "Issue 69 date $date RAF = ".$issue->getBacklog($timestamp).'<br><br>';

   $date = "2012-06-25 23:59:59";
   $timestamp = Tools::datetime2timestamp($date);
   echo "Issue 69 date $date RAF = ".$issue->getBacklog($timestamp).'<br><br>';

   $date = "2012-07-31 23:59:59";
   $timestamp = Tools::datetime2timestamp($date);
   echo "Issue 69 date $date RAF = ".$issue->getBacklog($timestamp).'<br><br>';

}

?>
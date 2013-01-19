<?php
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

class ExportCsvTools {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @param int $teamid
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $myFile
    * @return string
    */
   public static function exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile) {
      $sepChar=';';

      $fh = fopen($myFile, 'w');

      // write header
      // WARNING i18n: translations with HTML chars (&eacute;) include ';' which is the CSV separation char !
      $stringData = T_("Project").$sepChar.
         T_("m_id").$sepChar.
         T_("ExtRef").$sepChar.
         T_("Summary").$sepChar.
         T_("Status").$sepChar.
         T_("Submitted").$sepChar.
         T_("Start date").$sepChar.
         T_("DeadLine").$sepChar.
         T_("Product Version").$sepChar.
         T_("Priority").$sepChar.
         "Category".$sepChar.
         T_("Resolution").$sepChar.
         T_("MgrEffortEstim").$sepChar.
         T_("BI").$sepChar.
         T_("BS").$sepChar.
         "Elapsed".$sepChar.
         T_("BL").$sepChar.
         T_("Progress").$sepChar.
         T_("Delivery Date").$sepChar.
         T_("Delivery Sheet").$sepChar.
         T_("Assigned to").$sepChar.
         "\n";
      fwrite($fh, $stringData);

      $projList = TeamCache::getInstance()->getTeam($teamid)->getProjects();
      $formatedProjList = implode( ', ', array_keys($projList));

      // Note: if you filter on TeamMembers, you won't have issues temporarily affected to other teams
      //$memberList = Team::getMemberList($teamid);
      //$formatedMemberList = implode( ', ', array_keys($memberList));

      // for all issues with status !=  {resolved, closed}

      $query = "SELECT * FROM `mantis_bug_table` ".
         "WHERE status < get_project_resolved_status_threshold(project_id) ".
         "AND project_id IN ($formatedProjList) ".
         //"AND handler_id IN ($formatedMemberList) ".
         "ORDER BY id DESC";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $user = UserCache::getInstance()->getUser($issue->getHandlerId());

         $deadLine = "";
         if (NULL != $issue->getDeadLine()) {
            $deadLine = date("d/m/Y", $issue->getDeadLine());
         }
         $deliveryDate = "";
         if (NULL != $issue->getDeliveryDate()) {
            $deliveryDate = date("d/m/Y", $issue->getDeliveryDate());
         }

         // remove sepChar from summary text
         $formatedSummary = str_replace($sepChar, " ", $issue->getSummary());

         $startDate="";
         if (NULL != ($d = $issue->startDate())) {
            $startDate = date("d/m/Y", $d);
         }

         // write data
         $stringData = $issue->getProjectName().$sepChar.
            $issue->getId().$sepChar.
            $issue->getTcId().$sepChar.
            $formatedSummary.$sepChar.
            $issue->getCurrentStatusName().$sepChar.
            date("d/m/Y", $issue->getDateSubmission()).$sepChar.
            $startDate.$sepChar.
            $deadLine.$sepChar.
            $issue->getVersion().$sepChar.
            $issue->getPriorityName().$sepChar.
            $issue->getCategoryName().$sepChar.
            $issue->getResolutionName().$sepChar.
            $issue->getMgrEffortEstim().$sepChar.
            $issue->getEffortEstim().$sepChar.
            $issue->getEffortAdd().$sepChar.
            $issue->getElapsed().$sepChar.
            $issue->getBacklog().$sepChar.
            round(100 * $issue->getProgress())."%".$sepChar.
            $deliveryDate.$sepChar.
            $issue->getDeliveryId().$sepChar.
            $user->getShortname().
            "\n";
         fwrite($fh, $stringData);

      }

      // Add resolved issues modified into the period
      $query = "SELECT * FROM `mantis_bug_table` ".
         "WHERE status >= get_project_resolved_status_threshold(project_id) ".
         "AND project_id IN ($formatedProjList) ".
         //"AND handler_id IN ($formatedMemberList) ".
         "AND last_updated > $startTimestamp ".
         "AND last_updated < $endTimestamp ".
         "ORDER BY id DESC";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $user = UserCache::getInstance()->getUser($issue->getHandlerId());

         $deliveryDate = "";
         if (NULL != $issue->getDeliveryDate()) {
            $deliveryDate = date("d/m/Y", $issue->getDeliveryDate());
         }

         // remove sepChar from summary text
         $formatedSummary = str_replace($sepChar, " ", $issue->getSummary());

         $startDate="";
         if (NULL != ($d = $issue->startDate())) {
            $startDate = date("d/m/Y", $d);
         }

         // write data
         $stringData = $issue->getProjectName().$sepChar.
            $issue->getId().$sepChar.
            $issue->getTcId().$sepChar.
            $formatedSummary.$sepChar.
            $issue->getCurrentStatusName().$sepChar.
            date("d/m/Y", $issue->getDateSubmission()).$sepChar.
            $startDate.$sepChar.
            $deadLine.$sepChar.
            $issue->getVersion().$sepChar.
            $issue->getPriorityName().$sepChar.
            $issue->getCategoryName().$sepChar.
            $issue->getResolutionName().$sepChar.
            $issue->getMgrEffortEstim().$sepChar.
            $issue->getEffortEstim().$sepChar.
            $issue->getEffortAdd().$sepChar.
            $issue->getElapsed().$sepChar.
            $issue->getBacklog().$sepChar.
            round(100 * $issue->getProgress())."%".$sepChar.
            $deliveryDate.$sepChar.
            $issue->getDeliveryId().$sepChar.
            $user->getShortname().
            "\n";
         fwrite($fh, $stringData);
      }

      fclose($fh);
      return $myFile;
   }

   /**
    * format: nom;prenom;trigramme;date de debut;date de fin;nb jours
    * format date: "jj/mm/aa"
    * @param int $month
    * @param int $year
    * @param int $teamid
    * @param string $teamName
    * @param string $path
    * @return string
    */
   public static function exportHolidaystoCSV($month, $year, $teamid, $teamName, $path="") {
      $sepChar=';';

      $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
      $nbDaysInMonth = date("t", $monthTimestamp);
      $startT = mktime(0, 0, 0, $month, 1, $year);
      $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

      // create filename & open file
      $myFile = $path.DIRECTORY_SEPARATOR.$teamName."_Holidays_".Tools::formatdate("%Y%m", $monthTimestamp).".csv";
      $fh = fopen($myFile, 'w');

      $team = TeamCache::getInstance()->getTeam($teamid);
      foreach($team->getMembers() as $userid => $username) {
         $user1 = UserCache::getInstance()->getUser($userid);

         // if user was working on the project within the timestamp
         if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
            ($user1->isTeamManager($teamid, $startT, $endT))) {

            
            $timeTracks = $user1->getTimeTracks($startT, $endT);
            $issueIds = array();
            foreach ($timeTracks as $timeTrack) {
               $issueIds[] = $timeTrack->getIssueId();
            }
            
            $daysOf = $user1->getDaysOfInPeriod($timeTracks, $issueIds);

            // concatenate days
            $startBlockTimestamp = 0;
            $endBlockTimestamp = 0;
            $blockSize = 0;

            $evtTimestamp = NULL;
            for ($i = 1; $i <= $nbDaysInMonth; $i++) {
               if (array_key_exists($evtTimestamp,$daysOf)) {

                  $evtTimestamp = mktime(0, 0, 0, $month, $i, $year);

                  if (1 == $daysOf["$evtTimestamp"]) {
                     // do not write, concatenate evt to block
                     if (0 == $startBlockTimestamp) {$startBlockTimestamp = $evtTimestamp; }
                     $blockSize += 1;
                     $endBlockTimestamp = $evtTimestamp;

                  } else {
                     // write previous block if exist
                     if (0 != $blockSize) {
                        $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                           date("d/m/y", $startBlockTimestamp).$sepChar.
                           date("d/m/y", $endBlockTimestamp).$sepChar.
                           $blockSize."\n";
                        fwrite($fh, $stringData);
                        $startBlockTimestamp = 0;
                        $endBlockTimestamp = 0;
                        $blockSize = 0;
                     }

                     // write current line ( < 1)
                     $evtDate      = date("d/m/y", $evtTimestamp);
                     $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                        $evtDate.$sepChar.
                        $evtDate.$sepChar.
                        $daysOf["$evtTimestamp"]."\n";
                     fwrite($fh, $stringData);
                  }


               } else {
                  // write previous block if exist
                  if (0 != $blockSize) {
                     $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                        date("d/m/y", $startBlockTimestamp).$sepChar.
                        date("d/m/y", $endBlockTimestamp).$sepChar.
                        $blockSize."\n";
                     fwrite($fh, $stringData);
                     $startBlockTimestamp = 0;
                     $endBlockTimestamp = 0;
                     $blockSize = 0;
                  }

               }
            }
            if (0 != $blockSize) {
               $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                  date("d/m/y", $startBlockTimestamp).$sepChar.
                  date("d/m/y", $endBlockTimestamp).$sepChar.
                  $blockSize."\n";
               fwrite($fh, $stringData);
               $startBlockTimestamp = 0;
               $endBlockTimestamp = 0;
               $blockSize = 0;
            }
         }
      }

      fclose($fh);
      return $myFile;
   }

}

// Initialize complex static variables
ExportCsvTools::staticInit();

?>

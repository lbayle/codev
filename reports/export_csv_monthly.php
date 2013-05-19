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

require('../path.inc.php');

class ExportCSVMonthlyController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {

      if (!is_dir(Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports')) {
         mkdir(Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports', 0755);
      }

   }

   protected function display() {
      if(Tools::isConnectedUser()) {

         if (0 != $this->teamid) {

            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $formatedteamName = str_replace(" ", "_", $team->getName());

            // dates
            $month = date('m');
            $year = date('Y');

            // The first day of the current month
            $startdate = Tools::getSecurePOSTStringValue("startdate", Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year)));
            $this->smartyHelper->assign('startDate', $startdate);
            $startTimestamp = Tools::date2timestamp($startdate);

            // The current date plus one year
            $nbDaysInMonth  = date("t", mktime(0, 0, 0, $month, 1, $year));
            $enddate = Tools::getSecurePOSTStringValue("enddate", Tools::formatDate("%Y-%m-%d", mktime(23, 59, 59, $month, $nbDaysInMonth, $year)));
            $this->smartyHelper->assign('endDate', $enddate);
            $endTimestamp = Tools::date2timestamp($enddate);
            $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

            if ('computeCsvMonthly' == $_POST['action']) {
               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

               $myFile = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$formatedteamName."_Mantis_".date("Ymd").".csv";

               ExportCsvTools::exportManagedIssuesToCSV($this->teamid, $startTimestamp, $endTimestamp, $myFile);
               $this->smartyHelper->assign('managedIssuesToCSV', basename($myFile));

               $myFile = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$formatedteamName."_Projects_".date("Ymd", $timeTracking->getStartTimestamp())."-".date("Ymd", $timeTracking->getEndTimestamp()).".csv";

               $this->exportProjectMonthlyActivityToCSV($timeTracking, $myFile);
               $this->smartyHelper->assign('projectMonthlyActivityToCSV', basename($myFile));

               // reduce scope to enhance speed
               $reports = array();
               for ($i = 1; $i <= 12; $i++) {
                  $reports[] = basename(ExportCsvTools::exportHolidaystoCSV($i, $year, $this->teamid, $formatedteamName,  Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'));
               }
               $this->smartyHelper->assign('reports', $reports);

               $this->smartyHelper->assign('reportsDir', Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports');
            }
         }
      }
   }

   /**
    * creates for each project a table with the following fields:
    * id | TC | startDate | endDate | status | total elapsed | elapsed + Backlog | elapsed in period | Backlog
    * TOTAL
    * @param TimeTracking $timeTracking
    * @param string $myFile
    * @return string
    */
   public static function exportProjectMonthlyActivityToCSV(TimeTracking $timeTracking, $myFile) {
      $sepChar=';';


      $fh = fopen($myFile, 'w');

      // returns : $projectTracks[projectid][bugid][jobid] = duration
      $projectTracks = $timeTracking->getProjectTracks();

      foreach ($projectTracks as $projectId => $bugList) {
         $totalEffortEstim = 0;
         $totalElapsed = 0;
         $totalBacklog = 0;
         $totalElapsedPeriod = 0;

         // write table header
         $project = ProjectCache::getInstance()->getProject($projectId);
         $stringData = $project->getName()."\n";

         // WARNING i18n: HTML translation like french accents (eacute;) add an unwanted column sepChar (;)
         $stringData .=("ID").$sepChar;
         $stringData .=("Task").$sepChar;
         $stringData .=("Ext.ID").$sepChar;
         $stringData .=("Start date").$sepChar;
         $stringData .=("End date").$sepChar;
         $stringData .=("Status").$sepChar;
         $stringData .=("Total EffortEstim").$sepChar;
         $stringData .=("Total elapsed").$sepChar;
         $stringData .=("elapsed + Backlog").$sepChar;
         $stringData .=("elapsed in period").$sepChar;
         $stringData .=("BL").$sepChar;
         $stringData .="\n";

         // write table content (by bugid)
         foreach ($bugList as $bugid => $jobs) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            // remove sepChar from summary text
            $formatedSummary = str_replace($sepChar, " ", $issue->getSummary());

            $stringData .= $bugid.$sepChar;
            $stringData .= $formatedSummary.$sepChar;
            $stringData .= $issue->getTcId().$sepChar;
            $stringData .= date("d/m/Y", $issue->startDate()).$sepChar;
            $stringData .= date("d/m/Y", $issue->endDate()).$sepChar;
            $stringData .= $issue->getCurrentStatusName().$sepChar;
            $stringData .= ($issue->getEffortEstim() + $issue->getEffortAdd()).$sepChar;
            $stringData .= $issue->getElapsed().$sepChar;
            $stringData .= ($issue->getElapsed() + $issue->getBacklog()).$sepChar;

            // sum all job durations
            $elapsedInPeriod = 0;
            foreach($jobs as $jobId => $duration) {
               $elapsedInPeriod += $duration;
            }
            $stringData .= $elapsedInPeriod.$sepChar;

            $stringData .= $issue->getBacklog().$sepChar;
            $stringData .="\n";

            $totalEffortEstim += $issue->getEffortEstim() + $issue->getEffortAdd();
            $totalElapsed += $issue->getElapsed();
            $totalBacklog += $issue->getBacklog();
            $totalElapsedPeriod += $elapsedInPeriod;
         }

         // total per project
         $stringData .= ("TOTAL").$sepChar.$sepChar.$sepChar.$sepChar.$sepChar.$sepChar;
         $stringData .= $totalEffortEstim.$sepChar;
         $stringData .= $totalElapsed.$sepChar;
         $stringData .= ($totalElapsed + $totalBacklog).$sepChar;
         $stringData .= $totalElapsedPeriod.$sepChar;
         $stringData .= $totalBacklog.$sepChar;
         $stringData .= "\n";

         $stringData .="\n";
         fwrite($fh, $stringData);
      }
      fclose($fh);
      return $myFile;
   }

}

// ========== MAIN ===========
ExportCSVMonthlyController::staticInit();
$controller = new ExportCSVMonthlyController('../', 'CSV Report','ImportExport');
$controller->execute();

?>

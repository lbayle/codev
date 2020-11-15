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

class EditJobsController extends Controller {

   private static $logger;
   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
       self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {
         // Admins only
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {

            $sql = AdodbWrapper::getInstance();
            $this->smartyHelper->assign('jobType', Job::$typeNames);

            // set random color
            $rndJobColor = sprintf('%06X', mt_rand(0, 0xFFFFFF));
            $this->smartyHelper->assign('rndJobColor', $rndJobColor);

            $action = Tools::getSecurePOSTStringValue('action', 'none');

            if ('addJob' == $action) {
               $job_name = Tools::getSecurePOSTStringValue('job_name');
               $job_type = Tools::getSecurePOSTStringValue('job_type');
               $job_color = Tools::getSecurePOSTStringValue('job_color');

               // TODO check if not already in table !

               //Check for a hex color string without hash 'c1c2b4'
               $job_color_trim = trim(str_replace("#", "", $job_color));
               if(preg_match('/^[a-f0-9]{6}$/i', $job_color_trim)) {
                  // save to DB
                  Jobs::create($job_name, $job_type, $job_color_trim);
               } else {
                  $this->smartyHelper->assign('error', T_("Invalid Color : '$job_color' ($job_color_trim)"));
               }


            } else if ('editJob' == $action) {
               $job_id = Tools::getSecurePOSTIntValue('jobId');
               $job_name = Tools::getSecurePOSTStringValue('jobName');
               $job_type = Tools::getSecurePOSTIntValue('jobType');
               $job_color = Tools::getSecurePOSTStringValue('jobColor');

               if (Jobs::JOB_NA != $job_id) {
                  //Check for a hex color string without hash 'c1c2b4'
                  $job_color_trim = trim(str_replace("#", "", $job_color));
                  if(preg_match('/^[a-f0-9]{6}$/i', $job_color_trim)) {
                     try {
                        Jobs::updateJob($job_id, $job_name, $job_type, $job_color_trim);
                     } catch (Exception $ex) {
                        $this->smartyHelper->assign('error', T_("Failed to update job : $job_name, $job_type, $job_color_trim"));
                     }
                  } else {
                     $this->smartyHelper->assign('error', T_("Invalid Color : '$job_color' ($job_color_trim)"));
                  }
               } else {
                  $this->smartyHelper->assign('error', T_("Sorry, this Job cannot be changed"));
               }
            } elseif ('deleteJob' == $action) {
               $job_id = Tools::getSecurePOSTIntValue('job_id');

               if ((Jobs::JOB_NA == $job_id) || (Jobs::JOB_SUPPORT == $job_id)) {
                  $this->smartyHelper->assign('error', T_("This job must not be deleted."));

               } else {
                  $query = "DELETE FROM codev_project_job_table WHERE job_id = ".$sql->db_param();
                  try {
                     $sql->sql_query($query, array($job_id));
                  } catch (Exception $e) {
                     $this->smartyHelper->assign('error', T_("Couldn't remove the job association"));
                  }

                  $query = "DELETE FROM codev_job_table WHERE id = ".$sql->db_param();
                  try {
                     $sql->sql_query($query, array($job_id));
                  } catch (Exception $e) {
                     $this->smartyHelper->assign('error', T_("Couldn't delete the job"));
                  }
               }
            }

            $jobs = $this->getJobs();
            $this->smartyHelper->assign('jobs', $jobs);
         }
      }
   }

   /**
    * Get job tuples
    * @return mixed[int] The jobs
    */
   private function getJobs() {
      $jobs = new Jobs();
      $jobList = $jobs->getJobs();
      Tools::usort($jobList);
      $smartyJobs = array();
      $jobsWithoutSupport = array();
      foreach($jobList as $job) {
         $smartyJobs[$job->getId()] = array(
            "name" => $job->getName(),
            "type" => $job->getType(),
            "typeName" => Job::$typeNames[$job->getType()],
            "color" => $job->getColor(),
         );

         if ((Jobs::JOB_SUPPORT != $job->getId()) &&
             (Jobs::JOB_NA != $job->getId())) {
            $jobsWithoutSupport[] = $job->getId();
            $smartyJobs[$job->getId()]["allowDeleteJob"] = true;
         }
         if (Jobs::JOB_NA != $job->getId()) {
            $smartyJobs[$job->getId()]["allowEditJob"] = true;
         }
      }

      $formattedJobs = implode(", ",$jobsWithoutSupport);
      // if job already used for TimeTracking, delete forbidden
      $sql = AdodbWrapper::getInstance();
      $query2 = "SELECT jobid, COUNT(jobid) as count ".
         "FROM codev_timetracking_table ".
         " WHERE jobid IN (".$formattedJobs.") GROUP BY jobid";
      try {
         $result2 = $sql->sql_query($query2);
      } catch (Exception $e) {
         return NULL;
      }
      while($row = $sql->fetchObject($result2)) {
         $smartyJobs[$row->jobid]["allowDeleteJob"] = (0 == $row->count);
      }

      return $smartyJobs;
   }
}

// ========== MAIN ===========
EditJobsController::staticInit();
$controller = new EditJobsController('../', 'CodevTT Administration : Jobs Edition','Admin');
$controller->execute();



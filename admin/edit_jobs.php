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


            } elseif ('addAssociationProject' == $action) {

               // Add Job to selected projects
               $job_id = Tools::getSecurePOSTIntValue('job_id');
               $proj = explode(",",Tools::getSecurePOSTStringValue('formattedProjects'));
               foreach($proj as $project_id) {
                  // TODO check if not already in table !

                  // save to DB
                  $query = "INSERT INTO `codev_project_job_table`  (`project_id`, `job_id`) VALUES ('".$project_id."','".$job_id."');";
                  $result = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result) {
                     $this->smartyHelper->assign('error', T_("Couldn't add the job association"));
                  }
               }
               
            } elseif ('deleteJob' == $action) {
               $job_id = Tools::getSecurePOSTIntValue('job_id');

               if ((Jobs::JOB_NA == $job_id) || (Jobs::JOB_SUPPORT == $job_id)) {
                  $this->smartyHelper->assign('error', T_("This job must not be deleted."));

               } else {
                  $query = "DELETE FROM `codev_project_job_table` WHERE job_id = ".$job_id.';';
                  $result = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result) {
                     $this->smartyHelper->assign('error', T_("Couldn't remove the job association"));
                  }

                  $query = "DELETE FROM `codev_job_table` WHERE id = $job_id;";
                  $result = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result) {
                     $this->smartyHelper->assign('error', T_("Couldn't delete the job"));
                  }
               }

            } elseif ('deleteJobProjectAssociation' == $action) {
               $asso_id = Tools::getSecurePOSTIntValue('asso_id');

               $query = "DELETE FROM `codev_project_job_table` WHERE id = ".$asso_id.';';
               $result = SqlWrapper::getInstance()->sql_query($query);
               if (!$result) {
                  $this->smartyHelper->assign('error', T_("Couldn't remove the job association"));
               }
            }

            $jobs = $this->getJobs();
            $this->smartyHelper->assign('jobs', $jobs);

            //$this->smartyHelper->assign('assignedJobs', $this->getAssignedJobs($jobs));
            $this->smartyHelper->assign('assignedJobs', $jobs);

            $projects = Project::getProjects();
            $this->smartyHelper->assign('projects', $projects);
            $this->smartyHelper->assign('tuples', $this->getAssignedJobTuples($projects));
         }
      }
   }

   /**
    * Get assigned jobs
    * @param array $jobs All jobs
    * @return string[int] The assigned jobs
    */
   private function getAssignedJobs(array $jobs) {
      $assignedJobs = array();
      foreach($jobs as $id => $value) {
         if($value['type'] == Job::type_assignedJob) {
            $assignedJobs[$id] = $value['name'];
         }
      }
      return $assignedJobs;
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
            $smartyJobs[$job->getId()]["deletedJob"] = true;
         }
      }

      $formattedJobs = implode(", ",$jobsWithoutSupport);
      // if job already used for TimeTracking, delete forbidden
      $query2 = "SELECT jobid, COUNT(jobid) as count ".
         "FROM `codev_timetracking_table` ".
         "WHERE jobid IN ($formattedJobs) GROUP BY jobid;";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         return NULL;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result2)) {
         $smartyJobs[$row->jobid]["deletedJob"] = (0 == $row->count);
      }

      return $smartyJobs;
   }

   /**
    * Get assigned jobs
    * @param array $plist The projects
    * @return mixed[int] The assigned jobs
    */
   private function getAssignedJobTuples(array $plist) {
      $query = "SELECT job.id as job_id, job.name AS job_name, project_job.id, project_job.project_id ".
         "FROM `codev_job_table` as job ".
         "JOIN `codev_project_job_table` as project_job ON job.id = project_job.job_id ".
         "ORDER BY project_job.project_id;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $projects = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // if SuiviOp do not allow tu delete
         $desc = $row->job_name." - ".$plist[$row->project_id];
         $desc = str_replace("'", "\'", $desc);
         $desc = str_replace('"', "\'", $desc);

         $projects[$row->id] = array(
            "desc" => $desc,
            "jobid" => $row->job_id,
            "jobname" => $row->job_name,
            "projectid" => $row->project_id,
            "project" => $plist[$row->project_id]
         );
      }

      return $projects;
   }

}

// ========== MAIN ===========
EditJobsController::staticInit();
$controller = new EditJobsController('../', 'CodevTT Administration : Jobs Edition','Admin');
$controller->execute();

?>

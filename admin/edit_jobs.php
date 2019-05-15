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


            } elseif ('addAssociationProject' == $action) {

               // Add Job to selected projects
               $project_id = Tools::getSecurePOSTIntValue('project');
               $jobs = explode(",",Tools::getSecurePOSTStringValue('formattedJobs'));
               foreach($jobs as $job_id) {
                  // TODO check if not already in table !

                  // save to DB
                  $query = "INSERT INTO codev_project_job_table  (project_id, job_id)".
                           " VALUES (".$sql->db_param().",".$sql->db_param().")";
                  try {
                     $sql->sql_query($query, array($project_id, $job_id));
                  } catch (Exception $e) {
                     $this->smartyHelper->assign('error', T_("Couldn't add the job association"));
                  }
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

            } elseif ('deleteJobProjectAssociation' == $action) {
               $asso_id = Tools::getSecurePOSTIntValue('asso_id');

               $query = "DELETE FROM codev_project_job_table WHERE id = ".$sql->db_param();
               try {
                  $result = $sql->sql_query($query, array($asso_id));
               } catch (Exception $e) {
                  $this->smartyHelper->assign('error', T_("Couldn't remove the job association"));
               }
            }

            $jobs = $this->getJobs();
            $this->smartyHelper->assign('jobs', $jobs);

            //$this->smartyHelper->assign('assignedJobs', $this->getAssignedJobs($jobs));
            $this->smartyHelper->assign('assignedJobs', $jobs);

            $projects = Project::getProjects();
            $this->smartyHelper->assign('jobAssignations', $this->getAssignedJobTuples($projects, $this->teamid));
            unset($projects[Config::getInstance()->getValue(Config::id_externalTasksProject)]);
            $this->smartyHelper->assign('projects', $projects);
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
         $smartyJobs[$row->jobid]["deletedJob"] = (0 == $row->count);
      }

      return $smartyJobs;
   }

   /**
    * Get assigned jobs
    * @param array $plist The projects
    * @return mixed[int] The assigned jobs
    */
   private function getAssignedJobTuples(array $plist, $teamid) {
      
      $team = TeamCache::getInstance()->getTeam($teamid);
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT job.id as job_id, job.name AS job_name, project_job.id, project_job.project_id ".
         "FROM codev_job_table as job ".
         "JOIN codev_project_job_table as project_job ON job.id = project_job.job_id ".
         " ORDER BY project_job.project_id;";
      $result = $sql->sql_query($query);

      $projects = array();
      while($row = $sql->fetchObject($result)) {
         // if SuiviOp do not allow tu delete
         $desc = $row->job_name." - ".$plist[$row->project_id];
         $desc = str_replace("'", "\'", $desc);
         $desc = str_replace('"', "\'", $desc);

         // NA for sideTasks & externalTasks project are not remobable
         $isRemovable =  (Config::getInstance()->getValue(Config::id_externalTasksProject) != $row->project_id) && 
                             (!((Jobs::JOB_NA == $row->job_id) && ($team->isSideTasksProject($row->project_id))));
         
         $projects[$row->id] = array(
            "desc" => $desc,
            "jobid" => $row->job_id,
            "jobname" => $row->job_name,
            "projectid" => $row->project_id,
            "project" => $plist[$row->project_id],
            "isRemovable" => $isRemovable
         );
      }

      return $projects;
   }

}

// ========== MAIN ===========
EditJobsController::staticInit();
$controller = new EditJobsController('../', 'CodevTT Administration : Jobs Edition','Admin');
$controller->execute();



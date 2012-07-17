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

require('include/super_header.inc.php');

require('classes/smarty_helper.class.php');

include_once('classes/jobs.class.php');
include_once('classes/project.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/user_cache.class.php');

/**
 * Get assigned jobs
 * @param array $jobs All jobs
 * @return array string[int] The assigned jobs
 */
function getAssignedJobs(array $jobs) {
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
function getJobs() {
   $jobs = new Jobs();
   $jobList = $jobs->getJobs();
   Tools::usort($jobList);
   $smartyJobs = array();
   $jobSupport = Config::getInstance()->getValue(Config::id_jobSupport);
   $jobsWithoutSupport = array();
   foreach($jobList as $job) {
      $smartyJobs[$job->id] = array(
         "name" => $job->name,
         "type" => $job->type,
         "typeName" => Job::$typeNames[$job->type],
         "color" => $job->color,
      );

      if($jobSupport != $job->id) {
         $jobsWithoutSupport[] = $job->id;
         $smartyJobs[$job->id]["deletedJob"] = true;
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
function getAssignedJobTuples(array $plist) {
   $query = "SELECT codev_project_job_table.id, codev_project_job_table.project_id, codev_project_job_table.job_id, codev_job_table.name AS job_name ".
      "FROM `codev_project_job_table`, `codev_job_table` ".
      "WHERE codev_project_job_table.job_id = codev_job_table.id ".
      "ORDER BY codev_project_job_table.project_id";
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

// ========== MAIN ===========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CoDev Administration : Jobs Edition');

if(isset($_SESSION['userid'])) {
   // Admins only
   global $admin_teamid;
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   if ($session_user->isTeamMember($admin_teamid)) {
      $smartyHelper->assign('jobType', Job::$typeNames);

      if (isset($_POST['job_name'])) {
         $job_name = Tools::getSecurePOSTStringValue('job_name');
         $job_type = Tools::getSecurePOSTStringValue('job_type');
         $job_color = Tools::getSecurePOSTStringValue('job_color');

         // TODO check if not already in table !

         // save to DB
         Jobs::create($job_name, $job_type, $job_color);
      } elseif (isset($_POST['projects'])) {
         $job_id = Tools::getSecurePOSTIntValue('job_id');

         // Add Job to selected projects
         if(isset($_POST['formattedProjects'])) {
            $proj = explode(",",Tools::getSecurePOSTStringValue('formattedProjects'));
            foreach($proj as $project_id){
               // TODO check if not already in table !
               // save to DB
               $query = "INSERT INTO `codev_project_job_table`  (`project_id`, `job_id`) VALUES ('".$project_id."','".$job_id."');";
               $result = SqlWrapper::getInstance()->sql_query($query);
               if (!$result) {
                  $smartyHelper->assign('error', "Couldn't add the job association");
               }
            }
         }
      } elseif (isset($_POST['job_id'])) {
         $job_id = Tools::getSecurePOSTIntValue('job_id');

         // TODO delete Support job not allowed

         $query = "DELETE FROM `codev_project_job_table` WHERE job_id = ".$job_id.';';
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            $smartyHelper->assign('error', "Couldn't remove the job association");
         }

         $query = "DELETE FROM `codev_job_table` WHERE id = $job_id;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            $smartyHelper->assign('error', "Couldn't delete the job");
         }
      } elseif (isset($_POST['asso_id'])) {
         $asso_id = Tools::getSecurePOSTIntValue('asso_id');

         $query = "DELETE FROM `codev_project_job_table` WHERE id = ".$asso_id.';';
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            $smartyHelper->assign('error', "Couldn't remove the job association");
         }
      }

      $jobs = getJobs();
      $smartyHelper->assign('jobs', $jobs);
      $smartyHelper->assign('assignedJobs', getAssignedJobs($jobs));

      $projects = Project::getProjects();
      $smartyHelper->assign('projects', $projects);
      $smartyHelper->assign('tuples', getAssignedJobTuples($projects));
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

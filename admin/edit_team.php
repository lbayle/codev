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

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

include_once('classes/config.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/project.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/team.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

/**
 * Get team members
 * @param int $teamid
 * @return mixed[string]
 */
function getTeamMembers($teamid) {
   $query = "SELECT codev_team_user_table.id, codev_team_user_table.user_id, codev_team_user_table.team_id, codev_team_user_table.access_level, ".
      "codev_team_user_table.arrival_date, codev_team_user_table.departure_date, mantis_user_table.username, mantis_user_table.realname ".
      "FROM `codev_team_user_table`, `mantis_user_table` ".
      "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
      "AND codev_team_user_table.team_id=$teamid ".
      "ORDER BY mantis_user_table.username;";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }
   $teamMemberTuples = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $teamMemberTuples[$row->id] = array(
         "username" => $row->username,
         "userid" => $row->user_id,
         "realname" => $row->realname,
         "arrivaldate" => date("Y-m-d", $row->arrival_date),
         "accessLevel" => Team::$accessLevelNames[$row->access_level]
      );

      if (0 != $row->departure_date) {
         $teamMemberTuples[$row->id]["departuredate"] = date("Y-m-d", $row->departure_date);
      }
   }
   return $teamMemberTuples;
}

/**
 * Get team projects
 * @param int $teamid
 * @return mixed[string]
 */
function getTeamProjects($teamid) {
   global $externalTasksProject;

   $query = "SELECT codev_team_project_table.id, codev_team_project_table.type, ".
      "mantis_project_table.id AS project_id, mantis_project_table.name, mantis_project_table.enabled, ".
      "mantis_project_table.description ".
      "FROM `codev_team_project_table`, `mantis_project_table` ".
      "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
      "AND codev_team_project_table.team_id=$teamid ".
      "ORDER BY mantis_project_table.name";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }
   $teamProjectsTuple = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $teamProjectsTuple[$row->id] = array(
         "name" => $row->name,
         "enabled" => (1 == $row->enabled) ? T_('enabled') : T_('disabled'),
         "projectid" => $row->project_id,
         "typeNames" => Project::$typeNames[$row->type],
         "description" => $row->description
      );

      // if ExternalTasksProject do not allow to delete
      if ($externalTasksProject != $row->project_id) {
         $teamProjectsTuple[$row->id]["delete"] = 'ok';
      }
   }

   return $teamProjectsTuple;
}

/**
 * Get new astreintes
 * @param Team $team The team
 * @param array $projList The projects
 * @return string[int]
 */
function getNewAstreintes(Team $team, array $projList) {
   // get SideTasksProject Inactivity Issues

   if ((NULL == $projList) || (0 == count($projList))) {
      // TODO $logger->warn("no project defined for this team, OnDuty tasks are defined in SideTasksProjects");
      return NULL;
   }

   $inactivityCatList = array();
   foreach ($projList as $pid => $pname) {
      if ($team->isSideTasksProject($pid)) {
         $p = ProjectCache::getInstance()->getProject($pid);
         $inactivityCatList[$pid] = $p->getInactivityCategoryId();
      }
   }

   if (0 == count($inactivityCatList)) {
      // TODO $logger->warn("no inactivity category defined for SideTasksProjects => no OnDuty tasks ");
      return NULL;
   }

   $formatedInactivityCatList = implode( ', ', array_keys($inactivityCatList));

   $query = "SELECT * FROM `mantis_bug_table` ".
            "WHERE project_id IN ($formatedInactivityCatList) ";

   $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);
   if (NULL != $astreintesList) {
      $formatedAstreintesList = implode( ', ', $astreintesList);
      $query .= "AND id NOT IN ($formatedAstreintesList) ";
   }
   $query .= "ORDER BY id";

   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $issues = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      #echo "DEBUG $row->id cat $row->category_id inac[$row->project_id] = ".$inactivityCatList[$row->project_id]."</br>";
      if ($row->category_id == $inactivityCatList[$row->project_id]) {
         $issues[$row->id] = IssueCache::getInstance()->getIssue($row->id)->summary;
      }
   }

   return $issues;
}

/**
 * Get astreintes
 * @return mixed[int]
 */
function getAstreintes() {
   $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);

   if (NULL == $astreintesList) return NULL;

   $astreintes = array();
   foreach ($astreintesList as $bugid) {
      $issue = IssueCache::getInstance()->getIssue($bugid);

      $deleteDesc = $issue->summary;
      $deleteDesc = str_replace("'", "\'", $deleteDesc);
      $deleteDesc = str_replace('"', "\'", $deleteDesc);

      $astreintes[$bugid] = array(
         "desc" => $deleteDesc,
         "description" => $issue->summary,
      );
   }

   return $astreintes;
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CoDev Administration : Team Edition');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   global $admin_teamid;
   $teamList = NULL;
   // leadedTeams only, except Admins: they can edit all teams
   if ($session_user->isTeamMember($admin_teamid)) {
      $teamList = Team::getTeams();
   } else {
      $teamList = $session_user->getLeadedTeamList();
   }

   if(count($teamList) > 0) {
      if (isset($_POST['deletedteam'])) {
         $teamidToDelete = Tools::getSecurePOSTIntValue("deletedteam");
         if(array_key_exists($teamidToDelete,$teamList)) {
            $query = "DELETE FROM `codev_team_project_table` WHERE team_id = $teamidToDelete;";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               $smartyHelper->assign('error', "Couldn't delete the team");
            }

            $query = "DELETE FROM `codev_team_user_table` WHERE team_id = $teamidToDelete;";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               $smartyHelper->assign('error', "Couldn't delete the team");
            }

            $query = "DELETE FROM `codev_team_table` WHERE id = $teamidToDelete;";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               $smartyHelper->assign('error', "Couldn't delete the team");
            }

            unset($_SESSION['teamid']);
            unset($teamList[$teamidToDelete]);
         }
      }

      // use the teamid set in the form, if not defined (first page call) use session teamid
      if (isset($_GET['teamid'])) {
         $teamid = Tools::getSecureGETIntValue('teamid');
      } else if(isset($_SESSION['teamid'])) {
         $teamid = $_SESSION['teamid'];
      } else {
         $teamIds = array_keys($teamList);
         if(count($teamIds) > 0) {
            $teamid = $teamIds[0];
         } else {
            $teamid = 0;
         }
      }

      $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

      if(array_key_exists($teamid,$teamList)) {
         $_SESSION['teamid'] = $teamid;

         $team = TeamCache::getInstance()->getTeam($teamid);

         // ----------- actions ----------
         $action = isset($_POST['action']) ? $_POST['action'] : '';
         if ($action == "updateTeamLeader") {
            $teamleader_id = Tools::getSecurePOSTIntValue('leaderid');
            if (!$team->setLeader($teamleader_id)) {
               $smartyHelper->assign('error', "Couldn't update the team leader");
            } else {
               // --- add teamLeader as Mantis manager of the SideTaskProject
               //$leader = UserCache::getInstance()->getUser($teamleader_id);
               //$access_level = 70; // TODO mantis manager
               //$leader->setProjectAccessLevel($stproj_id, $access_level);
            }
         } elseif ($action == "updateTeamCreationDate") {
            $formatedDate = Tools::getSecurePOSTStringValue("date_createTeam");
            $date_create = Tools::date2timestamp($formatedDate);
            if(!$team->setCreationDate($date_create)) {
               $smartyHelper->assign('error', "Couldn't update the creation date");
            }
         } elseif ($action == "addTeamMember") {
            $memberid = Tools::getSecurePOSTIntValue('memberid');
            $memberAccess = Tools::getSecurePOSTIntValue('member_access');
            $formatedDate = Tools::getSecurePOSTStringValue("date1");
            $arrivalTimestamp = Tools::date2timestamp($formatedDate);
            try { 
               // save to DB
               $team->addMember($memberid, $arrivalTimestamp, $memberAccess);

               // CodevTT administrators can manage ExternalTasksProject in Mantis
               if ($admin_teamid == $team->id) {
                  $newUser = UserCache::getInstance()->getUser($memberid);
                  $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
                  $access_level = 70; // TODO mantis manager
                  $newUser->setProjectAccessLevel($extProjId, $access_level);
               }
            } catch (Exception $e) {
               $smartyHelper->assign('error', "Couldn't add user $memberid to the team");
            }

         } elseif ($action == "setMemberDepartureDate") {
            $formatedDate = Tools::getSecurePOSTStringValue("date2");
            $departureTimestamp = Tools::date2timestamp($formatedDate);
            $memberid = Tools::getSecurePOSTIntValue('memberid');

            $team->setMemberDepartureDate($memberid, $departureTimestamp);
         } elseif ($action == "addMembersFrom") {
            $src_teamid = Tools::getSecurePOSTIntValue('f_src_teamid');

            // add all members declared in Team $src_teamid (same dates, same access)
            // except if already declared
            $team->addMembersFrom($src_teamid);
         } elseif (isset($_POST["deletememberid"])) {
            $memberid = Tools::getSecurePOSTIntValue('deletememberid');
            $query = "DELETE FROM `codev_team_user_table` WHERE id = $memberid;";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               $smartyHelper->assign('error', "Couldn't delete the member of the team");
            }
         } elseif (isset($_POST['addedprojectid'])) {
            $projectid = Tools::getSecurePOSTIntValue('addedprojectid');
            $projecttype= Tools::getSecurePOSTIntValue('project_type');

            try {
               // prepare Project to CoDev (associate with CoDev customFields if needed)
               $project = ProjectCache::getInstance()->getProject($projectid);
               $project->prepareProjectToCodev();

               // save to DB
               if(!$team->addProject($projectid, $projecttype)) {
                  $smartyHelper->assign('error', "Couldn't add the project to the team");
               }
            } catch (Exception $e) {
               $smartyHelper->assign('error', "Couldn't add project $projectid  to the team");
            }
         } elseif (isset($_POST['deletedprojectid'])) {
            $projectid = Tools::getSecurePOSTIntValue('deletedprojectid');
            $query = "DELETE FROM `codev_team_project_table` WHERE id = ".$projectid.';';
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               $smartyHelper->assign('error', "Couldn't remove the project of the team");
            }
         } elseif (isset($_POST['addedastreinte_id'])) {
            $astreinte_id = Tools::getSecurePOSTIntValue('addedastreinte_id');
            $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);
            if (NULL == $astreintesList) {
               $formatedList = "$astreinte_id";
            } else {
               $formatedList  = implode( ',', $astreintesList);
               $formatedList .= ",$astreinte_id";
            }
            Config::getInstance()->setValue(Config::id_astreintesTaskList, $formatedList, Config::configType_array);
         } elseif (isset($_POST['deletedastreinte_id'])) {
            $astreinte_id = Tools::getSecurePOSTIntValue('deletedastreinte_id');
            $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);
            if (NULL != $astreintesList) {
               if (1 == count($astreintesList)) {
                  #Config::getInstance()->deleteValue(Config::id_astreintesTaskList);
                  Config::getInstance()->setValue(Config::id_astreintesTaskList, "", Config::configType_array);
               } else {
                  $key = array_search($astreinte_id, $astreintesList);
                  unset($astreintesList[$key]);
                  $formatedList  = implode( ',', $astreintesList);
                  Config::getInstance()->setValue(Config::id_astreintesTaskList, $formatedList, Config::configType_array);
               }
            }
         }

         $smartyHelper->assign('team', $team);
         
         $smartyHelper->assign('users', SmartyTools::getSmartyArray(User::getUsers(),$team->leader_id));
         $smartyHelper->assign('date', date("Y-m-d", $team->date));

         $smartyHelper->assign('accessLevel', Team::$accessLevelNames);

         $smartyHelper->assign('departureDate', date("Y-m-d", time()));

         $smartyHelper->assign('teamMembers', getTeamMembers($teamid));

         $team = TeamCache::getInstance()->getTeam($teamid);

         $smartyHelper->assign('teamEnabled', $team->isEnabled());
         $smartyHelper->assign('otherProjects', $team->getOtherProjects());
         $smartyHelper->assign('typeNames', Project::$typeNames);

         $smartyHelper->assign('teamProjects', getTeamProjects($teamid));

         $smartyHelper->assign('newAstreintes', getNewAstreintes($team,$team->getProjects()));

         $smartyHelper->assign('astreintes', getAstreintes());
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>

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

class EditTeamController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamList = NULL;
         // leadedTeams only, except Admins: they can edit all teams
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $teamList = Team::getTeams();
         } else {
            $teamList = $session_user->getLeadedTeamList();
         }

         if(count($teamList) > 0) {
            if (isset($_POST['deletedteam'])) {
               $teamidToDelete = Tools::getSecurePOSTIntValue("deletedteam");
               if(array_key_exists($teamidToDelete,$teamList)) {

                  $retCode = Team::delete($teamidToDelete);
                  if (!$retCode) {
                     $this->smartyHelper->assign('error', T_("Couldn't delete the team"));
                  }

                  unset($_SESSION['teamid']);
                  unset($teamList[$teamidToDelete]);
               }
            }

            // use the teamid set in the form, if not defined (first page call) use session teamid
            if (isset($_GET['teamid'])) {
               $teamid = Tools::getSecureGETIntValue('teamid');
               if(array_key_exists($teamid,$teamList)) {
                  $_SESSION['teamid'] = $teamid;
               }
            } else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
               $teamid = $_SESSION['teamid'];
            } else {
               $teamIds = array_keys($teamList);
               if(count($teamIds) > 0) {
                  $teamid = $teamIds[0];
               } else {
                  $teamid = 0;
               }
            }

            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

            if(array_key_exists($teamid,$teamList)) {

               $team = TeamCache::getInstance()->getTeam($teamid);

               // ----------- actions ----------
               $action = isset($_POST['action']) ? $_POST['action'] : '';
               if ($action == "updateTeamLeader") {
                  $teamleader_id = Tools::getSecurePOSTIntValue('leaderid');
                  if (!$team->setLeader($teamleader_id)) {
                     $this->smartyHelper->assign('error', T_("Couldn't update the team leader"));
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
                     $this->smartyHelper->assign('error', T_("Couldn't update the creation date"));
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
                     if (Config::getInstance()->getValue(Config::id_adminTeamId) == $team->getId()) {
                        $newUser = UserCache::getInstance()->getUser($memberid);
                        $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
                        $access_level = 70; // TODO mantis manager
                        $newUser->setProjectAccessLevel($extProjId, $access_level);
                     }
                  } catch (Exception $e) {
                     $this->smartyHelper->assign('error', "Couldn't add user $memberid to the team");
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
                     $this->smartyHelper->assign('error', T_("Couldn't delete the member of the team"));
                  }
               } elseif (isset($_POST['addedprojectid'])) {
                  $projectid = Tools::getSecurePOSTIntValue('addedprojectid');
                  $projecttype= Tools::getSecurePOSTIntValue('project_type');

                  try {
                     // prepare Project to CoDev (associate with CoDev customFields if needed)
                     // WARN: Project constructor cannot be used in here.
                     Project::prepareProjectToCodev($projectid);

                     // save to DB
                     if(!$team->addProject($projectid, $projecttype)) {
                        $this->smartyHelper->assign('error', T_("Couldn't add the project to the team"));
                     }
                  } catch (Exception $e) {
                     $this->smartyHelper->assign('error', T_("Could NOT add project to the team"));
                  }
               } elseif (isset($_POST['deletedprojectid'])) {
                  $projectid = Tools::getSecurePOSTIntValue('deletedprojectid');
                  if(!$team->removeProject($projectid)) {
                     $this->smartyHelper->assign('error', T_("Could NOT remove the project from the team"));
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

               $this->smartyHelper->assign('team', $team);

               $this->smartyHelper->assign('users', SmartyTools::getSmartyArray(User::getUsers(),$team->getLeaderId()));
               $this->smartyHelper->assign('date', date("Y-m-d", $team->getDate()));

               $this->smartyHelper->assign('accessLevel', Team::$accessLevelNames);

               $this->smartyHelper->assign('departureDate', date("Y-m-d", time()));

               $this->smartyHelper->assign('teamMembers', $this->getTeamMembers($teamid));

               $this->smartyHelper->assign('teamEnabled', $team->isEnabled());
               $this->smartyHelper->assign('otherProjects', $team->getOtherProjects());
               $this->smartyHelper->assign('typeNames', Project::$typeNames);

               $this->smartyHelper->assign('teamProjects', $this->getTeamProjects($teamid));

               $this->smartyHelper->assign('newAstreintes', $this->getNewAstreintes($team,$team->getTrueProjects()));

               $this->smartyHelper->assign('astreintes', $this->getAstreintes());
            }
         }
      }
   }

   /**
    * Get team members
    * @param int $teamid
    * @return mixed[string]
    */
   private function getTeamMembers($teamid) {
      $query = "SELECT user.id as user_id, user.username, user.realname, ".
         "team_user.id, team_user.arrival_date, team_user.departure_date, team_user.team_id, team_user.access_level ".
         "FROM `mantis_user_table` as user ".
         "JOIN `codev_team_user_table` as team_user ON user.id = team_user.user_id ".
         "WHERE team_user.team_id=$teamid ".
         "ORDER BY user.username;";
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
   private function getTeamProjects($teamid) {
      $query = "SELECT project.id AS project_id, project.name, project.enabled, project.description, ".
         "team_project.id, team_project.type ".
         "FROM `mantis_project_table` as project ".
         "JOIN `codev_team_project_table` as team_project ON project.id = team_project.project_id ".
         "WHERE team_project.team_id=$teamid ".
         "ORDER BY project.name;";
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
         if (Config::getInstance()->getValue(Config::id_externalTasksProject) != $row->project_id) {
            $teamProjectsTuple[$row->id]["delete"] = 'ok';
         }
      }

      return $teamProjectsTuple;
   }

   /**
    * Get new astreintes
    * @param Team $team The team
    * @param Project[] $projList The projects
    * @return string[]
    */
   private function getNewAstreintes(Team $team, array $projList) {
      // get SideTasksProject Inactivity Issues

      if ((NULL == $projList) || (0 == count($projList))) {
         // TODO $logger->warn("no project defined for this team, OnDuty tasks are defined in SideTasksProjects");
         return NULL;
      }

      $inactivityCatList = array();
      foreach ($projList as $project) {
         if ($team->isSideTasksProject($project->getId())) {
            $inactivityCatList[$project->getId()] = $project->getCategory(Project::cat_st_inactivity);
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
            $issues[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row)->getSummary();
         }
      }

      return $issues;
   }

   /**
    * Get astreintes
    * @return mixed[int]
    */
   private function getAstreintes() {
      $astreintesList = Config::getInstance()->getValue(Config::id_astreintesTaskList);

      if (NULL == $astreintesList) return NULL;

      $issues = Issue::getIssues($astreintesList);

      $astreintes = array();
      foreach ($issues as $issue) {
         $deleteDesc = $issue->getSummary();
         $deleteDesc = str_replace("'", "\'", $deleteDesc);
         $deleteDesc = str_replace('"', "\'", $deleteDesc);

         $astreintes[$issue->getId()] = array(
            "desc" => $deleteDesc,
            "description" => $issue->getSummary(),
         );
      }

      return $astreintes;
   }

}

// ========== MAIN ===========
EditTeamController::staticInit();
$controller = new EditTeamController('CoDev Administration : Team Edition','Admin');
$controller->execute();

?>

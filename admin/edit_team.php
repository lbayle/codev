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
      if(Tools::isConnectedUser()) {

         $teamList = NULL;
         // leadedTeams only, except Admins: they can edit all teams
         if ($this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $teamList = Team::getTeams(true);
         } else {
            $teamList = $this->session_user->getLeadedTeamList(true);
         }

         if(count($teamList) > 0) {

            if (isset($_POST['deletedteam'])) {
               $teamidToDelete = Tools::getSecurePOSTIntValue("deletedteam");
               if(array_key_exists($teamidToDelete,$teamList)) {

                  $retCode = Team::delete($teamidToDelete);
                  if (!$retCode) {
                     $this->smartyHelper->assign('error', T_("Couldn't delete the team"));
                  } else {
                     if ($teamidToDelete == $_SESSION['teamid']) {
                        unset($_SESSION['teamid']);
                        $this->updateTeamSelector();
                     }
                     unset($teamList[$teamidToDelete]);
                  }
               }
            }

            // use the teamid set in the form, if not defined (first page call) use session teamid
            if (isset($_POST['displayed_teamid'])) {
               $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');

            } else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
               $displayed_teamid = $_SESSION['teamid'];

            } else {
               $teamIds = array_keys($teamList);
               if(count($teamIds) > 0) {
                  $displayed_teamid = $teamIds[0];
               } else {
                  $displayed_teamid = 0;
               }
            }

            $this->smartyHelper->assign('availableTeams', SmartyTools::getSmartyArray($teamList,$displayed_teamid));

            if(array_key_exists($displayed_teamid,$teamList)) {

               $team = TeamCache::getInstance()->getTeam($displayed_teamid);

               if ($displayed_teamid != Config::getInstance()->getValue(Config::id_adminTeamId)) {
                  $this->smartyHelper->assign('allowDeleteTeam', 1);
               }

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
               } elseif ($action == "setTeamEnabled") {
                  $isTeamEnabled = (0 == Tools::getSecurePOSTIntValue("isTeamEnabled")) ? false : true;
                  if(!$team->setEnabled($isTeamEnabled)) {
                     $this->smartyHelper->assign('error', T_("Couldn't enable/disable team"));
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
               } elseif ($action == 'addMembersFrom') {
                  $src_teamid = Tools::getSecurePOSTIntValue('f_src_teamid');

                  // add all members declared in Team $src_teamid (same dates, same access)
                  // except if already declared
                  $team->addMembersFrom($src_teamid);
               } elseif ($action == 'removeIssueTooltip') {
                  $projectid = Tools::getSecurePOSTIntValue('projectid');
                  $project = ProjectCache::getInstance()->getProject($projectid);
                  $project->setIssueTooltipFields(NULL, $displayed_teamid);

               } elseif ($action == 'setConsistencyCheck') {
                  $keyvalue = Tools::getSecurePOSTStringValue('checkItems');
                  $checkList = Tools::doubleExplode(':', ',', $keyvalue);
                  $team->setConsistencyCheckList($checkList);

               } elseif ($action == 'setGeneralPrefs') {
                  $keyvalue = Tools::getSecurePOSTStringValue('checkItems');
                  $checkList = Tools::doubleExplode(':', ',', $keyvalue);
                  $team->setGeneralPrefsList($checkList);

               } elseif ($action == 'createSideTaskProject') {
                  $stprojName = Tools::getSecurePOSTStringValue('stprojName');
                  $stproj_id = $team->createSideTaskProject($stprojName);
                  if ($stproj_id > 0) {
                     $stproj = ProjectCache::getInstance()->getProject($stproj_id);
                     
                     // add teamLeader as Mantis manager of the SideTaskProject
                     $leader = UserCache::getInstance()->getUser($team->getLeaderId());
                     $access_level = 70; // TODO mantis manager
                     $leader->setProjectAccessLevel($stproj_id, $access_level);

                     // add SideTaskProject Categories
                     $stproj->addCategoryProjManagement(T_("Project Management"));
                     $stproj->addCategoryInactivity(T_("Inactivity"));
                     $stproj->addCategoryIncident(T_("Incident"));
                     $stproj->addCategoryTools(T_("Tools"));
                     $stproj->addCategoryWorkshop(T_("Team Workshop"));
                  }
               } elseif (isset($_POST["deleteValue"])) {
               	  $duration = TimeTrackingTools::getDurationList($displayed_teamid);
               	  $duration_value = Tools::getSecurePOSTStringValue('deleteValue');
               	  unset($duration[$duration_value]);
               	  if (count($duration) == 0) {
               	  	  Config::deleteValue(Config::id_durationList, array(0, 0, $displayed_teamid, 0, 0, 0));
               	  } else {
               	  	  Config::setValue(Config::id_durationList, Tools::doubleImplode(":", ",", $duration), Config::configType_keyValue, NULL, 0, 0, $displayed_teamid); 
               	  }
               } elseif (isset($_POST["addValue"])){
               	  $duration = TimeTrackingTools::getDurationList($displayed_teamid);
               	  $duration_value = Tools::getSecurePOSTStringValue('addValue');
               	  $duration_display = Tools::getSecurePOSTStringValue('addDisplay');
               	  $duration[$duration_value] = $duration_display;
               	  Config::setValue(Config::id_durationList, Tools::doubleImplode(":", ",", $duration), Config::configType_keyValue, NULL, 0, 0, $displayed_teamid);          	
               } elseif (isset($_POST["updateValue"])) {
               	  $duration = TimeTrackingTools::getDurationList($displayed_teamid);
               	  $duration_value = Tools::getSecurePOSTStringValue('updateValue');
               	  $duration_display = Tools::getSecurePOSTStringValue('updateDisplay');
               	  $duration[$duration_value] = $duration_display;
               	  Config::setValue(Config::id_durationList, Tools::doubleImplode(":", ",", $duration), Config::configType_keyValue, NULL, 0, 0, $displayed_teamid); 
               } elseif (isset($_POST["deletememberid"])) {
                  $memberid = Tools::getSecurePOSTIntValue('deletememberid');
                  $query = "DELETE FROM `codev_team_user_table` WHERE id = $memberid;";
                  $result = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result) {
                     $this->smartyHelper->assign('error', T_("Couldn't delete the member of the team"));
                  }
               } elseif (isset($_POST['addedprojectid'])) {
                  $projectid = Tools::getSecurePOSTIntValue('addedprojectid');
                  if (0 != $projectid) {
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
                        $this->smartyHelper->assign('error', T_("Couldn't add the project to the team"));
                     }
                  }
               } elseif (isset($_POST['deletedprojectid'])) {
                  $projectid = Tools::getSecurePOSTIntValue('deletedprojectid');
                  if(!$team->removeProject($projectid)) {
                     $this->smartyHelper->assign('error', T_("Could NOT remove the project from the team"));
                  }
               } elseif (isset($_POST['addedastreinte_id'])) {
                  $onduty_id = Tools::getSecurePOSTIntValue('addedastreinte_id');
                  if (0 != $onduty_id) {
                     $team->addOnDutyTask($onduty_id);
                  }
               } elseif (isset($_POST['deletedastreinte_id'])) {
                  $onduty_id = Tools::getSecurePOSTIntValue('deletedastreinte_id');
                  $team->removeOnDutyTask($onduty_id);
               }
               $this->smartyHelper->assign('team', $team);

               $smartyUserList = array();
               $userList = User::getUsers();
               $selectedUserid = $team->getLeaderId();
               foreach ($userList as $id => $name) {
                  $u = UserCache::getInstance()->getUser($id);
                  $uname = $u->getRealname();
                  if (empty($uname)) { $uname = $name;}
                  $smartyUserList[$id] = array(
                     'id' => $id,
                     'name' => $uname,
                     'selected' => $id == $selectedUserid,
                  );
               }

               $this->smartyHelper->assign('users', $smartyUserList);
               $this->smartyHelper->assign('date', date("Y-m-d", $team->getDate()));

               $this->smartyHelper->assign('accessLevel', Team::$accessLevelNames);

               $this->smartyHelper->assign('arrivalDate', date("Y-m-d", time()));
               $this->smartyHelper->assign('departureDate', date("Y-m-d", time()));

               $this->smartyHelper->assign('teamMembers', $this->getTeamMembers($displayed_teamid));

               $this->smartyHelper->assign('teamEnabled', $team->isEnabled());
               $this->smartyHelper->assign('otherProjects', $team->getOtherProjects());
               $this->smartyHelper->assign('typeNames', Project::$typeNames);

               $this->smartyHelper->assign('teamProjects', $this->getTeamProjects($displayed_teamid));

               $this->smartyHelper->assign('onDutyCandidates', $this->getOnDutyCandidates($team,$team->getTrueProjects()));

               $this->smartyHelper->assign('onDutyTasks', $this->getOnDutyTasks($team));
               $this->smartyHelper->assign('duration', TimeTrackingTools::getDurationList($displayed_teamid));

               $projectList = $this->getTooltipProjectCandidates($team);
               $this->smartyHelper->assign('tooltipProjectCandidates', $projectList);
               $this->smartyHelper->assign('issueTooltips', $this->getIssueTooltips($projectList, $displayed_teamid));
               $this->smartyHelper->assign('itemSelection_openDialogBtLabel', T_('Configure Tooltips'));

               $consistencyChecks = $this->getConsistencyChecks($team);
               $this->smartyHelper->assign('consistencyChecks', $consistencyChecks);

               $teamGeneralPrefs = $this->getTeamGeneralPrefs($team);
               $this->smartyHelper->assign('teamGeneralPrefs', $teamGeneralPrefs);
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
            "type" => $row->type,
            "typeNames" => Project::$typeNames[$row->type],
            "description" => $row->description
         );

         // if ExternalTasksProject do not allow to delete
         if (Config::getInstance()->getValue(Config::id_externalTasksProject) != $row->project_id) {
            $teamProjectsTuple[$row->id]["delete"] = 'ok';
         }
      }

      // add jobList
      foreach ($teamProjectsTuple as $id => $info) {
         $p = ProjectCache::getInstance()->getProject($info['projectid']);
         $teamProjectsTuple[$id]['jobs'] = implode(', ', $p->getJobList($info['type']));
      }

      return $teamProjectsTuple;
   }

   /**
    * Get inactivity tasks that can be defined as OnDuty tasks
    * @param Team $team The team
    * @param Project[] $projList The projects
    * @return string[]
    */
   private function getOnDutyCandidates(Team $team, array $projList) {
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

      $astreintesList = $team->getOnDutyTasks();
      if (!empty($astreintesList)) {
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
   private function getOnDutyTasks(Team $team) {

      $bugidList = $team->getOnDutyTasks();
      if (is_null($bugidList) || empty($bugidList)) { return NULL; }

      $onDutyTasks = array();
      foreach ($bugidList as $bugid) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $desc = $issue->getSummary();
         $desc = str_replace("'", "\'", $desc);
         $desc = str_replace('"', "\'", $desc);

         $onDutyTasks[$issue->getId()] = array(
            "id" => $issue->getId(),
            "description" => $desc,
         );
      }
      return $onDutyTasks;
   }

   private function getTooltipProjectCandidates(Team $team) {
      $projects = $team->getProjects();
      $teamid = $team->getId();

      $candidatesList = array();

      foreach ($projects as $id => $name) {
         $project = ProjectCache::getInstance()->getProject($id);

         // do not display SideTasksProjects & ExternalTaskProject
         if ( $project->isExternalTasksProject() ||
              $project->isSideTasksProject(array($teamid))) {
            continue;
         }
         $candidatesList[$id] = $name;
      }
      return $candidatesList;
   }

   private function getIssueTooltips($projects, $teamid) {

      $issueTooltips = array();

      foreach ($projects as $id => $name) {
         $project = ProjectCache::getInstance()->getProject($id);

         // do not display projects having no specific tooltips
         $result = Config::getValue(Config::id_issueTooltipFields, array(0, $id, $teamid, 0, 0, 0), true);
         if ($result == NULL) {
         	continue;
         }
         
         $fields = $project->getIssueTooltipFields($teamid);

         $formattedFields = array();
         foreach ($fields as $f) {
            $formattedFields[] = Tools::getTooltipFieldDisplayName($f);
         }

         $strFields = implode(', ', $formattedFields);
         $issueTooltips[$id] = array(
            "projectId" => $id,
            "projectName" => $name,
            "tooltipFields" => $strFields
         );
      }
      return $issueTooltips;
   }

   private function getConsistencyChecks(Team $team) {

      // get
      $checkList = $team->getConsistencyCheckList();

      $consistencyChecks = array();
      foreach ($checkList as $name => $enabled) {

         $consistencyChecks["$name"] = array(
             'name'       => $name,
             'label'      => ConsistencyCheck2::$checkDescriptionList["$name"],
             'isChecked'  => $enabled,
             'isDisabled' => false
         );
      }
      return $consistencyChecks;
   }

   private function getTeamGeneralPrefs(Team $team) {

      // get
      $checkList = $team->getGeneralPrefsList();

      $generalPrefs = array();
      foreach ($checkList as $name => $enabled) {

         $generalPrefs["$name"] = array(
             'name'       => $name,
             'label'      => T_(Team::$generalPrefsDescriptionList["$name"]),
             'isChecked'  => $enabled,
             'isDisabled' => false
         );
      }
      return $generalPrefs;
   }

}

// ========== MAIN ===========
EditTeamController::staticInit();
$controller = new EditTeamController('../', 'Administration : Team Edition','Admin');
$controller->execute();

?>

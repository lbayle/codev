<?php

/**
 * requires MantisPlugin.class.php
 */
require_once( config_get('class_path') . 'MantisPlugin.class.php' );

require_once( dirname(dirname(__FILE__))."/CodevTT/classes/IssueMantisPluginHelper.php");

/**
 * CodevTTPlugin Class
 */
class CodevTTPlugin extends MantisPlugin {

   /**
    *  A method that populates the plugin information and minimum requirements.
    */
   public function register() {
      $this->name = plugin_lang_get('title');
      $this->description = plugin_lang_get('description');
      $this->page = '';

      $this->version = '0.7.2';

      $this->requires = array(
          'MantisCore' => '1.3'
      );

      $this->author = 'CodevTT';
      $this->contact = 'lbayle.work@gmail.com';
      $this->url = 'http://codevtt.org';
   }

  public function init() {
    require_once( 'classes/FilterCommandField.class.php' );
    require_once( 'classes/CommandColumn.class.php' );
    require_once( 'classes/ElapsedColumn.class.php' );
    require_api( 'logging_api.php' );
  }

   /**
    * Default plugin configuration.
    */
   public function hooks() {

      global $g_event_cache;

      //la liste des EVENT se trouve dans core/events_inc.php
      //la construction de l'affichage se fait dans core/html_api.php

      $hooks = array(

          // Report new issue page.
          'EVENT_REPORT_BUG_FORM' => 'report_bug_form',
          'EVENT_REPORT_BUG'      => 'report_bug',

          // Update issue page.
          'EVENT_UPDATE_BUG_FORM' => 'update_bug_form',
          'EVENT_UPDATE_BUG'      => 'update_bug',

          #Uncomment the following line to show codevtt in main menu
          //'EVENT_MENU_MAIN' => 'add_codevtt_menu',

          'EVENT_VIEW_BUG_DETAILS' => 'view_bug_form',

          // check BEFORE DELETE (but unfortunately after the 'are you sure?' page...)
          'EVENT_BUG_DELETED' => 'bug_delete',

          // add filter to the 'view bugs' page
          'EVENT_FILTER_FIELDS'  => 'filter_fields',

          // display 'Commands' column in 'view bugs' page
          'EVENT_FILTER_COLUMNS' => 'filter_columns'
      );

      # contributed to MantisBT 1.3
      #if( version_compare( MANTIS_VERSION, '1.3', '>') ) {
      if (!is_null($g_event_cache['EVENT_MANAGE_PROJECT_DELETE'])) {
         $hooks['EVENT_MANAGE_PROJECT_DELETE'] = 'projectDelete';
      }
      #}
      return $hooks;
   }

  function filter_fields($p_event) {
     $filters = array();
     $project_id=helper_get_current_project();

     // do not display command filter if user < DEVELOPER
     if (!$this->isAccessGranted($project_id)) {
        log_event(LOG_FILTERING, "report_bug_form | user not allowed use Command filter");
     } else {
        $filters[] = 'FilterCommandField';
     }
     return $filters;
  }

  function filter_columns() {
     $columns = array();
     $project_id=helper_get_current_project();

     // do not display command filter if user < DEVELOPER
     if (!$this->isAccessGranted($project_id)) {
        log_event(LOG_FILTERING, "report_bug_form | user not allowed to display CodevTT columns");
     } else {
        $columns['CommandColumn'] = 'CommandColumn';
        $columns['ElapsedColumn'] = 'ElapsedColumn';
     }
     return $columns;
  }

   /**
    * display combobox to select the command in 'report bug' page
    * @param type $event_id
    */
   public function report_bug_form($event, $bug_id) {
      #log_event(LOG_FILTERING, "report_bug_form | event= $event");

      $project_id=helper_get_current_project();

      // do not display command combobox if user < DEVELOPER
      if (!$this->isAccessGranted($project_id)) {
         log_event(LOG_FILTERING, "report_bug_form | user not allowed to set Commands");
      } else {
         $cmdList = $this->getAvailableCommands($project_id);
         if (0 != count($cmdList)) {

            $size = (count($cmdList) < 3) ? 3 : 6;

            echo '<div class="field-container">';
            echo '<label><span>'.plugin_lang_get('command').'</span></label>';
            echo ' <select multiple="multiple"  id="codevtt_command_id" name="codevtt_command_id[]" size="'.$size.'">';
            echo ' <option value="0" selected="selected"></option>';
            foreach ($cmdList as $id => $name) {
               echo '<option value="' . $id . '" >' . $name . '</option>';
            }
            echo ' </select>';
            echo '<span class="label-style"></span>';
            echo '</div>';
         }
      }
   }

   public function report_bug($event, BugData $p_bug_data) {
      #log_event(LOG_FILTERING, "report_bug | event= $event");

      // do not display command combobox if user < DEVELOPER
      if (!$this->isAccessGranted($p_bug_data->project_id)) {
         log_event(LOG_FILTERING, "report_bug_form | user not allowed to set Commands");
      } else {
         if (gpc_isset( 'codevtt_command_id' )) {
            $command_ids = gpc_get_int_array( 'codevtt_command_id');

            foreach ($command_ids as $command_id) {
              log_event(LOG_FILTERING, "report_bug | assign bug_id= $p_bug_data->id to Command $command_id");
              $this->assignCommand($p_bug_data->id, $command_id);
            }
         }
      }
   }

   /**
    * display combobox to select the command in 'update bug' page
    * @param type $event
    * @param type $t_bug_id
    */
   public function update_bug_form($event, $t_bug_id) {
      #log_event(LOG_FILTERING, "report_bug | event= $event");

      $project_id=helper_get_current_project();

      // do not display command combobox if user < DEVELOPER
      if (!$this->isAccessGranted($project_id)) {
         log_event(LOG_FILTERING, "report_bug_form | user not allowed to update Commands");
      } else {
         $assigned_query = "SELECT `command_id` FROM `codev_command_bug_table` WHERE `bug_id` = " . db_param();
         $assigned_request = db_query($assigned_query, array( $t_bug_id ));
         $assigned_commands = array();
         while ($row = db_fetch_array( $assigned_request )) {
            $assigned_commands[] = $row['command_id'];
         }

         $t_bug = bug_get( $t_bug_id, true );
         $t_project_id = $t_bug->project_id;
         $cmdList = $this->getAvailableCommands($t_project_id);
         if (0 != count($cmdList)) {

            $size = (count($cmdList) < 3) ? 3 : 6;

            echo '<tr>';
            echo '<td class="category">'.plugin_lang_get('command').'</td>';
            echo '<td><select multiple="multiple"  size="'.$size.'" id="codevtt_command_id" name="codevtt_command_id[]">';
            foreach ($cmdList as $id => $name) {
               echo '<option value="' . $id . '"';
               if (in_array($id, $assigned_commands)) {
                  echo ' selected="selected"';
               }
               echo ' >' . $name. '</option>';
            }
            echo '</select></td></tr>';
         }
      }
   }

   public function update_bug($event, BugData $p_bugData_prev, BugData $p_bugData_new) {
      #log_event(LOG_FILTERING, "update_bug | event= $event");

      // if status changed to 'resolved' then set Backlog = 0
      $this->checkStatusChanged($event, $p_bugData_prev, $p_bugData_new);

      // do not display command combobox if user < DEVELOPER
      if (!$this->isAccessGranted($p_bugData_prev->project_id)) {
         log_event(LOG_FILTERING, "report_bug_form | user not allowed to update Commands");
      } else {
         if (gpc_isset( 'codevtt_command_id' )) {
            $prev_command_ids=array_keys($this->getAssignedCommands($p_bugData_prev->id));
            $new_command_ids = gpc_get_int_array( 'codevtt_command_id');

            //log_event(LOG_FILTERING, "update_bug | prev_command_ids=".var_export($prev_command_ids, true));
            //log_event(LOG_FILTERING, "update_bug | new_command_ids=".var_export($new_command_ids, true));

            // 1) find out which ones must be removed
            // (remove if present in prev_command_ids and not in new_command_ids)
            foreach ($prev_command_ids as $prev_cmd_id) {
              if (!in_array($prev_cmd_id, $new_command_ids)) {
                $delete_query = "DELETE FROM codev_command_bug_table WHERE bug_id=" . db_param()." AND command_id=" . db_param();
                log_event(LOG_FILTERING, "update_bug | remove Command $prev_cmd_id");
                db_query($delete_query, array( $p_bugData_prev->id, $prev_cmd_id ));

                // remove from WBS
                $delete_from_wbs_query = "DELETE FROM `codev_wbs_table` WHERE root_id = (SELECT wbs_id from codev_command_table where id = ".db_param().") AND bug_id = ".db_param();
                db_query($delete_from_wbs_query, array( $prev_cmd_id, $p_bugData_prev->id ));

              }
            }

            // 2) find out which ones are not already assigned
            // (add if present in new_command_ids and not in prev_command_ids)
            foreach ($new_command_ids as $new_cmd_id) {
              if (!in_array($new_cmd_id, $prev_command_ids)) {
                log_event(LOG_FILTERING, "update_bug | add Command $new_cmd_id");
                $this->assignCommand($p_bugData_prev->id, $new_cmd_id);
              }
            }
         }
      }
   }


   /**
    * show bug's commands in bug view page
    * @param type $event
    * @param type $t_bug_id
    */
   public function view_bug_form($event, $t_bug_id) {

      $project_id=helper_get_current_project();

      // do not display command combobox if user < DEVELOPER
      if (!$this->isAccessGranted($project_id)) {
         log_event(LOG_FILTERING, "report_bug_form | user not allowed to view Commands");
      } else {
         $query  = "SELECT codev_command_table.* FROM `codev_command_bug_table`, `codev_command_table` ".
                    "WHERE codev_command_bug_table.bug_id=" . db_param() . " " .
                    "AND codev_command_table.id = codev_command_bug_table.command_id ".
                    "ORDER BY codev_command_table.name";

         $result = db_query($query, array( $t_bug_id ));
         $commandList = array();
         while ($row = db_fetch_array( $result )) {
            $commandList[$row['id']] = $row['reference'] . " - " . $row['name'];
         }
         if (0 != count($commandList)) {

            $formattedCmdList = implode('<br>', $commandList);

            echo '<tr>';
            echo '   <td class="category">'.plugin_lang_get('command').'</td>';
            echo '   <td colspan="5" >'.$formattedCmdList.'</td>';
            echo '</tr>';
         }
      }
   }

   /**
    * Forbid issue deletion if timetracks exists
    *
    * @param type $event
    * @param type $bug_id
    */
   public function bug_delete($event, $bug_id) {

      $query = "SELECT codev_timetracking_table.date, codev_timetracking_table.userid, codev_timetracking_table.duration, ".
              "mantis_user_table.username, mantis_user_table.realname ".
              "FROM `codev_timetracking_table`, `mantis_user_table` ".
              "WHERE codev_timetracking_table.bugid = " . db_param() . " ".
              "AND codev_timetracking_table.userid = mantis_user_table.id ";

      $errMsg = "";
      $result = db_query($query, array( $bug_id ) );
      while ($row = db_fetch_array( $result )) {
         $errMsg .= date('Y-m-d',$row['date'])." - " . $row['username'] . " (" . $row['realname'] . ") - duration " . $row['duration'] . " <br>";
      }

      if ("" != $errMsg) {
         trigger_error(' CodevTT plugin : There are timetracks on this issue ! <br><br>'.$errMsg, ERROR);
      }

   }


   public function checkStatusChanged($event, BugData $bugData_prev, BugData $bugData_new) {

   $t_resolved_status_threshold = config_get( 'bug_resolved_status_threshold', null, null, $bugData_prev->project_id );

   // if status changed to 'resolved' then set Backlog = 0
   // Note: and ($bugData_prev->status < $bugData_new->status) ?
      if ($bugData_new->status === $t_resolved_status_threshold) {
         try {
            $issue = new IssueMantisPluginHelper($bugData_prev->id);
            $issue->setBacklog(0, $bugData_prev->handler_id);
         } catch (Exception $e) {
            // trigger_error
            echo "CodevTT plugin ERROR: ".$e->getMessage().'<br>';
            echo "CodevTT plugin ERROR: ".$e->getTraceAsString().'<br>';
         }
      }
   }

   /**
    * Forbid project deletion if timetracks exists on project issues.
    *
    * @param type $event
    * @param type $project_id
    */
   public function projectDelete($event, $project_id) {

      $query = "SELECT codev_timetracking_table.bugid, codev_timetracking_table.date, codev_timetracking_table.userid, codev_timetracking_table.duration, ".
              "mantis_user_table.username, mantis_user_table.realname ".
              "FROM `codev_timetracking_table`, `mantis_user_table` ".
              "WHERE codev_timetracking_table.userid = mantis_user_table.id ".
              "AND codev_timetracking_table.bugid IN (".
              "   SELECT id FROM mantis_bug_table WHERE project_id = " . db_param() . ")";

      $errMsg = "";
      $result = db_query($query, array( $project_id ) );
      while ($row = db_fetch_array( $result )) {
         $errMsg .= 'Issue '.$row['bugid'].': '.date('Y-m-d',$row['date'])." - " . $row['username'] . " (" . $row['realname'] . ") - duration " . $row['duration'] ." <br>";
      }

      if ("" != $errMsg) {
         trigger_error(' CodevTT plugin : There are timetracks on project issues ! <br><br>'.$errMsg, ERROR);
      }
   }

   private function getUserTeams( $project_id ) {

      $userid = current_user_get_field( 'id' );

      // find user teams
      $query = "SELECT DISTINCT codev_team_table.id, codev_team_table.name " .
               "FROM `codev_team_user_table`, `codev_team_table` " .
               "WHERE codev_team_user_table.team_id = codev_team_table.id ".
               "AND   codev_team_user_table.user_id = " . db_param();

      // only teams where project is defined
      $query .= "AND 1 = is_project_in_team(" . db_param() . ", codev_team_table.id) ";
      $query .= "ORDER BY codev_team_table.name";

      $result = db_query($query, array( $userid, (int)$project_id));
      $teamidList = array();
      while ($row = db_fetch_array($result)) {
         $teamidList[] = $row['id'];
      }
      return $teamidList;
   }

   /**
    * returns the commands from the current users's teams.
    *
    */
   private function getAvailableCommands($project_id) {

      $cmdList = array();

      $teamidList = $this->getUserTeams( $project_id );

      // find team Commands
      if (0 != count($teamidList)) {
         $formattedTeamList = implode(", ", $teamidList);

         $query = "SELECT id, name, reference FROM `codev_command_table` ".
                  "WHERE team_id IN (" . $formattedTeamList . ") ".
                  "AND enabled = 1 ";

         // do not include closed commands.
         $query .= "AND (state < 6 OR state IS NULL) "; // WARN: HARDCODED value of Command::$state_closed
         $query .= "ORDER BY reference, name";

         $result = db_query($query);
         $cmdList = array();
         while ($row = db_fetch_array($result)) {
            $cmdList[$row['id']] = $row['reference'] . " :: " . $row['name'];
         }
      }
      return $cmdList;
   }

   public function getAssignedCommands($p_bug_id) {
        log_event(LOG_FILTERING, "getAssignedCommands | bug_id=$p_bug_id");

     $cmdList = array();

     $query = "SELECT codev_command_table.id, codev_command_table.name, codev_command_table.reference FROM `codev_command_table` ".
              "JOIN `codev_command_bug_table` ON codev_command_table.id = codev_command_bug_table.command_id ".
              "WHERE codev_command_bug_table.bug_id = ".db_param();

     $result = db_query($query, array( $p_bug_id));
     while($row = db_fetch_array($result)) {
        $cmdList[$row['id']] = $row['reference'] . " :: " . $row['name'];
        log_event(LOG_FILTERING, "getAssignedCommands | bug $p_bug_id is in command ".$row['id'].' - '.$row['name']);
     }
     return $cmdList;
   }


   /**
    * assign a bug to a command
    */
   public function assignCommand($p_bug_id, $command_id) {

     //TODO test if command id is valid !!!!

     // === create bug-command associations
     $query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES";
     $query .= " (" . db_param() . ", " . db_param() . ");";
     db_query($query, array( $command_id, $p_bug_id ) );

     // === add to WBS
     // 1) get the wbs_id of this command
     $query2 = "SELECT name, wbs_id FROM codev_command_table WHERE id = " . db_param();
     $result2 = db_query($query2, array( $command_id ));
     $row2 = db_fetch_array( $result2 );
     $wbs_id = $row2['wbs_id'];
     $cmd_name = $row2['name'];

     // 2) if wbs_id is null, the root element must be created
     // (this happens only once when upgrading from 0.99.24 or below)
     $order = 1;
     if (is_null($wbs_id)) {
        #echo "Create WBS root element for Command $command_id<br>";
        // add root element
        $query3 = "INSERT INTO codev_wbs_table  (`order`, `expand`, `title`) ".
                "VALUES (" . db_param() . ", " . db_param() . ", " . db_param() . ")";
        db_query($query3, array( 1, 1, $cmd_name ));
        $wbs_id = db_insert_id();

        $query4 = "UPDATE codev_command_table SET wbs_id = " . db_param() . " WHERE id = " . db_param();
        db_query($query4, array( $wbs_id, $command_id ));

        // 2.1) add all existing issues to the WBS
        $query6 = "SELECT bug_id from codev_command_bug_table WHERE command_id = " . db_param() . " ORDER BY bug_id";
        $result6 = db_query($query6, array( $command_id));
        while ($row6 = db_fetch_array( $result6 )) {
           #echo "add issue $row6->bug_id to command $command_id<br>";
           $query7 = "INSERT INTO codev_wbs_table  (`root_id`, `parent_id`, `bug_id`, `order`, `expand`) ".
                   "VALUES (" . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ")";
           #echo "SQL query7 = $query7<br>";
           $result7 = db_query($query7, array( $wbs_id, $wbs_id, $row6['bug_id'], $order, 0));
           $order += 1;
        }
     } else {
        // 3) add bug_id to the wbs root element
        $query5 = "INSERT INTO codev_wbs_table  (`root_id`, `parent_id`, `bug_id`, `order`, `expand`) ".
                "VALUES (" . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ", " . db_param() . ")";
        #echo "SQL query5 = $query5<br>";
        db_query($query5, array( $wbs_id, $wbs_id, $p_bug_id, $order, 0 ));
     }
   }

   /**
    *
    * @param type $project_id
    */
   private function isAccessGranted($project_id = null) {
      $user_id = current_user_get_field( 'id' );

      // TODO mantis rights or CodevTT rights ?!
      $accessLevel = access_get_project_level( $project_id, $user_id );

      $isGranted = ($accessLevel >= DEVELOPER );

      return $isGranted;
   }
}

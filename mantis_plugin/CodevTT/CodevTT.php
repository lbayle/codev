<?php

/**
 * requires MantisPlugin.class.php
 */
require_once( config_get('class_path') . 'MantisPlugin.class.php' );

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

      $this->version = '0.3';
      $this->requires = array(
          'MantisCore' => '1.2.0',
      );

      $this->author = 'CodevTT';
      $this->contact = 'lance2m83@gmail.com';
      $this->url = 'http://codevtt.org';
   }

   /**
    * Default plugin configuration.
    */
   public function hooks() {
      //la liste des EVENT se trouve dans core/events_inc.php
      //la construction de l'affichage se fait dans core/html_api.php
      $hooks = array(
          //'EVENT_REPORT_BUG_DATA' => 'report_bug_data',
          'EVENT_REPORT_BUG' => 'assignCommand',
          'EVENT_REPORT_BUG_FORM' => 'report_bug_form',

          #Uncomment the following line to show codevtt in main menu
          //'EVENT_MENU_MAIN' => 'add_codevtt_menu',

          #'EVENT_UPDATE_BUG_FORM' => 'update_bug_form',
          #'EVENT_UPDATE_BUG' => 'assignCommand',

          'EVENT_VIEW_BUG_DETAILS' => 'view_bug_form',

      );
      return $hooks;
   }

   /**
    *
    * @param string $event
    * @param array $t_bug_data
    */
   public function assignCommand($event, $t_bug_data) {

      #$command_ids = gpc_get_int_array( 'command_id');


      $t_bug_id = $t_bug_data->id;

      // delete all existing bug-command associations
      if ($event != 'EVENT_REPORT_BUG_FORM') {
         $delete_query = "DELETE FROM `codev_command_bug_table` WHERE `bug_id` = '$t_bug_id';";
         $delete_result = mysql_query($delete_query) or exit(mysql_error());
      }

      // create bug-command associations
      if (isset($_POST['command_id'])) {

         $command_ids = $_POST['command_id'];

         $query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES";
         $separator = "";
         //TODO test if command id is valid !!!!
         foreach ($command_ids as $command_id) {
            #error_log ("ForEach: $command_id => $t_bug_id");
            $query = $query . $separator . " ('$command_id', '$t_bug_id')";
            $separator = ",";
         }
         $query = $query . ";";
         #error_log ("Query: $query");
         $result = mysql_query($query) or exit(mysql_error());
      }
   }

   public function update_bug_form($event, $t_bug_id) {

      $assigned_query = "SELECT `command_id` FROM `codev_command_bug_table` WHERE `bug_id` = '$t_bug_id'";
      $assigned_request = mysql_query($assigned_query) or exit(mysql_error());
      $assigned_commands = array();
      $index = 0;
      while ($row = mysql_fetch_assoc($assigned_request)) {
         $assigned_commands[$index++] = $row['command_id'];
      }
      mysql_free_result($assigned_request);

      $query = "SELECT `id`, `name`, `reference`, `team_id` FROM `codev_command_table` ORDER BY `reference`,`name`";
      $command_request = mysql_query($query) or exit(mysql_error());
      //TODO filter with team id
      echo'
            <tr ';
      echo helper_alternate_class();
      echo '>
            <td class="category">
                    <span class="required">*</span>';
      echo plugin_lang_get('command');
      echo'</td>
            <td>
            <select multiple="multiple"  size="5" name="command_id[]">';
      while ($command_array = mysql_fetch_assoc($command_request)) {
         echo '<option value="' . $command_array['id'] . '"';
         if (in_array($command_array['id'], $assigned_commands)) {
            echo ' selected="selected"';
         }
         echo' >' . $command_array['reference'] . ': ' . $command_array['name'] . '</option>';
      }
      echo '</select>
            </td>
            </tr>
            ';
      mysql_free_result($command_request);
   }

   /**
    * returns the commands from the current users's teams.
    *
    */
   private function getAvailableCommands($project_id) {
      
      $cmdList = array();
      
      $userid = current_user_get_field( 'id' );

      // find user teams 
      $query = "SELECT DISTINCT codev_team_table.id, codev_team_table.name " .
               "FROM `codev_team_user_table`, `codev_team_table` " .
               "WHERE codev_team_user_table.team_id = codev_team_table.id ".
               "AND   codev_team_user_table.user_id = $userid ";
      
      // only teams where project is defined
      $query .= "AND 1 = is_project_in_team($project_id, codev_team_table.id) ";
      
      $query .= "ORDER BY codev_team_table.name";

      $result = mysql_query($query) or exit(mysql_error());
      $teamidList = array();
      while ($row = mysql_fetch_object($result)) {
         $teamidList[] = $row->id;
         #echo "getAvailableCommands() FOUND $row->id - $row->name<br/>";
      }

      // find team Commands
      if (0 != count($teamidList)) {
         $formattedTeamList = implode(", ", $teamidList);

         $query = "SELECT id, name, reference FROM `codev_command_table` ".
                  "WHERE team_id IN (" . $formattedTeamList . ") ".
                  "AND enabled = 1 ".
                  "ORDER BY reference, name";
         $result = mysql_query($query) or exit(mysql_error());
         $cmdList = array();
         while ($row = mysql_fetch_object($result)) {
            $cmdList[$row->id] = "$row->reference :: $row->name";
         }
      }

      mysql_free_result($result);
      return $cmdList;
   }

   /**
    * display combobox to select the command in 'report bug' page
    * @param type $event_id
    */
   public function report_bug_form($event_id) {

      $project_id=helper_get_current_project();
      #echo "project_id=$project_id<br>";

      $cmdList = $this->getAvailableCommands($project_id);
      if (O != count($cmdList)) {

         echo '<tr '.helper_alternate_class().'>';
         echo '<td class="category">';
         echo '   <span class="required">*</span>';
         echo plugin_lang_get('command').'</td>';
         echo '<td>';
         echo ' <select multiple="multiple"  size="5" name="command_id[]">';
         foreach ($cmdList as $id => $name) {
            echo '<option value="' . $id . '" >' . $name . '</option>';
         }
         echo ' </select>';
         echo '</td>';
         echo '</tr>';

      }
   }

   public function add_codevtt_menu() {
      // WARNING: CodevTT is not always installed in ../codevtt
      return array(
          '<a href="../codevtt/index.php">' . plugin_lang_get('codevtt_menu') . '</a>',
          '<a href="' . plugin_page('import_to_command') . '">' . plugin_lang_get('import_menu') . '</a>',
      );
   }

   /**
    * show bug's commands in bug view page
    * @param type $event
    * @param type $t_bug_id 
    */
   public function view_bug_form($event, $t_bug_id) {

//select codev_command_table.* from codev_command_bug_table, codev_command_table where bug_id = '358' and codev_command_bug_table.command_id = codev_command_table.id

      $query  = "SELECT codev_command_table.* FROM `codev_command_bug_table`, `codev_command_table` ".
                 "WHERE codev_command_bug_table.bug_id=$t_bug_id ".
                 "AND codev_command_table.id = codev_command_bug_table.command_id ".
                 "ORDER BY codev_command_table.name";

      $result = mysql_query($query) or exit(mysql_error());
      $commandList = array();
      while ($row = mysql_fetch_object($result)) {
         $commandList[$row->id] = "$row->reference - $row->name";
      }
      if (0 != count($commandList)) {

         $formattedCmdList = implode('<br>', $commandList);

         echo '<tr '.helper_alternate_class().'>';
         echo '   <td class="category">'.plugin_lang_get('command').'</td>';
         echo '   <td colspan="5" >'.$formattedCmdList.'</td>';
         echo '</tr>';
      }
   }
}

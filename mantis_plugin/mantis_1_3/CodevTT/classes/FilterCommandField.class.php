<?php

class FilterCommandField extends MantisFilter {

   /**
    * Field name, as used in the form element and processing.
    */
   public $field = "command";

   /**
    * Filter title, as displayed to the user.
    */
   public $title = 'Command';

   /**
    * Filter type, as defined in core/constant_inc.php
    */
   public $type = FILTER_TYPE_MULTI_STRING;

   /**
    * Default filter value, used for non-list filter types.
    */
    public $default = null;

   /**
    * Form element size, used for non-boolean filter types.
    */
   public $size = 1;

   /**
    * Number of columns to use in the bug filter.
    */
   public $colspan = 3;


   public function __construct() {
      $this->title = 'Command';
   }


   /**
    * Validate the filter input, returning true if input is
    * valid, or returning false if invalid.  Invalid inputs will
    * be replaced with the filter's default value.
    * @param multi Filter field input
    * @return boolean Input valid (true) or invalid (false)
    */
   public function validate( $p_filter_input ) {
        return true;
   }

   /**
    * Build the SQL query elements 'join', 'where', and 'params'
    * as used by core/filter_api.php to create the filter query.
    * @param multi Filter field input
    * @return array Keyed-array with query elements; see developer guide
    */
   function query( $p_filter_input ) {

      //require_api( 'logging_api.php' );
      //log_event(LOG_FILTERING, "query( ".var_export($p_filter_input, true).")");

      $t_cmdId = $p_filter_input[0];
      //log_event(LOG_FILTERING, '$t_cmdId = '.$t_cmdId);

      $t_bug_table = db_get_table( 'bug' );

      $t_query = array(
        'join' => "JOIN codev_command_bug_table ON $t_bug_table.id = codev_command_bug_table.bug_id ",
        'where' => "codev_command_bug_table.command_id = $t_cmdId ",
      );
      //log_event(LOG_FILTERING, "query = ".var_export($t_query, true));
      return $t_query;
   }

   /**
    * Display the current value of the filter field.
    * @param multi Filter field input
    * @return string Current value output
    */
   function display( $p_filter_value ) {
      //require_api( 'logging_api.php' );
      //log_event(LOG_FILTERING, "display( $p_filter_value )");

      $query = "SELECT name, reference FROM `codev_command_table` WHERE id = " . db_param() ;
      $result = db_query($query, array( $p_filter_value));
      $row = db_fetch_array( $result );
      $display = /* $row['reference'] . " :: " . */ $row['name'];

      return $display;
   }

   /**
    * For list type filters, define a keyed-array of possible
    * filter options, not including an 'any' value.
    * @return array Filter options keyed by value=>display
    */
   public function options() {
      //require_api( 'logging_api.php' );

      $project_id=helper_get_current_project();
      #echo "project_id=$project_id<br>";
      $options = $this->getAvailableCommands($project_id);
      //log_event(LOG_FILTERING, "options() project_id=".$project_id);

      //log_event(LOG_FILTERING, "options = ". var_export($options, true));
      return $options;
   }


   /**
    * TODO this method is also defined in CodevTT.php, make a library !!
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
               "AND   codev_team_user_table.user_id = " . db_param();

      // only teams where project is defined
      $query .= "AND 1 = is_project_in_team(" . (int)$project_id . ", codev_team_table.id) ";

      $query .= "ORDER BY codev_team_table.name";

      $result = db_query($query, array( $userid));
      $teamidList = array();
      while ($row = db_fetch_array($result)) {
         $teamidList[] = $row['id'];
         #echo "getAvailableCommands() FOUND $row['id'] - $row['name']<br/>";
      }

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
}



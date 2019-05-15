<?php
class CommandColumn extends MantisColumn {

   private $cache = array();

   public function __construct() {
      $this->title = 'Command';
      $this->column = 'Command';
      $this->sortable = false;
   }

  /**
   * Build the SQL query elements 'join' and 'order' as used by
   * core/filter_api.php to create the filter sorting query.
   * @param string $p_direction Sorting order ('ASC' or 'DESC').
   * @return array Keyed-array with query elements; see developer guide
   */
  public function sortquery( $p_direction ) {
/*    $t_version_id = $this->id;
    $t_bug_table = db_get_table( 'mantis_bug_table' );
    $t_status_table = plugin_table( 'status', 'ProductMatrix' );
    return array(
      'join' => "LEFT JOIN $t_status_table pvmst ON $t_bug_table.id=pvmst.bug_id AND pvmst.version_id=$t_version_id",
      'order' => "pvmst.status $p_dir",
    );
*/
    return array();
  }

   public function cache( array $p_bugs ) {
      if ( count( $p_bugs ) < 1 ) {
         return;
      }
      $t_bug_table = db_get_table( 'mantis_bug_table' );

      $t_bug_ids = array();
      foreach ( $p_bugs as $t_bug ) {
         $t_bug_ids[] = $t_bug->id;
      }
      $t_bug_ids = implode( ',', $t_bug_ids );
      $t_query  = "SELECT DISTINCT b.id, cmd.name FROM $t_bug_table b
                    INNER JOIN codev_command_bug_table cmd_bugs ON cmd_bugs.bug_id = b.id
                    INNER JOIN codev_command_table cmd ON cmd.id = cmd_bugs.command_id
                    WHERE b.id IN ( $t_bug_ids )";
      $t_result = db_query_bound( $t_query );
      while ( $t_row = db_fetch_array( $t_result ) ) {
         $this->cache[$t_row['id']] = $t_row['name'];
      }
   }


/* 
  // use cache, but the cache() function needs to be fixed...
   public function display( BugData $p_bug, $p_columns_target ) {
      if ( isset( $this->cache[$p_bug->id] ) ) {
         if ( $p_columns_target == COLUMNS_TARGET_VIEW_PAGE ||
            $p_columns_target == COLUMNS_TARGET_PRINT_PAGE
         ) {
            echo $this->cache[$p_bug->id];
         } else {
            echo $this->cache[$p_bug->id];
         }
      }
   }
*/
  /**
   * Function to display column data for a given bug row.
   * @param BugData $p_bug            A BugData object.
   * @param integer $p_columns_target Column display target.
   * @return void
   */   
   public function display( $p_bug, $p_columns_target ) {
      //require_api( 'logging_api.php' );
      //log_event(LOG_FILTERING, "display( $p_bug->id )");


      $query = 'SELECT cmd.id, cmd.name FROM codev_command_table cmd WHERE cmd.id IN (SELECT cmd_bugs.command_id FROM codev_command_bug_table cmd_bugs WHERE cmd_bugs.bug_id = ' . db_param() . ' )';
      $request = db_query_bound($query, array( $p_bug->id ));
      $commands = array();
      while ($row = db_fetch_array( $request )) {
         $commands[$row['id']] = $row['name'];
      }
      $strCmds = implode (', ', $commands);
      //log_event(LOG_FILTERING, "commands = $strCmds");
      echo $strCmds;
   }
}

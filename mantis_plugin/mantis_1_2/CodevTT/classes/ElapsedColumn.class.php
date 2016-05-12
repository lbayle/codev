<?php
class ElapsedColumn extends MantisColumn {

   private $cache = array();

   public function __construct() {
      $this->title = 'Elapsed';
      $this->column = 'Elapsed';
      $this->sortable = false;
   }


   public function cache( array $p_bugs ) {
      if ( count( $p_bugs ) < 1 ) {
         return;
      }
      //$t_bug_table = db_get_table( 'mantis_bug_table' );

      $t_bug_ids = array();
      foreach ( $p_bugs as $t_bug ) {
         $t_bug_ids[] = $t_bug->id;
      }
      $str_bug_ids = implode( ',', $t_bug_ids );

      $t_query  = "SELECT bugid, SUM(duration) as duration FROM `codev_timetracking_table` tt
                    WHERE bugid IN ( $str_bug_ids )
                    GROUP BY bugid";

      $t_result = db_query_bound( $t_query );
      while ( $t_row = db_fetch_array( $t_result ) ) {
         $this->cache[$t_row['bugid']] = round($t_row['duration'], 3);
      }
   }



  /**
   * Function to display column data for a given bug row.
   * @param BugData $p_bug            A BugData object.
   * @param integer $p_columns_target Column display target.
   * @return void
   */   
   public function display( $p_bug, $p_columns_target ) {
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

/*
   public function display_no_cache( BugData $p_bug, $p_columns_target ) {
      //require_api( 'logging_api.php' );
      //log_event(LOG_FILTERING, "display( $p_bug->id )");

      $query = "SELECT SUM(duration) as duration FROM `codev_timetracking_table` WHERE bugid = ". db_param();
      $result = db_query_bound($query, array( $p_bug->id ));
      $row = db_fetch_array( $result );

      $elapsed = round($row['duration'], 3);

      //log_event(LOG_FILTERING, "commands = $strCmds");
      echo $elapsed;
   }
*/
   
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

}

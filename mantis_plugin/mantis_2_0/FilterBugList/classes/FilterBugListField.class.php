<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

class FilterBugListField extends MantisFilter {

   /**
    * Field name, as used in the form element and processing.
    */
   public $field = "list";

   /**
    * Filter title, as displayed to the user.
    */
   public $title = 'Bug List';
   
   /**
    * Filter type, as defined in core/constant_inc.php
    */
//    public $type = FILTER_TYPE_MULTI_INT;
   public $type = FILTER_TYPE_STRING;
   
   /**
    * Default filter value, used for non-list filter types.
    */
    public $default = null;

   /**
    * Form element size, used for non-boolean filter types.
    */
   public $size = null;

   /**
    * Number of columns to use in the bug filter.
    */
   public $colspan = 5;
   

   public function __construct() {
      $this->title = plugin_lang_get( 'field_label', 'FilterBugList' );
   }   
   
    public static function inputs( $p_inputs=null ) {
         static $s_inputs = null;

         if ( is_array($p_inputs) ) {
               return $s_inputs;
         } else {
               $s_inputs = $p_inputs;
         }
    }
    
    
   /**
    * Format the filter input, returning the list cleaned with any separator
    * character.
    * @param multi $p_filter_input Filter field input
    * @return string the filtered string or null
    */
    public static function format_inputs( $p_filter_input = null ) {
        $t_list = null;
        if ( is_array( $p_filter_input ) ) {
            // Should never be used (dead code) when $type = FILTER_TYPE_STRING
            $t_list = array();
            foreach( $p_filter_input as $t_bug_id ) {
               if( is_numeric(trim($t_bug_id)) && !preg_match('/\./', trim($t_bug_id)) ){
                   $t_list[] = trim($t_bug_id);
               }
            }
    
            $t_list = join( ',', $t_list );
        } else {
            // Match any types of separator !!! :-)
            // Replace non numbers by the arbitrary space character
            $t_list = trim(preg_replace( '/[^0-9]/', ' ', $p_filter_input ));
            // replace all series of space characters by a comma separator for the
            // query
            $t_list = preg_replace( '/\s+/', ',', $t_list );
        }
        return $t_list;
    }   
    
   /**
    * Validate the filter input, returning true if input is
    * valid, or returning false if invalid.  Invalid inputs will
    * be replaced with the filter's default value.
    * @param multi Filter field input
    * @return boolean Input valid (true) or invalid (false)
    */
   public function validate( $p_filter_input ) {
        self::inputs( $p_filter_input );
        return true;
   }

   /**
    * Build the SQL query elements 'join', 'where', and 'params'
    * as used by core/filter_api.php to create the filter query.
    * @param multi Filter field input
    * @return array Keyed-array with query elements; see developer guide
    */
   function query( $p_filter_input ) {
      $t_list = self::format_inputs( $p_filter_input );

      if( empty($t_list ) ){
          return;
      }
      
      $t_bug_table = db_get_table( 'mantis_bug_table' );
      

      $t_query = array(
         'where' => "$t_bug_table.id IN ( $t_list )",
      );

      return $t_query;
   }

   /**
    * Display the current value of the filter field.
    * @param multi Filter field input
    * @return string Current value output
    */
   function display( $p_filter_value ) {
      return self::format_inputs( $p_filter_value );   
   }

   /**
    * For list type filters, define a keyed-array of possible
    * filter options, not including an 'any' value.
    * @return array Filter options keyed by value=>display
    */
   public function options() {
   }
}

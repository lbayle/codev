<?php

/**
 * requires MantisPlugin.class.php
 */
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

/**
 * CodevTTPlugin Class
 */
class CodevTTPlugin extends MantisPlugin {

	/**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = '';

		$this->version = '0.2';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'CodevTT';
		$this->contact = 'lance2m83@gmail.com';
		$this->url = '';
	}

	/**
	 * Default plugin configuration.
	 */
	function hooks( ) {
		//la liste des EVENT se trouve dans core/events_inc.php
		//la construction de l'affichage se fait dans core/html_api.php
		$hooks = array(
			//'EVENT_REPORT_BUG_DATA' => 'report_bug_data',
			'EVENT_REPORT_BUG' => 'assignCommand',
			'EVENT_REPORT_BUG_FORM' => 'report_bug_form',
			#Uncomment the following line to show codevtt in main menu
			//'EVENT_MENU_MAIN' => 'add_codevtt_menu',
			
                        'EVENT_UPDATE_BUG_FORM' => 'update_bug_form',
                        'EVENT_UPDATE_BUG' => 'assignCommand',
		);
		return $hooks;
	}
	

   /**
    *
    * @param string $event
    * @param array $t_bug_data
    */
   function assignCommand($event, $t_bug_data) {

      #$command_ids = gpc_get_int_array( 'command_id');


      $t_bug_id = $t_bug_data->id;

      // delete all existing bug-command associations
      if ($event != 'EVENT_REPORT_BUG_FORM') {
         $delete_query = "DELETE FROM `codev_command_bug_table` WHERE `bug_id` = '$t_bug_id';";
         $delete_result = mysql_query($delete_query) or exit( mysql_error() );
      }

      // create bug-command associations
      if( isset($_POST['command_id'])) {

         $command_ids = $_POST['command_id'];

         $query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES";
         $separator = "";
         //TODO test if command id is valid !!!!
         foreach ($command_ids as $command_id) {
            #error_log ("ForEach: $command_id => $t_bug_id");
            $query = $query . $separator ." ('$command_id', '$t_bug_id')";
            $separator = ",";
         }
         $query = $query . ";";
         #error_log ("Query: $query");
         $result = mysql_query($query) or exit( mysql_error() );

      }
   }
        
	function update_bug_form($event, $t_bug_id) {
            
            $assigned_query = "SELECT `command_id` FROM `codev_command_bug_table` WHERE `bug_id` = '$t_bug_id'";
            $assigned_request = mysql_query( $assigned_query ) or exit( mysql_error() );
            $assigned_commands = array();
            $index=0;
            while ($row = mysql_fetch_assoc($assigned_request)) {
                $assigned_commands[$index++] = $row['command_id'];
            }
            mysql_free_result($assigned_request);
            
            $query = "SELECT `id`, `name`, `reference`, `team_id` FROM `codev_command_table` ORDER BY `reference`,`name`";
            $command_request = mysql_query( $query ) or exit( mysql_error() );
            //TODO filter with team id
            echo'
            <tr ';
            echo helper_alternate_class() ;
            echo '>
            <td class="category">
                    <span class="required">*</span>' ;
                    echo plugin_lang_get( 'command' );
            echo'</td>
            <td>
            <select multiple="multiple"  size="5" name="command_id[]">';
            while ($command_array = mysql_fetch_assoc($command_request)) {
                    echo '<option value="'.$command_array['id'].'"';
                    if (in_array($command_array['id'], $assigned_commands)) {
                        echo ' selected="selected"';
                    }       
                    echo' >'.$command_array['reference'].': '.$command_array['name'].'</option>';
            }
            echo '</select>
            </td>
            </tr>
            ';
            mysql_free_result($command_request);
            
        }
        
	function report_bug_form($project_id) {
            $query = "SELECT `id`, `name`, `reference`, `team_id` FROM `codev_command_table` ORDER BY `reference`,`name`";
            $command_request = mysql_query( $query ) or exit( mysql_error() );
            //TODO filter with team id
            echo'
            <tr ';
            echo helper_alternate_class() ;
            echo '>
            <td class="category">
                    <span class="required">*</span>' ;
                    echo plugin_lang_get( 'command' );
            echo'</td>
            <td>
            <select multiple="multiple"  size="5" name="command_id[]">';
            while ($command_array = mysql_fetch_assoc($command_request)) {
                    echo '<option value="'.$command_array['id'].'" >'.$command_array['reference'].': '.$command_array['name'].'</option>';
            }
            echo '</select>
            </td>
            </tr>
            ';
            mysql_free_result($command_request);
	}

	 function add_codevtt_menu( ) {
		 return array( 
                     '<a href="../codevtt/index.php">' . plugin_lang_get( 'codevtt_menu' ) . '</a>',
                     '<a href="'.plugin_page( 'import_to_command' ).'">' . plugin_lang_get( 'import_menu' ) . '</a>',
                         );
	}
	

}

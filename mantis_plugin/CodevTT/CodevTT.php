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

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'CodevTT';
		$this->contact = 'lancelot.demeulemeester@atos.net';
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
			'EVENT_REPORT_BUG' => 'report_bug',
			'EVENT_REPORT_BUG_FORM' => 'report_bug_form',
			#Uncomment the following line to show codevtt in main menu
			//'EVENT_MENU_MAIN' => 'import_codevtt_menu',
			
			//'EVENT_MENU_FILTER' => 'export_issues_menu',
			//'EVENT_LAYOUT_CONTENT_BEGIN' => 'search_adel_form',
			// 'EVENT_MENU_MANAGE' => 'menu_manage',
			// 'EVENT_MENU_MANAGE_CONFIG' => 'menu_manage_config',
			// 'EVENT_MENU_SUMMARY' => 'menu_summary',
			// 'EVENT_MENU_DOCS' => 'menu_docs',
			// 'EVENT_MENU_ACCOUNT' => 'menu_account',
			// 'EVENT_MENU_MAIN_FRONT' => 'menu_main_front',
		);
		return $hooks;
	}
	function report_bug_data($event, $t_bug_data) {
	    echo "bug data => $event: $t_bug_data";
		return $t_bug_data;
	}
	
	function report_bug($event, $t_bug_data) {
		$command_id = gpc_get_int( 'command_id');
		$t_bug_id = $t_bug_data->id;
		//TODO test if command id is valid !!!!
		$query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES ('$command_id', '$t_bug_id');";
		$result = mysql_query($query) or exit( mysql_error() );

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
		<select name="command_id">';
		while ($command_array = mysql_fetch_assoc($command_request)) {
			echo '<option value="'.$command_array['id'].'" >'.$command_array['reference'].': '.$command_array['name'].'</option>';
		}
		echo '</select>
		</td>
		</tr>
		';
		mysql_free_result($command_request);
	}
	// function menu_main_front()  {
		// return array('MENU MAIN FRONT');
	// }
	
	// function menu_manage()  {
		// return array('MENU MANAGE');
	// }
	// function menu_manage_config()  {
		// return array('MENU MANAGE CONFIG');
	// }
	// function menu_summary()  {
		// return array('MENU SUMMARY');
	// }
	// function menu_docs()  {
		// return array('MENU DOCS');
	// }
	// function menu_account()  {
		// return array('MENU ACCOUNT');
	// }

	 function import_codevtt_menu( ) {
		 return array( '<a href="../codevtt/index.php">' . plugin_lang_get( 'codevtt_menu' ) . '</a>', );
	}
	

}

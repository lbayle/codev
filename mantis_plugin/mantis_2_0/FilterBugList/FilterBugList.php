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

//require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
//require_once( 'core/filter_api.php' );

class FilterBugListPlugin extends MantisPlugin  {

    /**
     *  A method that populates the plugin information and minimum requirements.
     */
    function register( ) {
        $this->name = plugin_lang_get( 'title' );
        $this->description = plugin_lang_get( 'description' );
        
        $this->version = '2.0.0';
        $this->requires = array(
        	'MantisCore' => '2.0.0'
        );
        
        $this->author = 'Alain D\'EURVEILHER';
        $this->contact = 'alain.deurveilher@gmail.com';
        $this->url = 'https://github.com/mantisbt-plugins/FilterBugList';
    }
    
 
    function init() {
//        spl_autoload_register( array( 'FilterBugListPlugin', 'autoload' ) );
        
//        $t_path = config_get_global('plugin_path' ). plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
        
//        set_include_path(get_include_path() . PATH_SEPARATOR . $t_path);
    }
 

    function hooks( ) {
        $hooks = array(
            'EVENT_FILTER_FIELDS' => 'filter_bug_list'
        );
        return $hooks;
    }

    
    function filter_bug_list($p_event) {
		require_once( 'classes/FilterBugListField.class.php' );
		return array(
			'FilterBugListField'
		);
    }
    
}

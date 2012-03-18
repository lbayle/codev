<?php

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

// NSV 17 Mar 2012
// =======================================

require('Smarty.class.php');

/**
 * Smarty helper : Construct a smarty objet for templating engine
 */
class SmartyHelper {
    
    private $smarty;

    /**
     * Constructor
     * @param String Version of codev
     * @param String User name
     * @param String Real name
     */
    public function __construct() {
        $this->smarty = new Smarty();

        $this->smarty->setCaching(false);
        
        // function declaration
        function smarty_translate ($params, $content, $smarty, &$repeat) {
            if (isset($content)) {
                return T_($content);
            }
        }

        // register with smarty
        $this->smarty->registerPlugin("block","t", "smarty_translate");
    }
    
    /**
     * Asign the key to value
     * @param String The key
     * @param String The value
     */
    public function assign($key, $value) {
        $this->smarty->assign($key, $value);
    }
    
    /**
     * Display the template
     * @param String the template to be displayed
     */
    public function display($template) {
        $this->smarty->display('tpl/'.$template.'html');
    }
    
    /**
     * Display the default template
     * @param String Version of codev
     * @param String User name
     * @param String Real name
     * @param String Mantis URL
     */
    public function displayTemplate($codevVersion, $username, $realname, $mantisURL) {
        $this->smarty->assign("year", date("Y"));
        $this->smarty->assign("codevVersion", $codevVersion);
        $this->smarty->assign("username", $username);
        $this->smarty->assign("realname", $realname);
        $this->smarty->assign('page', $_SERVER['PHP_SELF']);
        $this->smarty->assign('tpl_name', str_replace('.php','',substr(strrchr($_SERVER['PHP_SELF'],'/'),1)));
        $this->smarty->assign('mantisURL', $mantisURL);
        $this->smarty->assign('rootWebSite', getServerRootURL().'/');
        
        $this->smarty->display('tpl/template.html');
    }

}

?>

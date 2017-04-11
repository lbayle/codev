<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

require_once('i18n/i18n.inc.php');

$page_name = T_("Install - Step 4");
require_once('install/install_header.inc.php');

Config::getInstance()->setQuiet(true);

require_once('install/install_menu.inc.php');

#$logger = Logger::getLogger("install_step4");

function displayPage() {

   // relative path ("./" or "../")
   // absolute path is preferable for IE/W3C compatibility
   $rootWebSite = is_null(Constants::$codevURL) ? '../' : Constants::$codevURL;
   if ('/' !== substr($rootWebSite, -1)) { $rootWebSite .= '/'; }

   $rootMantisURL = is_null(Constants::$mantisURL) ? '../../mantis/' : Constants::$mantisURL;
   if ('/' !== substr($rootMantisURL, -1)) { $rootMantisURL .= '/'; }

   echo "<h1 class='center'>".T_('Manual steps')."</h1>";

   echo "<div style='margin-top:6em;'>";

   echo "<h2>".T_("Check Mantis Plugins")."</h2>";
   echo '<ul>';
   echo '<li>'.T_("Login to Mantis as 'Administrator'.").'</li>';
   echo '<li>'.T_("Go to")." <a href=' ".$rootMantisURL."manage_plugin_page.php' target='_blank't>".T_("manage plugins").'</a></li>';
   echo '<li>'.T_('Check that the <b>CodevTT</b> plugin is installed and activated.').'</li>';
   echo '<li>'.T_('Check that the <b>FilterBugList</b> plugin is installed and activated').'</li>';
   echo '</ul>';

#   echo '<br>';

   echo "<h2>".T_("Check generated configuration files")."</h2>";
   echo '<ul>';
   echo '<li> '.T_("Mantis Path & URL in ").' : <b>config.ini</b></li>';
   echo '<li> '.T_("Log directory in ").' : <b>log4php.xml</b></li>';
   echo '</ul>';

#   echo '<br>';

   echo "<h2>".T_("Remove CodevTT Installation files")."</h2>";
   echo '<ul>';
   echo '<li>'.T_("For security reasons, delete the following directory: ").Constants::$codevRootDir.DIRECTORY_SEPARATOR.'install</li>';
   echo '</ul>';

#   echo '<br>';

   echo "<h2>".T_("Install Firefox Greasemonkey addon")."</h2>";
   echo "<span class='help_font'>".T_("Note: This step is optional")."</span>";
   echo "<ul>
         <li>".T_("Install addon:")." <a href='https://addons.mozilla.org/fr/firefox/addon/greasemonkey/' target='_blank't>greasemonkey</a></li>
         <li>".T_("Load script:")." <a href='".$rootWebSite."mantis_monkey.user.js' target='_blank't>mantis_monkey.user.js</a></li>
         </ul>";

   echo "</div>";

   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Done !")."' onClick=\"location.href='install_step5.php'\">\n";
   echo "</div>\n";
}

function displayFooter() {
   echo "<br/><hr />
         <address class='right'>
            <a href='http://www.gnu.org/licenses/gpl.html' target='_blank'><img title='GPL v3' src='../images/copyleft.png' /></a>
            2010-".date('Y')."&nbsp; <span title='Freedom is nothing else but a chance to be better. (Albert Camus)'><a href='http://codevtt.org' target='_blank'>CodevTT.org</a></span><br>
         </address>";
}

// =========== MAIN ==========
displayPage();
displayFooter();



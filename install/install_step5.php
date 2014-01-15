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

$page_name = T_("Installation finished");
require_once('install/install_header.inc.php');

Config::getInstance()->setQuiet(true);

require_once('install/install_menu.inc.php');

#$logger = Logger::getLogger("install_step4");

function displayPage() {

      // relative path ("./" or "../")
      // absolute path is preferable for IE/W3C compatibility
      $rootWebSite = is_null(Constants::$codevURL) ? '../' : Constants::$codevURL;
      if ('/' !== substr($rootWebSite, -1)) { $rootWebSite .= '/'; }

   echo "<div style='margin-top:6em;'>

   <h1 class='center'>Congratulations !</h1>
   <div class='center'>CodevTT is now installed.</div>

   <div class='left'  style='margin-top:6em;'>
      <h2>What's next ?</h2>

      <ul>
         <li>Check the generated configuration files: <b>config.ini</b> & <b>log4php.xml</b></li><br>
         <li><a href='".$rootWebSite."index.php' title='CodevTT & Mantis share the same users' target='_blank't>login !</a></li>
         <li>Go to the <a href='".$rootWebSite."admin/create_team.php' target='_blank't>create team</a> page and create your team</li>
         <li>In the <a href='".$rootWebSite."admin/edit_team.php' target='_blank't>edit team</a> page, add members & projects to your team</li>
      </ul>
       <span class='help_font'>Note: CodevTT & Mantis share the same users / passwords</span>

   </div>

   <div class='left'  style='margin-top:3em;'>
      <strong>Troubleshooting</strong>
      <ul>
         <li>Check the <a href='http://codevtt.org/site/?forum=installation' target='_blank't>Installation forum</a></li>
         <li>Read the <a href='http://codevtt.org/site/?page_id=153' target='_blank't>Documentation</a></li>
      </ul>
   </div>

</div>";
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

?>

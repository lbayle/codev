<?php

include_once('../include/session.inc.php');

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

include_once '../path.inc.php';

require_once 'i18n.inc.php';

$page_name = T_("Installation finished");
require_once 'install_header.inc.php';

require_once "mysql_connect.inc.php";

require_once "config.class.php";
Config::getInstance()->setQuiet(true);

require_once "internal_config.inc.php";

require_once 'install_menu.inc.php';

#$logger = Logger::getLogger("install_step4");


function displayPage() {

   echo "
<div style='margin-top:6em;'>

   <h1 class='center'>Congratulations !</h1>
   <div class='center'>CodevTT is now installed.</div>

   <div class='left'  style='margin-top:6em;'>
      <h2>What's next ?</h2>
      <b>CodevTT / Mantis configuration</b>
      <ul>
         <li>Check the 'STATUS' section in constants.php</li>
         <li>Install the 'CodevTT' mantis plugin</li>
         <li>If you use firefox, install <a href='https://addons.mozilla.org/fr/firefox/addon/greasemonkey/' target='_blank't>greasemonkey</a></li>
         <li>Install <a href='../mantis_monkey.user.js' target='_blank't>greasemonkey script</a></li>
         <li>Delete codevTT install directory</li>
      </ul>
   </div>

   <div class='left'  style='margin-top:3em;'>
      <b>Start working</b>
      <ul>
         <li><a href='../index.php' title='CodevTT & Mantis share the same users' target='_blank't>login !</a></li>
         <li>Go to the <a href='../admin/create_team.php' target='_blank't>create team</a> page and create your team</li>
         <li>In the <a href='../admin/edit_team.php' target='_blank't>edit team</a> page, add members & projects to your team</li>
      </ul>
       <span class='help_font'>Note: CodevTT & Mantis share the same users</span>
   </div>

</div>


   ";
}

function displayFooter() {
echo "
<br/><hr />
<address class='right'>
   <a href='http://www.gnu.org/licenses/gpl.html' target='_blank'><img title='GPL v3' src='../images/copyleft.png' /></a>
   2010-".date('Y')."&nbsp; <span title='Freedom is nothing else but a chance to be better. (Albert Camus)'><a href='http://codevtt.org' target='_blank'>CodevTT.org</a></span><br>
</address>
";


}

// =========== MAIN ==========

displayPage();
displayFooter();
?>

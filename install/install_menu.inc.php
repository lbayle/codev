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

include_once "tools.php";
include_once 'i18n.inc.php';

$installFolder="/install/";

$firstStep="install.php";
$firstLinkName = T_("Install");
$firstLink = "<a href='".getServerRootURL().$installFolder.$firstStep."' title='".$firstLinkName."'>".$firstLinkName."</a>";

$secondStep="install_step1.php";
$secondLinkName = T_("Step 1");
$secondLink = "<a href='".getServerRootURL().$installFolder.$secondStep."' title='".$secondLinkName."'>".$secondLinkName."</a>";

$thirdStep="install_step2.php";
$thirdLinkName = T_("Step 2");
$thirdLink = "<a href='".getServerRootURL().$installFolder.$thirdStep."' title='".$thirdLinkName."'>".$thirdLinkName."</a>";

$fourthStep="install_step3.php";
$fourthLinkName = T_("Step 3");
$fourthLink = "<a href='".getServerRootURL().$installFolder.$fourthStep."' title='".$fourthLinkName."'>".$fourthLinkName."</a>";

// Don't show the link if we are already on the page
if(strpos($_SERVER['REQUEST_URI'],$firstStep)) {
    $firstLink = $firstLinkName;
    $thirdLink = $thirdLinkName;
    $fourthLink = $fourthLinkName;
} elseif(strpos($_SERVER['REQUEST_URI'],$secondStep)) {
    $secondLink = $secondLinkName;
    $thirdLink = $thirdLinkName;
    $fourthLink = $fourthLinkName;
} elseif(strpos($_SERVER['REQUEST_URI'],$thirdStep)) {
    $thirdLink = $thirdLinkName;
    $fourthLink = $fourthLinkName;
} elseif(strpos($_SERVER['REQUEST_URI'],$fourthStep)) {
    $fourthLink = $fourthLinkName;
}

echo "<div id='menu'>\n";
echo "  <table  style='margin-top: 2em'>\n";
echo "    <tr>\n";
echo "      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis' title='MantisBT'>Mantis</a></td>\n";
echo "      <td>".$firstLink." | ".$secondLink." | ".$thirdLink." | ".$fourthLink."</td>\n";
echo "      <td><a href='".getServerRootURL()."/doc/index.php' title='".T_("Documentation")."'>Doc</a></td>\n";
echo "    </tr>\n";
echo "  </table>\n";
echo "<br/><br/></div>";

?>

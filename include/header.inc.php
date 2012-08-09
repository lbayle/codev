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

include_once("include/super_header.inc.php");

include_once('include/internal_config.inc.php')

?>
<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>

<head>
<title>CodevTT</title>

<?php
#header( 'Content-Type: text/html; charset=utf-8' );
?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">


<?php
   echo "<link rel='shortcut icon' href='".getServerRootURL()."/images/favicon.ico' />\n";

   // --- JQUERY ---
   #echo "<link type='text/css' href='".getServerRootURL()."/lib/jquery/css/ui-lightness/jquery-ui-1.8.16.custom.css' rel='Stylesheet' />\n";
   echo "<link type='text/css' href='".getServerRootURL()."/lib/jquery/css/Aristo/Aristo.css' rel='Stylesheet' />\n";

   echo "<script type='text/javascript' src='".getServerRootURL()."/lib/jquery/js/jquery-1.7.1.min.js'></script>\n";
   echo "<script type='text/javascript' src='".getServerRootURL()."/lib/jquery/js/jquery.bgiframe-2.1.2.js'></script>\n";
   echo "<script type='text/javascript' src='".getServerRootURL()."/lib/jquery/js/jquery.tools-1.2.7.min.js'></script>\n";

   echo "<script type='text/javascript' src='".getServerRootURL()."/lib/jquery/js/jquery-ui-1.8.16.custom.min.js'></script>\n";
   #echo "<script type='text/javascript' src='".getServerRootURL()."/lib/jquery/js/jquery-ui-1.8.17.custom.min.js'></script>\n";

   echo "<script type='text/javascript' src='".getServerRootURL()."/lib/datatables/media/js/jquery.dataTables.js'></script>\n";

   // --- CODEV ---
   echo "<link href='".getServerRootURL()."/codev.css' rel='stylesheet' type='text/css' />\n";
   echo "<link href='".getServerRootURL()."/light.css' rel='stylesheet' type='text/css' media='print' />\n";
?>
</head>

<?php
   if (isset($_POST['on_load_focus'])) {
      echo "<body onLoad='".$_POST['on_load_focus'].".focus()' >\n";
   } else {
      echo "<body>\n";
   }
?>

<div id='header'>

<table id='header'>
<tr>
<td width=300>
<?php
   echo "<a href='".getServerRootURL()."/'><img title='".InternalConfig::$codevVersion."' src='".getServerRootURL()."/images/codevtt_logo_03.png' /></a>";
?>
</td>
<td>
<?php
   echo"<h1>$page_name</h1>";
?>
</td>
<td width=300>
<?php echo "<a href='".Tools::curPageName()."?locale=fr'><img title='Francais' src='".getServerRootURL()."/images/drapeau_fr.jpg' /></a>";?>
&nbsp;
<?php echo "<a href='".Tools::curPageName()."?locale=en'><img title='English' src='".getServerRootURL()."/images/drapeau_gb.jpg' /></a>";?>
</td>
</tr>
</table>

</div>


<?php 

   # WARN: order of these includes is important.
   include_once "tools.php"; 
   include_once "internal_config.inc.php";
   include_once "mysql_connect.inc.php";
   include_once "constants.php";
?>

<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>

<head>
<title>CoDev TimeTracking</title>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>

<?php 
   echo "<link rel='shortcut icon' href='".getServerRootURL()."/images/favicon.ico' />\n";
   echo "<link href='".getServerRootURL()."/calendar/calendar.css' rel='stylesheet' type='text/css' />\n";
   echo "<script language='javascript' src='".getServerRootURL()."/calendar/calendar.js'></script>\n";
   echo "<link href='".getServerRootURL()."/codev.css' rel='stylesheet' type='text/css' />\n";
   echo "<link href='".getServerRootURL()."/light.css' rel='stylesheet' type='text/css' media='print' />\n";
?>
</head>

<body>
<div id='header'>

<table id='header'>
<tr>
<td width=300>
<?php
   echo "<a href='".getServerRootURL()."/'><img title='$codevVersion' src='".getServerRootURL()."/images/clock_logo_06.png' /></a>";
?>
</td>
<td>
<?php
   $page_name = isset($_POST[page_name]) ? $_POST[page_name] : "";
   echo"<h1>$page_name</h1>";    
?>
</td>
<td width=300>
<?php echo "<a href='".curPageName()."?locale=fr'><img title='Francais' src='".getServerRootURL()."/images/drapeau_fr.jpg' /></a>";?>
&nbsp;
<?php echo "<a href='".curPageName()."?locale=en'><img title='English' src='".getServerRootURL()."/images/drapeau_gb.jpg' /></a>";?>
</td>
</tr>
</table>

</div>
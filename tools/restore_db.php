<?php if (!isset($_SESSION)) { session_name("codevtt"); session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
<?php /*
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
*/ ?>

<?php 
   include_once '../path.inc.php'; 
   include_once "tools.php"; 
   include_once "mysql_connect.inc.php";
?>

<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>

<head>
<title>CoDev TimeTracking</title>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'>

<?php
   $_POST[page_name] = "Restore DB"; 

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
   


<script language="JavaScript">

  function execSqlScript() {
     document.forms["form"].action.value = "execSqlScript";
     document.forms["form"].submit();
   }
  
</script>


<?php


// --------------------------------------------

function displayDBInfo() {
	global $db_mantis_host;
	global $db_mantis_user;
	global $db_mantis_database;
	
	echo "<table>\n";
	echo "<tr>\n";
	echo "<th>variable</th>\n";
   echo "<th>value</th>\n";
	echo "</tr>\n";
   echo "<tr>\n";
   echo "<td>db_mantis_host</td>\n<td>$db_mantis_host</td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<td>db_mantis_user</td>\n<td>$db_mantis_user</td>\n";
   echo "</tr>\n";
   echo "<tr>\n";
   echo "<td>db_mantis_database</td>\n<td>$db_mantis_database</td>\n";
   echo "</tr>\n";
   echo "</table>\n";
   
}

function setSqlFileForm($filename) {
   
  echo "<div class=left>";
  // Create form
  echo "<form id='form' name='form' method='post' action='restore_db.php'>\n";
  

  echo("SQL File: <input  size='100' name='filename' type='text' id='filename' value='$filename'>\n");
  echo "&nbsp;<input type=button value='Exec SQL Script' onClick='javascript: execSqlScript()'>\n";
    
  echo "<input type=hidden name=action      value=noAction>\n";
    
  echo "</form>\n";
  echo "</div>";
}




// --------------------------------------------
function execSQLscript($sqlFile) {
 
      $requetes="";
 
      $sql=file($sqlFile); 
      foreach($sql as $l){ 
         if (substr(trim($l),0,2)!="--"){ // remove comments
            $requetes .= $l;
         }
      }
 
      $reqs = split(";",$requetes);// identify single requests
      foreach($reqs as $req){
         if (!mysql_query($req) && trim($req)!="") {
            die("ERROR : ".$req." ---> ".mysql_error()); 
         }
      }
      echo "done";
}
   
   
   
   
   
   
   
// ========== MAIN =============   
   
$defaultFilename = "./bugtracker.sql";
   
$filename    = isset($_POST[filename]) ? $_POST[filename] : $defaultFilename;

echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
displayDBInfo();
echo "<br/>";
echo "<br/>";
echo "<br/>";
setSqlFileForm($filename);
echo "<br/>";
echo "<br/>";
echo "<br/>";


$action = $_POST[action];
if ("execSqlScript" == $action) {

	if ((NULL != $filename) && file_exists($filename)) {
   	
      execSQLscript($filename);
      
   } else {
      echo "ERROR: file not found: <$filename><br>\n";
   	
   }
}









?>




<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
?>

<?php
   $_POST[page_name] = "CoDev Administration"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>

<?php
   global $codevVersion;
   echo "<div align=center>"; 
   echo "<b>CoDev-TimeTracking</b> $codevVersion </br>";  
   echo "</div>"; 
?>
<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
} 
?>

<?php
   include_once 'i18n.inc.php';
   $_POST[page_name] = "CoDev Administration"; 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>

<?php
   global $codevVersion;
   echo "<div align=center>"; 
   echo "$codevVersion </br>";  
   echo "</div>"; 
?>
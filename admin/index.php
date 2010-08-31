<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
?>

<?php include '../header.inc.php'; ?>
<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>
<br/>
<h1>CoDev Administration</h1>
<?php include 'menu_admin.inc.php'; ?>


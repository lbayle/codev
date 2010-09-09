<?php if (!isset($_SESSION)) { session_start(); } ?>


<?php
/*
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
*/
?>

<?php
   $_POST[page_name] = "Centre de Documentation"; 
   include '../header.inc.php'; 
?>
<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>
<br/>
<?php include 'menu_doc.inc.php'; ?>


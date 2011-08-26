<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
/*
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
}
*/

$OpenSource_FR = "Un logiciel libre est un logiciel qui respecte le droit de l’Homme : liberté, égalité, fraternité. Liberté car l’utilisateur de ce programme est libre. Egalite parce ce que personne n’a de pouvoir sur personne par le logiciel libre. Et fraternité, car nous encourageons la coopération entre les utilisateurs."

?>

<?php
   $_POST['page_name'] = "Centre de Documentation";
   include 'header.inc.php';
?>
<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_doc.inc.php'; ?>


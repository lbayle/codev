<?php
include_once('../include/session.inc.php');

include_once '../path.inc.php';

/*
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
}
*/

$OpenSource_FR = "Un logiciel libre est un logiciel qui respecte le droit de l’Homme : liberté, égalité, fraternité. Liberté car l’utilisateur de ce programme est libre. Egalite parce ce que personne n’a de pouvoir sur personne par le logiciel libre. Et fraternité, car nous encourageons la coopération entre les utilisateurs.";

$page_name = "Centre de Documentation";
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
?>
<br/>
<?php include 'menu_doc.inc.php'; ?>


<?php
echo "<h2>".T_("Reporter un bug / Demander un &eacute;volution")."</h2>";
echo T_("Pour reporter des bug sur CodevTT ou demander des &eacute;volutions merci de vous rendre sur:");
#echo '<br>';

echo "<table class='invisible'>\n";
echo "<tr>";
echo "<td><h3><a href='http://codevtt.org/mantis' title=''>http://codevtt.org/mantis</a></h3><td>";
echo "</tr>";
echo "<tr>";
echo "<td>".T_("User").': </td><td>codevtt</td>';
echo "</tr>";
echo "<tr>";
echo "<td>".T_("Password").': </td><td>'.T_('(none)').'</td><br>';
echo "</tr>";
echo "</table>";
echo '<br>';
echo '<br>';
echo T_("N'oubliez pas de mentionner vos coordon&eacute;es pour tout retour.");

?>

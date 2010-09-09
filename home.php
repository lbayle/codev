<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
   $_POST[page_name] = "Bienvenu sur le serveur CoDev"; 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<div id="homepage_list"  class="left">

<br/>
<br/>
<br/>

<ul>
   <li>
      <?php echo "<a href='http://".$_SERVER['HTTP_HOST']."/mantis.php'>Mantis</a>";?>
   </li>
   <br/>
   <li>
      <?php echo "<a href='".getServerRootURL()."/timetracking/time_tracking.php'>Saisie des CRA</a>";?>
   </li>
   <br/>
   <li>
      <?php echo "<a href='".getServerRootURL()."/timetracking/holidays_report.php'>Affichage des cong&eacute;s</a>";?>
   </li>
</ul>
 
<br/>
<br/>
<br/>

<ul>
   <li>
      <?php echo "<a href='".getServerRootURL()."/reports'>Suivi des fiches Mantis</a>";?>
   </li>
   <br/>
   <li>
      <?php echo "<a href='".getServerRootURL()."/timetracking/week_activity_report.php'>Activit&eacute; hebdomadaire</a>";?>
   </li>
   <br/>
   <li>
      <?php echo "<a href='".getServerRootURL()."/timetracking/time_tracking_report.php'>Indicateurs de production</a>";?>
   </li>
   <br/>
   <li>
      <?php echo "<a href='".getServerRootURL()."/reports/issue_info.php'>Informations sur une fiche</a>";?>
   </li>
</ul>




</div>

<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>

<?php include 'footer.inc.php'; ?>

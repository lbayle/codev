<?php
   include_once "tools.php";
   include_once 'i18n.inc.php';
?>

<div id="menu">

<?php 
echo "
<table class='menu'>
   <tr>
      <td class='menu'><a href='".getServerRootURL()."/index.php' title='Acceuil'>".T_("Home")."</a></td>

      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis.php' title='MantisBT'>Mantis</a></td>

      <td>
      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>".T_("Time Tracking")."</a>
      |
      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='Vacances'>".T_("Holidays Reports")."</a>
      |
      <a href='".getServerRootURL()."/tools/check.php' title='Consistency Check'>".T_("Check")."</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/reports/issue_info.php' title='Activit&eacute; par t&acirc;che'>".T_("Task info")."</a>
      |
      <a href='".getServerRootURL()."/reports/' title=''>".T_("Mantis Reports")."</a>
      |
      <a href='".getServerRootURL()."/reports/productivity_report.php' title='Indicateurs de production'>".T_("Productivity Reports")."</a>
      |
      <a href='".getServerRootURL()."/timetracking/week_activity_report.php' title='Activit&eacute; hebdo'>".T_("Weekly activities")."</a>
      |
      <a href='".getServerRootURL()."/reports/proj_management_report.php' title='Raports pour la gestion de projet'>".T_("Export to CSV")."</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/doc/index.php' title='Documentation'>Doc</a>
      |
      <a href='".getServerRootURL()."/admin/index.php' title='CoDev Administration'>Admin</a>
      </td>
   </tr>
</table>"
?>
<br/>
<br/>
</div>

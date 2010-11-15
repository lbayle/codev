<?php include_once "tools.php"; ?>

<div id="menu">

<?php echo "   
<table class='menu'>
   <tr>
      <td class='menu'><a href='".getServerRootURL()."/home.php' title='Acceuil'>Home</a></td>
      
      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis.php' title='MantisBT'>Mantis</a></td>
      
      <td>
      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>Time Tracking</a>
      |
      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='Vacances'>Holidays Reports</a>
      </td>
      <td><a href='".getServerRootURL()."/reports/' title=''>Mantis Reports</a>
      |
      <a href='".getServerRootURL()."/timetracking/productivity_report.php' title='Indicateurs de production'>Productivity Reports</a>
      |
      <a href='".getServerRootURL()."/timetracking/proj_management_report.php' title='Indicateurs de gestion de projet'>Project Management</a>
      |
      <a href='".getServerRootURL()."/timetracking/week_activity_report.php' title='Activit&eacute; hebdo'>Weekly activities</a>
      |
      <a href='".getServerRootURL()."/reports/issue_info.php' title='Activit&eacute; par t&acirc;che'>Task tracking</a>
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

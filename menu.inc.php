<?php include_once "tools.php"; ?>

<div id="menu">

<?php echo "   
<table class='menu'>
   <tr>
      <td class='menu'><a href='".getServerRootURL()."/index.php' title='Acceuil'>Home</a></td>
      
      <td><a href='http://".$_SERVER['HTTP_HOST']."/mantis.php' title='MantisBT'>Mantis</a></td>
      
      <td>
      <a href='".getServerRootURL()."/timetracking/time_tracking.php' title=''>Time Tracking</a>
      |
      <a href='".getServerRootURL()."/timetracking/holidays_report.php' title='Vacances'>Holidays Reports</a>
      |
      <a href='".getServerRootURL()."/tools/check.php' title='Consistency Check'>Check</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/reports/issue_info.php' title='Activit&eacute; par t&acirc;che'>Task info</a>
      |
      <a href='".getServerRootURL()."/reports/' title=''>Mantis Reports</a>
      |
      <a href='".getServerRootURL()."/reports/productivity_report.php' title='Indicateurs de production'>Productivity Reports</a>
      |
      <a href='".getServerRootURL()."/timetracking/week_activity_report.php' title='Activit&eacute; hebdo'>Weekly activities</a>
      |
      <a href='".getServerRootURL()."/reports/proj_management_report.php' title='Raports pour la gestion de projet'>Export to CSV</a>
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

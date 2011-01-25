<?php 
   include_once 'i18n.inc.php';
   include_once "../tools.php";
?>

<div id="menu">

<?php 

echo "   
<table>
   <tr>
      <td><a href='".getServerRootURL()."/admin/create_team.php' title=''>".T_("Create Team")."</a>
      |
      <a href='".getServerRootURL()."/admin/edit_team.php' title=''>".T_("Edit Team")."</a>
      |
      <a href='".getServerRootURL()."/admin/edit_jobs.php' title=''>".T_("Edit Jobs")."</a>
      </td>
      <td>
      <a href='".getServerRootURL()."/doc/codev_adminguide.html' title='Aide Admin'>".T_("Admin Guide")."</a>
      </td>
   </tr>
</table>"
?>      
<br/>
<br/>
</div>

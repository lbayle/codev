<?php 
   include_once '../path.inc.php';
   include_once 'i18n.inc.php';
   include_once "tools.php";
?>



<div id="menu">

<?php 

   
echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/admin/create_team.php'>".T_("Create Team")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_team.php'>".T_("Edit Team")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_jobs.php'>".T_("Edit Jobs")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/admin/edit_holidays.php'>".T_("Edit Holidays")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/codev_adminguide.html'>".T_("Admin Guide")."</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>      
<br/>
<br/>
</div>

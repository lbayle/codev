<?php 
   include_once '../path.inc.php';
   include_once 'i18n.inc.php';
   include_once "tools.php";
?>



<div id="menu">

<?php 

 
echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/timetracking/team_activity_report.php'>".T_("Team Activity")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/timetracking/project_activity_report.php'>".T_("Projects Activity")."</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>      
<br/>
<br/>
</div>

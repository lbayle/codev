<?php 
   include_once '../path.inc.php';
   include_once 'i18n.inc.php';
   include_once "tools.php";
?>



<div id="menu">

<?php 

 
echo "<table>\n";
echo "   <tr>\n";
echo "      <td><a href='".getServerRootURL()."/reports/export_csv_weekly.php'>".T_("Weekly")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/reports/export_csv_monthly.php'>".T_("Monthly")."</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>      
<br/>
<br/>
</div>

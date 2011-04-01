<?php 
   include_once '../path.inc.php';
   include_once 'i18n.inc.php';
   include_once "tools.php";
?>



<div id="menu">

<?php 

echo "   
<table>
   <tr>
      <td><a href='".getServerRootURL()."/reports/export_csv_weekly.php' title=''>".T_("Weekly")."</a>
      |
      <a href='".getServerRootURL()."/reports/export_csv_monthly.php' title=''>".T_("Monthly")."</a>
      </td>
   </tr>
</table>"
?>      
<br/>
<br/>
</div>

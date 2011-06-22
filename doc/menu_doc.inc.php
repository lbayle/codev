
<?php include_once '../path.inc.php'; ?>

<?php include_once "tools.php"; ?>

<div id="menu">

<?php 
echo "<table>\n";
echo "   <tr>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/AOI-DOC-Cycle_dev-v2.1.png'>".T_("Cycle flow")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/doc/mantis_userguide.html'>".T_("Mantis Status Transistions")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/doc/indicateurs_de_production.html'>".T_("Productivity Reports")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/system_allocations.PNG' >".T_("FDJ System Allocation")."</a>\n";
echo "      |\n";
echo "      <a href='".getServerRootURL()."/doc/prod_libs.svg' >".T_("FDJ Libs")."</a>\n";
echo "      </td>\n";
echo "      <td>\n";
echo "      <a href='".getServerRootURL()."/doc/codev_adminguide.html' >".T_("CoDev admin guide")."</a>\n";
echo "      |\n";
#echo "      <a href='http://".$_SERVER['HTTP_HOST']."/mantis/doc/en/administration_guide.html'>Mantis admin guide</a>\n";
echo "      <a href='file:///Z:/mantis/doc/en/administration_guide.html'>Mantis admin guide</a>\n";
echo "      </td>\n";
echo "   </tr>\n";
echo "</table>\n";
?>      
<br/>
<br/>
</div>


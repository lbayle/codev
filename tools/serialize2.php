<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';

   $_POST['page_name'] = T_("Tools: serialize");
   include 'header.inc.php';
?>


<?php
include_once 'install.class.php';


/**
 * local test func
 */
function addCustomMenuItem($serialized, $name, $url) {

    $pos = '10'; // invariant

    if ((NULL != $serialized) && ("" != $serialized)) {
		$menuItems = unserialize($serialized);
    } else {
    	$menuItems = array();
    }
	$menuItems[] = array($name, $pos, $url);

    $newStr = serialize($menuItems);

	return $newStr;
}



#==== MAIN =====

echo "<br/>\n";
echo "<br/>\n";
echo "<br/>\n";

$main_menu_custom_options = 'a:1:{i:0;a:3:{i:0;s:5:"CoDev";i:1;i:10;i:2;s:18:"../codev/index.php";}}';

#$string = 'a:14:{i:0;s:9:"selection";i:1;s:4:"edit";i:2;s:8:"priority";i:3;s:2:"id";i:4;s:1:"0";i:5;s:17:"sponsorship_total";i:6;s:14:"bugnotes_count";i:7;s:10:"attachment";i:8;s:11:"category_id";i:9;s:14:"target_version";i:10;s:8:"severity";i:11;s:6:"status";i:12;s:12:"last_updated";i:13;s:7:"summary";}';
#$string= 'a:13:{i:0;s:9:"selection";i:1;s:4:"edit";i:2;s:8:"priority";i:3;s:2:"id";i:4;s:10:"project_id";i:5;s:17:"sponsorship_total";i:6;s:14:"bugnotes_count";i:7;s:10:"attachment";i:8;s:11:"category_id";i:9;s:8:"severity";i:10;s:6:"status";i:11;s:12:"last_updated";i:12;s:7:"summary";}';
#$string = 'a:13:{i:0;s:9:"selection";i:1;s:4:"edit";i:2;s:8:"priority";i:3;s:2:"id";i:4;s:2:"39";i:5;s:17:"sponsorship_total";i:6;s:14:"bugnotes_count";i:7;s:10:"attachment";i:8;s:11:"category_id";i:9;s:8:"severity";i:10;s:6:"status";i:11;s:12:"last_updated";i:12;s:7:"summary";}';
$string='a:13:{i:0;s:9:"selection";i:1;s:4:"edit";i:2;s:8:"priority";i:3;s:2:"id";i:4;s:2:"33";i:5;s:17:"sponsorship_total";i:6;s:14:"bugnotes_count";i:7;s:10:"attachment";i:8;s:11:"category_id";i:9;s:8:"severity";i:10;s:6:"status";i:11;s:12:"last_updated";i:12;s:7:"summary";}';

echo "-- Create new Menu with toto -> http://toto.fr</br>";
$serialized = addCustomMenuItem(NULL, 'toto', 'http://toto.fr');
echo "serialized=$serialized<br/>\n";
echo "<br/>\n";

echo "-- add new item to this Menu titi -> http://titi.fr</br>";
$serialized = addCustomMenuItem($serialized, 'titi', 'http://titi.fr');
echo "serialized=$serialized<br/>\n";
echo "<br/>\n";

#$serialized = addCustomMenuItem($serialized, 'CodevTT', '../codev/index.php');
#echo "serialized=$serialized<br/>\n";


$tok = strtok($_SERVER["SCRIPT_NAME"], "/");
echo "-- add new item to this Menu CodevTT -> (link to CodevTT)</br>";
#echo "../".$tok."/index.php<br/>\n";
$serialized = addCustomMenuItem($serialized, 'CodevTT', '../'.$tok.'/index.php');
echo "serialized=$serialized<br/>\n";

echo "<br/>\n";
echo "<br/>\n";
echo "----------</br>";
echo "<br/>\n";
echo "<br/>\n";


echo "previous: $string</br>";
$arrayItems = unserialize($string);
#print_r($arrayItems);
#$arrayItems[4] = "custom_ADEL"; Reference externe
$arrayItems[4] = "custom_Reference externe"; 
$newStr = serialize($arrayItems);
echo "new: $newStr</br>";

?>

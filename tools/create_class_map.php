<?php

require_once('../lib/dynamic_autoloader/ClassFileMapFactory.php');
require_once('../lib/dynamic_autoloader/ClassFileMapAutoloader.php');

// Set up the include path
define('BASE_PATH', realpath(dirname(__FILE__).'/..'));
set_include_path(BASE_PATH.PATH_SEPARATOR.get_include_path());

$lib_class_map = ClassFileMapFactory::generate("../");
$_autoloader = new ClassFileMapAutoloader();
$_autoloader->addClassFileMap($lib_class_map);

$data = serialize($_autoloader);
if(file_put_contents("../classmap.ser",$data)) {
   echo "OK<br>";
} else {
   echo "FAILED<br>";
}

echo "Found ".count($lib_class_map->getClassMap())." classes :<br>";
echo '<table border="1"><thead><tr><th align="left">Class</th><th align="left">Path</th></tr></thead><tbody>';
foreach ($lib_class_map->getClassMap() as $key => $file) {
   echo "<tr><td>$key</td><td>$file</td></tr>";
}
echo "</tbody></table>\n";


?>
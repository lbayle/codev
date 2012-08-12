<?php

require('../path.inc.php');

require_once('lib/dynamic_autoloader/ClassFileMapFactory.php');
require_once('lib/dynamic_autoloader/ClassFileMapAutoloader.php');

$lib_class_map = ClassFileMapFactory::generate("../");
$_autoloader = new ClassFileMapAutoloader();
$_autoloader->addClassFileMap($lib_class_map);
$data = serialize($_autoloader);
if(file_put_contents("../classmap.ser",$data)) {
   echo "OK";
} else {
   echo "FAILED";
}

?>
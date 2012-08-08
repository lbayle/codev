<?php

require('lib/dynamic_autoloader/ClassFileMapFactory.php');
require('lib/dynamic_autoloader/ClassFileMapAutoloader.php');

$lib_class_map = ClassFileMapFactory::generate("../classes");
$_autoloader = new ClassFileMapAutoloader();
$_autoloader->addClassFileMap($lib_class_map);
$data = serialize($_autoloader);
if(file_put_contents("../classmap.ser",$data)) {
   echo "OK";
} else {
   echo "FAILED";
}

?>
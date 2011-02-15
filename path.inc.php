<?php

define ( 'BASE_PATH' , realpath( dirname(__FILE__) ) );

$codevPathInclude  = BASE_PATH . DIRECTORY_SEPARATOR . 'include';
$codevPathClasses  = BASE_PATH . DIRECTORY_SEPARATOR . 'classes';
$codevPathCalendar = BASE_PATH . DIRECTORY_SEPARATOR. 'calendar' . DIRECTORY_SEPARATOR . 'classes';
$codevPathi18n     = BASE_PATH . DIRECTORY_SEPARATOR . 'i18n';

$path = array(
   BASE_PATH,
   $codevPathInclude,
   $codevPathClasses,
   $codevPathCalendar,
   $codevPathi18n,
   get_include_path()
   );

$strPath=implode( PATH_SEPARATOR, $path );
set_include_path( $strPath );

# warn: i don't know why but, an 'echo' here changes the CSS of the page...
#echo "DEBUG PHP include_path : ".get_include_path()." <br/>";

?>
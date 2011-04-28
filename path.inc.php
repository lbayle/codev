<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?php

define ( 'BASE_PATH' , realpath( dirname(__FILE__) ) );

$codevPathInclude  = BASE_PATH . DIRECTORY_SEPARATOR . 'include';
$codevPathClasses  = BASE_PATH . DIRECTORY_SEPARATOR . 'classes';
$codevPathCalendar = BASE_PATH . DIRECTORY_SEPARATOR . 'calendar' . DIRECTORY_SEPARATOR . 'classes';
$codevPathi18n     = BASE_PATH . DIRECTORY_SEPARATOR . 'i18n';
$codevPathGraphs   = BASE_PATH . DIRECTORY_SEPARATOR . 'graphs';

$path = array(
   BASE_PATH,
   $codevPathInclude,
   $codevPathClasses,
   $codevPathCalendar,
   $codevPathi18n,
   $codevPathGraphs,
   get_include_path()
   );

$strPath=implode( PATH_SEPARATOR, $path );
set_include_path( $strPath );


// -----------------


// example: http://127.0.0.1/codev/
// example: http://55.7.137.27/louis/codev/
function getServerRootURL() {
   
   #if (isset($_GET['debug'])) {
   #foreach($_SERVER as $key => $value) {
   #   echo "_SERVER key=$key val=$value<br/>";
   #}
   
   $rootURL = "http://".$_SERVER['HTTP_HOST'].substr( $_SERVER['PHP_SELF'], 0 , strrpos( $_SERVER['PHP_SELF'], '/') );
   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   $rootURL = str_replace("/classes", "", $rootURL);   
   $rootURL = str_replace("/timetracking", "", $rootURL);   
   $rootURL = str_replace("/reports", "", $rootURL);   
   $rootURL = str_replace("/doc", "", $rootURL);   
   $rootURL = str_replace("/images", "", $rootURL);   
   $rootURL = str_replace("/calendar", "", $rootURL);   
   $rootURL = str_replace("/admin", "", $rootURL);   
   $rootURL = str_replace("/tools", "", $rootURL);   
   $rootURL = str_replace("/i18n", "", $rootURL);   
   $rootURL = str_replace("/graphs", "", $rootURL);   
   
   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   return $rootURL;
}





# warn: i don't know why but, an 'echo' here changes the CSS of the page...
#echo "DEBUG PHP include_path : ".get_include_path()." <br/>";

?>
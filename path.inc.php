<?php
/*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

define('BASE_PATH', realpath(dirname(__FILE__)));

$codevPathClasses = BASE_PATH . DIRECTORY_SEPARATOR . 'classes';
$codevPathGraphs = BASE_PATH . DIRECTORY_SEPARATOR . 'graphs';
$codevPathInstall = BASE_PATH . DIRECTORY_SEPARATOR . 'install';
$codevPathJPGraphs = BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jpgraph' . DIRECTORY_SEPARATOR . 'src';
$codevPathSmarty = BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Smarty';
$codevPathOdtPhp = BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'odtphp' . DIRECTORY_SEPARATOR . 'library';
$codevPathTests = BASE_PATH . DIRECTORY_SEPARATOR . 'tests';
$codevPathImport = BASE_PATH . DIRECTORY_SEPARATOR . 'import';
$codevIndicators = BASE_PATH . DIRECTORY_SEPARATOR . 'indicator_plugins';

$path = array(
   BASE_PATH,
   $codevPathClasses,
   $codevPathGraphs,
   $codevPathInstall,
   $codevPathJPGraphs,
   $codevPathSmarty,
   $codevPathOdtPhp,
   $codevPathTests,
   $codevPathImport,
   $codevIndicators,
   get_include_path()
);

$strPath=implode( PATH_SEPARATOR, $path );
set_include_path( $strPath );

// example: http://127.0.0.1/codev/
// example: http://55.7.137.27/louis/codev/
function getServerRootURL() {

   #if (isset($_GET['debug'])) {
   #foreach($_SERVER as $key => $value) {
   #   echo "_SERVER key=$key val=$value<br/>";
   #}}

   $protocol = ($_SERVER['HTTPS'] == "on") ? "https" : "http";
   #$protocol = "http";

   $rootURL = "$protocol://".$_SERVER['HTTP_HOST'].substr( $_SERVER['PHP_SELF'], 0 , strrpos( $_SERVER['PHP_SELF'], '/') );
   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   $rootURL = str_replace("/classes", "", $rootURL);
   $rootURL = str_replace("/timetracking", "", $rootURL);
   $rootURL = str_replace("/reports", "", $rootURL);
   $rootURL = str_replace("/doc", "", $rootURL);
   $rootURL = str_replace("/images", "", $rootURL);
   $rootURL = str_replace("/admin", "", $rootURL);
   $rootURL = str_replace("/tools", "", $rootURL);
   $rootURL = str_replace("/i18n", "", $rootURL);
   $rootURL = str_replace("/graphs", "", $rootURL);
   $rootURL = str_replace("/install", "", $rootURL);
   $rootURL = str_replace("/tests", "", $rootURL);
   $rootURL = str_replace("/import", "", $rootURL);
   $rootURL = str_replace("/blog", "", $rootURL);
   $rootURL = str_replace("/management", "", $rootURL);
   $rootURL = str_replace("/indicator_plugins", "", $rootURL);

   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   return $rootURL;
}

#echo "DEBUG PHP include_path : ".get_include_path()." <br/>";

?>

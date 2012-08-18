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
$codevPathInstall = BASE_PATH . DIRECTORY_SEPARATOR . 'install';
$codevPathJPGraphs = BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jpgraph' . DIRECTORY_SEPARATOR . 'src';
$codevPathOdtPhp = BASE_PATH . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'odtphp' . DIRECTORY_SEPARATOR . 'library';
$codevPathTests = BASE_PATH . DIRECTORY_SEPARATOR . 'tests';
$codevPathImport = BASE_PATH . DIRECTORY_SEPARATOR . 'import';

$path = array(
   BASE_PATH,
   $codevPathClasses,
   $codevPathInstall,
   $codevPathJPGraphs,
   $codevPathOdtPhp,
   $codevPathTests,
   $codevPathImport,
   get_include_path()
);

$strPath=implode( PATH_SEPARATOR, $path );
set_include_path( $strPath );

#echo "DEBUG PHP include_path : ".get_include_path()." <br/>";

?>

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

   include_once "mysql_config.inc.php";

   #echo "db_mantis_database $db_mantis_database<br/>";
   
   $bugtracker_link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect to database: ". mysql_error());
   
   mysql_select_db($db_mantis_database) or die("Could not select database: ". mysql_error());

   mysql_query('SET CHARACTER SET utf8');
   mysql_query('SET NAMES utf8');

?>

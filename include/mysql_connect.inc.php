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

include_once('include/mysql_config.inc.php');
include_once('classes/sqlwrapper.class.php');

$connection = SqlWrapper::createInstance(DatabaseInfo::$db_mantis_host, DatabaseInfo::$db_mantis_user,
                                         DatabaseInfo::$db_mantis_pass, DatabaseInfo::$db_mantis_database);
$connection->sql_query('SET CHARACTER SET utf8');
$connection->sql_query('SET NAMES utf8');
$bugtracker_link = $connection->getLink();

?>

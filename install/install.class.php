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

abstract class Install {

   const FILENAME_MYSQL_CONFIG = "../include/mysql_config.inc.php";
   const FILENAME_CONSTANTS = "../constants.php"; // DEPRECATED since 0.99.18
   const FILENAME_CUSTOM_CONSTANTS_CODEVTT = "../install/custom_constants_inc.codevtt.php";
   const FILENAME_CUSTOM_STRINGS_CODEVTT = "../install/custom_strings_inc.codevtt.php";
   const FILENAME_CUSTOM_RELATIONSHIPS_CODEVTT = "../install/custom_relationships_inc.codevtt.php";
   const FILENAME_GREASEMONKEY_SAMPLE = "../tools/mantis_monkey.user.js.sample";
   const FILENAME_GREASEMONKEY = "../mantis_monkey.user.js";
   const FILENAME_TABLES = "codevtt_tables.sql";
   const FILENAME_PROCEDURES = "codevtt_procedures.sql";

}

?>

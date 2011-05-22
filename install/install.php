<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
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
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install");
   include 'install_header.inc.php';

   include 'install_menu.inc.php';
?>

<?php


/*
 *
 *
 * create MySQL 'codev' user with access SELECT, INSERT, UPDATE, DELETE, CREATE
 *
 * Step 1
 *
 * - [user] create DB config file & test connection        OK
 * - [auto] create DB tables (from SQL file)               OK
 * - [auto] create Mantis codev user (if necessary ?)
 * - [auto] create admin team & add to codev_config_table  OK
 *
 * Step 2
 *
 * - [auto] create custom fields & add to codev_config_table  OK
 * - [auto] create CodevMetaProject (optional ?)
 * - [user] update codev_config_table with user prefs
 * - [user]
 *
 * - [user] create CommonSideTasks Project                 OK
 * - [auto] asign N/A job to commonSideTasks               OK
 * - [user] create default side tasks
 * - [user] config astreintes

 * Step 3
 * - [user] create jobs
 * - [user] config support job
 * - [user] add custom fields to existing projects
 */


?>
<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

# This sample file contains the essential files that you MUST
# configure to your specific settings.  You may override settings
# from config_defaults_inc.php by assigning new values in this file

# Rename this file to config_inc.php after configuration.

# In general the value OFF means the feature is disabled and ON means the
# feature is enabled.  Any other cases will have an explanation.

# Look in http://www.mantisbt.org/docs/ or config_defaults_inc.php for more
# detailed comments.

# --- Database Configuration ---
$g_hostname = 'localhost';
$g_db_type = 'mysql';
$g_database_name = 'bugtracker';
$g_db_username = 'root';
$g_db_password = '';

# --- Branding ---
$g_window_title			= 'CoDev Mantis';
$g_logo_image			  = 'images/mantis_logo.gif';
$g_favicon_image		= 'images/favicon.ico';

# --- Real names ---
$g_show_realname = OFF;
$g_show_user_realname_threshold = NOBODY;	# Set to access level (e.g. VIEWER, REPORTER, DEVELOPER, MANAGER, etc)

# --- Anonymous Access / Signup ---
$g_allow_signup	= ON;
$g_allow_anonymous_login = OFF;
$g_anonymous_account = '';

# --- Email Configuration ---
$g_enable_email_notification = OFF;
$g_phpMailer_method		  = PHPMAILER_METHOD_SMTP; # PHPMAILER_METHOD_MAIL; # or PHPMAILER_METHOD_SMTP, PHPMAILER_METHOD_SENDMAIL
$g_smtp_host			      = 'smtp.mail.atosorigin.com';			# used with PHPMAILER_METHOD_SMTP
$g_smtp_port            = 465;
$g_smtp_connection_mode = 'ssl';
$g_smtp_username	   	  = 'louis.bayle';					# used with PHPMAILER_METHOD_SMTP
$g_smtp_password		    = 'xxxx';					# used with PHPMAILER_METHOD_SMTP
$g_from_name			      = 'CoDev Mantis';
$g_from_email           = 'noreply@codevMantis.com';	# the "From: " field in emails
#$g_administrator_email  = 'administrator@example.com';
#$g_webmaster_email      = 'webmaster@example.com';
$g_return_path_email    = 'lbayle.work@gmail.com';	# the return address for bounced mail
#$g_email_receive_own	= OFF;
#$g_email_send_using_cronjob = OFF;

# --- Attachments / File Uploads ---
$g_allow_file_upload	= ON;
$g_file_upload_method	= DATABASE; # or DISK
$g_absolute_path_default_upload_folder = ''; # used with DISK, must contain trailing \ or /.
$g_max_file_size		= 9000000;	# in bytes
$g_preview_attachments_inline_max_size = 256 * 1024;
$g_allowed_files		= '';		# extensions comma separated, e.g. 'php,html,java,exe,pl'
$g_disallowed_files		= '';		# extensions comma separated

# --- Others (cf. config_defaults_inc.php) ---
$g_default_home_page = 'my_view_page.php';	# Set to name of page to go to after login
$g_show_footer_menu = ON;
$g_show_project_menu_bar = ON;
$g_news_enabled = ON;
#$g_status_colors = ...
#$g_status_enum_string = ...
$g_enable_eta = ON;
$g_enable_projection = OFF;
$g_eta_enum_string = '10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days';
$g_time_tracking_enabled = OFF;
$g_time_tracking_stopwatch = OFF;
$g_time_tracking_with_billing = OFF;

# ========= LoB CoDev config ==========
$g_status_enum_string	= '10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,90:closed';

$g_status_colors['accepted'] = '#FF6A6E';
$g_status_colors['analyzed'] = '#fff494';
$g_status_colors['openned']  = '#c2dfff';
$g_status_colors['deferred'] = '#8080ff';


?>

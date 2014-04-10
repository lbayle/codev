<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.


# --- Database Configuration ---
$g_hostname      = 'localhost';
$g_db_username   = 'mantis';
$g_db_password   = 'secret';
$g_database_name = 'bugtracker';
$g_db_type       = 'mysql';

# --- Anonymous Access / Signup ---
$g_allow_signup			= ON;
$g_allow_anonymous_login	= OFF;
$g_anonymous_account		= '';

# --- Email Configuration ---
$g_phpMailer_method = PHPMAILER_METHOD_SMTP;
$g_smtp_host = '00.00.00.00';
$g_administrator_email = 'lbayle.work@gmail.com';
$g_webmaster_email = 'lbayle.work@gmail.com';
$g_from_name = 'Mantis - CodevTT';
$g_from_email = 'noreply@gmail.com';
$g_return_path_email = 'lbayle.work@gmail.com';
$g_email_receive_own = OFF;       #  defines whether users should receive emails for their own actions
$g_email_send_using_cronjob = ON; # Disables sending of emails as soon as an action is performed. Emails are instead queued and must be sent by running scripts/send_emails.php periodically

$g_send_reset_password = ON;
$g_validate_email = ON;

# email cronjob
# crontab -e
#   */5 * * * * php /var/www/html/mantis/scripts/send_emails.php

# --- Email notifications
# http://www.mantisbt.org/docs/master-1.2.x/en/administration_guide.html#ADMIN.CONFIG.EMAIL

$g_enable_email_notification = ON;

$g_default_notify_flags = array(
  'reporter'      => OFF,
  'handler'       => OFF,
  'monitor'       => OFF,
  'bugnotes'      => OFF,
  'explicit'      => OFF,
  'threshold_min' => NOBODY,
  'threshold_max' => NOBODY);

$g_notify_flags = array (
  'new'          => array ('handler' => 1, 'threshold_min' => MANAGER),  // a new bug has been added
  'owner'        => array ('handler' => 1, 'threshold_min' => MANAGER),  // the bug has been assigned a new owner
  'reopened'     => array ('threshold_min' => MANAGER), // the bug has been reopened
#  'deleted'      => array ('monitor' => 1,), // a bug has been deleted
#  'bugnote'      => array ('threshold_min' => MANAGER), // a bugnote has been added to a bug
#  'sponsor': the sponsorship for the bug has changed (added, deleted or updated)
#  'relation': a relationship for the bug has changed (added, deleted or updated)
#  'monitor': a user is added to the monitor list. 

  // STATUS
#  'feedback'     => array ('handler' => 1, 'monitor' => 1),
#  'acknowledged' => array ('handler' => 1, 'monitor' => 1),
#  'analyzed'     => array ('handler' => 1, 'monitor' => 1),
#  'open'         => array ('handler' => 1, 'monitor' => 1),
  'resolved'     => array ('reporter' => 1, 'monitor' => 1, 'threshold_min' => REPORTER, 'threshold_max' => MANAGER),
#  'validated'    => array ('handler' => 1, 'monitor' => 1),
#  'delivered'    => array ('handler' => 1, 'monitor' => 1),
#  'closed'       => array ('handler' => 1, 'monitor' => 1, 'threshold_max' => NOBODY),
);


# --- Attachments / File Uploads ---
# $g_allow_file_upload	= ON;
# $g_file_upload_method	= DATABASE; # or DISK
# $g_absolute_path_default_upload_folder = ''; # used with DISK, must contain trailing \ or /.
# $g_max_file_size		= 5000000;	# in bytes
# $g_preview_attachments_inline_max_size = 256 * 1024;
# $g_allowed_files		= '';		# extensions comma separated, e.g. 'php,html,java,exe,pl'
# $g_disallowed_files		= '';		# extensions comma separated

# --- Branding ---
$g_window_title			= 'Mantis - CodevTT';
# $g_logo_image			= 'images/mantis_logo.png';
# $g_favicon_image		= 'images/favicon.ico';

# --- Real names ---
# $g_show_realname = OFF;
# $g_show_user_realname_threshold = NOBODY;	# Set to access level (e.g. VIEWER, REPORTER, DEVELOPER, MANAGER, etc)

# --- Others ---
#$g_default_home_page = 'my_view_page.php';	# Set the name of the page to go to after login
$g_default_home_page = 'view_all_bug_page.php';

# --- Configuration du langage et du format de la date ---
$g_default_language='french';
$g_short_date_format='d-m-Y';
$g_normal_date_format='d-m-Y H:i';
$g_complete_date_format='d-m-Y H:i';

# --- customize workflow ---
# see also custom_constant_inc.php and custom_strings_inc.php
$g_status_enum_string = '10:new,20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved,82:validated,85:delivered,90:closed';

$g_status_colors['analyzed']  = '#fff494';
$g_status_colors['open']   = '#c2dfff';
$g_status_colors['validated'] = '#9EDB63';
$g_status_colors['delivered'] = '#61DB63';

$g_status_enum_workflow[NEW_]         ='20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[FEEDBACK]     ='30:acknowledged,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[ACKNOWLEDGED] ='20:feedback,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[ANALYZED]     ='20:feedback,50:open,80:resolved';
$g_status_enum_workflow[OPEN_]         ='20:feedback,80:resolved';
$g_status_enum_workflow[RESOLVED]     ='20:feedback,82:validated,85:delivered,90:closed';
$g_status_enum_workflow[VALIDATED]    ='20:feedback,85:delivered,90:closed';
$g_status_enum_workflow[DELIVERED]    ='20:feedback,90:closed';
$g_status_enum_workflow[CLOSED]       ='20:feedback';
# --- END customize workflow ---

# --- remove unused fields ---
# http://www.mantisbt.org/forums/viewtopic.php?f=4&t=15606
# extract from config_deafults_inc.php

/**
    * An array of the fields to show on the bug report page.
    *
    * The following fields can not be included:
    * id, project, date_submitted, last_updated, status,
    * resolution, tags, fixed_in_version, projection, eta,
    * reporter.
    *
    * The following fields must be included:
    * category_id, summary, description.
    *
    * To overload this setting per project, then the settings must be included in the database through
    * the generic configuration form.
    *
    * @global array $g_bug_report_page_fields
    */
   $g_bug_report_page_fields = array(
      'category_id',
      'view_state',
      'handler',
      'priority',
      'severity',
      'reproducibility',
#      'platform',
#      'os',
#      'os_version',
      'product_version',
      'product_build',
      'target_version',
      'summary',
      'description',
      'additional_info',
      'steps_to_reproduce',
      'attachments',
      'due_date',
   );

   /**
    * An array of the fields to show on the bug view page.
    *
    * To overload this setting per project, then the settings must be included in the database through
    * the generic configuration form.
    *
    * @global array $g_bug_view_page_fields
    */
   $g_bug_view_page_fields = array (
      'id',
      'project',
      'category_id',
      'view_state',
      'date_submitted',
      'last_updated',
      'reporter',
      'handler',
      'priority',
      'severity',
      'reproducibility',
      'status',
      'resolution',
      'projection',
#      'eta',
#      'platform',
#      'os',
#      'os_version',
      'product_version',
      'product_build',
      'target_version',
      'fixed_in_version',
      'summary',
      'description',
      'additional_info',
      'steps_to_reproduce',
      'tags',
      'attachments',
      'due_date',
   );


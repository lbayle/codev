<?php
$g_hostname = 'localhost';
$g_db_type = 'mysql';
$g_database_name = 'bugtracker';
$g_db_username = 'mantis';
$g_db_password = 'secret';

# --- Branding ---
$g_window_title      = 'CodevTT';
$g_logo_image        = 'images/mantis_logo.gif';
$g_favicon_image     = 'images/favicon.ico';
$g_default_home_page = 'my_view_page.php';	# Set to name of page to go to after login

$g_send_reset_password   = OFF;
$g_validate_email        = OFF;
$g_allow_signup          = OFF;
$g_show_project_menu_bar = OFF;
$g_time_tracking_enabled = OFF;

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

# --- Configuration du langage et du format de la date ---
$g_default_language='french';
$g_short_date_format='d-m-Y';
$g_normal_date_format='d-m-Y H:i';
$g_complete_date_format='d-m-Y H:i';

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

?>

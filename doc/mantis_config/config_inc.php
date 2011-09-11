<?php
$g_hostname = 'localhost';
$g_db_type = 'mysql';
$g_database_name = 'mantis';
$g_db_username = 'codev';
$g_db_password = 'secret';

# LoB
# --- Branding ---
$g_window_title			= 'CodevTT';
$g_logo_image			= 'images/mantis_logo.gif';
$g_favicon_image		= 'images/favicon.ico';

$g_send_reset_password = OFF;
$g_validate_email = OFF;
date_default_timezone_set('Europe/Paris');

#$g_show_project_menu_bar = ON;

# --- customize workflow ---
# see also custom_constant_inc.php and custom_strings_inc.php
$g_status_enum_string = '10:new,20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved,85:delivered,90:closed';

$g_status_colors['analyzed']  = '#fff494';
$g_status_colors['open']   = '#c2dfff';
$g_status_colors['delivered'] = '#9EDB63';

$g_status_enum_workflow[NEW_]         ='20:feedback,30:acknowledged,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[FEEDBACK]     ='30:acknowledged,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[ACKNOWLEDGED] ='20:feedback,40:analyzed,50:open,80:resolved';
$g_status_enum_workflow[ANALYZED]     ='20:feedback,50:open,80:resolved';
$g_status_enum_workflow[OPEN_]         ='20:feedback,80:resolved';
$g_status_enum_workflow[RESOLVED]     ='20:feedback,85:delivered,90:closed';
$g_status_enum_workflow[DELIVERED]    ='20:feedback,90:closed';
$g_status_enum_workflow[CLOSED]       ='20:feedback';
# --- END customize workflow ---

?>

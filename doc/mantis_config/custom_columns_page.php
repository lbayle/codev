<?php

# http://bugtracker.morinie.fr/mantis/dokuwiki/doku.php?id=mantis:start:customization:functions:custom_column

#This modification allows your user to customize the list of columns to display in the View Issues page. 
#You just need to add this function in the custom_functions_inc.php file (create it if necessary) : 

# You also need to copy custom_columns_page.php in your Mantis root directory (at the same level as the file core.php).

require_once( 'core.php' );

$t_column = gpc_get_string( 'column' );
$t_action = gpc_get_string( 'action' );

html_page_top1( 'Column management' );
html_head_end();
html_body_begin();

$t_project_id = helper_get_current_project();
$t_user_id = auth_get_current_user_id();

$t_column_list = array(
                       'selection' => lang_get_defaulted( 'selection' ),
                       'edit' => lang_get_defaulted( 'edit' ),
                       'id' => lang_get_defaulted( 'id' ),
                       'project_id' => lang_get_defaulted( 'Project' ),
                       'reporter_id' => lang_get_defaulted( 'reporter' ),
                       'handler_id' => lang_get_defaulted( 'handler' ),
                       'priority' => lang_get_defaulted( 'priority' ),
                       'reproducibility' => lang_get_defaulted( 'reproducibility' ),
                       'projection' => lang_get_defaulted( 'projection' ),
                       'eta' => lang_get_defaulted( 'eta' ),
                       'resolution' => lang_get_defaulted( 'resolution' ),
                       'fixed_in_version' => lang_get_defaulted( 'fixed_in_version' ),
                       'view_state' => lang_get_defaulted( 'view_state' ),
                       'os' => lang_get_defaulted( 'os' ),
                       'os_build' => lang_get_defaulted( 'os_build' ),
                       'platform' => lang_get_defaulted( 'platform' ),
                       'version' => lang_get_defaulted( 'version' ),
                       'date_submitted' => lang_get_defaulted( 'date_submitted' ),
                       'attachment' => lang_get_defaulted( 'attachment' ),
                       'category' => lang_get_defaulted( 'category' ),
                       'sponsorship_total' => lang_get_defaulted( 'sponsorship_total' ),
                       'severity' => lang_get_defaulted( 'severity' ),
                       'status' => lang_get_defaulted( 'status' ),
                       'last_updated' => lang_get_defaulted( 'last_updated' ),
                       'summary' => lang_get_defaulted( 'summary' ),
                       'bugnotes_count' => lang_get_defaulted( 'bugnotes_count' ),
                       'target_version' => lang_get_defaulted( 'target_version' )
                       );
$t_columns = config_get( 'view_issues_page_columns', null, $t_user_id, $t_project_id );

$t_custom_fields = custom_field_get_linked_ids( $t_project_id );
foreach( $t_custom_fields as $t_id ) {
    $t_custom = custom_field_get_definition( $t_id );
    $t_column_list[] = $t_custom['name'];
}

if ( $t_action != 'remove' ) {
    foreach( $t_columns as $t_value ) {
        if ( isset( $t_column_list[$t_value] ) ) {
            unset( $t_column_list[$t_value] );
        }
    }
}

echo '<div align="center"><form action="custom_columns_page.php" name="column">' . "\n";
echo '  <input type="hidden" name="column" value="' . $t_column . '" />' . "\n";

switch( $t_action ) {
  case 'remove':
    echo '  <input type="hidden" id="action" name="action" value="' . $t_action . 'confirm" />' . "\n";
    echo 'Delete the \'' . $t_column_list[$t_column] . '\' column?' . "\n";
    break;
  case 'addbefore':
  case 'addafter':
    echo '  <input type="hidden" id="action" name="action" value="' . $t_action . 'confirm" />' . "\n";
?>
    Select the column to add: <select name="newcolumn">
<?php
    foreach( $t_column_list as $t_value => $t_name ) {
        echo '<option value="' . $t_value . '">' . $t_name . '</option>' . "\n";
    }
?>
    </select>
<?php 
    break;
  case 'removeconfirm':
    $t_newcolumns = array();
    for( $i=0; $i<count( $t_columns ); $i++ ) {
        if ( $t_column != $t_columns[$i] ) {
            $t_newcolumns[] = $t_columns[$i];
        }
    }
    config_set( 'view_issues_page_columns', $t_newcolumns, $t_user_id, $t_project_id );
?>
    <script type="text/javascript" language="JavaScript">
       <!--
         window.opener.location.reload();
         window.close();
       -->
    </script>
<?php 
    break;
  case 'addbeforeconfirm':
  case 'addafterconfirm':
    $t_newcolumn = gpc_get_string( 'newcolumn' );
    $t_newcolumns = array();
    for( $i=0; $i<count( $t_columns ); $i++ ) {
        if ( $t_column == $t_columns[$i] ) {
            if ( $t_action == 'addbeforeconfirm' ) {
                $t_newcolumns[] = $t_newcolumn;
                $t_newcolumns[] = $t_columns[$i];
            } else {
                $t_newcolumns[] = $t_columns[$i];
                $t_newcolumns[] = $t_newcolumn;
            }
        } else {
            $t_newcolumns[] = $t_columns[$i];
        }
    }
    config_set( 'view_issues_page_columns', $t_newcolumns, $t_user_id, $t_project_id );
?>
    <script type="text/javascript" language="JavaScript">
       <!--
         window.opener.location.reload();
         window.close();
       -->
    </script>
<?php 
    break;
  default:
?>
    <script type="text/javascript" language="JavaScript">
       <!--
         window.close();
       -->
    </script>
<?php 
    break;
}

echo '  <br /><br /><input type="submit" value="Ok" />&nbsp;<input type="button" value="Cancel" onclick="document.getElementById( \'action\' ).value=\'cancel\'; document.forms.column.submit();" />' . "\n";
echo '</form></div>';

html_body_end();
html_end();

?>
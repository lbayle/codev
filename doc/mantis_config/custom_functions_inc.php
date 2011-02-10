<?php

# http://bugtracker.morinie.fr/mantis/dokuwiki/doku.php?id=mantis:start:customization:functions:custom_column

#This modification allows your user to customize the list of columns to display in the View Issues page. 
#You just need to add this function in the custom_functions_inc.php file (create it if necessary) : 

# You also need to copy custom_columns_page.php in your Mantis root directory (at the same level as the file core.php).
 
$t_col_id = 0;
 
function custom_function_override_print_column_title( $p_column, $p_columns_target = COLUMNS_TARGET_VIEW_PAGE ) {
    global $t_sort, $t_dir, $t_col_id;
 
    ob_start();
    if ( strpos( $p_column, 'custom_' ) === 0 ) {
        $t_custom_field = substr( $p_column, 7 );
 
        if ( COLUMNS_TARGET_CSV_PAGE != $p_columns_target ) {
            echo '<td>';
        }
 
        $t_field_id = custom_field_get_id_from_name( $t_custom_field );
        if ( $t_field_id === false ) {
            echo '@', $t_custom_field, '@';
        } else {
            $t_def = custom_field_get_definition( $t_field_id );
            $t_custom_field = lang_get_defaulted( $t_def['name'] );
 
            if ( COLUMNS_TARGET_CSV_PAGE != $p_columns_target ) {
                print_view_bug_sort_link( $t_custom_field, $p_column, $t_sort, $t_dir, $p_columns_target );
                print_sort_icon( $t_dir, $t_sort, $p_column );
            } else {
                echo $t_custom_field;
            }
        }
 
        if ( COLUMNS_TARGET_CSV_PAGE != $p_columns_target ) {
            echo '</td>';
        }
    } else {
        $t_function = 'print_column_title_' . $p_column;
        if ( function_exists( $t_function ) ) {
            $t_function( $t_sort, $t_dir, $p_columns_target );
        } else {
            echo '<td>';
            print_view_bug_sort_link( lang_get_defaulted( $p_column ), $p_column, $t_sort, $t_dir, $p_columns_target );
            print_sort_icon( $t_dir, $t_sort, $p_column );
            echo '</td>';
        }
    }
    $t_content = ob_get_contents();
    ob_end_clean();
    if ( $p_columns_target == COLUMNS_TARGET_VIEW_PAGE ) {
        $t_table = '<table width="100%" border="0" cellspacing="0" id="col_' . $t_col_id . '" style="display: none;"><tr><td><a href="javascript:NewWindow( \'custom_columns_page.php?column=' . $p_column . '&action=addbefore\' );" title="Add a new column before"><img src="images/plus.png" alt="+" border="0" /></a></td><td><a href="javascript:NewWindow( \'custom_columns_page.php?column=' . $p_column . '&action=remove\' );" title="Remove this column"><img src="images/minus.png" alt="-" border="0" /></a></td><td><a href="javascript:NewWindow( \'custom_columns_page.php?column=' . $p_column . '&action=addafter\' );" title="Add a column after"><img border="0" src="images/plus.png" alt="+" /></a></td></tr></table>';
        if ( $t_col_id == 0 ) {
            $t_javascript = '<script type="text/javascript" language="JavaScript">' . "\n";
            $t_javascript .= '  <!--' . "\n";
            $t_javascript .= '    function NewWindow( url ) {' . "\n";
            $t_javascript .= '      temp = window.open( url, \'column\', \'width=700,height=300,toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1\' );' . "\n";
            $t_javascript .= '      temp.focus();' . "\n";
            $t_javascript .= '    }' . "\n";
            $t_javascript .= '  -->' . "\n";
            $t_javascript .= '</script>' . "\n";
            $t_table = $t_javascript . $t_table;
        }
        $t_content = str_replace( '<td>', '<td onmouseover="document.getElementById( \'col_' . $t_col_id . '\' ).style.display=\'\';" onmouseout="document.getElementById( \'col_' . $t_col_id . '\' ).style.display=\'none\';">' . $t_table, $t_content );
        $t_col_id++;
    }
    echo $t_content;
}
?>
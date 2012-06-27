<?php
	$g_relationships[ BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY ] = array(
		'#forward' => true,
		'#complementary' => BUG_CUSTOM_RELATIONSHIP_CONSTRAINS,
		'#description' => 'rel_constrained_by',
		'#notify_added' => 'email_notification_title_for_action_constrained_by_relationship_added',
		'#notify_deleted' => 'email_notification_title_for_action_constrained_by_relationship_deleted',
		'#edge_style' => array ( 'style' => 'dashed', 'color' => '808080' ),
	);
 
	$g_relationships[ BUG_CUSTOM_RELATIONSHIP_CONSTRAINS ] = array(
		'#forward' => true,
		'#complementary' => BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY,
		'#description' => 'rel_constrains',
		'#notify_added' => 'email_notification_title_for_action_constrains_relationship_added',
		'#notify_deleted' => 'email_notification_title_for_action_constrains_relationship_deleted',
		'#edge_style' => array ( 'style' => 'dashed', 'color' => '808080' ),
	);
 
?>

<?php

  $s_status_enum_string	= '10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed';

  $s_accepted_bug_button = "Issue accepted";
  $s_accepted_bug_title = "Accept issue";
  $s_email_notification_title_for_status_bug_accepted = "The following issue has been ACCEPTED.";
  
  
  $s_analyzed_bug_button = 'Issue Analyzed';
  $s_analyzed_bug_title = 'Analyze';
  $s_email_notification_title_for_status_bug_analyzed = 'The following issue has been ANALYZED.';

  $s_openned_bug_button = 'Issue Openned';
  $s_openned_bug_title = 'Open';
  $s_email_notification_title_for_status_bug_openned = 'The following issue has been OPENNED.';
  
  $s_deferred_bug_button = 'Issue Deferred';
  $s_deferred_bug_title = 'Deferred';
  $s_email_notification_title_for_status_bug_deferred = 'The developpement has been STOPPED temporarily.';
  
  $s_delivered_bug_button = 'Issue Delivered';
  $s_delivered_bug_title = 'Delivered';
  $s_email_notification_title_for_status_bug_delivered = 'The issue has been assigned to an FDL-Issue.';
  
  $s_eta_enum_string = '10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days';
  
  $s_severity_enum_string = '10:question,50:minor,60:major,70:crash,80:block';
  $s_resolution_enum_string = '10:open,20:fixed,30:reopened,40:unable to reproduce,50:not fixable,60:duplicate,70:not a bug,80:suspended,90:won\'t fix';



  # Custom Relationships
  $s_rel_constrained_by = 'constrained by';
  $s_email_notification_title_for_action_constrained_by_relationship_added = 'Constrained-By Relationship Added';
  $s_email_notification_title_for_action_constrained_by_relationship_deleted = 'Constrained-By Relationship Deleted';
  $s_rel_constrains = 'constrains';
  $s_email_notification_title_for_action_constrains_relationship_added = 'Constrains Relationship Added';
  $s_email_notification_title_for_action_constrains_relationship_deleted = 'Constrains Relationship Deleted';

?>

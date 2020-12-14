FilterBugList
================

Plugin for Mantis allowing to filter by a set of bug IDs in the 'View all' page
By Alain D'EURVEILHER (alain.deurveilher@gmail.com)

+------------------------------------------------------------------------------+
Version 2.1.0:
Update compatibility with Mantis 2.1.0
+------------------------------------------------------------------------------+
Version 1.0.0:

Creation of the plugin
The filter can accept any kind of bug list as a string, using any non-numeric
character as a separator.
Such as for instance: 5079, #5073-5108 5107 49396{5006}}
which will result in a filtering of the following bug IDs: 5079,5073,5108,5107,49396,5006

The filter is also accessible via the FilterBugList_list query in the url,
for instance:
http://my.server/my-mantis/view_all_set.php?type=1&temporary=y&FilterBugList_list=5079,5073,5108,5107,49396,5006
+------------------------------------------------------------------------------+

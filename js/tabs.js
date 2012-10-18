/**
 * Tabs with resize and history
 * Convention :
 * - <div class="tabs">...</div>
 */
jQuery(document).ready(function() {
   var tabs = jQuery(".tabs");
   if(tabs.length > 0) {
      // Lib handle the history
      jQuery.ajax({
         url: "lib/jquery.bbq/jquery.ba-bbq.min.js",
         dataType: "script",
         async: false,
         cache: true
      });

      // The "tab widgets" to handle.
      tabs.tabs({
         "show": function(event, ui) {
            var tab = jQuery(ui.panel);

            // Resize chart in tabs
            var chart = tab.find('.chart');
            if(chart.length != 0) {
               chart.each(function() {
                  var plot = jQuery(this).data('jqplot');
                  // Plot null if the plot is in the first visible tab
                  // If plot is already draw, do not redraw
                  if(plot != null && plot._drawCount == 0) {
                     plot.replot();
                  }
               });
            }

            // Resize table in tabs
            var table = tab.find('div.dataTables_scrollBody>table');
            if(table.length != 0) {
               var oTable = table.dataTable();
               if (oTable.length > 0) {
                  oTable.fnAdjustColumnSizing();
               }
            }
         }
      });

      // This selector will be reused when selecting actual tab widget A elements.
      var tab_a_selector = 'ul.ui-tabs-nav a';

      // Enable tabs on all tab widgets. The `event` property must be overridden so
      // that the tabs aren't changed on click, and any custom event name can be
      // specified. Note that if you define a callback for the 'select' event, it
      // will be executed for the selected tab whenever the hash changes.
      tabs.tabs({ event: 'change' });

      // Define our own click handler for the tabs, overriding the default.
      tabs.find(tab_a_selector).click(function(){
         var state = {};

         // Get the id of this tab widget.
         var id = jQuery(this).closest('.tabs').attr('id');

         // Get the index of this tab.
         // Set the state!
         var parent = jQuery(this).parent();
         var idParent = parent.attr('id');
         if(idParent != null) {
            state[id] = idParent;
         } else {
            state[id] = parent.prevAll().length;
         }

         jQuery.bbq.pushState(state);
      });

      // Bind an event to window.onhashchange that, when the history state changes,
      // iterates over all tab widgets, changing the current tab as necessary.
      jQuery(window).bind('hashchange', function(e) {

         // Iterate over all tab widgets.
         tabs.each(function(){

            // Get the index for this tab widget from the hash, based on the
            // appropriate id property. In jQuery 1.4, you should use e.getState()
            // instead of $.bbq.getState(). The second, 'true' argument coerces the
            // string value to a number.
            var idx = jQuery.bbq.getState(this.id, true) || 0;

            // Select the appropriate tab for this tab widget by triggering the custom
            // event specified in the .tabs() init above (you could keep track of what
            // tab each widget is on using .data, and only select a tab if it has
            // changed).
            var parent = jQuery(this).find('li#'+idx);
            if(parent.length) {
               parent.find('a').triggerHandler('change');
            } else {
               jQuery(this).find(tab_a_selector).eq(idx).triggerHandler('change');
            }
         });

         jQuery(".formWithTabsHistory").each(function() {
            jQuery(this).attr("action",document.location.href);
         });
      });

      // Since the event is only triggered when the hash changes, we need to trigger
      // the event now, to handle the hash the page may have loaded with.
      jQuery(window).trigger('hashchange');
   }
});

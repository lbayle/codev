/*
 * Progress bar
 * Convention :
 * - <div class="progress">xx%</div>
 */
jQuery(document).ready(function() {
   applyProgresBar("body");
});

function applyProgresBar(context) {
   // Transform a div in a jquery progressbar if it's not already a progressbar (ajax problem)
   jQuery(context).find(".progress").not(".ui-progressbar").each(function() {
      var progress = jQuery(this);
      var title = progress.text();
      var value = parseInt(title.replace('%',''));
      progress.text("");
      progress.progressbar({
         value: value
      });
      //progress.find(".ui-progressbar-value").attr('title',title);
      progress.find(".ui-progressbar-value").append("<div>"+title+"</div>");
   });
}

jQuery(document).ready(function() {
   jQuery(".progress").each(function() {
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
});

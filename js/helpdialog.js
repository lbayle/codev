/*
 * Help dialogBox
 * Convention :
 * - <a class="help_link" id="xxx_link">
 * - <div class="help_dialog" id="xxx">
 */
jQuery(document).ready(function(){
   jQuery.each(jQuery(".help_link"),function() {
      jQuery(this).click(function(e) {
         e.preventDefault();
         jQuery("#"+jQuery(this).attr("id").replace("_link","")).dialog("open");
      });
   });
   jQuery(".help_dialog").dialog({
      autoOpen: false,
      hide: "fade"
   });
});

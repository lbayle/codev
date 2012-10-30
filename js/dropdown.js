// Dropdown for menu
jQuery(document).ready(function() {
   jQuery("ul.dropdown li").hover(function() {
      //jQuery(this).addClass("hover");
      jQuery('ul:first',this).css('visibility', 'visible');
    }, function() {
      jQuery(this).removeClass("hover");
      jQuery('ul:first',this).css('visibility', 'hidden');
    }
   );

   jQuery("ul.submenu li:has(ul)").find("a:first").append("&raquo;");
});
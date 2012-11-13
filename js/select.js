/**
 * Select with search
 * Convention :
 * - <select class="select2">...</select>
 */
jQuery(document).ready(function() {
   var $select2 = jQuery('.select2');
   if($select2.length > 0) {
      // Lib handle the history
      jQuery.ajax({
         url: "lib/select2/select2.min.js",
         dataType: "script",
         async: false,
         cache: true
      });

      applySelect2("body");
   }
});

function applySelect2(context) {
   var $select2 = jQuery(context).find('.select2');
   if($select2.length > 0) {
      $select2.select2();
   }
}

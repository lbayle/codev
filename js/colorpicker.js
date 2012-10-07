// Colorpicker
jQuery(document).ready(function() {
   var colorPicker = jQuery('.colorpicker');
   if(colorPicker.length > 0) {
      jQuery.ajax({
         url: "lib/colorpicker/colorpicker.min.js",
         dataType: "script",
         async: false,
         cache: true
      });

      jQuery('#colorpicker').ColorPicker({
         onSubmit: function(hsb, hex, rgb, el) {
            jQuery(el).val(hex);
            jQuery(el).css("background-color","#"+hex);
            jQuery(el).ColorPickerHide();
         },
         onBeforeShow: function () {
            jQuery(this).ColorPickerSetColor(this.value);
         }
      }).bind('keyup', function(){
         jQuery(this).ColorPickerSetColor(this.value);
      });
   }
});

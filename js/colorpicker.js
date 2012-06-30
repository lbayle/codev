// Colorpicker
jQuery(document).ready(function() {
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
});

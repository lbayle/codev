// Editable
jQuery(document).ready(function() {
   jQuery.ajax({
      url: "lib/jquery.jeditable/jquery.jeditable.mini.js",
      dataType: "script",
      async: false,
      cache: true
   });

   jQuery.editable.addInputType('datepicker', {
      element : function(settings, original) {
         var input = jQuery('<input>');
         if (settings.width != 'none') {
            input.width(settings.width);
         }
         if (settings.height != 'none') {
            input.height(settings.height);
         }
         input.attr('autocomplete','off');
         jQuery(this).append(input);
         return(input);
      },
      plugin : function(settings, original) {
         /* Workaround for missing parentNode in IE */
         var form = jQuery(this);
         settings.onblur = 'ignore';
         form.find('input').datepicker({
            showWeek: true,
            showOtherMonths: true,
            showAnim: "slideDown",
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            selectOtherMonths: false
         }).bind('click', function(e) {
            e.preventDefault();
            jQuery(this).datepicker('show');
         }).bind('dateSelected', function(e, selectedDate, $td) {
            form.submit();
         });
      }
   });
});

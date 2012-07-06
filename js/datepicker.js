// Datepicker
jQuery(document).ready(function() {
    jQuery.datepicker.regional['fr'] = {
        closeText: 'Fermer',
        prevText: '&#x3c;Préc',
        nextText: 'Suiv&#x3e;',
        currentText: 'Courant',
        monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
            'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
        monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
            'Jul','Aoû','Sep','Oct','Nov','Déc'],
        dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
        dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
        dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
        weekHeader: 'Sm',
        dateFormat: 'dd/mm/yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    };

    jQuery(".datepicker").datepicker({
        showWeek: true,
        showOtherMonths: true,
        showAnim: "slideDown",
        showOn: "both",
        buttonImage: "images/calendar.png",
        buttonImageOnly: true,

        dateFormat: 'yy-mm-dd',

        changeMonth: false,
        changeYear: false,
        selectOtherMonths: false
    });
});

jQuery.editable.addInputType('datepicker', {
    element : function(settings, original) {
        var input = jQuery('<input>');
        if (settings.width  != 'none') { input.width(settings.width);  }
        if (settings.height != 'none') { input.height(settings.height); }
        input.attr('autocomplete','off');
        jQuery(this).append(input);
        return(input);
    },
    plugin : function(settings, original) {
        /* Workaround for missing parentNode in IE */
        var form = this;
        settings.onblur = 'ignore';
        jQuery(this).find('input').datepicker({
            showWeek: true,
            showOtherMonths: true,
            showAnim: "slideDown",
            dateFormat: 'yy-mm-dd',
            changeMonth: false,
            changeYear: false,
            selectOtherMonths: false
        }).bind('click', function() {
                jQuery(this).datepicker('show');
            return false;
        }).bind('dateSelected', function(e, selectedDate, $td) {
            jQuery(form).submit();
        });
    }
});


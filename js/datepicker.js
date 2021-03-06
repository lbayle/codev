// Datepicker

// locales defined here:
// https://github.com/jquery/jquery-ui/tree/master/ui/i18n


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

    jQuery.datepicker.regional['de_DE'] = {
        closeText: 'Schließen',
        prevText: '&#x3c;Zurück',
        nextText: 'Weiter&#x3e;',
        currentText: 'Jetzt',
        monthNames: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
        monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
        dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
        dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
        weekHeader: 'KW',
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        isRTL: false,
        showMonthAfterYear: false,
        yearSuffix: ''
    };

   jQuery.datepicker.regional['it_IT'] = {
      closeText: "Chiudi",
      prevText: "&#x3C;Prec",
      nextText: "Succ&#x3E;",
      currentText: "Oggi",
      monthNames: [ "Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno",
         "Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre" ],
      monthNamesShort: [ "Gen","Feb","Mar","Apr","Mag","Giu",
         "Lug","Ago","Set","Ott","Nov","Dic" ],
      dayNames: [ "Domenica","Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato" ],
      dayNamesShort: [ "Dom","Lun","Mar","Mer","Gio","Ven","Sab" ],
      dayNamesMin: [ "Do","Lu","Ma","Me","Gi","Ve","Sa" ],
      weekHeader: "Sm",
      dateFormat: "dd/mm/yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['es_ES'] = {
      closeText: "Cerrar",
      prevText: "&#x3C;Ant",
      nextText: "Sig&#x3E;",
      currentText: "Hoy",
      monthNames: [ "enero","febrero","marzo","abril","mayo","junio",
      "julio","agosto","septiembre","octubre","noviembre","diciembre" ],
      monthNamesShort: [ "ene","feb","mar","abr","may","jun",
      "jul","ago","sep","oct","nov","dic" ],
      dayNames: [ "domingo","lunes","martes","miércoles","jueves","viernes","sábado" ],
      dayNamesShort: [ "dom","lun","mar","mié","jue","vie","sáb" ],
      dayNamesMin: [ "D","L","M","X","J","V","S" ],
      weekHeader: "Sm",
      dateFormat: "dd/mm/yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['nl_NL'] = {
      closeText: "Sluiten",
      prevText: "←",
      nextText: "→",
      currentText: "Vandaag",
      monthNames: [ "januari", "februari", "maart", "april", "mei", "juni",
      "juli", "augustus", "september", "oktober", "november", "december" ],
      monthNamesShort: [ "jan", "feb", "mrt", "apr", "mei", "jun",
      "jul", "aug", "sep", "okt", "nov", "dec" ],
      dayNames: [ "zondag", "maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag" ],
      dayNamesShort: [ "zon", "maa", "din", "woe", "don", "vri", "zat" ],
      dayNamesMin: [ "zo", "ma", "di", "wo", "do", "vr", "za" ],
      weekHeader: "Wk",
      dateFormat: "dd-mm-yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['pt_BR'] = {
      closeText: "Fechar",
      prevText: "&#x3C;Anterior",
      nextText: "Próximo&#x3E;",
      currentText: "Hoje",
      monthNames: [ "Janeiro","Fevereiro","Março","Abril","Maio","Junho",
      "Julho","Agosto","Setembro","Outubro","Novembro","Dezembro" ],
      monthNamesShort: [ "Jan","Fev","Mar","Abr","Mai","Jun",
      "Jul","Ago","Set","Out","Nov","Dez" ],
      dayNames: [
         "Domingo",
         "Segunda-feira",
         "Terça-feira",
         "Quarta-feira",
         "Quinta-feira",
         "Sexta-feira",
         "Sábado"
      ],
      dayNamesShort: [ "Dom","Seg","Ter","Qua","Qui","Sex","Sáb" ],
      dayNamesMin: [ "Dom","Seg","Ter","Qua","Qui","Sex","Sáb" ],
      weekHeader: "Sm",
      dateFormat: "dd/mm/yy",
      firstDay: 0,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['zh_CN'] = {
      closeText: "关闭",
      prevText: "&#x3C;上月",
      nextText: "下月&#x3E;",
      currentText: "今天",
      monthNames: [ "一月","二月","三月","四月","五月","六月",
      "七月","八月","九月","十月","十一月","十二月" ],
      monthNamesShort: [ "一月","二月","三月","四月","五月","六月",
      "七月","八月","九月","十月","十一月","十二月" ],
      dayNames: [ "星期日","星期一","星期二","星期三","星期四","星期五","星期六" ],
      dayNamesShort: [ "周日","周一","周二","周三","周四","周五","周六" ],
      dayNamesMin: [ "日","一","二","三","四","五","六" ],
      weekHeader: "周",
      dateFormat: "yy-mm-dd",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: true,
      yearSuffix: "年"
   };

   jQuery.datepicker.regional['zh_TW'] = {
      closeText: "關閉",
      prevText: "&#x3C;上個月",
      nextText: "下個月&#x3E;",
      currentText: "今天",
      monthNames: [ "一月","二月","三月","四月","五月","六月",
      "七月","八月","九月","十月","十一月","十二月" ],
      monthNamesShort: [ "一月","二月","三月","四月","五月","六月",
      "七月","八月","九月","十月","十一月","十二月" ],
      dayNames: [ "星期日","星期一","星期二","星期三","星期四","星期五","星期六" ],
      dayNamesShort: [ "週日","週一","週二","週三","週四","週五","週六" ],
      dayNamesMin: [ "日","一","二","三","四","五","六" ],
      weekHeader: "週",
      dateFormat: "yy/mm/dd",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: true,
      yearSuffix: "年"
   };

   jQuery.datepicker.regional['ko'] = {
      closeText: "닫기",
      prevText: "이전달",
      nextText: "다음달",
      currentText: "오늘",
      monthNames: [ "1월","2월","3월","4월","5월","6월",
      "7월","8월","9월","10월","11월","12월" ],
      monthNamesShort: [ "1월","2월","3월","4월","5월","6월",
      "7월","8월","9월","10월","11월","12월" ],
      dayNames: [ "일요일","월요일","화요일","수요일","목요일","금요일","토요일" ],
      dayNamesShort: [ "일","월","화","수","목","금","토" ],
      dayNamesMin: [ "일","월","화","수","목","금","토" ],
      weekHeader: "주",
      dateFormat: "yy. m. d.",
      firstDay: 0,
      isRTL: false,
      showMonthAfterYear: true,
      yearSuffix: "년"
   };

   jQuery.datepicker.regional['ar'] = {
      closeText: "إغلاق",
      prevText: "&#x3C;السابق",
      nextText: "التالي&#x3E;",
      currentText: "اليوم",
      monthNames: [ "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو",
      "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر" ],
      monthNamesShort: [ "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12" ],
      dayNames: [ "الأحد", "الاثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت" ],
      dayNamesShort: [ "أحد", "اثنين", "ثلاثاء", "أربعاء", "خميس", "جمعة", "سبت" ],
      dayNamesMin: [ "ح", "ن", "ث", "ر", "خ", "ج", "س" ],
      weekHeader: "أسبوع",
      dateFormat: "dd/mm/yy",
      firstDay: 0,
      isRTL: true,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['ru'] = {
      closeText: "Закрыть",
      prevText: "&#x3C;Пред",
      nextText: "След&#x3E;",
      currentText: "Сегодня",
      monthNames: [ "Январь","Февраль","Март","Апрель","Май","Июнь",
      "Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь" ],
      monthNamesShort: [ "Янв","Фев","Мар","Апр","Май","Июн",
      "Июл","Авг","Сен","Окт","Ноя","Дек" ],
      dayNames: [ "воскресенье","понедельник","вторник","среда","четверг","пятница","суббота" ],
      dayNamesShort: [ "вск","пнд","втр","срд","чтв","птн","сбт" ],
      dayNamesMin: [ "Вс","Пн","Вт","Ср","Чт","Пт","Сб" ],
      weekHeader: "Нед",
      dateFormat: "dd.mm.yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
   };

   jQuery.datepicker.regional['tr'] = {
      closeText: "kapat",
      prevText: "&#x3C;geri",
      nextText: "ileri&#x3e",
      currentText: "bugün",
      monthNames: [ "Ocak","Şubat","Mart","Nisan","Mayıs","Haziran",
      "Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık" ],
      monthNamesShort: [ "Oca","Şub","Mar","Nis","May","Haz",
      "Tem","Ağu","Eyl","Eki","Kas","Ara" ],
      dayNames: [ "Pazar","Pazartesi","Salı","Çarşamba","Perşembe","Cuma","Cumartesi" ],
      dayNamesShort: [ "Pz","Pt","Sa","Ça","Pe","Cu","Ct" ],
      dayNamesMin: [ "Pz","Pt","Sa","Ça","Pe","Cu","Ct" ],
      weekHeader: "Hf",
      dateFormat: "dd.mm.yy",
      firstDay: 1,
      isRTL: false,
      showMonthAfterYear: false,
      yearSuffix: ""
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

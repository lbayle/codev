// Date line plot
jQuery(document).ready(function() {

   // Libs
   jQuery.ajax({
      url: "lib/jquery.jqplot/jquery.jqplot.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   jQuery.ajax({
      url: "lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   jQuery.ajax({
      url: "lib/jquery.jqplot/plugins/jqplot.cursor.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   jQuery.ajax({
      url: "lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   jQuery.ajax({
      url: "lib/jquery.jqplot/plugins/jqplot.highlighter.min.js",
      dataType: "script",
      async: false,
      cache: true
   });

   jQuery.jqplot.config.enablePlugins = false;

   jQuery('.date_line_plot').each(function() {
      jQuery(this).data("plotoptions", {
         // animate: true,
         // animateReplot: true,
         title: {
            // Nothing to do
         },
         legend: {
            show: true
         },
         series: [{

         }],
         seriesDefaults: {
            pointLabels: {
               show:true
            }
         },
         cursor: {
            show: true,
            style: "pointer"
         },
         highlighter: {
            show: true,
            showTooltip: false
         },
         axesDefaults: {
            useSeriesColor:true,
            rendererOptions: {
               alignTicks: true
            }
         },
         axes: {
            xaxis: {
               renderer: jQuery.jqplot.DateAxisRenderer,
               tickOptions: {
                  formatString: "%b %Y"
               }
            },
            yaxis: {
               // Nothing to do
            }
         }
      });
   });
});
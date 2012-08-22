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
   jQuery.ajax({
      url: "lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
      jQuery.ajax({
         url: "lib/jquery.jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js",
         dataType: "script",
         async: false,
         cache: true
      });
      jQuery.ajax({
         url: "lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js",
         dataType: "script",
         async: false,
         cache: true
      });
      jQuery.ajax({
         url: "lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js",
         dataType: "script",
         async: false,
         cache: true
      });
      jQuery.ajax({
         url: "lib/jquery.jqplot/plugins/jqplot.categoryAxisRenderer.min.js",
         dataType: "script",
         async: false,
         cache: true
      });

   jQuery.jqplot.config.enablePlugins = false;

   // Chart by date
   jQuery('.date_chart').each(function() {
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
   
   // Chart by category
   jQuery('.category_chart').each(function() {
      jQuery(this).data("plotoptions", {
         title: {
            // Nothing to do
         },
         highlighter: {
            show: true,
            showTooltip: false
         },
         axesDefaults: {
            tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer ,
            tickOptions: {
               angle: -30
            }
         },
         axes: {
            xaxis: {
               renderer: jQuery.jqplot.CategoryAxisRenderer
            },
            yaxis: {
               min: 0
            }
         }
      });
   });

   // Pie chart
   jQuery('.pie_chart').each(function() {
      jQuery(this).data("plotoptions", {
         seriesDefaults: {
            // Make this a pie chart.
            renderer: jQuery.jqplot.PieRenderer,
            rendererOptions: {
               // Put data labels on the pie slices.
               // By default, labels show the percentage of the slice.
               showDataLabels: true
            }
         },
         legend: {
            show:true,
            location: 'e'
         }
      });

      var table = jQuery(this).siblings('table');
      if(table.size() > 0) {
         var rows = table.find('tbody tr');
         var cells = rows.find('td');
         // Now bind function to the highlight event to show the tooltip
         // and highlight the row in the legend.
         jQuery(this).bind('jqplotDataHighlight',
            function (ev, seriesIndex, pointIndex, data, radius) {
               var color = 'rgb(50%,50%,100%)';
               cells.css('background-color', '#ffffff');
               rows.eq(pointIndex).find('td').css('background-color', color);
            }
         );

         // Bind a function to the unhighlight event to clean up after highlighting.
         jQuery(this).bind('jqplotDataUnhighlight',
            function (ev, seriesIndex, pointIndex, data) {
               cells.css('background-color', '#ffffff');
            }
         );
      }
   });

});

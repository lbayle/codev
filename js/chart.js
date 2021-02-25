// Date line plot
jQuery(document).ready(function() {

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
               },
               tickInterval: "1 month"
            },
            yaxis: {
               // Nothing to do
            }
         }
      });
   });

   jQuery('.day_date_chart').each(function() {
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
                  formatString: "%d %b %Y"
               },
               tickInterval: "1 week"
            },
            yaxis: {
               // Nothing to do
            }
         }
      });
   });

   // Chart by date with a line on y = 0
   jQuery('.date_chart_with_horizontal_line').each(function() {
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
         canvasOverlay: {
            show: true,
            objects: [{
                  horizontalLine: {
                    name: 'barney',
                    y: 0,
                    lineWidth: 2,
                    color: 'gray',
                    shadow: false
                }
             }]
         },
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
               },
               tickInterval: "1 month"
            },
            yaxis: {
               // Nothing to do
            }
         }
      });
   });

   // Chart with labels on X
   // var line1 = [14, 32, 41, 44];
   // chartoptions.axes.xaxis.ticks = ['toto', 'titi', 'tutu', 'tata'];
   // chartoptions.title = 'my graph title';
   // chartoptions.seriesDefaults.pointLabels.formatString = "%#.1f"
   // jQuery('#chart1').jqplot([line1], chartoptions);
   jQuery('.xlabels_chart').each(function() {
      jQuery(this).data("plotoptions", {
         title: 'xlabels_chart default title',
         legend: {
            show: true,
            placement: 'inside',
            location: 'ne',
            rowSpacing: '0px'
         },
         seriesDefaults: {
            showMarker:true,
            pointLabels: {
               show:true
            }
         },
//         highlighter: {
//            show: true,
//            showTooltip: false
//         },
         axesDefaults: {
            tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer ,
            tickOptions: {
               angle: -30
               //fontSize: '10pt'
            }
         },
         axes: {
            xaxis: {
               renderer: jQuery.jqplot.CategoryAxisRenderer,
               showTicks: true,
        		   showTickMarks: true
            },
            yaxis: {
               min: 0
            }
         }
      });
   });

   // Chart by category
   jQuery('.area_chart').each(function() {
      jQuery(this).data("plotoptions", {
         stackSeries: true,
         showMarker: false,
         seriesDefaults: {
             fill: true
         },
         axes: {
            xaxis: {
               tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
               tickOptions: {
                 angle: -45
               }
            },
            yaxis: {
               min: 0
            }
         },
         legend: {
            show: true,
            placement: 'outsideGrid',
            location: 'ne',
            rowSpacing: '0px'
         }
      });
      /*
      jQuery(this).bind('jqplotDataHighlight', function(ev, seriesIndex, pointIndex, data) {
            var idx = 4 - seriesIndex
            $('tr.jqplot-table-legend').removeClass('legend-row-highlighted');
            $('tr.jqplot-table-legend').children('.jqplot-table-legend-label').removeClass('legend-text-highlighted');
            $('tr.jqplot-table-legend').eq(idx).addClass('legend-row-highlighted');
            $('tr.jqplot-table-legend').eq(idx).children('.jqplot-table-legend-label').addClass('legend-text-highlighted');
        });

        jQuery(this).bind('jqplotDataUnhighlight', function(ev, seriesIndex, pointIndex, data) {
            $('tr.jqplot-table-legend').removeClass('legend-row-highlighted');
            $('tr.jqplot-table-legend').children('.jqplot-table-legend-label').removeClass('legend-text-highlighted');
        });
        */
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
               showDataLabels: true,
               highlightMouseOver: false,
               startAngle: "180"
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
               var cell = rows.find('td').filter(function(index) { return jQuery(this).text().indexOf(data[0]) != -1; });
               cell.parent().children("td").css('background-color', color);
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

// vertical bar chart
jQuery('.bar_chart').each(function() {
   jQuery(this).data("plotoptions", {
      title: 'bar_chart default title',
      stackSeries: true,
      captureRightClick: false,
      legend: {
         show: true,
         placement: 'inside',
         location: 'ne',
         rowSpacing: '0px'
      },
      axes: {
          xaxis: {
              renderer: jQuery.jqplot.CategoryAxisRenderer
              //ticks: ticks
          }
      },
      seriesDefaults: {
         renderer:jQuery.jqplot.BarRenderer,
         rendererOptions: {
            highlightMouseDown: true
         },
         pointLabels: {show: true, formatString: '%d'}
      }
   });
});

// horizontal bar chart
jQuery('.bar_chart_horiz').each(function() {
   jQuery(this).data("plotoptions", {
      title: 'bar_chart default title',
      stackSeries: true,
      captureRightClick: false,
      legend: {
         show: true,
         placement: 'inside',
         location: 'ne',
         rowSpacing: '0px'
      },
      axes: {
          yaxis: {
              renderer: jQuery.jqplot.CategoryAxisRenderer
              //ticks: ticks
          }
      },
      seriesDefaults: {
         renderer:jQuery.jqplot.BarRenderer,
         rendererOptions: {
            highlightMouseDown: true,
            barDirection: 'horizontal'
         },
         pointLabels: {show: true, formatString: '%d'}
      }
   });
});

function updateChart(chart, data) {
   chart.empty();
   if(data.length > 0) {
      var jsonData = jQuery.parseJSON(data);
      var line = [];
      jQuery.each(jsonData, function (index, value){
         line.push([index,value]);
      });

      var chartoptions = chart.data('plotoptions');
      chart.jqplot([line], chartoptions);
   }
}

$(document).ready(function(){
   var jsonUserData;
   var jsonTimetrackData;
   var jsonProjectionData;

   scheduler.locale.labels.m_tab = "Timeline Month";
   scheduler.locale.labels.section_custom="Section";
   scheduler.config.start_on_monday = true;
   scheduler.config.xml_date="%Y-%m-%d %h:%i";


   $.ajax({ url: 'reports/scheduler_ajax.php',
            async:false,
            data: {action: 'getTeam'},
            type: 'post',
            success: function(data) {
               jsonUserData = JSON.parse(data);
                     },
            error: function(errormsg) {
                        console.log(errormsg);
            }
   });
   
   var d = new Date();
   
   scheduler.createTimelineView({
       name:	"mTimeline",
       x_unit:	"day",
       x_date: "%j %M",
       x_step: 1,
       x_size: 14,
       x_start: 0,
       x_length: 14,
       event_dy : 'full',
       y_unit:jsonUserData,
       y_property:"user_id", 
       dx: 80,   // sets width of resource column
       render:"bar",
   });
   
   scheduler.init('scheduler_here', d, "mTimeline");
   
   scheduler.config.readonly = true;
   scheduler.config.full_day = true;

   $.ajax({ url: 'reports/scheduler_ajax.php',
            async:false,
            data: {action: 'getOldTimetrack'},
            type: 'post',
            success: function(data) {
               jsonTimetrackData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   scheduler.parse(jsonTimetrackData,"json");

   $.ajax({ url: 'reports/scheduler_ajax.php',
            async:false,
            data: {action: 'getProjection'},
            type: 'post',
            success: function(data) {
               jsonProjectionData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });

   scheduler.parse(jsonProjectionData,"json");

});

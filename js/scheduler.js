var jsonTimetrackData;
var jsonUserData;
var jsonProjectionData;
   
scheduler.locale.labels.m_tab = "Timeline Month";
scheduler.locale.labels.section_custom="Section";
scheduler.config.xml_date="%Y-%m-%d %h:%i";
      
scheduler.config.readonly = true;
scheduler.config.full_day = true;

$( "#tabsScheduler" ).on( "tabscreate", function( event, ui ) {
   createSchedulerTable();
});

function createSchedulerTable() {
   
   scheduler.clearAll();

   var jsonUserDataPromise = $.ajax({ url: 'reports/scheduler_ajax.php',
            data: {action: 'getTeam'},
            type: 'post',
            success: function(data) {
               jsonUserData = JSON.parse(data);
                     },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   
   var jsonTimetrackDataPromise = $.ajax({ url: 'reports/scheduler_ajax.php',
            data: {action: 'getOldTimetrack'},
            type: 'post',
            success: function(data) {
               jsonTimetrackData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   
   //if(NULL $_SESSION['tasksUserList'])
   
    var jsonProjectionDataPromise = $.ajax({ url: 'reports/scheduler_ajax.php',
            data: {action: 'getProjection'},
            type: 'post',
            success: function(data) {
               jsonProjectionData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   
   var d = new Date();
   
   $.when(jsonUserDataPromise).done(function(){
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
   });
   
   $.when(jsonUserDataPromise, jsonTimetrackDataPromise).done(function(){
      scheduler.parse(jsonTimetrackData,"json");
   });
   
   $.when(jsonUserDataPromise, jsonTimetrackDataPromise, jsonProjectionDataPromise).done(function(){
      scheduler.parse(jsonProjectionData,"json");
   });
}

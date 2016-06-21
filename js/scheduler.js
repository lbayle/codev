
var jsonUserData;
var jsonTimetrackData;
var jsonProjectionData;

scheduler.locale.labels.m_tab = "Timeline Month";
scheduler.locale.labels.section_custom="Section";
scheduler.config.start_on_monday = true;
scheduler.config.xml_date="%Y-%m-%d %h:%i";



//var sections=[
//    {key:1, label:"Res1"},
//    {key:2, label:"Res2"}
//];
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

// set schedule to current day
var d = new Date();
//var year1 = d.getFullYear();
//var month1 = d.getMonth();
//var day1 = d.getDate();

//===============
//Data loading
//===============
scheduler.init('scheduler_here', d, "mTimeline");

      scheduler.config.readonly = true;
      scheduler.config.full_day = true;
      
scheduler.parse(jsonTimetrackData,"json");
scheduler.parse(jsonProjectionData,"json");

//scheduler.parse([
//    {text:"task1", start_date:"2016-06-15", end_date:"2016-06-18 12:00", 
//     user_id:"1"},
//    {text:"task2",start_date:"2016-06-16 00:00", end_date:"2016-07-28 00:00", 
//     user_id:"2", color:"red"},
//    {text:"task2", start_date:"2016-06-16 00:00", end_date:"2016-06-17 00:00", 
//     user_id:"1"}
//],"json");	

/**
 * 
 * @param {boolean} redirect : true if user is redirect to planning tab
 * @returns {undefined}
 */
function createSchedulerTable(redirect) {
   if(null == redirect)
   {
      redirect = false;
   }
   if(redirect)
   {
      // Redirect to planning tab
      window.location.hash = '#tabsScheduler';
   }
   
   
   $( "#backlogTableBody" ).empty();
   scheduler.clearAll();
   
   $('#loading').show();  // show loading indicator

   var jsonUserDataPromise = $.ajax({ url: 'scheduler/scheduler_ajax.php',
            data: {action: 'getTeam'},
            type: 'post',
            success: function(data) {
               jsonUserData = JSON.parse(data);
                     },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   
   var jsonTimetrackDataPromise = $.ajax({ url: 'scheduler/scheduler_ajax.php',
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
   
    var jsonProjectionDataPromise = $.ajax({ url: 'scheduler/scheduler_ajax.php',
            data: {action: 'getProjection'},
            type: 'post',
            success: function(data) {
               jsonProjectionData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
   });
   
   var scheduler_windowStartDate = new Date(); // 'YYYY-MM-DD' scheduler_windowStartDate
   
   $.when(jsonUserDataPromise).done(function(){
      scheduler.createTimelineView({
         name:	"mTimeline",
         x_unit:	"day",
         x_date: "%j %M",
         x_step: 1,
         x_size: 30,  // scheduler_nbDaysToDisplay
         x_start: 0,
         x_length: 30,  // scheduler_nbDaysToDisplay
         event_dy : 'full',
         y_unit:jsonUserData,
         y_property:"user_id", 
         dx: 80,   // sets width of resource column
         render:"bar",
      });
   
      scheduler.init('scheduler_here', scheduler_windowStartDate, "mTimeline");
   });
   
   $.when(jsonUserDataPromise, jsonTimetrackDataPromise).done(function(){
      scheduler.parse(jsonTimetrackData,"json");
   });
   
   $.when(jsonUserDataPromise, jsonTimetrackDataPromise, jsonProjectionDataPromise).done(function(){
      scheduler.parse(jsonProjectionData["activity"],"json");
      
      if(undefined !== jsonProjectionData["backlog"]){
         $.each(jsonProjectionData["backlog"], function(userName, taskArray){
            var i = 0;
            $.each(taskArray, function(taskid, backlog){
               var trObject = $("#backlogTableBody").append("<tr></tr>");
               if(0 === i){
                  var tdUserObject = trObject.append("<td>"+userName+"</td>");
               }
               else{
                  var tdUserObject = trObject.append("<td></td>");
               }
               i++;
               var tdTaskObject = trObject.append('<td><a href="reports/issue_info.php?bugid='+taskid+'">'+taskid+'</a></td>');
               var tdBacklogObject = trObject.append("<td>"+backlog+"</td>");
            });              
         });
      }
      $('#loading').hide();  // hide loading indicator
   });
}

function refreshPlanning() { 
   // Set a little timeout to refresh planning after tab openned
   setTimeout(function(){ 
      scheduler.updateView();
   }, 10);
};


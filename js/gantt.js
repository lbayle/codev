/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

function setGanttOptions() {
   gantt.config.start_date = new Date(ganttPageSmartyData.windowStartDate); // 'YYYY-MM-DD'

   gantt.config.date_grid = "%Y-%m-%d";
   gantt.config.readonly = true;
   gantt.config.row_height = 22;

   gantt.config.date_scale = "%d/%m";
   //gantt.config.subscales = [{unit:"week", step:1, date:"Week #%W"}];
   gantt.config.subscales = [{unit:"month", step:1, date:"%F %Y"}];
	gantt.config.xml_date="%d-%m-%Y %H:%i";

   gantt.templates.scale_cell_class = function(date){
      if(date.getDay()==0||date.getDay()==6){ return "weekend"; }
   };
   gantt.templates.task_cell_class = function(item,date){
      if(date.getDay()==0||date.getDay()==6){ return "weekend"; }
   };
   gantt.templates.tooltip_text = function(start,end,task){
      return task.tooltipHtml;
   };
   gantt.templates.task_text=function(start,end,task){
      return task.barText;
   };
   gantt.templates.rightside_text = function(start, end, task){
      return task.assignedTo;
   };

   //default columns definition
   gantt.config.columns = [
      {name:"text",       label:"Task",  width:"*", tree:true },
      {name:"start_date", label:"Start", align: "center" },
      {name:"end_date",   label:"End", template:function(obj){ return obj.end_date_real; }},
      {name:"duration",   label:"Backlog", align: "center", template:function(obj){ return obj.duration_real; } },
   ];

   // set filter on
    gantt.attachEvent("onBeforeTaskDisplay", function(id, task){
      var summaryFilter = $("#summaryFilter").val();
      if (task.text.toString().toLowerCase().indexOf(summaryFilter) >= 0) {
         return true;
      }
      if (task.summary.toString().toLowerCase().indexOf(summaryFilter) >= 0) {
         return true;
      }
      if (task.assignedTo.toString().toLowerCase().indexOf(summaryFilter) >= 0) {
         return true;
      }
      // not candidate, return false
      return false;
    });

}

/**
 *
 * @param {boolean} redirect : true if user is redirect to planning tab
 * @returns {undefined}
 */
function computeGantt(redirect) {
   if(null === redirect) { redirect = false; }
   if(redirect) {
      // Redirect to gantt tab
      window.location.hash = '#tabGantt_gantt';
   }
   $('#loading').show();  // show spinner


   // ajax call to get tasks
   var jsonGanttTasksPromise = $.ajax({
      url: ganttPageSmartyData.ajaxPage,
      data: {action: 'getGanttTasks'},
      type: 'post',
      dataType:"json",
      success: function(data) {
         if("SUCCESS" === data.statusMsg) {

            jsonGanttTasks = data.ganttTasks;
            gantt.config.end_date =  new Date(data.ganttEndDate); // 'YYYY-MM-DD'
         } else {
            $('#loading').hide();  // hide spinner
            console.error("Ajax statusMsg", data.statusMsg);
            alert(data.statusMsg);
         }
      },
      error: function(jqXHR, textStatus, errorThrown) {
         $('#loading').hide();  // hide spinner
         console.error(textStatus, errorThrown);
         alert("ERROR: Please contact your CodevTT administrator");
      }
   });

   $.when(jsonGanttTasksPromise).done(function(){
      gantt.parse(jsonGanttTasks);
      console.log("number of tasks currently loaded in the gantt", gantt.getTaskCount());
      console.log("number of tasks visible on the screen (those that are not collapsed)", gantt.getVisibleTaskCount());

      $('#loading').hide();  // hide spinner
   });

}
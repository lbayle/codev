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

console.log("ganttPageSmartyData", ganttPageSmartyData);

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
console.log("compute gantt here !");

   var jsonGanttTasksPromise = $.ajax({
      url: ganttPageSmartyData.ajaxPage,
      data: {action: 'getGanttTasks'},
      type: 'post',
      dataType:"json",
      success: function(data) {
         if("SUCCESS" === data.statusMsg) {

            jsonGanttTasks = data.ganttTasks;
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
      //gantt.parse(jsonGanttTasks,"json");
      gantt.parse(jsonGanttTasks);

      $('#loading').hide();  // hide spinner
   });

}
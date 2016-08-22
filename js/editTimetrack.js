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
jQuery(document).ready(function() {

   //========================================================
   jQuery("#editWeekTimetrack_link").click(function(e) {
      e.preventDefault();

      // find timetrackId
      var timetrackId = $(this).parents('.weekTimetrack').attr('data-weekTimetrackId');

      // get timetrack data
      jQuery.ajax({
         type: "POST",
         url:  timetrackingPageCommonData.ajaxPage, // "timetracking/time_tracking_ajax.php", // {$ajaxPage}",
         data: { action: 'getEditTimetrackData',
                 timetrackId: timetrackId
         },
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {

               $('#timeToEdit').empty();
               var availableDurationList = data.durationsList;
               for (var id in availableDurationList) {
                  if (availableDurationList.hasOwnProperty(id)) {
                     $('#timeToEdit').append(
                        $('<option>').attr('value', id).append(availableDurationList[id])
                     );
                  }
               }
               $("#timetrackId").val(timetrackId);
               $("#timeToEdit").val(data.duration);
               $("#issue_note_edit").val(data.note);
               $("#taskSummary").text(data.issueSummary);
               $("#datepickerEditer").datepicker("setDate" ,data.date);

               // open edit dialogbox
               $("#editWeekTimetrack_dialog" ).dialog( "open" );
            } else {
               console.error("Ajax statusMsg", data.statusMsg);
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
   });


   $("#editWeekTimetrack_dialog").dialog({
               autoOpen: false,
               height: 'auto',
               width: 500,
               modal: true,
               buttons: {
                  Ok: function() {
                        var note = $("#issue_note_edit").val();
                        var date = $("#datepickerEditer").val();
                        var duration = $("#timeToEdit").val();
                        var timetrackId = $("#timetrackId").val();

                        $.ajax({
                           url: timetrackingPageCommonData.ajaxPage,
                           type: "POST",
                           dataType:"json",
                           data: {
                              action: 'updateTimetrack',
                              timetrackId: timetrackId,
                              note: note,
                              date: date,
                              duration: duration,
                              userid:timetrackingPageCommonData.userid,
                              weekid:timetrackingPageCommonData.weekid,
                              year:timetrackingPageCommonData.year
                           },
                           success: function(data) {
console.log("data", data);
                              if ('SUCCESS' === data.statusMsg) {

                                 // update weekTimetracks table
                                 var myTr = $("#weekTimetrackingTuples .weekTimetrack[data-weekTimetrackId^="+timetrackId+"]");
                                 myTr.find(".weekTimetrack_duration").text(duration);
                                 myTr.find(".weekTimetrack_date").text(data.cosmeticDate);
                                 
                                 // update complete timesheet
                                 jQuery("#weekTaskDetailsDiv").html(jQuery.trim(data.timesheetHtml));
                                 updateWidgets("#weekTaskDetailsDiv");

                              } else {
                                 console.error("Ajax statusMsg", data.statusMsg);
                                 alert(data.statusMsg);
                              }
                           },
                           error: function(jqXHR, textStatus, errorThrown) {
                              console.error(textStatus, errorThrown);
                              alert("ERROR: Please contact your CodevTT administrator");
                           }
                        });

                        jQuery(this).dialog( "close" );
                  },
                  Cancel: function() {
                     jQuery(this).dialog( "close" );
                  }
               }
   });

});


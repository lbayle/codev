/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery(document).ready(function() {
   $("#editTimetrackModal").dialog({
               autoOpen: false,
               height: 'auto',
               width: 500,
               modal: true,
               buttons: {
                  Ok: function() {
                        console.log("start");
                        var note = $("#issue_note_edit").val();
                        var date = $("#datepickerEditer").val();
                        var duration = $("#timeToEdit").val();
                        var timetrackId = $("#timetrackId").val();
                        $.ajax({
                           url: 'timetracking/editTimetrackAjax.php',
                           type: "POST",
                           dataType:"json",
                           data: {action: 'updateTimetrack', timetrackId: timetrackId, note: note, date: date, duration: duration},
                           success: function() {
                              window.location.href = window.location.href;
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

function openEditTimetrackModal(timetrackId, date, duration, taskSummary) {
      event.preventDefault();
      $("#editTimetrackModal" ).dialog( "open" );
      
      //Summary
      $("#taskSummary").text(taskSummary);
      
      //date
      $("#datepickerEditer").datepicker("setDate" ,date);
      
      $("#timetrackId").val(timetrackId);

      
      //Call for duration list
      $.ajax({
         url: 'timetracking/editTimetrackAjax.php',
         type: "POST",
         dataType:"json",
         data: {action: 'getEditableValue', timetrackId: timetrackId},
         success: function(data) {
            // fill duration combobox values
            $('#timeToEdit').empty();
            var availableDurationList = data['durationsList'];
            for (var id in availableDurationList) {
               if (availableDurationList.hasOwnProperty(id)) {
                  $('#timeToEdit').append(
                     $('<option>').attr('value', id).append(availableDurationList[id])
                  );
               }
            }
            //duration
            $("#timeToEdit").val(duration);
            
            $("#issue_note_edit").val(data['note']);
         }
      });
      

   }
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

function updateJobList(availableJobs) {

   // fill job combobox values
   var jobSelect = jQuery('#job');
   jobSelect.empty();
   //var nbJobs = Object.keys(availableJobs).length; (IE not compatible with ecmascript5...)
   var nbJobs = 0;
   for(var k in availableJobs) {
      if (availableJobs.hasOwnProperty(k)) {
         nbJobs++;
      }
   }
   if (nbJobs > 1) {
      jobSelect.append(jQuery('<option>').attr('value', '0').append('').attr('selected', 'selected'));
   }
   for (var id in availableJobs) {
      if (availableJobs.hasOwnProperty(id)) {
         jobSelect.append(
            jQuery('<option>').attr('value', id).append(availableJobs[id])
         );
      }
   }
}

function getIssuesAndDurations(event) {

   /* stop form from submitting normally */
   event.preventDefault();

   var bValid = true;
   if (bValid) {

      jQuery('#loading').show(); // spinner

      // ajax call may be slow, empty the task list first.
      var bugidSelect = jQuery('#bugid');
      bugidSelect.empty();
      bugidSelect.select2('data', null);
      bugidSelect.append(jQuery('<option>').attr('value', '0').append(timetrackingSmartyData.i18n_pleaseWait).attr('selected', 'selected'));

      jQuery.ajax({
         url: timetrackingSmartyData.ajaxPage,
         type: "POST",
         dataType:"json",
         data: { action: 'getIssuesAndDurations',
                 managedUserid: timetrackingSmartyData.userid,
                 projectid: jQuery("#projectid").val()
         },
         success: function(data) {

            // fill job combobox values
            bugidSelect.empty();
            bugidSelect.select2('data', null);
            var availableIssues = data['availableIssues'];

            //var nbIssues = Object.keys(availableIssues).length; (IE not compatible with ecmascript5...)
            var nbIssues = 0;
            for(var k in availableIssues) {
               if (availableIssues.hasOwnProperty(k)) {
                  nbIssues++;
               }
            }
            if (nbIssues > 1) {
               bugidSelect.append(jQuery('<option>').attr('value', '0').append('').attr('selected', 'selected'));
            }
            var summary = '';
            var issueInfo;
            for (var id in availableIssues) {

               if (availableIssues.hasOwnProperty(id)) {
                  issueInfo = availableIssues[id];
                  if ((null != issueInfo['tcId']) && ('' != issueInfo['tcId'])) {
                     summary = id + ' / ' + issueInfo['tcId'] + ' : ' + issueInfo['summary'];
                  } else {
                     summary = id + ' : ' + issueInfo['summary'];
                  }
                  bugidSelect.append(
                     jQuery('<option>').attr('value', id).append(summary)
                  );
               }
            }
            // fill job combobox values
            var availableJobs = data['availableJobs'];
            updateJobList(availableJobs);

            // fill duration combobox values
            jQuery('#duree').empty();
            var availableDurationList = data['availableDurations'];
            for (var id in availableDurationList) {
               if (availableDurationList.hasOwnProperty(id)) {
                  jQuery('#duree').append(
                     jQuery('<option>').attr('value', id).append(availableDurationList[id])
                  );
               }
            }
            jQuery('#loading').hide();
         },
         error: function(jqXHR, textStatus, errorThrown) {
            jQuery('#loading').hide();
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
   }
}

// add timetrack and reload Timetracking page
function addTimetrackAndReload(ttNote) {
   var form1 = jQuery("#form1");
   var bugid      = form1.find("select[name=bugid]").val();
   var trackJobid = form1.find("select[name=trackJobid]").val();
   var timeToAdd  = form1.find("select[name=timeToAdd]").val();
   var trackDate  = jQuery("#datepicker").val();

   // we want to dissociate addTimetrack from reloading the page
   // to avoid F5 button to re-send an addTimetrack (using ajax blocks this behaviour)
   jQuery.ajax({
      type: "POST",
      url:  timetrackingSmartyData.ajaxPage,
      data: { action: 'addTimetrack',
              bugid: bugid,
              trackDate: trackDate,
              trackJobid: trackJobid,
              trackUserid: timetrackingSmartyData.userid, // managedUser
              timeToAdd: timeToAdd,
              issue_note: ttNote
      },
      dataType:"json",
      success: function(data) {
         if ('SUCCESS' === data.statusMsg) {
            // reload page (almost full content must be updated, so best is to reload)
            var formReloadPage = jQuery("#formReloadTimetrackingPage");
            formReloadPage.find("input[name=bugid]").val(bugid);
            formReloadPage.find("input[name=date]").val(trackDate);
            formReloadPage.find("input[name=weekid]").val(timetrackingSmartyData.weekid);
            formReloadPage.submit();
         }
      },
      error: function(jqXHR, textStatus, errorThrown) {
         console.error(textStatus, errorThrown);
         alert("ERROR: Please contact your CodevTT administrator");
      }
   });

}

// ================== DOCUMENT READY ====================
jQuery(document).ready(function() {

         // Set the date
   if (timetrackingSmartyData.datepickerLocale != 'en') {
      jQuery.datepicker.setDefaults($.datepicker.regional[timetrackingSmartyData.datepickerLocale]);
   }

   jQuery("#datepicker").datepicker("setDate" ,timetrackingSmartyData.datepickerDate);

   // on project change, taskList & jobList must be updated
   jQuery("#projectid").change(function(event) {
      // use ajax to update fields
      // TODO add a spinner because reloading the bugid combobox may take time
      getIssuesAndDurations(event);
   });

   jQuery("#filters").click(function(event) {
      event.preventDefault();
      jQuery("#setfilter_dialog_form" ).dialog( "open" );
   });

   jQuery("#bugid").change(function() {

      // Here we just want to set the correct job list:
      // depending on the number of issues to display in the combobox,
      // it can be very slow, so we accept not to update the projectName & bugid combobox

      if ('0' === jQuery("#projectid").val()) {

         var form1 = jQuery("#form1");
         var bugid = form1.find("select[name=bugid]").val();

         jQuery.ajax({
            type: "POST",
            url:  timetrackingSmartyData.ajaxPage,
            data: { action: 'getJobList',
                    bugid: bugid
            },
            dataType:"json",
            success: function(data) {
               if ('SUCCESS' === data.statusMsg) {
                  var availableJobs = data['availableJobs'];
                  updateJobList(availableJobs);
               }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               console.error(textStatus, errorThrown);
               alert("ERROR: Please contact your CodevTT administrator");
            }
         });
      }
   });

   jQuery("#btAddTrack").click(function() {
      // check fields
      var foundError = 0;
      var msgString = timetrackingSmartyData.i18n_someFieldsAreMissing + "\n\n";

      var form1 = jQuery("#form1");
      var bugid = form1.find("select[name=bugid]").val();
      var jobid = form1.find("select[name=trackJobid]").val();
      var trackDuration = form1.find("select[name=timeToAdd]").val();

      //if (0 == document.forms["form1"].projectid.value) { msgString += "Projet\n"; ++foundError; }
      if (('0' === bugid) || ('' === bugid)) {
         msgString += timetrackingSmartyData.i18n_task + "\n";
         ++foundError;
      }
      if (('0' === jobid) || ('' === jobid)) {
         msgString += timetrackingSmartyData.i18n_job + "\n";
         ++foundError;
      }
      if (('0' === trackDuration) || ('' === trackDuration)) {
         msgString += timetrackingSmartyData.i18n_duration + "\n";
         ++foundError;
      }

      if (0 === foundError) {
         var trackDate = jQuery("#datepicker").val(); // YYYY-mm-dd

         // getUpdateBacklogData() uses Ajax (async). The deferred object is used to
         // define the success/error callbacks to the ajax call.
         // So the action on getUpdateBacklogData() will be treated asynchronously too.
         var deferred = getUpdateBacklogData(bugid, timetrackingSmartyData.userid, jobid, trackDuration, trackDate);

         // set success callback
         deferred.done(function (updateBacklogJsonData) {

            if ( 'BacklogUpdateNotNeeded' === updateBacklogJsonData['diagnostic'] ) {
               // no backlog nor Note needed, add timetrack with ajax
               addTimetrackAndReload('');

            } else if ( 'timetrackNoteOnly' === updateBacklogJsonData['diagnostic'] ) {
               // open dialogbox and send data with Ajax
               jQuery("#setTimetrackNoteDlg_taskSummary").text(jQuery("#bugid option:selected" ).text());
               jQuery("#setTimetrackNoteDlg_duration").text(jQuery("#duree option:selected" ).text());
               jQuery("#setTimetrackNoteDlg_job").text(jQuery("#job option:selected" ).text());
               jQuery("#setTimetrackNoteDlg_date").text(jQuery("#datepicker").val());
               jQuery("#setTimetrackNoteDlg").dialog( "open" );
            } else {
               // by default formUpdateBacklog has a submit action to send data via ajax
               // so here, it must be deactivated to submit the form 'normaly'.
               jQuery("#formUpdateBacklog").off('submit');

               // open dialogbox and send data with Ajax
               openUpdateBacklogDialogbox(updateBacklogJsonData);
            }
         });
         // set error callback:
         //deferred.fail(function (updateBacklogJsonData) { console.error('fail', updateBacklogJsonData);});
      } else {
         alert(msgString);
      }
   });

   jQuery("#setTimetrackNoteDlg").dialog({
      autoOpen: false,
      resizable: true,
      height: 'auto',
      width: 400,
      modal: true,
      buttons: [
         {
            text: timetrackingSmartyData.i18n_Ok,
            click: function() {
               var ttNote = jQuery("#setTimetrackNoteDlg_timetrackNote").val();
               addTimetrackAndReload(ttNote);
            }
         },
         {
            text: timetrackingSmartyData.i18n_Cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   // ENTER => OK button
    $("#setTimetrackNoteDlg").keydown(function (event) {
        if (event.keyCode == jQuery.ui.keyCode.ENTER) {
            $(this).parent().find("button:eq(0)").trigger("click");
            return false;
        }
    });

   //----------------------------------------------------------
   jQuery("#setfilter_dialog_form" ).dialog({
      autoOpen: false,
      height: 'auto',
      width: 500,
      modal: true,
      buttons: {
         Ok: function() {
            var form = jQuery("#formSetFilters");
            form.find("input[name=projectid]").val(jQuery("#projectid").val());
            form.find("input[name=bugid]").val(jQuery("#bugid").val());
            form.find("input[name=job]").val(jQuery("#job").val());
            form.find("input[name=duree]").val(jQuery("#duree").val());
            form.find("input[name=weekid]").val(jQuery("#weekid").val());
            form.find("input[name=year]").val(jQuery("#year").val());
            form.submit();
         },
         Cancel: function() {
            jQuery(this).dialog( "close" );
         }
      }
   });


   //----------------------------------------------------------
   var formUpdateWeek = jQuery("#formUpdateWeek");

   function updateFormWeek() {
      formUpdateWeek.find("input[name=projectid]").val(jQuery("#projectid").val());
      formUpdateWeek.find("input[name=bugid]").val(jQuery("#bugid").val());
      formUpdateWeek.find("input[name=job]").val(jQuery("#job").val());
      formUpdateWeek.find("input[name=duree]").val(jQuery("#duree").val());
   }

   jQuery("#previousweek").click(function() {
      updateFormWeek();

      var weekid = jQuery("#weekid").val();
      if (1 != weekid) {
         formUpdateWeek.find("select[name=weekid]").val(--weekid);
      } else {
         var year = jQuery("#year").val();
         var prevYear = (+year) - (+1); // convert strings to int and decrement
         var lastWeekPrevYear=timetrackingSmartyData.nbWeeksPrevYear;
         formUpdateWeek.find("select[name=weekid]").val(lastWeekPrevYear);
         formUpdateWeek.find("select[name=year]").val(prevYear);
      }
      var trackDate  = jQuery("#datepicker").val();
      formUpdateWeek.find("input[name=date]").val(trackDate);
      formUpdateWeek.submit();
   });

   jQuery("#nextweek").click(function() {
      updateFormWeek();

      var weekid = jQuery("#weekid").val();
      if (parseInt(weekid) < parseInt(timetrackingSmartyData.nbWeeksThisYear)) {
         formUpdateWeek.find("select[name=weekid]").val(++weekid);
      } else {
         var year = jQuery("#year").val();
         var nextYear = (+year) + (+1); // convert strings to int and increment

         formUpdateWeek.find("select[name=weekid]").val(1);
         formUpdateWeek.find("select[name=year]").val(nextYear);
      }
      var trackDate  = jQuery("#datepicker").val();
      formUpdateWeek.find("input[name=date]").val(trackDate);

      formUpdateWeek.submit();
   });

   jQuery("#weekid, #year").change(function() {
      updateFormWeek();
      formUpdateWeek.submit();
   });

//----------------------------------------------------------
   jQuery(".deleteWeekTimetrack_link").click(function(e) {
      e.preventDefault();

      var timetrackId = $(this).parents('.weekTimetrack').attr('data-weekTimetrackId');

      jQuery.ajax({
            type: "POST",
            url:  timetrackingSmartyData.ajaxPage, // "timetracking/time_tracking_ajax.php",
            data: { action: 'getDeleteTimetrackData',
                    timetrackId: timetrackId
            },
            dataType:"json",
            success: function(data) {
               if ('SUCCESS' === data.statusMsg) {
                  // update #formDeleteTrack
                  jQuery(".issue_summary").text(data.issueSummary);
                  jQuery("#desc_date").text(data.date);
                  jQuery("#desc_id").text(data.formatedId); // formatedId : "id / refExt"
                  jQuery("#desc_duration").text(data.duration);
                  jQuery("#desc_job").text(data.jobName);
                  jQuery("#formDeleteTrack").find("input[name=trackid]").val(timetrackId);
                  jQuery("#backlogChangeInfo").text("");

                  if(data.isRecreditBacklog) {
                     jQuery("#backlogChangeInfo").text("(Backlog will change to : " + data.futureBacklog + ")");
                  }
               }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               console.error(textStatus, errorThrown);
               alert("ERROR: Please contact your CodevTT administrator");
            }
         });
         jQuery("#deleteTrack_dialog_form").dialog("open");
      });
   //----------------------------------------------------------
   // delete track dialogBox
   jQuery("#deleteTrack_dialog_form").dialog({

      // the idea in doing the action by ajax and then reload the page
      // (instead of doing both actions in one single request)
      // is to avoid sending the action again if the user presses the 'F5' button

      autoOpen: false,
      resizable: true,
      height: 'auto',
      width: 400,
      modal: true,
      buttons: [
         {
            text: timetrackingSmartyData.i18n_Delete,
            click: function() {
               var formDeleteTrack = $('#formDeleteTrack');
               $.ajax({
                  url: timetrackingSmartyData.ajaxPage,
                  type: "POST",
                  dataType:"json",
                  data: formDeleteTrack.serialize(),
                  success: function(data) {
                     if ('SUCCESS' === data.statusMsg) {
                        // reload page (almost full content must be updated, so best is to reload)
                        var formReloadPage = jQuery("#formReloadTimetrackingPage");
                        formReloadPage.find("input[name=bugid]").val(data.bugid);
                        formReloadPage.find("input[name=date]").val(data.trackDate);
                        formReloadPage.submit();
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
            }
         },
         {
            text: timetrackingSmartyData.i18n_Cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   //----------------------------------------------------------
   jQuery(".editWeekTimetrack_link").click(function(e) {
      e.preventDefault();

      // find timetrackId
      var timetrackId = $(this).parents('.weekTimetrack').attr('data-weekTimetrackId');

      // get timetrack data
      jQuery.ajax({
         type: "POST",
         url:  timetrackingSmartyData.ajaxPage, // "timetracking/time_tracking_ajax.php",
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
               $('#editJob').empty();
               var availableJobs = data.availableJobs;
               for (var id in availableJobs) {
                  if (availableJobs.hasOwnProperty(id)) {
                     $('#editJob').append(
                        $('<option>').attr('value', id).append(availableJobs[id])
                     );
                  }
               }
               $("#timetrackId").val(timetrackId);
               $("#timeToEdit").val(data.duration);
               $('#backlogToEdit').val(data.backlog);
               $("#editJob").val(data.jobid);
               $("#issue_note_edit").val(data.note);
               $("#taskSummary").text(data.issueSummary);
               $("#datepickerEditer").datepicker("setDate" ,data.date);

               var myTr = $("#weekTimetrackingTuples .weekTimetrack[data-weekTimetrackId^="+timetrackId+"]");
               myTr.attr("data-weekTimetrackBacklog", data.backlog);
               myTr.attr("data-weekTimetrackDuration", data.duration);
               myTr.attr("data-weekTimetrackIsRecreditBacklog", data.isRecreditBacklog);

               // open edit dialogbox
               var editWeekTimetrack_dialog = $("#editWeekTimetrack_dialog" );
               var backlogChangeInfo = editWeekTimetrack_dialog.find('#backlogChangeInfo');
               backlogChangeInfo.text("");
               editWeekTimetrack_dialog.dialog( "open" );
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

   $( "#timeToEdit" ).change(function() {

      var editWeekTimetrackDialog = $('#editWeekTimetrack_dialog');
      var backlogChangeInfo = editWeekTimetrackDialog.find('#backlogChangeInfo');
      var timetrackId = editWeekTimetrackDialog.find('#timetrackId').val();
      var newDuration = parseFloat(editWeekTimetrackDialog.find('#timeToEdit').val());

      var myTr = $("#weekTimetrackingTuples .weekTimetrack[data-weekTimetrackId^="+timetrackId+"]");
      var oldDuration = parseFloat(myTr.attr("data-weekTimetrackDuration"));
      var backlog = parseFloat(myTr.attr("data-weekTimetrackBacklog"));
      var isRecreditBacklog = myTr.attr("data-weekTimetrackIsRecreditBacklog");

      if(isRecreditBacklog === 'true') {
         if(newDuration !== oldDuration) {
            var futureBacklog = backlog + oldDuration - newDuration;
            backlogChangeInfo.text("(Backlog will change to : " + futureBacklog + ")");
         }
         else {
            backlogChangeInfo.text("");
         }
      }
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
                        var jobid = $("#editJob").val();
                        var timetrackId = $("#timetrackId").val();
                        var backlog = $("#backlogToEdit").val();

                        $.ajax({
                           url: timetrackingSmartyData.ajaxPage,
                           type: "POST",
                           dataType:"json",
                           data: {
                              action: 'updateTimetrack',
                              timetrackId: timetrackId,
                              note: note,
                              date: date,
                              duration: duration,
                              jobid: jobid,
                              userid:timetrackingSmartyData.userid,
                              weekid:timetrackingSmartyData.weekid,
                              year:timetrackingSmartyData.year
                           },
                           success: function(data) {
                              if ('SUCCESS' === data.statusMsg) {

                                 // update weekTimetracks table
                                 var myTr = $("#weekTimetrackingTuples .weekTimetrack[data-weekTimetrackId^="+timetrackId+"]");
                                 myTr.find(".weekTimetrack_duration").text(duration);
                                 myTr.find(".weekTimetrack_date").text(data.cosmeticDate);
                                 myTr.find(".weekTimetrack_jobName").text(data.jobName);
                                 myTr.find(".weekTimetrack_ttNote").html(data.ttNote);

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


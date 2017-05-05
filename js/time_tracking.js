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

function getIssuesAndDurations(event) {

   /* stop form from submitting normally */
   event.preventDefault();

   var bValid = true;
   if (bValid) {
      var projectid = jQuery("#projectid").val();
      var formGetIssuesAndDurations = jQuery('#getIssuesAndDurationsForm');
      formGetIssuesAndDurations.find("input[name=projectid]").val(projectid);

      // ajax call may be slow, empty the task list first.
      var bugidSelect = jQuery('#bugid');
      bugidSelect.empty();
      bugidSelect.select2('data', null);
      bugidSelect.append(jQuery('<option>').attr('value', '0').append(timetrackingSmartyData.i18n_pleaseWait).attr('selected', 'selected'));

      jQuery.ajax({
         url: timetrackingSmartyData.ajaxPage,
         type: formGetIssuesAndDurations.attr("method"),
         dataType:"json",
         data: formGetIssuesAndDurations.serialize(),
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
            var jobSelect = jQuery('#job');
            jobSelect.empty();
            var availableJobs = data['availableJobs'];
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
         }
      });
   }
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
      getIssuesAndDurations(event);
   });

   jQuery("#filters").click(function(event) {
      event.preventDefault();
      jQuery("#setfilter_dialog_form" ).dialog( "open" );
   });

   jQuery("#bugid").change(function() {


      // if projectId not set: do it, to update jobs
      // TODO do not use form1, use Ajax
      if ('0' === jQuery("#projectid").val()) {
         var form1 = jQuery("#form1");
         form1.find("input[name=action]").val("setBugId");

         // Keep value of Week form
         form1.find("input[name=weekid]").val(jQuery("#weekid").val());
         form1.find("input[name=year]").val(jQuery("#year").val());
         form1.submit();
      }
   });

   jQuery("#btAddTrack").click(function() {
      // check fields
      var foundError = 0;
      var msgString = timetrackingSmartyData.i18n_someFieldsAreMissing + "\n\n";

      var form1 = jQuery("#form1");
      var bugid = form1.find("select[name=bugid]").val();
      var jobid = form1.find("select[name=job]").val();
      var trackDuration = form1.find("select[name=duree]").val();

      //if (0 == document.forms["form1"].projectid.value) { msgString += "Projet\n"; ++foundError; }
      if ('0' === bugid) {
         msgString += timetrackingSmartyData.i18n_task + "\n";
         ++foundError;
      }
      if ('0' === jobid) {
         msgString += timetrackingSmartyData.i18n_job + "\n";
         ++foundError;
      }
      if ('0' === trackDuration) {
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

            // if "BacklogUpdateNotNeeded" then submit form1, else raise UpdateBacklogDialogBox
            if ( 'BacklogUpdateNotNeeded' === updateBacklogJsonData['diagnostic'] ) {
               form1.find("input[name=action]").val("addTrack");
               form1.submit();
            } else {
               // by default formUpdateBacklog has a submit action to send data via ajax
               // so here, it must be deactivated to submit the form 'normaly'.
               jQuery("#formUpdateBacklog").off('submit');

               // open dialogbox and send data without Ajax
               openUpdateBacklogDialogbox(updateBacklogJsonData);
            }
         });
         // set error callback:
         //deferred.fail(function (updateBacklogJsonData) { console.error('fail', updateBacklogJsonData);});
      } else {
         alert(msgString);
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
   var formAddTimetrack = jQuery("#form1");
   var formUpdateBacklog = jQuery("#formUpdateBacklog");


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
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: {
         "Delete": function() {
            jQuery("#formDeleteTrack").submit();
         },
         Cancel: function() {
            jQuery(this).dialog("close");
         }
      }
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


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

var g_ttForbidenStatusList;
var g_currentStatus;

function getUpdateBacklogData(bugid, userid, jobid, timeToAdd, trackDate) {
   // ajax call to get DialogBox data
   var formGetUpdateBacklogData = jQuery('#formGetUpdateBacklogData');
   formGetUpdateBacklogData.find("input[name=bugid]").val(bugid);
   formGetUpdateBacklogData.find("input[name=userid]").val(userid);
   formGetUpdateBacklogData.find("input[name=trackJobid]").val(jobid);
   formGetUpdateBacklogData.find("input[name=trackDuration]").val(timeToAdd);
   formGetUpdateBacklogData.find("input[name=trackDate]").val(trackDate);

   var deferred = jQuery.Deferred();

   jQuery.ajax({
      type: formGetUpdateBacklogData.attr("method"),
      dataType:"json",
      url: formGetUpdateBacklogData.attr("action"),
      data: formGetUpdateBacklogData.serialize(),
      success: function(data){
         if (null === data) {
            console.error('getUpdateBacklogData SUCCESS but data = null');
            // default failure action
            alert('ERROR: Action canceled, could not retrieve Task info.');
            deferred.reject(); // call the 'fail' callback (if defined)
         } else {
            // call the 'done' callback (should be defined)
            deferred.resolve(data);
         }
      },
      error: function(data){
         // default failure action
         console.error('getUpdateBacklogData ERROR ' , data);
         alert('ERROR: Action canceled, could not retrieve Task info.');
         deferred.reject(); // call the 'fail' callback (if defined)
      }
   });
   return deferred;
}


// This dialog can be opened :
// 1) when adding a timetrack.
//   in this case a field with the timetrack will be displayed
//   so that user can correct the value before setting backlog
//   and pressing 'OK'
// 2) on a backlog update (click on backlog in weekTaskDetails)
//    -> user cannot add time.
function openUpdateBacklogDialogbox(updateBacklogJsonData) {

   // 1) when adding a timetrack.
   // timeToAdd is set with a nonZero value
   // action has to be set to "addTimetrack"

   // 2) when simple backlog update.
   // timeToAdd = 0
   // action has to be set to "updateBacklog"

   var data = updateBacklogJsonData;
   var formUpdateBacklog = jQuery("#formUpdateBacklog");

   jQuery("#backlog").val(data['calculatedBacklog']);

   jQuery("#divBacklogDialog").find(".issue_summary").text(data['summary']);
   jQuery("#desc_effortEstim").text(data['effortEstim']);
   jQuery("#desc_elapsed").text(data['elapsed']);
   jQuery("#desc_currentBacklog").text(data['currentBacklog']);
   jQuery("#desc_drift").text(data['drift']);
   jQuery("#desc_trackDate").text('(on '+data['trackDate']+')');

   if (data.hasOwnProperty("deadline")) {
      jQuery("#desc_deadline").text(data['deadline']);
      jQuery("#th_deadline").show();
      jQuery("#desc_deadline").show();
   } else {
      jQuery("#th_deadline").hide();
      jQuery("#desc_deadline").hide();
   }

   jQuery("#desc_drift").css("backgroundColor", '#' + data['driftColor']);
   formUpdateBacklog.find("input[name=year]").val(jQuery("#year").val());

   formUpdateBacklog.find("input[name=bugid]").val(data['bugid']);
   formUpdateBacklog.find("input[name=statusid]").val(data['currentStatus']);
   formUpdateBacklog.find("input[name=trackJobid]").val(data['trackJobid']);
   formUpdateBacklog.find("input[name=trackUserid]").val(data['trackUserid']);
   formUpdateBacklog.find("input[name=trackDate]").val(data['trackDate']);

   formUpdateBacklog.find("input[name=bugResolvedStatusThreshold]").val(data['bugResolvedStatusThreshold']);
   formUpdateBacklog.find("input[name=bugStatusNew]").val(data['bugStatusNew']);
   formUpdateBacklog.find("input[name=statusNameNew]").val(data['availableStatusList'][data['bugStatusNew']]);

   jQuery(".formUpdateBacklog_errorMsg").text("");

   var dialogBoxTitle = issueBacklogSmartyData.i18n_Task +' ' + data['dialogBoxTitle'] + ' - ' + issueBacklogSmartyData.i18n_UpdateBacklog;
   jQuery("#divBacklogDialog").dialog("option", "title", dialogBoxTitle);

   // set available status
   jQuery('#status').empty();
   var availItemList = data['availableStatusList'];
   for (var id in availItemList) {

      if (availItemList.hasOwnProperty(id)) {
         if (id == data['currentStatus']) {
            jQuery('#status').append(
               jQuery('<option>').attr('id', id).attr('selected', 'selected').append(availItemList[id])
            );
         } else {
            jQuery('#status').append(
               jQuery('<option>').attr('id', id).append(availItemList[id])
            );
         }
      }
   }

   // set available fixedInVersion
   jQuery('#fixedInVersion').empty();
   var availItemList = data['versionList'];
   for (var id in availItemList) {

      if (availItemList.hasOwnProperty(id)) {
         if (availItemList[id] == data['fixedInVersion']) {
            jQuery('#fixedInVersion').append(
               jQuery('<option>').attr('id', id).attr('selected', 'selected').append(availItemList[id])
            );
         } else {
            jQuery('#fixedInVersion').append(
               jQuery('<option>').attr('id', id).append(availItemList[id])
            );
         }
      }
   }

   g_ttForbidenStatusList = data['ttForbidenStatusList'];
   g_currentStatus = data['currentStatus'];

   // field fixedInVersion should only be displayed if resolved
   if ((g_currentStatus < data['bugResolvedStatusThreshold']) || (0 === data['versionList'].length)) {
      jQuery("#tr_fixedInVersion").hide();
   } else {
         jQuery("#tr_fixedInVersion").show();
   }

   // set assignedTo
   jQuery('#handlerid').empty();
   if (data['trackUserid'] != data['handlerId']) {
      jQuery('#handlerid').append(
         jQuery('<option>').attr('id', data['trackUserid']).attr('value', data['trackUserid']).append(data['trackUserName'])
      );
   }
   jQuery('#handlerid').append(
      jQuery('<option>').attr('id', data['handlerId']).attr('value', data['handlerId']).attr('selected', 'selected').append(data['handlerName'])
   );
   // auto-assign if task is not assigned yet.
   if (0 == data['handlerId']) {
      console.log('handlerId 0');
      $("#handlerid").val(data['trackUserid']);
   }

   // set timeToAdd
   jQuery('#timeToAdd').empty();

   if (0 == data['trackDuration']) {
      $('#tr_timeToAdd').hide();
      $('#tr_handlerid').hide();
      $('#tr_status').hide();
      $('#pTimetrackNote').hide();
      formUpdateBacklog.find("input[name=action]").val('updateBacklog');

   } else {
      $('#tr_timeToAdd').show();
      $('#tr_handlerid').show();
      $('#tr_status').show();
      $('#pTimetrackNote').show();
      formUpdateBacklog.find("input[name=action]").val('addTimetrack');

      // fill duration combobox values
      var availItemList = data['availableDurationList'];
      for (var id in availItemList) {

         if (availItemList.hasOwnProperty(id)) {
            if (id == data['trackDuration']) {
               jQuery('#timeToAdd').append(
                  jQuery('<option>').attr('id', id).attr('value', id).attr('selected', 'selected').append(availItemList[id])
               );
            } else {
               jQuery('#timeToAdd').append(
                  jQuery('<option>').attr('id', id).attr('value', id).append(availItemList[id])
               );
            }
         }
      }
   }
   jQuery("#divBacklogDialog").dialog("open");
}

function changeBackLogValue(previousStatus) {
   var bugResolvedStatusThreshold = jQuery("#bugResolvedStatusThreshold").val();
   var selectedStatusId = jQuery("#status").children(":selected").attr("id");
   if(selectedStatusId >= bugResolvedStatusThreshold) {
      $("#backlog").val("0");
   }
   if(selectedStatusId < bugResolvedStatusThreshold && previousStatus >= bugResolvedStatusThreshold) {
      $("#backlog").val("");
   }
}

function updateTips(t) {
   var tips = $("#formUpdateBacklog_validateTips");
   tips.text(t);
   //tips.addClass("ui-state-highlight");
   //setTimeout(function() {
   //   tips.removeClass("ui-state-highlight", 1500);
   //}, 500);
}

function checkRegexp(o, regexp, n, isNullable) {
   if(isNullable && $.trim(o.val()) == ''){
      return true;
   }
   else{
      if (!(regexp.test(o.val()))) {
         o.addClass("ui-state-error");
         updateTips(n);
         return false;
      } else {
         return true;
      }
   }
}

function checkStatus(backlog, selectedStatus, bugResolvedStatusThreshold, bugStatusNew, statusNameNew) {
   if ((selectedStatus < bugResolvedStatusThreshold) && (0 == backlog.val())) {
      backlog.addClass("ui-state-error");
      updateTips(issueBacklogSmartyData.i18n_TaskNotResolved);
      return false;
   }
   if ((selectedStatus == bugStatusNew) && (0 < backlog.val())) {
      backlog.addClass("ui-state-error");
      updateTips(issueBacklogSmartyData.i18n_StatusCantBe + statusNameNew);
      return false;
   }
   if ((selectedStatus >= bugResolvedStatusThreshold) && (0 != backlog.val())) {
      backlog.addClass("ui-state-error");
      updateTips(issueBacklogSmartyData.i18n_TaskResolved);
      return false;
   }
   if ("undefined" !== typeof g_ttForbidenStatusList[g_currentStatus]) {
      backlog.addClass("ui-state-error");
      var strStatusList = '';
      for (var id in g_ttForbidenStatusList) {
         strStatusList += g_ttForbidenStatusList[id] + ', ';
      }
      updateTips(issueBacklogSmartyData.i18n_wrongStatus + strStatusList);
      return false;
   }
   return true;
}

function isBacklogDialogFieldsValid() {
   var bValid = true;
   updateTips("");
   jQuery("#backlog").removeClass("ui-state-error");
   bValid = bValid && checkRegexp(jQuery("#backlog"), /^[0-9]+(\.[0-9][0-9]?5?)?$/i, "format:  '1',  '0.3'  or  '2.55' or '2.125'", false);

   var bugResolvedStatusThreshold = jQuery("#bugResolvedStatusThreshold").val();
   var bugStatusNew = jQuery("#bugStatusNew").val();
   var statusNameNew = jQuery("#statusNameNew").val();
   var selectedStatus = jQuery("#status").children(":selected").attr("id");
   bValid = bValid && checkStatus(jQuery("#backlog"), selectedStatus, bugResolvedStatusThreshold, bugStatusNew, statusNameNew);

   // if action is "updateBacklog" then there is no note to set even if note is mandatory
   if (issueBacklogSmartyData.isTrackNoteMandatory &&
       (! $('#pTimetrackNote').is(":hidden"))) {
      var timetrackNote = jQuery("#issue_note");
      if (bValid && timetrackNote.val() === '') {
         updateTips(issueBacklogSmartyData.i18n_ttNoteRequired);
         timetrackNote.addClass("ui-state-error");
         bValid = false;
      }
   }
   return bValid;
}

jQuery(document).ready(function() {

   // add eventHandler to update formUpdateBacklog field when status is changed in combobox
   jQuery('#status').on('focus', function () {
      previousStatus = jQuery("#status").children(":selected").attr("id");
   }).change(function() {
      jQuery("#status").blur();
      changeBackLogValue(previousStatus);
      var selectedStatusId = jQuery("#status").children(":selected").attr("id");
      jQuery("#formUpdateBacklog").find("input[name=statusid]").val(selectedStatusId);
      
      // field fixedInVersion should only be displayed if resolved
      var bugResolvedStatusThreshold = jQuery("#bugResolvedStatusThreshold").val();      
      if ((selectedStatusId < bugResolvedStatusThreshold) || (0 === $('#fixedInVersion').has('option').length)){
         jQuery("#tr_fixedInVersion").hide();
      } else {
         jQuery("#tr_fixedInVersion").show();
      }     
   });

   var backlog = jQuery("#backlog");
   var timetrackNote = jQuery("#issue_note");

   var allFields = $([]).add(backlog);
   var allFields = $([]).add(timetrackNote);


   jQuery("#divBacklogDialog").dialog({
      autoOpen: false,
      height: 'auto',
      width: 500,
      modal: true,
      open: function() {
         // Select input field contents
         jQuery("#backlog").select();
         changeBackLogValue(0);
      },
      buttons: {
         Ok: function() {

            var bValid = isBacklogDialogFieldsValid();
            if (bValid) {
               var formUpdateBacklog = jQuery("#formUpdateBacklog");
               jQuery.ajax({
                  type: "POST",
                  url:  timetrackingSmartyData.ajaxPage,
                  data: formUpdateBacklog.serialize(),
                  dataType:"json",
                  success: function(data) {
                     if ('SUCCESS' === data.statusMsg) {

                        if (data.hasOwnProperty('weekTaskDetailsHtml')) {
                           // action == 'updateBacklog'
                           // weekTaskDetailsDiv is the only part of the page to update,
                           // so no need to reload complete page
                           jQuery("#weekTaskDetailsDiv").html(jQuery.trim(data.weekTaskDetailsHtml));
                           updateWidgets("#weekTaskDetailsDiv");
                           jQuery("#divBacklogDialog").dialog("close");
                        } else {
                           // reload page (almost full content must be updated, so best is to reload)
                           var formReloadPage = jQuery("#formReloadTimetrackingPage");
                           formReloadPage.find("input[name=bugid]").val(data['bugid']);
                           formReloadPage.find("input[name=date]").val(data['date']);
                           formReloadPage.find("input[name=weekid]").val(data['weekid']);
                           formReloadPage.submit();
                        }
                     } else {
                        console.error(data.statusMsg);
                        jQuery(".formUpdateBacklog_errorMsg").text(data.statusMsg);
                     }
                  },
                  error: function(jqXHR, textStatus, errorThrown) {
                     console.error(textStatus, errorThrown);
                     jQuery(".formUpdateBacklog_errorMsg").text("ERROR: Please contact your CodevTT administrator");
                  }
               });
            }
         },
         Cancel: function() {
            jQuery( this ).dialog("close");
         }
      },
      close: function() {
         allFields.val("").removeClass("ui-state-error");
         allFields.css("backgroundColor", "transparent");
         updateTips(""); // the window is reused for BL updates
         if (issueBacklogSmartyData.isTrackNoteDisplayed) {
            $('.issue_note').val('');
         }
      }
   });

   // click on updateBacklog in timesheet
   jQuery('.js-updateBacklog-link').click(function(ev){
      ev.preventDefault();
      var bugid = this.getAttribute('data-bugId');
      var deferred = getUpdateBacklogData(bugid, issueBacklogSmartyData.userid);

      // set success callback: execute openUpdateBacklogDialogbox()
      deferred.done(function(updateBacklogJsonData) {

         // if "BacklogUpdateNotNeeded" then dialogBox should not be raised.
         if ( 'BacklogUpdateNotNeeded' === updateBacklogJsonData['diagnostic'] ) {
            console.log('BacklogUpdateNotNeeded');
         } else {
            // open dialogbox and send data via Ajax
            openUpdateBacklogDialogbox(updateBacklogJsonData);
         }
      });
   });

});

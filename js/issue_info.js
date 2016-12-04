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
function removeFromCmd(cmdid, cmdName){
   //var dialog = jQuery("#formRemoveFromCmd_dialog");
   //dialog.find(".desc_id").text(commandsetid);
   jQuery("#formRemoveFromCmd_cmdName").text(cmdName);
   jQuery("#formRemoveFromCmd").find("input[name=cmdid]").val(cmdid);
   jQuery("#formRemoveFromCmd_dialog").dialog( "open" );
}
function updateGeneralInfo(bugid) {
   jQuery.ajax({
      type: "GET",
      url: "reports/issue_info_ajax.php",
      data: "action=getGeneralInfo&bugid="+bugid,
      success: function(data) {
         jQuery("#issueGeneralInfo").html(jQuery.trim(data));
         updateWidgets("#issueGeneralInfo");
      },
      error: function(jqXHR, textStatus, errorThrown) {
         if(errorThrown == 'Forbidden') {
            window.location = issueInfoSmartyData.page;
         } else {
            alert(errorThrown);
         }
      }
   });
}

jQuery(document).ready(function() {

        // Set the date
   if (issueInfoSmartyData.datepickerLocale != 'en') {
      jQuery.datepicker.setDefaults($.datepicker.regional[issueInfoSmartyData.datepickerLocale]);
   }

// =================================================================
// Task selection form

jQuery("#projectid").change(function() {
   var projectid = jQuery(this).val();
   jQuery.ajax({
      type: "GET",
      url: "smarty_tools_ajax.php",
      data: "action=getProjectIssues&projectid="+projectid+"&bugid="+jQuery("#bugid").val(),
      success: function(data) {
         jQuery("#bugSelector").html(jQuery.trim(data));
         updateWidgets("#bugSelector");

         // Hide result because the form is no more consistent with the result
         jQuery("#result").hide();
      },
      error: function(jqXHR, textStatus, errorThrown) {
         if(errorThrown === 'Forbidden') {
            window.location = issueInfoSmartyData.page;
         } else {
            console.error(textStatus, errorThrown);
            alert(errorThrown);
         }
      }
   });
});

jQuery("#bugid").change(function() {
      console.log('submit form1');
   if ('0' !== this.value) {
      var form = jQuery('#form1');
      form.submit();
   }
});

// =================================================================
var fut_backlog = jQuery("#fut_backlog"),
    fut_issueMgrEffortEstim = jQuery("#fut_issueMgrEffortEstim"),
    fut_issueEffortEstim = jQuery("#fut_issueEffortEstim"),
    fut_allFields = jQuery([]).add(fut_backlog).add(fut_issueMgrEffortEstim).add(fut_issueEffortEstim);

// used to check formUpdateTimetracking_dialog values
var bugResolvedStatusThreshold, bugStatusNew, statusNameNew, currentStatus;


function checkRegexp(oField, regexp, oMsgLabel, msg) {
   if (!(regexp.test(oField.val()))) {
      oField.addClass("ui-state-error");
      oMsgLabel.text(msg); // .addClass("ui-state-highlight");
      //setTimeout(function() { oMsgLabel.removeClass("ui-state-highlight", 1500); }, 500);
      return false;
   } else {
      return true;
   }
}

function checkStatus(oBacklog, oMsgLabel) {
   if ((currentStatus < bugResolvedStatusThreshold) && (currentStatus > bugStatusNew) && (0 == oBacklog.val())) {
      oBacklog.addClass("ui-state-error");
      oMsgLabel.text(issueInfoSmartyData.i18n_checkStatus01); // "Task not resolved, backlog cannot be '0'"
      return false;
   }
   if ((currentStatus >= bugResolvedStatusThreshold) && (0 != oBacklog.val())) {
      oBacklog.addClass("ui-state-error");
      oMsgLabel.text(issueInfoSmartyData.i18n_checkStatus02); // "Task is resolved, backlog should be '0'"
      //return false;
   }
   if ((currentStatus == bugStatusNew) && (0 < oBacklog.val())) {
      oBacklog.addClass("ui-state-error");
      oMsgLabel.text(issueInfoSmartyData.i18n_checkStatus03 + statusNameNew); // "Backlog should not be set if status is : "
      return false;
   }
   return true;
}

function isTimetrackingFieldsValid() {

   var bValid = true;
   var fut_validateTips = jQuery("#fut_validateTips");
   fut_allFields.removeClass("ui-state-error");
   fut_validateTips.text('');
   if (issueInfoSmartyData.isManager) {
   bValid = bValid && checkRegexp(fut_issueMgrEffortEstim, /^[0-9]+(\.[0-9][0-9]?5?)?$/i, fut_validateTips, "format:  '1',  '0.3'  or  '2.55' or '2.125'");
   }
   bValid = bValid && checkRegexp(fut_issueEffortEstim, /^[0-9]+(\.[0-9][0-9]?5?)?$/i, fut_validateTips, "format:  '1',  '0.3'  or  '2.55' or '2.125'");
   bValid = bValid && checkStatus(fut_backlog, fut_validateTips);

   // backlog should not be set, if status is 'New'
   if ((currentStatus > bugStatusNew) && (null != fut_backlog.val())) {
      bValid = bValid && checkRegexp(fut_backlog, /^[0-9]+(\.[0-9][0-9]?5?)?$/i, fut_validateTips, "format:  '1',  '0.3'  or  '2.55' or '2.125'");
   }
   return bValid;
}

   // =================================================================
   jQuery("#updateTimetracking_link").click(function(e) {
      e.preventDefault();

      // fill cmdCandidates
      jQuery.ajax({
         type: "GET",
         url: issueInfoSmartyData.ajaxPage,
         data: "action=getTimetracking&bugid="+issueInfoSmartyData.bugid,
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {

               fut_issueMgrEffortEstim.val(data.issueMgrEffortEstim);
               fut_issueEffortEstim.val(data.issueEffortEstim);
               fut_backlog.val(data.issueBacklog);

               bugResolvedStatusThreshold = data.bugResolvedStatusThreshold;
               bugStatusNew = data.bugStatusNew;
               statusNameNew = data.statusNameNew;
               currentStatus = data.issueCurrentStatus;

               checkStatus(fut_backlog, jQuery("#fut_validateTips"));
               jQuery("#formUpdateTimetracking_dialog").dialog("open");
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

   jQuery("#formUpdateTimetracking_dialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: issueInfoSmartyData.i18n_update,
            click: function() {
               var bValid = isTimetrackingFieldsValid();
               if (bValid) {
                  jQuery("#formUpdateTimetracking").submit();
               }
            }
         },
         {
            text: issueInfoSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   jQuery("#formUpdateTimetracking").submit(function(event) {
      event.preventDefault();
      var form = jQuery(this);
      jQuery.ajax({
         type: form.attr("method"),
         url: form.attr("action"),
         data: form.serialize(),
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               // values saved, update displayed values
               updateGeneralInfo(data.issueId);
            } else {
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
      jQuery("#formUpdateTimetracking_dialog").dialog("close");
   });

   // =================================================================
   jQuery("#updateTaskInfo_link").click(function(e) {
      e.preventDefault();

      // TODO Ajax to update dialog fields/combobox with current values
      jQuery.ajax({
         type: "GET",
         url: issueInfoSmartyData.ajaxPage,
         data: "action=getTaskInfo&bugid=" + issueInfoSmartyData.bugid,
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               jQuery('#futi_extRef').val(data.extRef);
               jQuery('#futi_codevttType').val(data.codevttType);
               var cb = jQuery("#futi_cbHandlerId");
               cb.empty();
               cb.append(new Option('', '0'));
               var candidates = data.availableHandlerList;
               for (var id in candidates) {
                  if (candidates.hasOwnProperty(id)) {
                     cb.append(
                        jQuery('<option>').attr('value', id).append(candidates[id])
                     );
                  }
               }
               cb.val(data.currentHandlerId);
               cb = jQuery("#futi_cbStatus");
               cb.empty();
               candidates = data.availableStatusList;
               for (var id in candidates) {
                  if (candidates.hasOwnProperty(id)) {
                     cb.append(
                        jQuery('<option>').attr('value', id).append(candidates[id])
                     );
                  }
               }
               cb.val(data.currentStatus);
               cb = jQuery("#futi_cbTargetVersion");
               cb.empty();
               cb.append(new Option('', '0'));
               candidates = data.availableTargetVersion;
               for (var id in candidates) {
                  if (candidates.hasOwnProperty(id)) {
                     cb.append(
                        jQuery('<option>').attr('value', id).append(candidates[id])
                     );
                  }
               }
               cb.val(data.targetVersionId);

               jQuery('#futi_deadlineDatepicker').val(data.deadline);
               if (data.deadline) {
                  jQuery('#futi_deadlineDatepicker').val(data.deadline);
                  jQuery("#futi_deadlineDatepicker").removeAttr('disabled');
                  $('#futi_isDeadline').prop('checked', true);
               } else {
                  $('#futi_isDeadline').prop('checked', false);
                  jQuery("#futi_deadlineDatepicker").attr("disabled", "true");
               }
               if (data.deliveryDate) {
                  jQuery('#futi_deliveryDatepicker').val(data.deliveryDate);
                  jQuery("#futi_deliveryDatepicker").removeAttr('disabled');
                  $('#futi_isDelivery').attr('checked', true);
               } else {
                  $('#futi_isDelivery').attr('checked', false);
                  jQuery("#futi_deliveryDatepicker").attr("disabled", "true");
               }
               jQuery("#formUpdateTaskInfo_dialog").dialog("open");
            } else {
               console.error("statusMsg", data.statusMsg);
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });

   });
   jQuery("#formUpdateTaskInfo_dialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: issueInfoSmartyData.i18n_update,
            click: function() {
               jQuery("#formUpdateTaskInfo").submit();
            }
         },
         {
            text: issueInfoSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });
   jQuery("#formUpdateTaskInfo").submit(function(event) {
      event.preventDefault();
      var form = jQuery(this);
      jQuery.ajax({
         type: form.attr("method"),
         url: form.attr("action"),
         data: form.serialize(),
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               jQuery('#ti_issueExtRef').text(data.issueExtRef);
               jQuery('#ti_handlerName').text(data.handlerName);
               jQuery('#ti_statusName').text(data.statusName);
               jQuery('#ti_projectName').text(data.projectName);
               jQuery('#ti_categoryName').text(data.categoryName);
               jQuery('#ti_issueType').text(data.issueType);
               jQuery('#ti_priorityName').text(data.priorityName);
               jQuery('#ti_severityName').text(data.severityName);
               jQuery('#ti_targetVersion').text(data.targetVersion);
               var timeDrift = data.timeDrift;
               jQuery('#ti_deadLine').empty();
               if (timeDrift.tooltip && timeDrift.deadLine) {
                  jQuery('#ti_deadLine').html(timeDrift.deadLine + ' ' + timeDrift.tooltip);
               } else {
                  jQuery('#ti_deadLine').html(timeDrift.deadLine);
               }
               updateWidgets("#divTaskInfo"); // TODO why error ?

               // if status changed, Backlog & progress may need update
               if ('yes' === data.isUpdateGeneralInfo) {
                  updateGeneralInfo(data.issueId);
               }
            } else {
               console.error("statusMsg", data.statusMsg);
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
      jQuery("#formUpdateTaskInfo_dialog").dialog("close");
   });

   jQuery("#futi_isDeadline").change(function() {
         if ($(this).is(":checked")) {
            jQuery("#futi_deadlineDatepicker").removeAttr('disabled');
         } else {
            jQuery("#futi_deadlineDatepicker").attr("disabled", "true");
         }
   });
   jQuery("#futi_isDelivery").change(function() {
         if ($(this).is(":checked")) {
            jQuery("#futi_deliveryDatepicker").removeAttr('disabled');
         } else {
            jQuery("#futi_deliveryDatepicker").attr("disabled", "true");
         }
   });

   // =================================================================


   jQuery("#formRemoveFromCmd_dialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: issueInfoSmartyData.i18n_remove,
            click: function() {
            jQuery("#formRemoveFromCmd").submit();
            }
         },
         {
            text: issueInfoSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   jQuery("#formRemoveFromCmd").submit(function(event) {
      event.preventDefault();
      var form = jQuery(this);
      jQuery.ajax({
         type: form.attr("method"),
         url: form.attr("action"),
         data: form.serialize(),
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               // remove command from list
               var elem = document.getElementById('divCmd_'+data.cmdid);
               elem.parentElement.removeChild(elem);
            } else {
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
      jQuery("#formRemoveFromCmd_dialog").dialog("close");
   });

   jQuery("#addToCmd_link").click(function(e) {
      e.preventDefault();
      // fill cmdCandidates
      jQuery.ajax({
         type: "GET",
         url: issueInfoSmartyData.ajaxPage,
         data: "action=getCmdCandidates&bugid=" + issueInfoSmartyData.bugid,
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               // fill cbCmdCandidates
               var cbCmds = jQuery(".cbCmdCandidates");
               cbCmds.empty();
               //cbCmds.select2('data', null);

               var cmdCandidates = data.cmdCandidates;
               for (var id in cmdCandidates) {
                  if (cmdCandidates.hasOwnProperty(id)) {
                     cbCmds.append(
                        jQuery('<option>').attr('value', id).append(cmdCandidates[id])
                     );
                  }
               }
            } else {
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });


      jQuery("#formAddToCmd_dialog").dialog("open");
   });
   jQuery("#formAddToCmd_dialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: issueInfoSmartyData.i18n_add,
            click: function() {
               jQuery("#formAddToCmd").submit();
            }
         },
         {
            text: issueInfoSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
      
   });
   jQuery("#formAddToCmd").submit(function(event) {
      event.preventDefault();
      var form = jQuery(this);
      jQuery.ajax({
         type: form.attr("method"),
         url: form.attr("action"),
         data: form.serialize(),
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               // add command to list
               var imgObj = jQuery('<img></img>').attr('align', "absmiddle").attr('title', issueInfoSmartyData.i18n_removeTaskFromCommand).attr('src', "images/b_drop.png");
               imgObj.attr('onclick', "removeFromCmd('"+data.cmdid+"', '"+data.cmdName+"');return false;");
               var aObj = jQuery('<a></a>').attr('href', "management/command_info.php?cmdid="+data.cmdid).html(data.cmdName);
               var spanObj = jQuery('<span></span>').addClass("pointer").append(imgObj);
               var divCmdObj = jQuery('<div></div>').attr('id', 'divCmd_'+data.cmdid).append(spanObj).append(aObj);
               jQuery("#cmdList").append(divCmdObj);
            } else {
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert("ERROR: Please contact your CodevTT administrator");
         }
      });
      jQuery("#formAddToCmd_dialog").dialog("close");
   });
   // =================================================================
   jQuery("#getMantisNotes_link").click(function(e) {
      e.preventDefault();
      jQuery.ajax({
         type: "GET",
         url: issueInfoSmartyData.ajaxPage,
         data: "action=getMantisNotes&bugid=" + issueInfoSmartyData.bugid,
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               // fill notes table
               jQuery("#divNotesInfo").hide();
               jQuery("#divNotesTable").show();
               jQuery("#tableMantisNotes tbody tr").remove();
               jQuery.each(data.taskNotes, function(i, item) {
                   var $tr = jQuery('<tr>').append(
                       jQuery('<td>').text(item.dateSubmitted),
                       jQuery('<td>').text(item.reporterName),
                       jQuery('<td>').text(item.originTag),
                       jQuery('<td>').html(item.text)
                   );
                   var $line = $tr.wrap('<p>');
                   jQuery('#tableMantisNotes tbody').append($line);
               });
               // activate datatable
               jQuery.ajax({
                     async: false,
                     url: "js_min/datatable.min.js",
                     dataType: "script"
               });
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

});



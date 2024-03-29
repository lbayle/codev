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

// ================== DOCUMENT READY ====================
jQuery(document).ready(function() {

   // ---------------------------------------------------
   // Note on bubbling:
   // all row events are attached to the parent table (#weekTaskDetails)
   // when new row is added, buttons will subscribe to existing events
   // ---------------------------------------------------

   // Note: use 'on' instead of 'click' because we need bubbling
   $("#weekTaskDetails").on("click", ".durationTd", function(e) {
      e.preventDefault();
      var durTd = jQuery(this);

      if (durTd.hasClass('isEditable')) {
         
         var row = durTd.parent();
         var statusId=row.children('.statusTd').attr("data-statusId");

         if (timetrackingSmartyData.status_new == statusId) {
            // inform the user that he is not supposed to set timetracks if status is 'New'
            var statusName=row.children('.statusTd').children('.statusSpan').text();
            var actionLog = " Task " + row.attr("data-bugId") + ' ' + timetrackingSmartyData.i18n_pleaseUpdateStatus + ': ' + timetrackingSmartyData.i18n_StatusCantBe + statusName;
            addActionLog(actionLog, 'error_font');
            row.children('.statusTd').animate({backgroundColor: '#FFC87C'}, 'slow'); // orange
            
         } else {
            durTd.children('.durationSpan').hide();

            // create durationSelect if not exist
            var durSelect = durTd.children('.durationSelect');
            if (0 === $(durSelect).length) {
               var curDuration=durTd.children('.durationSpan').text();
               var select = $('<select size="1">').addClass("durationSelect");
               select.append(jQuery('<option>').attr('value', '0').append(''));
               $.each(timetrackingSmartyData.durationList, function(durId, durText) {
                  var option = jQuery('<option>').attr('value', durId).append(durText);
                  if (durId == curDuration) { option.attr('selected', 'selected');  }
                  select.append(option);
               });
               durTd.append(select);
               select.focus();
            }
         }
      }
   });

   $("#weekTaskDetails").on("focusout", ".durationSelect", function(e) {
      e.preventDefault();
      $(".durationSpan").show();
      $('.durationSelect').remove();
   });

   $("#weekTaskDetails").on("change", ".durationSelect", function(e) {
      e.preventDefault();

      var durSelect = jQuery(this);
      var durTd = durSelect.parent();
      var durSpan = durTd.children('.durationSpan');
      var durId   = durSelect.find(":selected").val();
      var durText = (0 == durId) ? "" : durId;
      durSpan.text(durText);

      var index = durTd.index();
      var date = $("#dayHeader-"+(index-3)).attr("data-isodate");
      var row = durTd.parent();


      // add/delete timeTrack
      jQuery.ajax({
         url: timetrackingSmartyData.ajaxPage,
         type: "POST",
         dataType:"json",
         data: { action: 'setDuration',
                 trackUserid: timetrackingSmartyData.userid,
                 bugid: row.attr("data-bugId"),
                 trackDate: date,
                 defaultJobId: row.attr("data-defaultJobId"),
                 duration: durId
         },
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {

               // update dayTotal & change day color if incompleteDay
               var dayTotalTd = $(".dayTotalElapsed[data-isodate='"+date+"']");
               dayTotalTd.text(data.dayTotalElapsed);
               if (1 == data.dayTotalElapsed) { 
                  dayTotalTd.removeClass( "incompleteDay" );
                  $(".dayHeader[data-isodate='"+date+"']").removeClass( "incompleteDay" );
               } else {
                  dayTotalTd.addClass( "incompleteDay" );
                  $(".dayHeader[data-isodate='"+date+"']").addClass( "incompleteDay" );
               }

               // inform that the server has been updated
               durTd.css("background-color","lightgreen");
               durTd.animate({backgroundColor: 'white'}, 'slow');

               // inform the user that he may want to update the backlog
               row.children('.backlogTd').animate({backgroundColor: '#FFC87C'}, 'slow'); // orange
               
               // log the action
               var actionLog = date + " Task "+ row.attr("data-bugId") +": Elapsed updated to " + durId;
               addActionLog(actionLog, 'help_font');

               // remove the select, once finished
               // Chrome: DOMException: Failed to execute 'removeChild' on 'Node': The node to be removed is no longer a child of this node. Perhaps it was moved in a 'blur' event handler?
               $(':focus').blur(); // force durationSelect focusout by giving focus to the document.body

            } else {
               console.error("Ajax statusMsg", data.statusMsg);
               durTd.css("background-color","#FF809F"); // red
               var actionLog = date + " Task " + row.attr("data-bugId") + " ERROR Elapsed update failed : " + data.statusMsg;
               addActionLog(actionLog, 'error_font');
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error('textStatus', textStatus);
            //console.error('errorThrown', errorThrown);
            durTd.css("background-color","#FF809F"); // red
            var actionLog = date + " Task " + row.attr("data-bugId") + " ERROR Elapsed update failed : "+ textStatus;
            addActionLog(actionLog, 'error_font');
         }
      });
   });

   // -------------------------------------------------------------------------------

   $("#weekTaskDetails").on("click", ".statusTd", function(e) {
      e.preventDefault();

      var statusTd = jQuery(this);
      var row = statusTd.parent();

      statusTd.children('.statusSpan').hide();

      // create statusSelect if not exist
      var statusSelect = statusTd.children('.statusSelect');
      if (0 === $(statusSelect).length) {

         jQuery.ajax({
            url: timetrackingSmartyData.ajaxPage,
            type: "POST",
            dataType:"json",
            data: { action: 'getAvailableStatusList',
                    bugid: row.attr("data-bugId")
            },
            success: function(data) {
               if ('SUCCESS' === data.statusMsg) {

                  // create statusSelect
                  var select = $('<select size="1">').addClass("statusSelect");

                  // fill select with availableStatusList
                  select.empty();
                  var availItemList = data['availableStatusList'];
                  for (var id in availItemList) {
                     if (availItemList.hasOwnProperty(id)) {
                        if (id == data['currentStatus']) {
                           select.append(
                              jQuery('<option>').attr('id', id).attr('selected', 'selected').append(availItemList[id])
                           );
                        } else {
                           select.append(
                              jQuery('<option>').attr('id', id).append(availItemList[id])
                           );
                        }
                     }
                  }
                  statusTd.append(select);
                  select.focus();
               } else {
                  statusTd.css("background-color","#FF809F"); // red
                  console.error("getAvailableStatusList Ajax statusMsg", data.statusMsg);
                  var actionLog = "ERROR Task "+ row.attr("data-bugId") + " getAvailableStatusList failed : " + data.statusMsg;
                  addActionLog(actionLog, 'error_font');
               }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               statusTd.css("background-color","#FF809F"); // red
               console.error('textStatus', textStatus);
               //console.error('errorThrown', errorThrown);
               var actionLog = "ERROR Task "+ row.attr("data-bugId") + " getAvailableStatusList failed : "+ textStatus;
               addActionLog(actionLog, 'error_font');
            }
         });
      }
   });

   $("#weekTaskDetails").on("focusout", ".statusSelect", function(e) {
      e.preventDefault();
      $(".statusSpan").show();
      $('.statusSelect').remove();
   });

   $("#weekTaskDetails").on("change", ".statusSelect", function(e) {
      e.preventDefault();
      
      var statusSelect = jQuery(this);
      var statusTd   = statusSelect.parent();
      var statusId   = statusSelect.find(":selected").attr("id");
      var statusText = statusSelect.find(":selected").val();
      var statusSpan = statusTd.children('.statusSpan');
      statusSpan.text(statusText);

      var row = statusTd.parent();

      // add/delete timeTrack
      jQuery.ajax({
         url: timetrackingSmartyData.ajaxPage,
         type: "POST",
         dataType:"json",
         data: { action: 'setStatus',
                 bugid: row.attr("data-bugId"),
                 statusid: statusId
         },
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {

               // inform that the server has been updated
               statusTd.css("background-color","lightgreen");
               statusTd.animate({backgroundColor: 'white'}, 'slow');
               statusTd.attr("data-statusId", statusId);

               // status has changed, timetracking may be disabled (see ForbidenStatusList)
               row.children('.durationTd').each(function () {
                  var tmpTd = jQuery(this);
                  if (data.isTimetrackEditable) {
                     tmpTd.addClass('isEditable').addClass('hover');
                  } else {
                     tmpTd.removeClass('isEditable').removeClass('hover');
                  }
               });

               // log the action
               var actionLog = "Task "+ row.attr("data-bugId") +": Status updated to " + statusText;
               addActionLog(actionLog, 'help_font');

               // if status -> resolved, backlog has been automaticaly set to '0'
               var backlogTd = row.children('.backlogTd');
               var backlog = backlogTd.children(".backlogSpan").text();
               if (backlog != data.backlog) {
                  backlogTd.children(".backlogSpan").text(data.backlog);
                  var actionLog = "Task "+ row.attr("data-bugId") + ": Backlog updated to " + data.backlog;
                  addActionLog(actionLog, 'help_font');
               }

               // inform the user that he may want to update the backlog
               if (data.currentStatus < data.bugResolvedStatusThreshold) {
                  backlogTd.addClass('isEditable'); // if reopened, backlog is editable
                  
                  // if reopened, backlog can't be 0
                  if (0 == data.backlog) {
                     var actionLog = "Task " + row.attr("data-bugId") + ' ' + timetrackingSmartyData.i18n_checkStatus01;
                     addActionLog(actionLog, 'error_font');
                     backlogTd.animate({backgroundColor: '#FFC87C'}, 'slow'); // orange
                  }
               } else {
                  backlogTd.removeClass('isEditable');
               }


               // remove the select, once finished
               // Chrome: DOMException: Failed to execute 'removeChild' on 'Node': The node to be removed is no longer a child of this node. Perhaps it was moved in a 'blur' event handler?
               $(':focus').blur(); // force Select focusout by giving focus to the document.body

            } else {
               console.error("Ajax statusMsg", data.statusMsg);
               statusTd.css("background-color","#FF809F"); // red
               var actionLog = "Task " + row.attr("data-bugId") + " ERROR Status update failed : " + data.statusMsg;
               addActionLog(actionLog, 'error_font');
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error('textStatus', textStatus);
            //console.error('errorThrown', errorThrown);
            statusTd.css("background-color","#FF809F"); // red
            var actionLog = "Task " + row.attr("data-bugId") + " ERROR Status update failed : "+ textStatus;
            addActionLog(actionLog, 'error_font');
         }
      });
   });

   // -------------------------------------------------------------------------------
   
   $("#weekTaskDetails").on("click", ".backlogTd", function(e) {
      e.preventDefault();
      var backlogTd = jQuery(this);

      if (backlogTd.hasClass('isEditable')) {
         // dynamicaly construct the input field if not exist
         var backlogInput = backlogTd.children('.backlogInput');
         if (0 === $(backlogInput).length) {
            var backlogSpan = backlogTd.children(".backlogSpan");
            var editBacklogInput = $('<input size="1" type="text" />');
            editBacklogInput.addClass("backlogInput").val(backlogSpan.text());

            backlogTd.css("background-color","white"); // TODO white is wrong, it disables datatable CSS (line color on hover)
            backlogSpan.hide();
            backlogTd.append(editBacklogInput);
            editBacklogInput.focus().select();
         }
      }
   });

   $("#weekTaskDetails").on("focusout", ".backlogInput", function(e) {
      e.preventDefault();
      $(".backlogSpan").show();
      $('.backlogInput').remove();
   });

   $("#weekTaskDetails").on("change", ".backlogInput", function(e) {
      e.preventDefault();

      var backlogInput = jQuery(this);
      var backlogTd = backlogInput.parent();
      var row = backlogTd.parent();
      
      var backlog = backlogInput.val().trim().replace(',', '.');
      backlogTd.children(".backlogSpan").text(backlog);

      // check backlog format
      var backlogFormat= /^[0-9]+(\.[0-9][0-9]?5?)?$/;  // "format:  '1',  '0.3'  or  '2.55' or '2.125'"
      if (!backlogFormat.test(backlog)) {
         backlogTd.css("background-color","#FF809F"); // red
         var actionLog = "ERROR Task "+ row.attr("data-bugId") + ' ' + timetrackingSmartyData.i18n_invalidBacklogFormat + " : <b>1</b> or <b>0.3</b> or <b>2.55</b> or <b>2.125</b>";
         addActionLog(actionLog, 'error_font');
         
      } else {
         jQuery.ajax({
            url: timetrackingSmartyData.ajaxPage,
            type: "POST",
            dataType:"json",
            data: { action: 'updateBacklog',
                    bugid: row.attr("data-bugId"),
                    backlog: backlog
            },
            success: function(data) {
               if ('SUCCESS' === data.statusMsg) {

                  // inform that the server has been updated
                  backlogTd.css("background-color","lightgreen");
                  backlogTd.animate({backgroundColor: 'white'}, 'slow'); // TODO white is wrong, it disables datatable CSS (line color on hover)

                  var actionLog = "Task "+ row.attr("data-bugId") + ": Backlog updated to " + backlog;
                  addActionLog(actionLog, 'help_font');

                  // remove the backlogInput, once finished
                  // Chrome: DOMException: Failed to execute 'removeChild' on 'Node': The node to be removed is no longer a child of this node. Perhaps it was moved in a 'blur' event handler?
                  $(':focus').blur(); // force backlogInput focusout by giving focus to the document.body
                  
               } else {
                  backlogTd.css("background-color","#FF809F"); // red
                  console.error("updateBacklog Ajax statusMsg", data.statusMsg);
                  var actionLog = "ERROR Task "+ row.attr("data-bugId") + " Backlog update failed : " + data.statusMsg;
                  addActionLog(actionLog, 'error_font');
               }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               backlogTd.css("background-color","#FF809F"); // red
               console.error('textStatus', textStatus);
               //console.error('errorThrown', errorThrown);
               var actionLog = "ERROR Task "+ row.attr("data-bugId") + " Backlog update failed : "+ textStatus;
               addActionLog(actionLog, 'error_font');
            }
         });
      }
   });

   //----------------------------------------------------------
   jQuery("#btAddTask").click(function(e) {
      e.preventDefault();

      jQuery("#divAddTaskDialog").dialog({width: 750});
      jQuery("#divAddTaskDialog").dialog("open");
   });

   jQuery("#divAddTaskDialog").dialog({
      autoOpen: false,
      height: 'auto',
      width: 500,
      modal: true,
      open: function() {
      },
      buttons: {
         Ok: function() {

            var bugid = jQuery("#addTask_bugid").val();

            // if task not already in table
            if ($("#weekTaskDetails tr[data-bugid='"+bugid+"']").length) {
               var actionLog = "Task "+bugid+" is already displayed";
               addActionLog(actionLog, 'warn_font');

               jQuery("#divAddTaskDialog" ).dialog("close");
            } else {

               var formReloadPage = jQuery("#formReloadTimetrackingPage");
               var weekid = formReloadPage.find("input[name=weekid]").val();
               var year   = formReloadPage.find("input[name=year]").val();

               jQuery.ajax({
                  url: timetrackingSmartyData.ajaxPage,
                  type: "POST",
                  dataType:"json",
                  data: { action: 'getWeekTasksElement',
                          bugid: bugid,
                          userid: timetrackingSmartyData.userid,
                          weekid: weekid,
                          year: year
                  },
                  success: function(data) {
                     if ('SUCCESS' === data.statusMsg) {
                        var wte = data['weekTasksElement'];

                        // --- add new line to table
                        var trObject = jQuery('<tr>').append(
                           $('<td>').html(wte.htmlDescription),
                           $('<td style="width:19px;">').html(wte.infoTooltip)
                        ).attr('data-bugid', wte.bugId).attr('data-defaultJobId', wte.defaultJobId);

                        // ---
                        var tdStatus = $('<td>');
                        if (wte.isStatusEditable) {
                           tdStatus.addClass("statusTd hover").attr("data-statusId", wte.statusId);
                           tdStatus.append($('<span>').addClass("statusSpan").text(wte.statusName));
                        } else {
                           if (null != wte.statusName) { tdStatus.text(wte.statusName); }
                        }
                        trObject.append(tdStatus);
                        // ---
                        var tdBacklog = $('<td style="height:28px">');
                        if (wte.isBacklogEditable) {
                           tdBacklog.addClass("backlogTd hover");
                           tdBacklog.append($('<span>').addClass("backlogSpan").text(wte.backlog));
                        } else {
                           if (null != wte.backlog) { tdBacklog.text(wte.backlog); }
                        }
                        trObject.append(tdBacklog);

                        // ---
                        $.each(wte.weekDays, function(i, day) {
                           var tdDay = $('<td>');
                           if (null != day.title) { tdDay.prop("title", day.title); }
                           if (null != day.bgColor) { tdDay.css("background-color", '#'+day.bgColor); }
                           if (day.isEditable) {
                              tdDay.addClass("durationTd hover isEditable");
                              var span = $('<span>').addClass("durationSpan");
                              if (0 != day.duration) { span.text(day.duration); }
                              tdDay.append(span);
                           } else {
                               if (0 != day.duration) { tdDay.text(day.duration); }
                           }
                           trObject.append(tdDay);
                        });
                        // ---
                        trObject.appendTo('#weekTaskDetails tbody');
                        jQuery("#divAddTaskDialog" ).dialog("close");
                     } else {
                        console.error("Ajax statusMsg", data.statusMsg);
                        alert(data.statusMsg);
                        var actionLog = "ERROR Add task "+bugid+" to table: " + data.statusMsg;
                        addActionLog(actionLog, 'error_font');
                     }
                  },
                  error: function(jqXHR, textStatus, errorThrown) {
                     console.error('textStatus', textStatus);
                     //console.error('errorThrown', errorThrown);
                     var actionLog = "ERROR Add task "+bugid+" to table: " + textStatus;
                     addActionLog(actionLog, 'error_font');
                  }
               });
            }
            
         },
         Cancel: function() {
            jQuery( this ).dialog("close");
         }
      },
      close: function() {
      }
   });

   //----------------------------------------------------------
   jQuery("#previousweek").click(function(e) {
      e.preventDefault();

      var formReloadPage = jQuery("#formReloadTimetrackingPage");
      var weekid = parseInt(formReloadPage.find("input[name=weekid]").val());
      if (1 !== weekid) {
         formReloadPage.find("input[name=weekid]").val(--weekid);
      } else {
         var year = parseInt(formReloadPage.find("input[name=year]").val());
         var lastWeekPrevYear=timetrackingSmartyData.nbWeeksPrevYear;
         formReloadPage.find("input[name=weekid]").val(lastWeekPrevYear);
         formReloadPage.find("input[name=year]").val(--year);
      }
      formReloadPage.find("input[name=action]").val("updateWeek");
      formReloadPage.submit();
   });

   jQuery("#nextweek").click(function(e) {
      e.preventDefault();

      var formReloadPage = jQuery("#formReloadTimetrackingPage");
      var weekid = parseInt(formReloadPage.find("input[name=weekid]").val());

      if (weekid < parseInt(timetrackingSmartyData.nbWeeksThisYear)) {
         formReloadPage.find("input[name=weekid]").val(++weekid);
      } else {
         var year = parseInt(formReloadPage.find("input[name=year]").val());
         formReloadPage.find("input[name=weekid]").val(1);
         formReloadPage.find("input[name=year]").val(++year);
      }
      formReloadPage.find("input[name=action]").val("updateWeek");
      formReloadPage.submit();
   });
   
   //----------------------------------------------------------
   
   jQuery('#addTask_bugid').select2({
      placeholder: timetrackingSmartyData.i18n_TypeToSearch,
      minimumInputLength: 3,
      width: 'resolve',
      //cache: true,
      ajax: {
         type: "POST",
         url: timetrackingSmartyData.ajaxPage,
         dataType: 'json',
         delay: 500, // wait 250 milliseconds before triggering the request
         data: function (params) {
            var query = {
                action: 'searchIssues',
                onlyAssignedTo:jQuery("#addTask_onlyAssignedTo").is(":checked"),
                hideResolved:jQuery("#addTask_hideResolved").is(":checked"),
                search: params.term,
                projectid: jQuery("#addTask_projectid").val(),
                userid: timetrackingSmartyData.userid
            };
            return query;
         },
         processResults: function (data, page) {
            return { results: data };
         }
      }
   });

   //----------------------------------------------------------
   jQuery("#dialog_ConsistencyCheck_link").click(function(event) {
      event.preventDefault();
      jQuery("#dialog_ConsistencyCheck").dialog("open");
   });
   jQuery("#dialog_ConsistencyCheck").dialog({
      autoOpen: false,
      hide: "fade",
      width: 750
   }).css( { 'max-height' : '500px' } ); // fix MaxHeight http://bugs.jqueryui.com/ticket/4820
   
   
});

// -----------------------
// type (css class): error_font, warn_font, help_font, success_font
function addActionLog(actionLog, font) {
   var prevLogs = jQuery("#actionLogsDiv").html();
   jQuery("#actionLogsDiv").html('<span class="'+font+'">' + actionLog + '</span><br>' + prevLogs);
}

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
   gantt.config.start_date = new Date(commandInfoSmartyData.ganttWindowStartDate); // 'YYYY-MM-DD'

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
 * @returns {undefined}
 */
function computeGantt() {

   //$('#loading').show();  // show spinner
   $("#ganttBtImg").attr("src","images/spinner.gif");

   // ajax call to get tasks
   var jsonGanttTasksPromise = $.ajax({
      url: commandInfoSmartyData.ajaxPage,
      data: {
         action: 'getGanttTasks',
         commandId: commandInfoSmartyData.commandId
      },
      type: 'post',
      dataType:"json",
      success: function(data) {
         if("SUCCESS" === data.statusMsg) {

            jsonGanttTasks = data.ganttTasks;
            gantt.config.end_date =  new Date(data.ganttEndDate); // 'YYYY-MM-DD'
         } else {
            //$('#loading').hide();  // hide spinner
            $("#ganttBtImg").attr("src","images/b_refresh.png");
            console.error("Ajax statusMsg", data.statusMsg);
            alert(data.statusMsg);
         }
      },
      error: function(jqXHR, textStatus, errorThrown) {
         //$('#loading').hide();  // hide spinner
         $("#ganttBtImg").attr("src","images/b_refresh.png");

         console.error(textStatus, errorThrown);
         alert("ERROR: Please contact your CodevTT administrator");
      }
   });

   $.when(jsonGanttTasksPromise).done(function(){
      gantt.parse(jsonGanttTasks);
      console.log("number of tasks currently loaded in the gantt", gantt.getTaskCount());
      console.log("number of tasks visible on the screen (those that are not collapsed)", gantt.getVisibleTaskCount());

      //$('#loading').hide();  // hide spinner
      $("#ganttBtImg").attr("src","images/b_refresh.png");

   });

}

// ==================
$( "#tab_gantt" ).on( "tabscreate", function( event, ui ) {
   setGanttOptions();
   gantt.init("gantt_here");
   computeGantt();
});

$('#summaryFilter').bind("enterKey",function(e){
   gantt.refreshData();
});

$('#summaryFilter').keyup(function(e){
    if(e.keyCode == 13) {
        $(this).trigger("enterKey");
    }
});

// ================== DOCUMENT READY ====================
jQuery(document).ready(function() {

//$('#loading').hide();  // hide spinner

   jQuery("#cmdid").change(function() {
      if ('0' !== this.value) {
         var form = jQuery('#mainForm');
         form.find("input[name=action]").val("displayCommand");
         form.submit();
      }
   });

   jQuery("#btDisplayPage").click(function() {
      var form = jQuery('#mainForm');
      form.find("input[name=action]").val("displayCommand");
      form.submit();
   });

   jQuery("#btCmdStateFilter").click(function(event) {
        event.preventDefault();
        jQuery("#setCmdStateFilter_dialog_form" ).dialog( "open" );
   });

   jQuery("#setCmdStateFilter_dialog_form" ).dialog({
        autoOpen: false,
        height: 'auto',
        width: 500,
        modal: true,
        buttons: [
            {
               text: commandInfoSmartyData.i18n_OK,
               click: function() {
                 var form = jQuery("#formSetStateFilter");

                 //  get selected week days
                 var checkItems = "";
                 jQuery(".cb_cmdStateFilter").each(function() {
                    var itemName = jQuery(this).attr("name");
                    var isChecked = jQuery(this).attr('checked') ? 1 : 0;
                    checkItems += itemName+":"+isChecked+",";
                 });
                 //alert("selected command states: "+checkItems);
                 form.find("input[name=checkedCmdStateFilters]").val(checkItems)

                 // TODO get selected item from mainForm combobox
                 form.find("input[name=cmdid]").val(jQuery("#cmdid").val());
                 form.submit();
               }
            },
            {
               text: commandInfoSmartyData.i18n_Cancel,
               click: function() {
                 jQuery(this).dialog( "close" );
               }
            }
        ]
   });

   jQuery('input[type=radio]').change(function() {
         if(jQuery(this).attr('id') == "radio1") {
            jQuery(".provisionTotalList").show();
            jQuery(".provisionsList").hide();
         } else if(jQuery(this).attr('id') == "radio2") {
            jQuery(".provisionTotalList").hide();
            jQuery(".provisionsList").show();
         }
      });

   jQuery.ajax({
      url: "js_min/datatable.min.js",
      dataType: "script",
      cache: true
   });

   // --- editable issue_table fields
   // Allowed format for MgrEffortEstim & EffortEstim values: 1, 0.3, 2.55 or 2.125
   var regex = new RegExp(/^[0-9]+(\.[0-9][0-9]?5?)?$/i);

   // Custom input created for editable columuns
   $.editable.addInputType('effortEstimationInput', {
      element : function(settings, original) {
         var input = $('<input id="input_effort_estimation" type="text" title="\'1\', \'0.3\', \'2.55\' or \'2.125\'" required="required">');
         input.width(settings.width);
         input.height(settings.height);
         $(this).append(input);
         return(input);
         },
      // The new value is submited only if it matches with the regex, otherwise, the cell's value is reset.
      submit: function(settings, original) {
         var updatedValue = document.getElementById('input_effort_estimation').value;
         if (regex.test(updatedValue)) {
            if  (original.revert !== updatedValue) {
               var form = $('form', this);
               form.submit();
            } else {
               original.reset();
               $('.alert-message').removeClass('error info').html('');
               return false;
            }
         } else {
            var alertMessage = $('.alert-message');
            var message = $('<div />', {
               html: commandInfoSmartyData.i18n_format + ' : \'1\', \'0.3\', \'2.55\' or \'2.125\'.'
            });
            alertMessage.hasClass('info') ? alertMessage.removeClass('info').addClass('error').html(message) : alertMessage.addClass('error').html(message);
            original.reset();
            $(original).trigger('dblclick');
            return false;
         }
      }
   });

   $('#issues_table').find('.td_editable').editable(function (value, settings) {
      return(value);
   }, {
      data: function (value, settings) {
         return value;
      },
      type: 'effortEstimationInput',
      event: 'dblclick',
      select: true,
      placeholder: '',
      // The new value is submited only if it matches with the regex, otherwise, the cell's value is reset.
      onblur: function(value) {
         if (regex.test(value)) {
            if (this.revert !== value) {
               var form = $('form', this);
               form.submit();
            } else {
               this.reset();
               $('.alert-message').removeClass('error info').html('');
            }
         } else {
            var alertMessage = $('.alert-message');
            var message = $('<div />', {
               html: commandInfoSmartyData.i18n_format + ' : \'1\', \'0.3\', \'2.55\' or \'2.125\'.'
            });
            alertMessage.hasClass('info') ? alertMessage.removeClass('info').addClass('error').html(message) : alertMessage.addClass('error').html(message);
            this.reset();
            $(this).trigger('dblclick');
         }
      },
      callback: function (value, settings) {
         // Getting the class attribut corresponding
         var fieldName = $(this).attr('data-fieldname');

         if ('EffortEstim' == fieldName) {
            var tdDrift = $(this).closest('tr').children('td.td_drift');
         } else if ('MgrEffortEstim' == fieldName) {
            var tdDriftMgr = $(this).closest('tr').children('td.td_drift_mgr');
         }

         var bugId = $(this).parent().children(':first-child').text();
         console.log(commandInfoSmartyData.ajaxPage);
         $.ajax({
            type: 'GET',
            url: commandInfoSmartyData.ajaxPage,
            data: 'action=updateTaskData&bugId=' + bugId + '&fieldName=' + fieldName + '&updatedValue=' + value + '&commandId=' + commandInfoSmartyData.commandId,
            dataType: 'json',
            success: function (data) {
               if ('SUCCESS' === data.statusMsg) {
                  var color = (null !== data.taskUpdate.driftColor) ? '#' + data.taskUpdate.driftColor : 'inherit';
                  if (null !== data.taskUpdate.drift && tdDrift) {
                     tdDrift.text(data.taskUpdate.drift);
                     tdDrift.css('background-color', color);
                  } else if (null !== data.taskUpdate.driftMgr && tdDriftMgr) {
                     // DriftMgr Update
                     tdDriftMgr.text(data.taskUpdate.driftMgr);
                     tdDriftMgr.css('background-color', color);

                     // Budget Update
                     var totalDriftColor = '#' + data.budgetUpdate.cmdTotalDriftColor;
                     $('#td_prov_and_mee_days').text(data.budgetUpdate.cmdProvAndMeeDays);
                     $('#td_total_reestimated').text(data.budgetUpdate.cmdTotalReestimated);
                     $('#td_total_reestimated').css('background-color', data.budgetUpdate.cmdTotalReestimatedColor);
                     $('#td_total_drift').text(data.budgetUpdate.cmdTotalDrift);
                     $('#td_total_drift').css('background-color', totalDriftColor);
                     $('#td_total_drift_percent').text(data.budgetUpdate.cmdTotalDriftPercent);
                     $('#td_total_drift_percent').css('background-color', totalDriftColor);
                     $('#td_prov_and_mee_cost').text(data.budgetUpdate.cmdProvAndMeeCost);
                     $('#td_total_reestimated_cost').text(data.budgetUpdate.cmdTotalReestimatedCost);
                     $('#td_total_reestimated_cost').css('background-color', data.budgetUpdate.cmdTotalReestimatedCostColor);
                     $('#td_total_drift_cost').text(data.budgetUpdate.cmdTotalDriftCost);
                     $('#td_total_drift_cost').css('background-color', totalDriftColor);
                  }

                  // WBS Update
                  $('#bt_loadTree').click();

                  // Update Message
                  var alertMessage = $('.alert-message');
                  var message = $('<div />', {
                        html: commandInfoSmartyData.i18n_reloadPageToUpdateIndicators
                     });
                  alertMessage.hasClass('error') ? alertMessage.removeClass('error').addClass('info').html(message) : alertMessage.addClass('info').html(message);
               } else {
                  console.error("Ajax statusMsg", data.statusMsg);
                  alert(data.statusMsg);
               }
            },
            error: function (jqXHR, textStatus, errorThrown) {
               console.error(textStatus, errorThrown);
               alert('ERROR: Please contact your CodevTT administrator.');
            }
         });
      }
   });
});
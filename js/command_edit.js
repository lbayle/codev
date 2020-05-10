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


   if (smartyDataCmdEdit.datepickerLocale !== 'en') {
      jQuery.datepicker.setDefaults($.datepicker.regional[smartyDataCmdEdit.datepickerLocale]);
   }

   // Set the date
   if ('' !== smartyDataCmdEdit.cmdStartDate) {
      jQuery("#cmdStartDate").datepicker("setDate", smartyDataCmdEdit.cmdStartDate);
   } else {
      jQuery("#cmdStartDate").datepicker();
   }
   if ('' !== smartyDataCmdEdit.cmdDeadline) {
      jQuery("#cmdDeadline").datepicker("setDate", smartyDataCmdEdit.cmdDeadline);
   } else {
      jQuery("#cmdDeadline").datepicker();
   }

   // Add range date
   jQuery("#cmdStartDate").datepicker("option","beforeShow",function(input) {
      jQuery(this).datepicker("option","maxDate",jQuery("#cmdDeadline").datepicker("getDate"));
   });
   jQuery("#cmdDeadline").datepicker("option","beforeShow",function(input) {
      jQuery(this).datepicker("option","minDate",jQuery("#cmdStartDate").datepicker("getDate"));
   });



   jQuery("#btSaveCommand").click(function() {
      // check fields
      var foundError = 0;
      var msgString = "";

      var form = jQuery("#updateCmdInfoForm");
      if (0 == form.find("input[name=cmdName]").val().length) {
         msgString += smartyDataCmdEdit.i18n_missingFieldCmdName+"\n";
         ++foundError;
      }
      if ('' != form.find("input[name=cmdTotalSoldDays]").val()) {
         bValid = checkRegexp(form.find("input[name=cmdTotalSoldDays]"), /^[0-9]+(\.[0-9]+)?$/i, "format: '1234' or '123.35'");
         if (!bValid) {
            msgString += smartyDataCmdEdit.i18n_nanSoldCharge+"\n";
            ++foundError;
         }
      }

      if (0 === foundError) {
         if ("" !== smartyDataCmdEdit.cmdId) {
            // function defined in wbsEditor.html
            var deferred = saveTree();
            // set success callback:
            deferred.done(function () {
               // If WBS save ok, then save & quit
               form.submit();
            });
            // set error callback:
            deferred.fail(function () {
               $("#tree").fancytree("destroy");
               initTree();
            });
         } else {
            form.submit();
         }

      } else {
         alert(msgString);
      }
   });

   if ("" !== smartyDataCmdEdit.cmdId) {
      // DialogBox for deleteCommand
      jQuery("#btDeleteCommand").click(function(event) {
         jQuery("#deleteCommand_dialog_form").dialog("open");
      });
   }

//   if ("" !== smartyDataCmdEdit.isAddIssueForm) {
      jQuery("#btAddCmdIssue").click(function(event) {
         addCmdIssue();
      });

      jQuery("#bugid").keypress(function(event) {
         if ( jQuery.ui.keyCode.ENTER == event.keyCode ) {
            addCmdIssue();
            event.preventDefault();
         }
      });
//   }

   jQuery.ajax({
      url: "js_min/datatable.min.js",
      dataType: "script",
      cache: true
   });

   // delete track dialogBox
   jQuery("#deleteCommand_dialog_form").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: smartyDataCmdEdit.i18n_delete,
            click: function() {
               jQuery("#formDeleteCommand").submit();
            }
         },
         {
            text: smartyDataCmdEdit.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   // delete track dialogBox
   jQuery("#removeCmdIssue_dialog_form").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: smartyDataCmdEdit.i18n_remove,
            click: function() {
               jQuery("#formRemoveCmdIssue").submit();
            }
         },
         {
            text: smartyDataCmdEdit.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   // delete track dialogBox
   jQuery("#removeCmdSet_dialog_form").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: smartyDataCmdEdit.i18n_remove,
            click: function() {
               jQuery("#formRemoveCmdSet").submit();
            }
         },
         {
            text: smartyDataCmdEdit.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   // ---------------------------------------------------
   // Note: use 'on' instead of 'click' because we need bubbling
   // when new row is added, buttons need to subscribe to existing events
   jQuery("#provisionsTable").on("click", ".deleteProvision_link", function(e) {
      e.preventDefault();
      var trProv = $(this).parents('.provRow');
      var rowId = $(trProv).attr('data-provRowId');
      var provDate = $(trProv).children('.provDate').text();
      var provType = $(trProv).children('.provType').text();
      var provBudgetDays = $(trProv).children('.provBudgetDays').text();
      var provBudget = $(trProv).find('.provBudget').text();
      var provCurrency = $(trProv).find('.provCurrency').text();
      var provSummary = $(trProv).children('.provSummary').text();
      var confirmString = smartyDataCmdEdit.i18n_confirmDeleteProvision + "\n\n" +
              provDate + " " +
              provType + "\n" +
              provBudgetDays + " " + smartyDataCmdEdit.i18n_days + "\n" +
              provBudget + " " + provCurrency + "\n\n" +
              provSummary;

      if (confirm(confirmString)) {
         $.ajax({
            url: smartyDataCmdEdit.ajaxPage,
            type: "POST",
            dataType:"json",
            data: {
               action: 'deleteProvision',
               cmdId: smartyDataCmdEdit.cmdId,
               provRowId: rowId
            },
            success: function (data) {
               if (null === data) {
                     console.error('ERROR deleteProvision: no data');
                     alert("ERROR: Please contact your CodevTT administrator");
               } else {
                  if ('SUCCESS' !== data.statusMsg) {
                     console.error("ERROR Ajax statusMsg", data.statusMsg);
                     alert(data.statusMsg);
                  } else {
                     // remove line from provisionsTable
                     jQuery(".provRow[data-provRowId='" + data.rowId + "']").remove();
                  }
               }
            },
            error:  function (jqXHR, textStatus, errorThrown) {
               console.error('ERROR deleteProvision errorThrown', errorThrown);
            }
         });
      }
   });

   // -------------------------------------------------------
   // read row and open dialog to edit the Provision
   // Note: use 'on' instead of 'click' because we need bubbling
   // when new row is added, buttons need to subscribe to existing events
   jQuery("#provisionsTable").on("click", ".editProvision_link", function(e) {

      var trProv = $(this).parents('.provRow');
      var rowId = $(trProv).attr('data-provRowId');
      var provDate = $(trProv).children('.provDate').text();
      var provTypeId = $(trProv).children('.provType').attr('data-provTypeId');
      var provBudgetDays = $(trProv).children('.provBudgetDays').text();
      var provBudget = $(trProv).find('.provBudget').text();
      var provCurrency = $(trProv).find('.provCurrency').text();
      var provSummary = $(trProv).children('.provSummary').text();
      var checked = $(trProv).find(".cbIsInCheckBudget").is(":checked");

      // fill addProvision_dialog_form with row values
      jQuery("#type").val(provTypeId);
      jQuery("#datepicker").val(provDate);
      jQuery("#budgetDays").val(provBudgetDays);
      jQuery("#budget").val(provBudget);
      jQuery("#provisionCurrency").val(provCurrency);
      jQuery("#provisionCurrencyDisplayed").html(provCurrency);
      jQuery("#summary").val(provSummary);
      jQuery("#cb_isInCheckBudget").prop('checked', checked); // WARN rename cb !!!
      jQuery("#addProvRowId").val(rowId);
      jQuery("#averageDailyRate").val('');
      updateADR();

      // use addProvision_dialog_form, just change action name to 'editProvision'
      var dialog = jQuery("#addProvision_dialog_form");
      dialog.dialog('option', 'title', smartyDataCmdEdit.i18n_editProvision);
      jQuery("#addProvDlgAction").val('editProvision');
      dialog.dialog("open");

   });

   // --------------------------------------------------------
   jQuery("#btAddProvision").click(function(event) {
      var dialog = jQuery("#addProvision_dialog_form");
      dialog.dialog('option', 'title', smartyDataCmdEdit.i18n_addProvision);
      jQuery("#addProvDlgAction").val('addProvision');
      jQuery("#addProvRowId").val(0);
      jQuery("#datepicker").val('');
      jQuery("#budgetDays").val('');
      jQuery("#budget").val('');
      jQuery("#summary").val('');
      jQuery("#averageDailyRate").val('');
      jQuery("#cb_isInCheckBudget").prop('checked', false); // WARN rename cb !!!

      dialog.dialog("open");
   });

   // add/edit provision dialogBox
   jQuery("#addProvision_dialog_form").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: "OK", // smartyDataCmdEdit.i18n_add,
            click: function() {
               var isInCheckBudget = jQuery("#cb_isInCheckBudget").attr('checked')?1:0;
               var form = jQuery("#formAddProvision");
               form.find("input[name=isInCheckBudget]").val(isInCheckBudget);

               // TODO check fields validity before sending to the server
               jQuery.ajax({
                  url: smartyDataCmdEdit.ajaxPage,
                  type: "POST",
                  dataType:"json",
                  async: false,
                  //processData: false,
                  //contentType: false,
                  data: form.serialize(),
                  success: function(data) {
                     if ('SUCCESS' === data.statusMsg) {
                        prov = data.provData;

                        if ('editProvision' === data.action) {
                           // update existing row
                           var myTr = $(".provRow[data-provRowId="+prov.provId+"]");
                           myTr.find(".provDate").text(prov.date);
                           myTr.find(".provType").text(prov.type).attr('data-provTypeId', data.type_id);
                           myTr.find(".provBudgetDays").text(prov.budget_days);
                           myTr.find(".provBudget").text(prov.budget);
                           myTr.find(".provCurrency").text(prov.currency);
                           myTr.find(".provSummary").html(prov.summary);
                           myTr.find(".cbIsInCheckBudget").prop('checked', prov.is_checked);
                           myTr.css("background-color", "#ccffcc"); // #ffff66

                        } else {
                           // addProvision
                           var trObj = createProvisionTr(prov);
                           trObj.css("background-color", "#ffff99");
                           trObj.prependTo("#provisionsTable tbody");
                        }
                     } else {
                        console.error("Ajax statusMsg", data.statusMsg);
                        alert(data.statusMsg);
                     }
                  },
                  error: function(jqXHR, textStatus, errorThrown) {
                     if('Forbidden' === errorThrown ) {
                        window.location = smartyDataCmdEdit.page;
                     }else {
                        console.error(textStatus, errorThrown);
                        alert("ERROR: Please contact your CodevTT administrator");
                     }
                  }
               });
               jQuery(this).dialog("close");
            }
         },
         {
            text: smartyDataCmdEdit.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });

   // --------------------------------------------------------
   // save the file data to a file variable for later use.
   var provCsvFile;
   jQuery("#fileInput").on("change", function(e) {
      provCsvFile = e.target.files[0];
      //console.log(provCsvFile);
   });

   jQuery("#btImportProvision").click(function(event) {

      // TODO check if csv file has been specified in #fileInput

      var data = new FormData();
      data.append("uploaded_csv", provCsvFile);
      data.append("action","importProvisionCSV");
      data.append("cmdid",smartyDataCmdEdit.cmdId);

      jQuery.ajax({
         async: false,
         type: "POST",
         url: smartyDataCmdEdit.ajaxPage,
         data: data,
         processData: false,
         contentType: false,
         dataType:"json",
         success: function(data) {
            if ('SUCCESS' === data.statusMsg) {
               provData = data.provData;
               jQuery.each(provData,function(key,prov) {
                  var trObj = createProvisionTr(prov);
                  trObj.css("background-color", "#ffff99");
                  trObj.prependTo("#provisionsTable tbody");
               });
            } else {
               console.error("Ajax statusMsg", data.statusMsg);
               alert(data.statusMsg);
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            if('Forbidden' === errorThrown ) {
               window.location = smartyDataCmdEdit.page;
            }else {
               console.error(textStatus, errorThrown);
               alert("ERROR: Please contact your CodevTT administrator");
            }
         }
      });
   });

   jQuery("#provisionCurrency").on("change", function(e) {
      var newCur = jQuery("#provisionCurrency").val();
      jQuery("#provisionCurrencyDisplayed").html(newCur);
   });

   jQuery("#budgetDays").keyup(function(event) {
      updateBudget();
   });

   jQuery("#averageDailyRate").keyup(function(event) {
      updateBudget();
   });

   jQuery("#budget").keyup(function(event) {
      updateADR();
   });

   jQuery("#type").change(function() {
      // DEFAULT: checked, except if type='management'
      var checked = ( jQuery("#type").val() == smartyDataCmdEdit.cmdProvisionTypeMngt) ? false : true;
      jQuery("#cb_isInCheckBudget").prop('checked', checked);
   });

}); // document ready
// =========================================================

function checkRegexp(o, regexp, n) {
   if (!(regexp.test(o.val()))) {
      //o.addClass("ui-state-error");
      //updateTips(n);
      return false;
   } else {
      return true;
   }
}

function addCmdIssue(){
   // check fields
   var foundError = 0;
   var msgString = "";
   var bug_id = jQuery.trim(document.forms["addCmdIssueForm"].bugid.value);

   if ('' != bug_id) {
      var reg=new RegExp("^[0-9]+$","i");
      if (!reg.test(bug_id)) {
         msgString += smartyDataCmdEdit.i18n_nanTaskId+"\n";
         ++foundError;
      }
   } else {
      msgString += smartyDataCmdEdit.i18n_missingFieldTaskId+"\n";
      ++foundError;
   }


   if (0 == foundError) {
      document.forms["addCmdIssueForm"].submit();
   } else {
      alert(msgString);
   }
}

function removeCmdIssue(bugid, project, description){
   jQuery("#desc_id").text(bugid);
   jQuery("#desc_project").text(project);
   jQuery("#desc_summary").text(description);

   jQuery("#formRemoveCmdIssue").find("input[name=bugid]").val(bugid);

   jQuery("#removeCmdIssue_dialog_form").dialog( "open" );
}

function removeCmdSet(commandsetid, name){
   var dialog = jQuery("#removeCmdSet_dialog_form");
   dialog.find(".desc_id").text(commandsetid);
   dialog.find(".desc_name").text(name);

   dialog.find("#formRemoveCmdSet").find("input[name=commandsetid]").val(commandsetid);

   dialog.dialog("open");
}

// ------------------------------------
// actions for 'addProvision_dialog_form'
var provData;
function updateADR() {
   if (('' != jQuery("#budget").val()) &&
      (0 != jQuery("#budget").val()) &&
      ('' != jQuery("#budgetDays").val()) &&
      (0 != jQuery("#budgetDays").val())
   ) {
      var adr = parseFloat(jQuery("#budget").val()) / parseFloat(jQuery("#budgetDays").val());
      jQuery("#averageDailyRate").val(adr.toFixed(2));
   }
}
function updateBudget() {
   if (('' != jQuery("#averageDailyRate").val()) &&
      (0 != jQuery("#averageDailyRate").val()) &&
      ('' != jQuery("#budgetDays").val()) &&
      (0 != jQuery("#budgetDays").val())
   ) {
      var budget = parseFloat(jQuery("#budgetDays").val()) * parseFloat(jQuery("#averageDailyRate").val());
      jQuery("#budget").val(budget.toFixed(2));
   }
}

// for tables: provisionsTable
function createProvisionTr(provData) {
   // console.log(provData);
   var trObj = jQuery("<tr></tr>").addClass("provRow").attr('data-provRowId', provData.provId);

   var btDelete = jQuery('<img align="absmiddle" src="images/b_drop.png">')
           .addClass("deleteProvision_link").addClass("pointer")
           .prop('title', smartyDataCmdEdit.i18n_delete);
   var btEdit = jQuery('<img align="absmiddle" src="images/b_edit.png">')
           .addClass("editProvision_link").addClass("pointer")
           .prop('title', smartyDataCmdEdit.i18n_edit);

   var tdObjButtons = jQuery('<td style="width:38px;">').addClass("ui-state-error-text");
   tdObjButtons.append(btDelete).append(' ').append(btEdit);

   var spanBudget = jQuery('<span>').text(provData.budget).addClass("provBudget");
   var spanCurrency = jQuery('<span>').text(provData.currency).addClass("provCurrency");
   var tdObjBudget = jQuery('<td style="text-align: right;">');
   tdObjBudget.append(spanBudget).append(' ').append(spanCurrency);

   trObj.append(tdObjButtons);
   trObj.append(jQuery('<td>').text(provData.date).addClass("provDate"));
   trObj.append(jQuery('<td>').text(provData.type).addClass("provType"));
   trObj.append(jQuery('<td>').text(provData.budget_days).addClass("provBudgetDays"));
   trObj.append(tdObjBudget);
   trObj.append(jQuery('<td>').html(provData.summary).addClass("provSummary"));
   if (provData.is_checked){
      trObj.append(jQuery("<td>").html('<input type="checkbox" class="cbIsInCheckBudget" disabled="disabled" checked/>'));
   } else {
      trObj.append(jQuery("<td>").html('<input type="checkbox" class="cbIsInCheckBudget" disabled="disabled" />'));
   }

   if (provData.csvFileLine){
      // for debugging purpose
      trObj.attr('data-csvFileLine', provData.csvFileLine);
   }
   return trObj;
}

// ------------------------------------

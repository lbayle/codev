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

   // Set the date
   if (editTeamSmartyData.datepickerLocale != 'en') {
      jQuery.datepicker.setDefaults($.datepicker.regional[editTeamSmartyData.datepickerLocale]);
   }
   // Set the date
   jQuery("#datepicker").datepicker("setDate" ,editTeamSmartyData.datepickerDate);

   jQuery("#displayed_teamid").change(function() {
      jQuery("#displayTeamForm").submit();
   });
// ----------
   var updateTeamInfoForm = jQuery("#updateTeamInfoForm");

   jQuery("#btUpdateTeamLeader").click(function() {
      updateTeamInfoForm.find("input[name=action]").val("updateTeamLeader");
      updateTeamInfoForm.submit();
   });

   jQuery("#btupdateTeamCreationDate").click(function() {
      updateTeamInfoForm.find("input[name=action]").val("updateTeamCreationDate");
      updateTeamInfoForm.submit();
   });
   jQuery("#bt_teamEnabled").click(function() {
      var isTeamEnabled = jQuery("#cb_teamEnabled").attr('checked')?1:0;
      updateTeamInfoForm.find("input[name=isTeamEnabled]").val(isTeamEnabled);
      updateTeamInfoForm.find("input[name=action]").val("setTeamEnabled");
      updateTeamInfoForm.submit();
   });
// ----------
   // Set the date
   jQuery("#datepicker1").datepicker("setDate" ,editTeamSmartyData.datepicker1_arrivalDate);
   jQuery("#datepicker2").datepicker("setDate" ,editTeamSmartyData.datepicker2_departureDate);

   var addTeamMemberForm = jQuery("#addTeamMemberForm");

   jQuery("#btAddMember").click(function() {
      // check fields
      var foundError = 0;
      var msgString = editTeamSmartyData.i18n_someFieldsAreMissing+"\n";

      if (0 == addTeamMemberForm.find("input[name=memberid]").val()) {
         msgString += editTeamSmartyData.i18n_teamMember+"\n";
         ++foundError;
      }

      if (0 == foundError) {
         addTeamMemberForm.find("input[name=action]").val("addTeamMember");
         addTeamMemberForm.submit();
      } else {
         alert(msgString);
      }
   });

   jQuery("#btSetMemberDepartureDate").click(function() {
      // check fields
      var foundError = 0;
      var msgString = editTeamSmartyData.i18n_someFieldsAreMissing+"\n\n";

      if (0 == addTeamMemberForm.find("input[name=memberid]").val()) {
         msgString += editTeamSmartyData.i18n_teamMember+"\n";
         ++foundError;
      }

      if (0 == foundError) {
         addTeamMemberForm.find("input[name=action]").val("setMemberDepartureDate");
         addTeamMemberForm.submit();
      } else {
         alert(msgString);
      }
   });

   jQuery("#btAddMembersFrom").click(function() {
      // check fields
      var foundError = 0;
      var msgString = editTeamSmartyData.i18n_someFieldsAreMissing+"\n\n";

      if (0 == addTeamMemberForm.find("input[name=f_src_teamid]").val()) {
         msgString += editTeamSmartyData.i18n_sourceTeam+"\n";
         ++foundError;
      }

      if (0 == foundError) {
         addTeamMemberForm.find("input[name=action]").val("addMembersFrom");
         addTeamMemberForm.submit();
      } else {
         alert(msgString);
      }
   });


// ----------
   jQuery("#btAddProject").click(function() {
      // check fields
      var foundError = 0;
      var msgString = editTeamSmartyData.i18n_someFieldsAreMissing+"\n\n";

      var addTeamProjectForm = jQuery("#addTeamProjectForm");

      if (0 == addTeamProjectForm.find("input[name=addedprojectid]").val()) {
         msgString += editTeamSmartyData.i18n_project+"\n";
         ++foundError;
      }

      if (0 == foundError) {
         addTeamProjectForm.submit();
      } else {
         alert(msgString);
      }
   });

// ----------
   jQuery("#btSetGeneralPrefs").click(function() {
      // check fields

      var generalPrefsForm = jQuery("#generalPrefsForm");

      // TODO get selected checkItems
      var checkItems = "";
      jQuery(".generalPrefsItem").each(function() {
         var itemName = jQuery(this).attr("name");
         var isChecked = jQuery(this).attr('checked') ? 1 : 0;
         checkItems += itemName+":"+isChecked+",";
      });
      //alert("checkItems "+checkItems);
      generalPrefsForm.find("input[name=checkItems]").val(checkItems)
      generalPrefsForm.submit();
   });

// ----------
   jQuery("#btSetConsistencyChecks").click(function() {
      // check fields

      var consistencyCheckForm = jQuery("#consistencyCheckForm");

      // TODO get selected checkItems
      var checkItems = "";
      jQuery(".consistencyCheckItem").each(function() {
         var itemName = jQuery(this).attr("name");
         var isChecked = jQuery(this).attr('checked') ? 1 : 0;
         checkItems += itemName+":"+isChecked+",";
      });
      //alert("checkItems "+checkItems);
      consistencyCheckForm.find("input[name=checkItems]").val(checkItems)
      consistencyCheckForm.submit();
   });

// ----------
   jQuery("#btAddAstreinte").click(function() {
      // check fields
      var foundError = 0;
      var msgString = editTeamSmartyData.i18n_someFieldsAreMissing+"\n\n";

      var addAstreinteForm = jQuery("#addAstreinteForm");

      if (0 == addAstreinteForm.find("input[name=addedastreinte_id]").val()) {
         msgString += editTeamSmartyData.i18n_inactivityTask+"\n";
         ++foundError;
      }

      if (0 == foundError) {
         addAstreinteForm.submit();
      } else {
         alert(msgString);
      }
   });

// ----------
   // delete track dialogBox
   jQuery("#deleteDuration_dialog_form").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      buttons: [
         {
            text: editTeamSmartyData.i18n_delete,
            click: function() {
               jQuery("#formDeleteDuration").submit();
            }
         },
         {
            text: editTeamSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]

   });

// ----------

   // change user in the AddUserDailyCost dialogBox
   jQuery("#udrUserid").change(function() {
      updateAddUdrDlg_userArrivalDate();
   });

   jQuery(".btAddUserDailyCost").click(function() {
      jQuery(".addUdrErrorMsg").html('');
      updateAddUdrDlg_userArrivalDate();
      jQuery(".dialogAddUserDailyCost").dialog("open");
   });

   jQuery(".dialogAddUserDailyCost").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      create:function () {
         // TODO je sais plus a quoi ca sert...
         $(this).closest(".ui-dialog").find(".ui-button:first").addClass("btAddUserDailyCost");
      },
      buttons: [
         {
            text: editTeamSmartyData.i18n_add,
            click: function() {

               // TODO: dataToSend
               var form = $('#formAddUserDailyCost');
               jQuery(".addUdrErrorMsg").html('');

               $.ajax({
                  url: editTeamSmartyData.ajaxPage,
                  type: form.attr('method'),
                  dataType:"json",
                  data: form.serialize(),
                  success: function(data) {
                     if (null === data) {
                        jQuery(".addUdrErrorMsg").html("ERROR: Please contact your CodevTT administrator");
                     } else {
                        if ('SUCCESS' !== data.statusMsg) {
                           jQuery(".addUdrErrorMsg").html(data.statusMsg);
                        } else {
                           // add new line to tableUserDailyCosts
                           var bt_deleteUDC = jQuery('<a align="absmiddle" >').addClass("bt_deleteUDC ui-icon pointer");

                           var trObject = jQuery('<tr>').append(
                               jQuery('<td style="width:38px;">').addClass("ui-state-error-text").append(bt_deleteUDC),
                               jQuery('<td>').text(data.udrStruct.userName),
                               jQuery('<td>').text(data.udrStruct.startDate),
                               jQuery('<td>').text(data.udrStruct.udr+" "+data.udrStruct.currency),
                               jQuery('<td>').text(data.udrStruct.description)
                           ).attr('data-udrId', data.udrStruct.id).addClass("udrRow");
                           trObject.prependTo('#tableUserDailyCosts tbody');

                           jQuery(".addUdrErrorMsg").html('');
                           jQuery(".dialogAddUserDailyCost").dialog("close");
                        }
                     }
                  },
                  error:  function(jqXHR, textStatus, errorThrown) {
                     console.error('ERROR AddUserDailyCost errorThrown', errorThrown);
                     jQuery(".addUdrErrorMsg").html("ERROR: Please contact your CodevTT administrator");
                  }
               });
            }
         },
         {
            text: editTeamSmartyData.i18n_cancel,
            click: function() {
               jQuery(".addDashboardErrorMsg").html('');
               jQuery(this).dialog("close");
            }
         }

      ]
   });

   // Note: use 'on' instead of 'click' because we need bubbeling
   // when new html content is added, buttons need to subscribe to existing events
   jQuery("#tableUserDailyCosts").on("click", ".bt_deleteUDC", function(e) {

      var parentRow = jQuery(this).closest("tr.udrRow");
      var udrId = parentRow.attr("data-udrId");
      jQuery(".deleteUdrErrorMsg").html('');

      // TODO "are you sure ?" dialogbox

      jQuery.ajax({
         url: editTeamSmartyData.ajaxPage,
         async:false, // why ?
         data: {
            action: 'deleteUDC',
            displayed_teamid:editTeamSmartyData.displayedTeamId,
            udrId: udrId
         },
         type: 'post',
         success: function(data) {
            data = JSON.parse(data);

            if('SUCCESS' !== data.statusMsg) {
               console.log(data.statusMsg);
               jQuery(".deleteUdrErrorMsg").html("ERROR: Failed to remove UDC");
            } else {
               // remove line from tableUserDailyCosts
               jQuery(".udrRow[data-udrId='" + data.udrId + "']").remove();
            }
         },
         error: function(jqXHR, textStatus, errorThrown) {
            console.error('ERROR AddUserDailyCost errorThrown', errorThrown);
            jQuery(".deleteUdrErrorMsg").html("ERROR: Please contact your CodevTT administrator");
         }
      });
   });

   jQuery("#btUpdateTeamADR").click(function() {
      var form = $('#formTeamADR');
      jQuery(".updateTeamAdrInfoMsg").html('');
      jQuery(".updateTeamAdrErrorMsg").html('');

      $.ajax({
         url: editTeamSmartyData.ajaxPage,
         type: form.attr('method'),
         dataType:"json",
         data: form.serialize(),
         success: function(data) {
            if (null === data) {
               jQuery(".updateTeamAdrErrorMsg").html("ERROR: Please contact your CodevTT administrator");
            } else {
               if ('SUCCESS' !== data.statusMsg) {
                  jQuery(".updateTeamAdrErrorMsg").html(data.statusMsg);
               } else {
                  jQuery(".updateTeamAdrInfoMsg").html(editTeamSmartyData.i18n_updatedSuccessfully);
                   $('.displayedTeamCurrency').html(data.teamCurrency);
               }
            }
         },
         error:  function(data) {
            console.error('ERROR data = ', data);
            jQuery(".updateTeamAdrErrorMsg").html("UpdateTeamADR ERROR!");
            //deferred.reject(); // call the 'fail' callback (if defined)
         }
      });
   });

   jQuery(".teamAdrHelpDialog_link").click(function(e) {
      e.preventDefault();
      jQuery(".teamAdrHelpDialog").dialog("open");
   });
   jQuery(".teamAdrHelpDialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      hide: "fade"
   });
   jQuery(".currencyHelpDialog_link").click(function(e) {
      e.preventDefault();
      jQuery(".currencyHelpDialog").dialog("open");
   });
   jQuery(".currencyHelpDialog").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      hide: "fade"
   });

}); // document ready

function updateAddUdrDlg_userArrivalDate() {
   jQuery.ajax({
      url: editTeamSmartyData.ajaxPage,
      data: {
         action: 'getUserArrivalDate',
         displayed_teamid:editTeamSmartyData.displayedTeamId,
         udrUserid: jQuery("#udrUserid").val()
      },
      type: 'post',
      success: function(data) {
         data = JSON.parse(data);

         if('SUCCESS' !== data.statusMsg) {
            console.error(data.statusMsg);
            jQuery("#addUdrDlg_userArrivalDate").html("ERROR");
         } else {
            jQuery("#addUdrDlg_userArrivalDate").html(data.arrivalDate);
         }
      },
      error: function(jqXHR, textStatus, errorThrown) {
         console.error('ERROR AddUserDailyCost errorThrown', errorThrown);
         jQuery(".deleteUdrErrorMsg").html("ERROR: Please contact your CodevTT administrator");
      }
   });
}

function removeTeamMember(id, description) {
   var confirmString = editTeamSmartyData.i18n_removeUserFromThisTeam + "\n\n" + description;
   var removeTeamMemberForm = jQuery("#removeTeamMemberForm");
   if (confirm(confirmString)) {
      removeTeamMemberForm.find("input[name=deletememberid]").val(id);
      removeTeamMemberForm.submit();
   }
}

function removeTeamProject(id, description){
   var confirmString = editTeamSmartyData.i18n_removeThisProjectFromTheTeam + "\n\n" + description;
   if (confirm(confirmString)) {
      var removeTeamProjectForm = jQuery("#removeTeamProjectForm");
      removeTeamProjectForm.find("input[name=deletedprojectid]").val(id);
      removeTeamProjectForm.submit();
   }
}

// this function is called by the selectItemsDialogbox, when 'OK' is clicked
function itemSelection_ok_callback(data) {
   // update tooltips table
   // data contains the new row to add/replace in destination widget.
   var response = jQuery.parseJSON(data);
   var projectid = response["projectid"];
   var line = jQuery("#issueTooltips_proj_"+response["projectid"]);
   if (line.length) {
      // update existing line
      jQuery("#issueTooltips_proj_"+projectid+"_fields").text(response["tooltipFields"]);
   } else {
      // create new line
      jQuery("#issueTooltipsTable").attr("style",""); // remove display:none
      jQuery("#noCustomTooltipsInfo").attr("style","display:none");
      jQuery("#issueTooltipsTable").append(
         jQuery('<tr>').attr('id', "issueTooltips_proj_"+projectid)
      );
      var deleteLink = jQuery('<a>').attr('class', 'ui-icon').attr('href',editTeamSmartyData.page).
                                    attr('onclick', "removeIssueTooltip('"+projectid+"','"+response["projectName"]+"');return false;");
      jQuery("#issueTooltips_proj_"+projectid).append(
         jQuery('<td>').attr('class', 'ui-state-error-text').attr("style","width:1em;").append(deleteLink)
      ).append(
         jQuery('<td>').text(response["projectName"])
      ).append(
         jQuery('<td>').attr('id', "issueTooltips_proj_"+projectid+"_fields").text(response["tooltipFields"])
      );
   }
}

function removeIssueTooltip(project_id, project_name){
   var confirmString = editTeamSmartyData.i18n_removeIssueTooltipCustomisationForProject + " " + project_name;
   if (confirm(confirmString)) {
      var removeIssueTooltipForm = jQuery("#removeIssueTooltipForm");
      removeIssueTooltipForm.find("input[name=projectid]").val(project_id);
      removeIssueTooltipForm.submit();
   }
}

function deleteOnDutyTask(id, description){
   var confirmString = editTeamSmartyData.i18n_noLongerOnDuty + "\n" + description;
   if (confirm(confirmString)) {
      var deleteAstreinteForm = jQuery("#deleteAstreinteForm");
      deleteAstreinteForm.find("input[name=deletedastreinte_id]").val(id);
      deleteAstreinteForm.submit();
   }
}

function deleteDuration(value, display){
   jQuery("#dur_value").text(value);
   jQuery("#dur_display").text(display);
   jQuery("#formDeleteDuration").find("input[name=deleteValue]").val(value);
   jQuery("#formDeleteDuration").find("input[name=displayed_teamid]").val(document.getElementById("displayed_teamid").value);
   jQuery("#deleteDuration_dialog_form").dialog("open");
}

function addDuration(){
   jQuery("#formAddDuration").find("input[name=addValue]").val(document.getElementById("addValue").value);
   jQuery("#formAddDuration").find("input[name=addDisplay]").val(document.getElementById("addDisplay").value);
   jQuery("#formAddDuration").find("input[name=displayed_teamid]").val(document.getElementById("displayed_teamid").value);
   var msg = validateAdd();
   if (msg.length == 0) {
      jQuery("#formAddDuration").submit();
   } else {
      alert(msg);
   }
}

function editDuration(el){
   var display = el.innerHTML;
   el.onclick = undefined;
   var value = el.id.replace("display","");
   el.innerHTML = "<input type='text' value='"+display+"' style='background-color:#F3F781;' id='editDisplay"+value+"' />"+
            "<a class='ui-icon' title='"+editTeamSmartyData.i18n_save+"' href='"+editTeamSmartyData.page+"' style='background:url(images/b_markAsRead.png);' "+
            "onclick='updateDuration("+value+");return false;'></a>";
}

function updateDuration(value){
   jQuery("#formUpdateDuration").find("input[name=updateValue]").val(value);
   jQuery("#formUpdateDuration").find("input[name=updateDisplay]").val(document.getElementById("editDisplay"+value).value);
   jQuery("#formUpdateDuration").find("input[name=displayed_teamid]").val(document.getElementById("displayed_teamid").value);
   var msg = validateUpdate();
   if (msg.length == 0) {
      jQuery("#formUpdateDuration").submit();
   } else {
      alert(msg);
   }
}

function validateAdd(){
   var value = "";
   var display = "";
   var msg = "";
   value = document.forms["formAddDuration"]["addValue"].value;
   display = document.forms["formAddDuration"]["addDisplay"].value;
   value = value.replace(/\s+/g, '');
   display = display.replace(/\s+/g, '');
   if (value.length == 0){
      msg += "\n Value must be filled out.";
   } else {
      if (isNaN(value)){
         msg += "\n Value must be numeric.";
      } else {
         value = parseFloat(value);
         if (value < 0 || value > 1) {
            msg += "\n Value must be between 0 and 1.";
         }
      }
   }
   if (display.length == 0){
      msg += "\n Display must be filled out.";
   }

   return msg;
}

function validateUpdate(){
   var display = "";
   var msg = "";
   display = document.forms["formUpdateDuration"]["updateDisplay"].value;
   display = display.replace(/\s+/g, '');
   if (display.length == 0){
      msg += "\n Display must be filled out";
   }

   return msg;
}





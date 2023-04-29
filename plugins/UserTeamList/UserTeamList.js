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

// destroy callback: called when the widjet is removed from the dashboard (see inettuts_codevtt.js).
function userTeamListJsDestroy() {
   console.log('userTeamListJsDestroy');
   //jQuery(".userTeamListHelpDialog").dialog('destroy').remove();
}

// this function will be run at jQuery(document).ready (see dashboard.html) or
// when a new widjet is added to the dashboard.
function userTeamListJsInit() {
console.log("userTeamListJsInit");

   // set select2 with width adapted to the content
   $(".userTeamList_userid").select2({ width: 'resolve' });

   // ------------------------
   // on reload with new date range
   jQuery('.userTeamList_submit').click(function(event) {
      /* stop form from submitting normally */
      event.preventDefault();

      var form = jQuery('#userTeamList_dateRange_form');

      var dashboardId = $(this).parents('.codevttDashboard').attr('data-dashboardId');
      form.find("input[name=dashboardId]").val(dashboardId);

      var url = form.attr('action');
      var type = form.attr('method');
      jQuery.ajax({
         async: false,
         type: type,
         url: url,
         dataType:"json",
         data: form.serialize(),
         success: function(data) {

            jQuery("#userTeamListDiv").html(jQuery.trim(data['userTeamList_htmlContent']));

            jQuery.each(data['userTeamList_jsFiles'], function( index, value ) {
               jQuery.ajax({
                     async: false,
                     url: value,
                     dataType: "script"
               });
            });
         },
         error: function(jqXHR, textStatus, errorThrown) {
            if(errorThrown == 'Forbidden') {
               window.location = userTeamListSmartyData.page; // TODO
            }
         }
      });
   });
   
   // ----------------------------------------------------------------------
   $("#userTeamListDiv").on("click", ".userTeamList_link", function(e) {
      e.preventDefault();
      
      var trTeam = $(this).parents('.userTeamList_userTeamsTr');
      var teamid = $(trTeam).attr('data-teamid');
      var arrivalDate = $(trTeam).children('.userTeamList_arrivalDate').text();
      var departureDate = $(trTeam).children('.userTeamList_departureDate').text();
      var roleId = $(trTeam).children('.userTeamList_accessLevel').attr('data-accessLevel_id');

//      jQuery('#userTeamList_dialog').dialog('option', 'title', realname);
      jQuery("#userTeamListDlg_teamId").text(teamid);
      jQuery("#userTeamListDlg_arrivalDate").datepicker("setDate" , arrivalDate);
      jQuery("#userTeamListDlg_departureDate").datepicker("setDate" , departureDate);
      jQuery("#userTeamListDlg_select_role").val(roleId);

      jQuery("#userTeamList_dialog").dialog( "open" );
   });

   jQuery("#userTeamList_dialog").dialog({
      autoOpen: false,
      resizable: true,
      height: 'auto',
      width: 400,
      modal: true,
      buttons: [
         {
            text: userTeamListSmartyData.i18n_update,
            click: function() {

               var dashboardId = jQuery('.codevttDashboard').attr('data-dashboardId');
               var displayed_userid = $('.userTeamList_userid').find(":selected").val();

               $.ajax({
                  url: userTeamListSmartyData.ajaxPage,
                  type: "POST",
                  dataType:"json",
                  data: {
                     action: 'updateUserTeamInfo',
                     displayed_userid: displayed_userid,
                     teamid: $("#userTeamListDlg_teamId").text(),
                     arrivalDate: $("#userTeamListDlg_arrivalDate").val(),
                     departureDate: $("#userTeamListDlg_departureDate").val(),
                     accessLevelId: $('#userTeamListDlg_select_role option:selected').val(),
                     dashboardId: dashboardId
                  },
                  success: function(data) {
                     if ('SUCCESS' === data.statusMsg) {
                        // UPDATE table
                        var myTr = $(".userTeamList_userTeamsTr[data-teamid="+data.teamId+"]");
                        myTr.find(".userTeamList_arrivalDate").html(data.arrivalDate);
                        myTr.find(".userTeamList_departureDate").html(data.departureDate);
                        myTr.find(".userTeamList_accessLevel").html(data.accessLevel);
                        myTr.find(".userTeamList_accessLevel").attr('data-accessLevel_id', data.accessLevelId);
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
            text: userTeamListSmartyData.i18n_cancel,
            click: function() {
               jQuery(this).dialog("close");
            }
         }
      ]
   });
   
   
   
   
};


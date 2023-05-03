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
   function CustomUserDataJsDestroy() {
      console.log('CustomUserDataJsDestroy');
      //jQuery(".CustomUserDataHelpDialog").dialog('destroy').remove();
   }

   // this function will be run at jQuery(document).ready (see dashboard.html) or
   // when a new widjet is added to the dashboard.
   function CustomUserDataJsInit() {
      //console.log('CustomUserDataJsInit');

      // --------------------------------------------------------------
      $("#CustomUserData_table").on("click", ".userFieldTd", function(e) {
         e.preventDefault();

         // dynamicaly construct the input field if not exist
         var userFieldTd = jQuery(this);
         var userFieldInput = userFieldTd.children('.userFieldInput');
         if (0 === $(userFieldInput).length) {
            var userFieldSpan = userFieldTd.children(".userFieldSpan");
            var fieldInput = $('<input type="text" style="width:100%;" />');
            fieldInput.addClass("userFieldInput").val(userFieldSpan.text());

            userFieldTd.css("background-color","white"); // TODO white is wrong, it disables datatable CSS (line color on hover)
            userFieldSpan.hide();
            userFieldTd.append(fieldInput);
            fieldInput.focus().select();
         }
      });

      $("#CustomUserData_table").on("focusout", ".userFieldInput", function(e) {
         e.preventDefault();
         $(".userFieldSpan").show();
         $('.userFieldInput').remove();
      });

      $("#CustomUserData_table").on("change", ".userFieldInput", function(e) {
         e.preventDefault();

         var userFieldInput = jQuery(this);
         var userFieldTd = userFieldInput.parent();
         var userFieldName = userFieldTd.attr("data-field");

         userFieldTd.children(".userFieldSpan").text(userFieldInput.val().trim());

         var row = userFieldTd.parent();
         var userid = row.attr("data-userid");
         var dashboardId = $(this).parents('.codevttDashboard').attr('data-dashboardId');

         // send attributesJsonStr (empty for this plugin, but still...)
         // Note: AdminToolsAttr is declared in dashboard.html
         var attributesJsonStr = jQuery('.AdminToolsAttr.attributesJsonStr');


         jQuery.ajax({
            url: CustomUserDataSmartyData.ajaxPage,
            async: false,
            type: "POST",
            dataType:"json",
            data: { action: 'updateUserField',
                    userid: userid,
                    userFieldName: userFieldName,
                    userFieldValue: userFieldInput.val().trim(),
                    dashboardId: dashboardId,
                    attributesJsonStr: attributesJsonStr.text()
            },
            success: function(data) {
               if('SUCCESS' === data.statusMsg) {
                  userFieldTd.css("background-color","lightgreen");
                  userFieldTd.animate({backgroundColor: 'white'}, "slow");
               
                  // remove the select, once finished
                  // Chrome: DOMException: Failed to execute 'removeChild' on 'Node': The node to be removed is no longer a child of this node. Perhaps it was moved in a 'blur' event handler?
                  $(':focus').blur(); // force durationSelect focusout by giving focus to the document.body
                  
               } else {
                  console.error('updateUserField Ajax', data);
                  userFieldTd.css("background-color","#FF809F"); // red
               }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               userFieldTd.css("background-color","#FF809F"); // red
               console.error('textStatus', textStatus);
            }
         });

      });
   
   
   } // CustomUserDataJsInit



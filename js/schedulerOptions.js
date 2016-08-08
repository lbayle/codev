
/**
* Set summary table
* @param {html} timePerUserPerTaskHTML : table in html
* @returns {undefined}
*/
function setTimePerUsersPerTaskSummaryTable(timePerUserPerTaskHTML)
{
   var timePerUserPerTaskSummary = jQuery(".scheduler_timePerUsersPerTaskSummary");
   timePerUserPerTaskSummary.empty();
   timePerUserPerTaskSummary.html(timePerUserPerTaskHTML);
}

/**
* Set the task select list
* @param {type} taskList : [id => name]
* @returns {undefined}
*/
function setTaskSelectList(taskList)
{
   var taskSelectList = jQuery("select.scheduler_taskList");
   // Disable select2 to update list
   taskSelectList.select2("destroy");

   taskSelectList.empty();

   // Set unselected user list
   if(null != taskList)
   {
      for(var taskId in taskList)
      {
         var taskOption = document.createElement("option");
         taskOption.setAttribute("value", taskId);
         taskOption.innerHTML = taskList[taskId];

         // Add element to select list
         taskSelectList.append(taskOption);
      }
      taskSelectList.trigger("change");
   }

   // Enable select2 to update list
   taskSelectList.select2();
}


/**
 * Initialize scheduler affectations tab
 * @returns {undefined}
 */
function initSchedulerAffectations(){
   
   
   
   // ================ Initialization ================
      
  
   // Ask to server already affected tasks (to feel summary table)
   jQuery.ajax({ 
      url: 'scheduler/scheduler_ajax.php',
      async:false,
      data: {
         action: 'getAllTaskUserList',
      },
      type: 'post',
      success: function(data) {
         data = JSON.parse(data);
         
         if(null != data['scheduler_summaryTableHTML'])
         {
           setTimePerUsersPerTaskSummaryTable(data['scheduler_summaryTableHTML']);
         }

      },
      error: function(errormsg) {
         console.log(errormsg);
      }
   });
   
   
   
         
   // ================ Functions ================
   
   
   function reinitializeTableAndSelects(unselectedUserList, selectedUserList, taskUserList, taskHandlerId)
   {
      var userList = jQuery("select.scheduler_userList");
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      
      jQuery(".scheduler_messageSave").text("");
      
      // Disable select2
      userList.select2("destroy");
      userList.empty();

      // Set unselected user list
      if(null != unselectedUserList)
      {
         for(var userId in unselectedUserList)
         {
            var userOption = document.createElement("option");
            userOption.setAttribute("value", userId);
            userOption.innerHTML = unselectedUserList[userId];

            // Add element to select list
            userList.append(userOption);
         }
      }
      userList.select2();

      // Set selected user list
      userTable.empty();
      if(null != selectedUserList && null != taskUserList)
      {
         for(var userId in selectedUserList)
         {
            if(userId == taskHandlerId)
            {
               addUser(userId, selectedUserList[userId], taskUserList[userId], false);
            }
            else
            {
               addUser(userId, selectedUserList[userId], taskUserList[userId], true);
            }
            
         }
      }
      
      checkTotalEffort();
   }
   
   /*
    * Add a user to the selected user table
    * @param {int} userId
    * @param {string} userName
    * @param {int} time : affected time of user on task
    * @param {boolean} removable : true if user can be removed of the list
    * @returns {undefined}
    */
   function addUser(userId, userName, time, removable)
   {
      var autoAffectation = false;
      if(0 > time)
      {
         time = 0;
      }
      
      if(null == time)
      {
         time = "";
      }
      
      if("" == time)
      {
         autoAffectation = true;
      }
      
      if(null == removable)
      {
         removable = true;
      }
      
      var addedUsers = jQuery(".scheduler_addedUsers");
      var userList = jQuery("select.scheduler_userList");
      
      var trUser = document.createElement("tr");
      trUser.setAttribute("data-userId", userId);
      
      // Remove column
      var tdRemoveUser = document.createElement("td");
      tdRemoveUser.setAttribute("class", "ui-state-error-text");
      trUser.appendChild(tdRemoveUser);
      
      if(removable)
      {
         var removeButton = document.createElement("a");
         removeButton.setAttribute("class", "ui-icon"); 
         tdRemoveUser.appendChild(removeButton);
      }
      
      // Username column
      var tdName = document.createElement("td");
      tdName.innerHTML = "" + userName;
      tdName.setAttribute("class", "scheduler_userName"); 
      trUser.appendChild(tdName);
      
      // Time column
      var tdTimeIput = document.createElement("td");
      tdTimeIput.setAttribute("class", "scheduler_userTime");
      trUser.appendChild(tdTimeIput);
      
      var timeInput = document.createElement("input");
      timeInput.setAttribute("type", "text"); 
      timeInput.setAttribute("value", time);
      timeInput.disabled = autoAffectation;
      tdTimeIput.appendChild(timeInput);
      
      // Button time column
      var tdAddTime = document.createElement("td");
      tdAddTime.setAttribute("class", "scheduler_addTime");
      trUser.appendChild(tdAddTime);
      
      var minusButton = document.createElement("input");
      minusButton.setAttribute("type", "button"); 
      minusButton.setAttribute("value", "-");
      minusButton.setAttribute("class", "scheduler_minusButton");
      minusButton.disabled = autoAffectation;
      tdAddTime.appendChild(minusButton);
      
      var plusButton = document.createElement("input");
      plusButton.setAttribute("type", "button"); 
      plusButton.setAttribute("value", "+");
      plusButton.setAttribute("class", "scheduler_plusButton");
      plusButton.disabled = autoAffectation;
      tdAddTime.appendChild(plusButton);
      
      // Auto time affectation column
      var tdAutoTimeAffectation = document.createElement("td");
      tdAutoTimeAffectation.setAttribute("class", "scheduler_autoAffectation");
      trUser.appendChild(tdAutoTimeAffectation);
      
      var autoAffectationButton = document.createElement("input");
      autoAffectationButton.setAttribute("type", "checkbox"); 
      autoAffectationButton.checked = autoAffectation; 
      tdAutoTimeAffectation.appendChild(autoAffectationButton);
      
      addedUsers.find("table tbody").append(trUser);
      
      checkTotalEffort();
      
      // Events
      $(minusButton).on("click", function(){
         
         var timeInput = $(this).parent().parent().find(".scheduler_userTime input");
         var newValue = parseInt(timeInput.val()) - 1;
         newValue = newValue < 0 ? 0 : newValue;
         timeInput.val(newValue);
         $(timeInput).trigger("change");
      });
      
      $(plusButton).on("click", function(){
         
         var timeInput = $(this).parent().parent().find(".scheduler_userTime input");
         timeInput.val(parseInt(timeInput.val()) + 1);
         $(timeInput).trigger("change");
      });
      
      $(timeInput).on("change", function(){
         
         checkTime($(timeInput));
         var newValue = parseFloat($(this).val());
         newValue = newValue < 0 ? 0 : newValue;
         $(this).val(newValue);
         checkTotalEffort();
      });
      
      $(autoAffectationButton).on("click", function(){

         var isChecked = $(this).prop("checked");
         var timeInput = $(this).parent().parent().find(".scheduler_userTime input");
         var addTimeInputs = $(this).parent().parent().find(".scheduler_addTime input");
         
         timeInput.prop( "disabled", isChecked);
         addTimeInputs.prop( "disabled", isChecked);
         
         if(isChecked)
         {
            timeInput.val(null);
         }
         else
         {
            timeInput.val("0");
         }
         
         checkTotalEffort();
      });
      
      if(removable)
      {
         $(removeButton).on("click", function(){

            var trUser = $(this).parent().parent();
            var userId = trUser.attr("data-userId");;
            var userName = trUser.find(".scheduler_userName").text();

            trUser.remove();

            var userOption = document.createElement("option");
            userOption.setAttribute("value", userId);
            userOption.innerHTML = userName;

            // Disable select2
            userList.select2("destroy");
            // Add element to select list
            userList.append(userOption);
            // Enable select2
            userList.select2();

            checkTotalEffort();
         });
      }
   }
   
   /*
    * Check if total affected effort correspond to total task effort
    * Enable/Disable button if effort is ok/ko
    * @returns {undefined}
    */
   function checkTotalEffort()
   {
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      var usersTime = userTable.find(".scheduler_userTime input");
      var usersAutoAffect = userTable.find(".scheduler_autoAffectation input");
      var selectedTaskId = jQuery("select.scheduler_taskList option:selected").val();
      // One or more user have auto affected time
      var atLeastOneAutoAffectation = false;
      
      // Add users time
      var totalUserTime = 0;
      for(var i=0 ; i<usersTime.length ; i++)
      {
         var userTime = isNaN(parseFloat(usersTime.eq(i).val())) ? 0 : parseFloat(usersTime.eq(i).val());
         totalUserTime = parseFloat(totalUserTime) + userTime;
         totalUserTime = totalUserTime.toFixed(1);
         if(usersAutoAffect.eq(i).prop("checked"))
         {
            atLeastOneAutoAffectation = true;
         }
         
      }
      
      // Write total users time
      jQuery(".scheduler_totalAffectedEffort").text(totalUserTime);
      
      
      var totalAffectedEffortComparison = jQuery(".scheduler_totalAffectedEffortComparison");
      var saveTaskModificationsButton = jQuery(".scheduler_saveUserAssignmentButton");
      
      // Get total estimed effort on task
      var totalEstimedEffort = parseFloat(jQuery(".scheduler_taskEffortEstim").eq(0).text());

      // Check if total users effort is equal to estimed effort or if at least one user time is auto affected
      if((totalEstimedEffort == totalUserTime || atLeastOneAutoAffectation) && "" != selectedTaskId)
      {
         totalAffectedEffortComparison.removeClass("error_font");
         totalAffectedEffortComparison.addClass("success_font");
         saveTaskModificationsButton.prop( "disabled", false );
      }
      else
      {
         totalAffectedEffortComparison.removeClass("success_font");
         totalAffectedEffortComparison.addClass("error_font");
         saveTaskModificationsButton.prop( "disabled", true );
      }
   }
   
   function checkRegexp(o, regexp, n) {
      o.removeClass("ui-state-error");
      if (!(regexp.test(o.val()))) {
         o.addClass("ui-state-error");
         //updateTips(n);
         return false;
      } else {
         return true;
      }
   }
   
   /**
    * Check if time respect imputations standard
    * @param input element
    * @returns {undefined}
    */
   function checkTime(input)
   {
      checkRegexp($(input), /^[0-9]+(\.[0-9][0-9]?5?)?$/i, "format:  '1',  '0.3'  or  '2.55' or '2.125'");
   }
   
   
   // ================ Events ================
   
   
   // On change in the project list
   jQuery(".scheduler_projectList").on("change", function(){
      var selectedProjectId = jQuery("select.scheduler_projectList option:selected").val();
      
      if("" != selectedProjectId)
      {

         // Get tasks
         jQuery.ajax({ 
            url: 'scheduler/scheduler_ajax.php',
            async:false,
            data: {
               action: 'getTaskList',
               projectId: selectedProjectId,
            },
            type: 'post',
            success: function(data) {
               data = JSON.parse(data);
               
               if(null != data["scheduler_taskList"])
               {
                  setTaskSelectList(data["scheduler_taskList"]);
               }
               

            },
            error: function(errormsg) {
               console.log(errormsg);
            }
         });
      }
   });
   
   
   // On change in the task list
   jQuery(".scheduler_taskList").on("change", function(){
      
      var selectedTaskId = jQuery("select.scheduler_taskList option:selected").val();
      var usersAffectationsContainer = jQuery(".scheduler_usersAffectations");
      
      if("" != selectedTaskId)
      {
         usersAffectationsContainer.show();
         // Get users and their time from the server
         jQuery.ajax({ 
            url: 'scheduler/scheduler_ajax.php',
            async:false,
            data: {
               action: 'getTaskUserList',
               taskId: selectedTaskId,
            },
            type: 'post',
            success: function(data) {
               data = JSON.parse(data);

               if(null != data['scheduler_taskEffortEstim'])
               {
                  jQuery(".scheduler_taskEffortEstim").text(data['scheduler_taskEffortEstim']);
               }

               reinitializeTableAndSelects(data['scheduler_unselectedUserList'], data['scheduler_selectedUserList'], data['scheduler_taskUserList'], data['scheduler_taskHandlerId']);
               
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
         });
      }
      else
      {
         usersAffectationsContainer.hide();
      }
      
      
   });
   
   // On add user button click
   jQuery(".scheduler_addUserButton").on("click", function(){
      
      var selectedTaskId = jQuery("select.scheduler_taskList option:selected").val();
      if("" != selectedTaskId)
      {
         var addedUsers = jQuery(".scheduler_addedUsers");
         var userList = jQuery("select.scheduler_userList");

         var userId = userList.find("option:selected").val();
         var userName = userList.find("option:selected").text();

         jQuery(".scheduler_userList option:selected").remove();
         jQuery(".scheduler_userList").select2("val", null);;

         addUser(userId, userName, null, true);
      }
      
   });
   
   // On save Task Modifications Button click
   jQuery(".scheduler_saveUserAssignmentButton").on("click", function(){
      
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      var selectedTaskId = jQuery(".scheduler_taskList option:selected").val();
      var selectedTaskSummary = jQuery(".scheduler_taskList option:selected").text();
      if(null != selectedTaskId)
      {
         var trUsers = jQuery(".scheduler_addedUsers table tbody").find("tr"); 
         var todoUsers = Array();

         for(var i=0 ; i<trUsers.length ; i++)
         {
            var userId = trUsers.eq(i).attr("data-userId");
            var userTime = trUsers.eq(i).find(".scheduler_userTime input").val();

            var todoUser = {
               userId: userId,
               userTime: userTime
            };

            todoUsers.push(todoUser);
         }


         todoUsers = JSON.stringify(todoUsers);

         jQuery.ajax({ 
            url: 'scheduler/scheduler_ajax.php',
            async:false,
            data: {
               action: 'setTaskUserList',
               taskId: selectedTaskId,
               taskUserList: todoUsers,
            },
            type: 'post',
            success: function(data) {
               data = JSON.parse(data);
               var messageSaveContainer = jQuery(".scheduler_messageSave");
               if(null != data["scheduler_message"])
               {
                  if("SUCCESS" == data["scheduler_status"])
                  {
                     messageSaveContainer.removeClass("error_font");
                     messageSaveContainer.addClass("success_font");
                     messageSaveContainer.text(data["scheduler_message"]);
                  }
                  else
                  {
                     messageSaveContainer.removeClass("success_font");
                     messageSaveContainer.addClass("error_font");
                     messageSaveContainer.text(data["scheduler_message"]);
                  }
               }
               else
               {
                  console.log("error : no message received from server");
               }
               
               if(null != data['scheduler_summaryTableHTML'])
               {
                  setTimePerUsersPerTaskSummaryTable(data['scheduler_summaryTableHTML']);
               }
               
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
         });
      }
      
   });

}



/**
 * Initialize scheduler options
 * @returns {undefined}
 */
function initSchedulerOptions(){
   // On save options Button click
   jQuery("#tabsScheduler_tabOptions .scheduler_saveOptionsButton").on("click", function(){

      var taskProviderId = jQuery("#tabsScheduler_tabOptions .scheduler_taskProvider:checked").val();
      var isDisplayExtRef = jQuery("#tabsScheduler_tabOptions #isDisplayExtRef").attr('checked') ? 1 : 0;
      var nbDaysToDisplay = jQuery("#tabsScheduler_tabOptions #nbDaysToDisplay").val();
      var windowStartDate = jQuery("#tabsScheduler_tabOptions #windowStartDate").val();
      var nbDaysForecast = jQuery("#tabsScheduler_tabOptions #nbDaysForecast").val();
      var warnThreshold  = jQuery("#tabsScheduler_tabOptions #warnThreshold").val();
      this.disabled = true;

      jQuery.ajax({ 
         url: 'scheduler/scheduler_ajax.php',
         async:false,
         data: {
            action: 'setOptions',
            taskProvider: taskProviderId,
            isDisplayExtRef: isDisplayExtRef,
            nbDaysToDisplay: nbDaysToDisplay,
            windowStartDate: windowStartDate,
            nbDaysForecast: nbDaysForecast,
            warnThreshold: warnThreshold
         },
         type: 'post',
         success: function(data) {
            data = JSON.parse(data);
            var messageSaveOptionsContainer = jQuery(".scheduler_messageSaveOptions");
            if("SUCCESS" == data["scheduler_status"])
            {
               messageSaveOptionsContainer.removeClass("error_font");
               messageSaveOptionsContainer.addClass("success_font");
               messageSaveOptionsContainer.text(data["scheduler_message"]);
            }
            else
            {
               messageSaveOptionsContainer.removeClass("success_font");
               messageSaveOptionsContainer.addClass("error_font");
               messageSaveOptionsContainer.text(data["scheduler_message"]);
            }
         },
         error: function(errormsg) {
            console.log(errormsg);
         }
      });
   });
   
   // On option element change
   jQuery(".scheduler_optionElement").on("change", function(){
      // Clear update message
      var messageSaveOptionsContainer = jQuery(".scheduler_messageSaveOptions");
      messageSaveOptionsContainer.empty();
      var saveOptionButton = jQuery("#tabsScheduler_tabOptions .scheduler_saveOptionsButton");
      saveOptionButton.prop("disabled", false);
   });
}
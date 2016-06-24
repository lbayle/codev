$(document).ready(function(){
   
   function reinitializeTableAndSelects(unselectedUserList, selectedUserList, taskUserList)
   {
      var userList = jQuery("select.scheduler_userList");
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      
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
            addUser(userId, selectedUserList[userId], taskUserList[userId]);
         }
      }
      
      checkTotalEffort();
   }
   
   // Add a user to the selected user table
   function addUser(userId, userName, time)
   {
      if(null == time || 0 > time)
      {
         time = 0;
      }
      
      var addedUsers = jQuery(".scheduler_addedUsers");
      var userList = jQuery("select.scheduler_userList");
      
      var trUser = document.createElement("tr");
      trUser.setAttribute("data-userId", userId);
      
      var tdRemoveUser = document.createElement("td");
      tdRemoveUser.setAttribute("class", "ui-state-error-text");
      trUser.appendChild(tdRemoveUser);
      
      var removeButton = document.createElement("a");
      removeButton.setAttribute("class", "ui-icon"); 
      tdRemoveUser.appendChild(removeButton);
      
      var tdName = document.createElement("td");
      tdName.innerHTML = "" + userName;
      tdName.setAttribute("class", "scheduler_userName"); 
      trUser.appendChild(tdName);
      
      var tdTimeIput = document.createElement("td");
      tdTimeIput.setAttribute("class", "scheduler_userTime");
      trUser.appendChild(tdTimeIput);
      
      var timeInput = document.createElement("input");
      timeInput.setAttribute("type", "text"); 
      timeInput.setAttribute("value", time);
      timeInput.innerHTML = "0";
      tdTimeIput.appendChild(timeInput);
      
      var tdAddTime = document.createElement("td");
      trUser.appendChild(tdAddTime);
      
      var minusButton = document.createElement("input");
      minusButton.setAttribute("type", "button"); 
      minusButton.setAttribute("value", "-");
      minusButton.setAttribute("class", "scheduler_minusButton");
      tdAddTime.appendChild(minusButton);
      
      var plusButton = document.createElement("input");
      plusButton.setAttribute("type", "button"); 
      plusButton.setAttribute("value", "+");
      plusButton.setAttribute("class", "scheduler_plusButton");
      tdAddTime.appendChild(plusButton);
      
      addedUsers.find("table tbody").append(trUser);
      
      $(minusButton).on("click", function(){
         
         var timeInput = $(this).parent().parent().find(".scheduler_userTime input");
         var newValue = parseInt(timeInput.val()) - 1;
         newValue = newValue < 0 ? 0 : newValue;
         timeInput.val(newValue);
         checkTotalEffort();
      });
      
      $(plusButton).on("click", function(){
         
         var timeInput = $(this).parent().parent().find(".scheduler_userTime input");
         timeInput.val(parseInt(timeInput.val()) + 1);
         checkTotalEffort();
      });
      
      $(timeInput).on("change", function(){
         
         var newValue = parseInt($(this).val());
         newValue = newValue < 0 ? 0 : newValue;
         $(this).val(newValue);
         checkTotalEffort();
      });
      
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
   
   
   function checkTotalEffort()
   {
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      var usersTime = userTable.find(".scheduler_userTime input");
      
      // Add users time
      var totalUserTime = 0;
      for(var i=0 ; i<usersTime.length ; i++)
      {
         totalUserTime = parseInt(totalUserTime) + parseInt(usersTime.eq(i).val());
      }
      
      // Write total users time
      jQuery(".scheduler_totalAffectedEffort").text(totalUserTime);
      
      var totalAffectedEffortComparison = jQuery(".scheduler_totalAffectedEffortComparison");
      var saveTaskModificationsButton = jQuery(".scheduler_saveTaskModificationsButton");
      
      // Get total estimed effort on task
      var totalEstimedEffort = parseInt(jQuery(".scheduler_taskEffortEstim").eq(0).text());

      // Check if total users effort is equal to estimed effort
      if(totalEstimedEffort == totalUserTime)
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
   
   // ++++++++++ Events ++++++++++
   
   // On change in the task list
   jQuery(".scheduler_taskList").on("change", function(){
      
      var selectedTaskId = jQuery("select.scheduler_taskList option:selected").val();
      
      // Get users and their time from the server
      jQuery.ajax({ 
         url: 'reports/scheduler_ajax.php',
         async:false,
         data: {
            action: 'getTaskUserList',
            taskId: selectedTaskId,
         },
         type: 'post',
         success: function(data) {
            data = JSON.parse(data);
            console.log(data);
            
            if(null != data['scheduler_taskEffortEstim'])
            {
               jQuery(".scheduler_taskEffortEstim").text(data['scheduler_taskEffortEstim']);
            }
            
            reinitializeTableAndSelects(data['scheduler_unselectedUserList'], data['scheduler_selectedUserList'], data['scheduler_taskUserList']);
            
         },
         error: function(errormsg) {
            console.log(errormsg);
         }
      });
      
   });
   
   // On add user button click
   jQuery(".scheduler_addUserButton").on("click", function(){
      
      var addedUsers = jQuery(".scheduler_addedUsers");
      var userList = jQuery("select.scheduler_userList");
              
      var userId = userList.find("option:selected").val();
      var userName = userList.find("option:selected").text();
      
      jQuery(".scheduler_userList option:selected").remove();
      jQuery(".scheduler_userList").select2("val", null);;
      
      addUser(userId, userName, 0);
      
   });
   
   // On save Task Modifications Button click
   jQuery(".scheduler_saveTaskModificationsButton").on("click", function(){
      
      var userTable = jQuery(".scheduler_addedUsers table tbody");
      var selectedTaskId = jQuery(".scheduler_taskList option:selected").val();
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

         console.log(todoUsers);

         todoUsers = JSON.stringify(todoUsers);

         jQuery.ajax({ 
            url: 'reports/scheduler_ajax.php',
            async:false,
            data: {
               action: 'setTaskUserList',
               taskId: selectedTaskId,
               taskUserList: todoUsers,
            },
            type: 'post',
            success: function(data) {
               console.log(data);
               //jsonTimetrackData = JSON.parse(data);
            },
            error: function(errormsg) {
               console.log(errormsg);
            }
         });
      }
      
   });
   

});

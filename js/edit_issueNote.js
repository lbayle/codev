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

// function called when clicked on note icon
function editIssueNote(bugid){

   // get note via ajax, to be sure that data is up to date.
   var formGetIssueNoteText = jQuery('#formGetIssueNoteText');
   formGetIssueNoteText.find("input[name=bugid]").val(bugid);
   jQuery.ajax({
      type: "POST",
      dataType:"json",
      url: formGetIssueNoteText.attr("action"),
      data: formGetIssueNoteText.serialize(),
      success: function(response) {

         // update dialogbox textarea
         var issuenoteid = response['issuenoteid']
         if (0 !== issuenoteid) {
            var issuenote_text = response['issuenote_text'];
            jQuery("#dialog_editIssueNote").find("textarea[name=issuenote_text]").val(issuenote_text);
         }
         jQuery("#formEditIssueNote").find("input[name=bugid]").val(bugid);
         jQuery("#formEditIssueNote").find("input[name=issuenoteid]").val(issuenoteid);

         var dialogBoxTitle = editIssueNoteSmartyData.i18n_task + ' ' + bugid + ' - ' + editIssueNoteSmartyData.i18n_editTimesheetNote;
         jQuery("#dialog_editIssueNote").dialog("option", "title", dialogBoxTitle);

         jQuery("#editIssueNoteDialogDesc").text("Task " + bugid );

         jQuery('#cb_isTimesheetNote').prop('checked', true);
         jQuery("#dialog_editIssueNote").dialog("open");

      },
      error: function(data) {
         console.error('editIssueNote', bugid, data);
         alert('ERROR: could not get IssueNoteText');
      }
   });

}

function markIssueNoteAsRead(bugid) {
   var formMarkIssueNoteAsRead = jQuery('#formMarkIssueNoteAsRead');
   formMarkIssueNoteAsRead.find("input[name=bugid]").val(bugid);
   jQuery.ajax({
      type: "POST",
      url: formMarkIssueNoteAsRead.attr("action"),
      data: formMarkIssueNoteAsRead.serialize(),
      success: function(data) {

         var img = jQuery("#b_markAsRead_" + bugid);
         img.off('click'); // disable markAsRead link
         img.attr("src","images/b_markAsRead_grey.png");

         var row = jQuery('#issueNotes_tr'+bugid);
         row.css("background-color", "#FFFFFF");

         //alert("Issue " + bugid + ": Note marked as read.");
      },
      error: function(data) {
         console.error('markIssueNoteAsRead', bugid, data);
         alert('ERROR: could not get mark IssueNote as read.');
      }
   });
}

function deleteNote(bugid, bugnote_id) {

   // ask delete confirmation
   if (confirm(editIssueNoteSmartyData.i18n_deleteTimesheetNoteForTask + bugid + ' ?')) {

      var formDeleteNote = jQuery('#formDeleteNote');
      formDeleteNote.find("input[name=bugid]").val(bugid);
      formDeleteNote.find("input[name=bugnote_id]").val(bugnote_id);
      jQuery.ajax({
         type: "POST",
         url: formDeleteNote.attr("action"),
         data: formDeleteNote.serialize(),
         success: function(data) {

            // remove IssueNote from DataTable
            var oTable = jQuery('#issueNotes_table').dataTable();
            var row = jQuery('#issueNotes_tr'+bugid).get(0);
            oTable.fnDeleteRow( oTable.fnGetPosition(row) );
            // TODO add {$realname} to column 'readBy_td'

            return false;
         },
         error: function(data) {
            console.error('deleteNote', bugid, bugnote_id, data);
            alert('ERROR: could not deleteNote.');
         }
      });
   }
}

jQuery(document).ready(function() {

   var dialog = jQuery("#dialog_editIssueNote").dialog({
         autoOpen: false,
         modal: true,
         hide: "fade",
         height: 'auto',
         width: "auto",
         closeOnEscape: true,
         buttons: {
            Ok: function() {
               var isChecked = jQuery("#cb_isTimesheetNote").attr('checked') ? 1 : 0;
               jQuery("#dialog_editIssueNote").find("input[name=isTimesheetNote]").val(isChecked);
               jQuery("#formEditIssueNote").submit();
               jQuery("#dialog_editIssueNote").find("textarea[name=issuenote_text]").val('');
               jQuery("#dialog_editIssueNote").dialog("close");
            },
            Cancel: function() {
               jQuery("#dialog_editIssueNote").find("textarea[name=issuenote_text]").val('');
               jQuery( this ).dialog("close");
            }
         }
   });

   // avoid multiple calls to 'submit' by adding the it in a namespace,
   // and removing previous namespace event handler.
   jQuery("#formEditIssueNote").unbind('submit.notes').bind('submit.notes', function(event) {
      /* stop form from submitting normally */
      event.preventDefault();
      /* get some values from elements on the page: */
      var formEditIssueNote = jQuery(this);

      jQuery.ajax({
         type: "POST",
         url: formEditIssueNote.attr("action"),
         data: formEditIssueNote.serialize(),
         success: function(data) {
            // cannot update all tooltips, the complete table must be updated
            jQuery("#weekTaskDetailsDiv").html(jQuery.trim(data));
            updateWidgets("#weekTaskDetailsDiv");
         },
         error: function(data) {
            console.error('formEditIssueNote', data);
            alert('ERROR: could not edit IssueNote.');
         }
      });
   });

   // click on the IssueNote will raise the editIssueNote dialogbox
   jQuery('.js-add-note-link').click(function(ev){
      var bugId = this.getAttribute('data-bugId');
      ev.preventDefault();
      editIssueNote( bugId );
   });

   // click on markAsRead will add a ReadBy tag to the IssueNote
   jQuery('.js-markNoteAsRead-link').click(function(ev){
      var bugId = this.getAttribute('data-bugId');
      ev.preventDefault();
      markIssueNoteAsRead( bugId );
   });

   // click on deleteBNote to the IssueNote
   jQuery('.js-deleteNote-link').click(function(ev){
      var bugId = this.getAttribute('data-bugId');
      var bugnoteId = this.getAttribute('data-bugnoteId');
      ev.preventDefault();
      deleteNote( bugId, bugnoteId );
   });

});




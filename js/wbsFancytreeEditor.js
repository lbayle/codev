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

// global: nodesToDelete list
var nodesToDelete = [];


jQuery(document).ready(function() {

   // -----------------------
   jQuery("#bt_loadTree").click(function (event) {
      event.preventDefault();
      $("#ajaxStatusMsg").html('');
      $("#tree").fancytree("destroy");
      initTree();

   });

   // -----------------------
   jQuery("#bt_saveTree").click(function (event) {
      event.preventDefault();
      $("#ajaxStatusMsg").html('');

      var deferred = saveTree();
      // set success callback:
      deferred.done(function () {
         // reload to update id of new elements (created in DB)
         $("#tree").fancytree("destroy");
         initTree();
      });
      // set error callback:
      deferred.fail(function () {
         console.error('WBS Save failed.');
         $("#tree").fancytree("destroy");
         initTree();
      });
   });

   // initial tree load
   initTree();
});

// --------------------------------------
function addContextMenu() {

   jQuery.contextMenu({
      selector: '#tree .fancytree-folder',
      items: {
         "edit": {name: wbsEditorSmartyData.i18n_rename, icon: "edit"},
         "add": {name: wbsEditorSmartyData.i18n_addSubfolder, icon: "add"},
         "sep1": "---------",
         "delete": {name: wbsEditorSmartyData.i18n_delete, icon: "delete", disabled: false}
      },
      callback: function (key, options) {

         // implement contextMenu actions
         var node = $.ui.fancytree.getNode(options.$trigger);
         node.setActive();

         // TODO if root element, disable 'delete' menu option

         switch (key) {
            case 'edit':
               $("#ajaxStatusMsg").html('');
               node.editStart();
               break;
            case 'add':
               $("#ajaxStatusMsg").html('');
               node.editCreateNode('child', {title: wbsEditorSmartyData.i18n_newFolder, folder: true});
               break;
            case 'delete':
               if (node.getLevel() !== 1 && !node.hasChildren()) {
                  // option 'defaultKey' => default null: generates default keys like that: "_" + counter)
                  if ('_' !== node.key.charAt(0)) {
                     console.log("node " + node.key + " exist in codevtt DB and must be removed when saving the tree");
                     nodesToDelete.push(node.key);
                  }
                  $("#ajaxStatusMsg").html('');
                  node.remove();
               } else if (1 === node.getLevel()) {
                  console.error("Root element cannot be deleted.");
                  alert(wbsEditorSmartyData.i18n_rootCantBeDeleted);
               } else {
                  console.error("Delete Failed: Folder must be empty.");
                  alert(wbsEditorSmartyData.i18n_folderMustBeEmpty);
               }
               break;
         }
      }
   });
}

// --------------------------------------------
function initTree() {

   jQuery("#tree").fancytree({
      treeId: "2",
      extensions: ["dnd5", "edit"],

      source: {
         type: "POST",
         url: 'include/fancytree_ajax.php',
         data: {
            wbsRootId: wbsEditorSmartyData.wbsRootId,
            hasDetail: "0",
            action: "loadWBS"
         }
      },
      // https://github.com/mar10/fancytree/wiki/ExtDnd5
      dnd5: {
         autoExpandMS: 500,          // Expand nodes after n milliseconds of hovering.
         preventForeignNodes: true,  // Prevent dropping nodes from another Fancytree
         preventRecursion: true,     // Prevent dropping nodes on own descendants
         preventVoidMoves: true,     // Prevent moving nodes 'before self', etc.
         preventNonNodes: true,      // Prevent dropping items other than Fancytree nodes
         effectAllowed: "all",       // Restrict the possible cursor shapes and modifier operations
         dropEffectDefault: "move",  // "auto",

         // TODO (later)
         //multiSource: false,           // true: Drag multiple (i.e. selected) nodes.

         // --- Ennable Drag-support:
         dragStart: function (sourceNode, data) {
            // dragStart: Callback(sourceNode, data), return true, to enable dragging
            if (1 === sourceNode.getLevel()) {
               console.error("Root element cannot be moved");
               return false;
            }
            data.effectAllowed = "all";
            data.dropEffect = "move";
            return true;
         },

         // --- Enable Drop-support:
         dragEnter: function (targetNode, data) {
            // dragEnter: Callback(targetNode, data), return true, to enable dropping
            data.dropEffect = "move";
            return true;
         },
         dragOver: function (targetNode, data) {

            // PLEASE NOTE (LoB, 2020-05-09):
            // Since I am using an old jquery version (1.8.3), Fancytree does not
            // report 'after' and 'before' hitModes.
            // This means I need to allow dropping 'over' a leaf and insert it 'after'
            // instead of blocking a drop 'over' a leaf
            /*
             if(!targetNode.folder && data.hitMode === "over"){
             console.warn("dragOver: No, this is not a folder, you can't drop it here !");
             return false;
             }
             */
            // Assume typical mapping for modifier keys
            data.dropEffect = data.dropEffectSuggested;
            // data.dropEffect = "move";
         },
         dragDrop: function (targetNode, data) {
            // This function MUST be defined to enable dropping of items on the tree.

            if (data.otherNode) {
               if (!targetNode.folder) {
                  // This is a workaround because I never get 'after' & 'before' hitModes
                  console.log("insert " + data.otherNode.key + " after " + targetNode.key);
                  data.otherNode.moveTo(targetNode, "after");
               } else {
                  data.otherNode.moveTo(targetNode, 'firstChild'); // data.hitMode);
               }
               $("#ajaxStatusMsg").html('');
            } else {
               // Drop a non-node (should not happen, see preventNonNodes)
               console.error("drop non-node !");
            }
            targetNode.setExpanded();
         }
      }, // dnd5

      // -----------------------
      edit: {
         //triggerStart: ["clickActive", "dblclick", "f2", "mac+enter", "shift+click"],
         triggerStart: ["clickActive", "dblclick"],
         beforeEdit: function (event, data) {
            if (!data.node.folder) {
               console.warn("No! you can only edit folders");
               return false;
            }
            if (1 >= data.node.getLevel()) {
               console.warn("No! you can't edit the root element " + data.node.getLevel());
               return false;
            }
         }
      } // edit
   });

   $(".fancytree-container").addClass("fancytree-connectors");
   addContextMenu();
}

function saveTree() {

   var deferred = jQuery.Deferred();

   var node = $.ui.fancytree.getTree("#tree").getRootNode();
   var json = node.toDict(true, function (dict) {
      delete dict.isLazy;
      delete dict.tooltip;
      delete dict.href;
      //delete dict.icon;
      delete dict.addClass;
      delete dict.noLink;
      delete dict.activate;
      delete dict.focus;
      //delete dict.expand;
      delete dict.select;
      delete dict.hideCheckbox;
      delete dict.unselectable;
   }).children;

   var jsonDict = JSON.stringify(json);
   $.ajax({
      type: "POST",
      url: 'include/fancytree_ajax.php',
      data: {jsonDynatreeDict: jsonDict,
         nodesToDelete: nodesToDelete,
         wbsRootId: wbsEditorSmartyData.wbsRootId,
         action: "saveWBS"
      },
      dataType:"json",
      success: function (data) {
         nodesToDelete = [];
         if('SUCCESS' !== data.statusMsg) {
            $("#ajaxStatusMsg").html('<span class="error_font">'+wbsEditorSmartyData.i18n_wbsSaveERR+'</span>');
            alert(data.statusMsg);
            console.error(data.statusMsg);
         } else {
            console.log("saveWBS: OK");
            $("#ajaxStatusMsg").html('<span class="success_font">'+wbsEditorSmartyData.i18n_wbsSaveOK+'</span>');
         }
         // call the 'done' callback (should be defined)
         deferred.resolve();
      },
      error: function () {
         $("#ajaxStatusMsg").html(wbsEditorSmartyData.i18n_wbsSaveERR);
         //alert("WBS Save ERROR!");
         console.error("WBS Save ERROR!");
         deferred.reject(); // call the 'fail' callback (if defined)
      }
   });
   return deferred;
}


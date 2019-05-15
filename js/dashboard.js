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
function addDashboardPlugin(){

   // show all plugins
   jQuery(".cbPluginCandidates > option").each(function() {
      //jQuery(this).show();
      jQuery(".cbPluginCandidates option[value="+ this.value + "]").show();
   });

   // hide already displayed plugins from candidates list
   // Note: this does not work with a select2 combobox
   jQuery('.attributesJsonStr').each(function() {
      var attributesJson = jQuery.parseJSON($(this).text());
      var pClassName = attributesJson['pluginClassName'];
      jQuery(".cbPluginCandidates option[value="+ pClassName + "]").hide();
   });
   // remove selection
   jQuery(".cbPluginCandidates option[value='0']").attr('selected', 'selected');
   jQuery('.pluginDescription').html('');
   jQuery(".pluginAttributes").html('');
   jQuery(".addDashboardErrorMsg").html('');
   jQuery(".pluginConfig").hide();
   jQuery(".dashboardDialog_btAdd .ui-button-text").html(dashboardSmartyData.i18n_Add); // remove spinner

   jQuery(".dialogAddDashboardPlugin").dialog("open");
}

function saveDashboard() {
   console.log("saveDashboard");

   // Parse the dashboard to get all Widgets and their attributesJsonStr
   var widgetAttributes = [];
   jQuery('.widget:has(.attributesJsonStr)').each(function() {
      var $widget = $(this);
      if (true !== $widget.data('isSlidingUp')) {
         var attributesJson = jQuery.parseJSON($widget.find('.attributesJsonStr').html());
         widgetAttributes.push(attributesJson);
      }
   });

   var dashboardSettingsJson = {};
   dashboardSettingsJson['dashboardTitle'] = dashboardSmartyData.dashboardTitle;
   dashboardSettingsJson['displayedPlugins'] = widgetAttributes;
   var dashboardSettingsJsonStr = JSON.stringify(dashboardSettingsJson);
   //console.log('JsonStr = ' , dashboardSettingsJsonStr);

   jQuery.ajax({
      type: "POST",
        url: 'include/dashboard_ajax.php',
        dataType:"json",
        data: { action: "saveDashboardSettings",
                dashboardId: dashboardSmartyData.dashboardId,
                dashboardSettingsJsonStr: dashboardSettingsJsonStr,
                isTeamDefaultSettings: "0"
        },
        success: function(data) {
            if (null === data) {
              alert('ERROR: could not save dashboard settings, please contact your administrator');
            } else if ('SUCCESS' !== data.statusMsg) {
              alert(data.statusMsg);
            }
        },
        error:  function(jqXHR, textStatus, errorThrown) {
            console.error(textStatus, errorThrown);
            alert('ERROR saveDashboardSettings: '+errorThrown);
        }
   });

}

jQuery(document).ready(function() {
   jQuery(".cbPluginCandidates").change(function() {
      if ('0' !== this.value) {
         jQuery(".addDashboardErrorMsg").html('');

         jQuery.ajax({
            type: "POST",
              url: 'include/dashboard_ajax.php',
              dataType:"json",
              data: { pluginClassName: this.value,
                      action: "getPluginConfigInfo",
                      dashboardId: dashboardSmartyData.dashboardId
              },
              success: function(data) {
                if (null === data) {
                  jQuery(".addDashboardErrorMsg").html("ERROR: could not get plugin configuration info,<br> please contact your administrator");
                  jQuery('.pluginDescription').html('');
                  jQuery(".pluginAttributes").html('');
                  jQuery(".pluginConfig").hide();
                } else {
                  if ('SUCCESS' !== data.statusMsg) {
                     jQuery('.pluginDescription').html('');
                     jQuery(".pluginAttributes").html('');
                     jQuery(".pluginConfig").hide();
                     jQuery(".addDashboardErrorMsg").html(data.statusMsg);
                  } else {
                     jQuery('.pluginDescription').html(data['description']);
                     jQuery(".pluginAttributes").html(jQuery.trim(data['attributesHtml']));
                     jQuery(".pluginConfig").show();
                  }
               }
              },
              error:  function() {
                 jQuery('.pluginDescription').html('');
                 jQuery(".pluginAttributes").html('');
                 jQuery(".pluginConfig").hide();
                 jQuery(".addDashboardErrorMsg").html("ERROR getPluginConfigInfo: please contact your administrator");
              }
         });
      } else {
         jQuery('.pluginDescription').html('');
         jQuery(".pluginAttributes").html('');
         jQuery(".pluginConfig").hide();
         jQuery(".addDashboardErrorMsg").html('');
      }
   });

   jQuery(".dialogAddDashboardPlugin").dialog({
      autoOpen: false,
      resizable: true,
      width: "auto",
      modal: true,
      create:function () {
         $(this).closest(".ui-dialog").find(".ui-button:first").addClass("dashboardDialog_btAdd");
      },
      buttons: [
         {
            text: dashboardSmartyData.i18n_Add,
            click: function() {
               jQuery(".dashboardDialog_btAdd .ui-button-text").html("<img src='images/spinner.gif' width='16' height='16' alt='"+dashboardSmartyData.i18n_loading+"' />");

               // on définit le sélecteur permettant de différencier:
               // - les champs type "options du plugin" (ceux qui sont affichés)
               // - les champs types "est envoyé en AJAX puis enregistré sur le serveur" (qui sont masqués),
               var ajaxFieldSelector = '[type=hidden]';

               var form = $('.formAddDashboardPlugin');

               // visible fields (input/select/textarea) are raw data to be parsed
               var $pluginElements = form.find(':input:not('+ajaxFieldSelector+')');

               // hidden fields: processed data returned by ajax
               var formElements = form.find(':input'+ajaxFieldSelector);

               // on transforme la liste des options en tableau associatif JS
               // (qui est un **objet JSON** en fait, puisque JSON = JavaScript Object Notation ;)
               var pluginAttributesArray = $pluginElements.serializeArray();

               // unchecked checkboxes are ommitted by the serializeArray, they need to be add manualy
               var uncheckedElements = $pluginElements.filter('input[type=checkbox]:not(:checked)');
               var uncheckedElementsArray = uncheckedElements.map(function(i, checkbox){ return { name:checkbox.name, value:0 }; }).get();
               pluginAttributesArray = pluginAttributesArray.concat(uncheckedElementsArray);

               var pluginOptionsJSONString = JSON.stringify(pluginAttributesArray);

               // hidden fields: processed data returned by ajax
               var formElements = form.find(':input'+ajaxFieldSelector);
               formElements.filter('[name=pluginAttributesJsonStr]').val(pluginOptionsJSONString);
               var dataToSend = formElements.serializeArray();
               //console.log('dataToSend = ', dataToSend.length, dataToSend);

               $.ajax({
                  url: 'include/dashboard_ajax.php',
                  type: form.attr('method'),
                  dataType:"json",
                  data: dataToSend,
                  success: function(data) {
                     if (null === data) {
                        jQuery(".addDashboardErrorMsg").html("ERROR: could not load plugin, please contact your administrator");
                        jQuery(".dashboardDialog_btAdd .ui-button-text").html(dashboardSmartyData.i18n_Add);
                     } else {
                        if ('SUCCESS' !== data.statusMsg) {
                           jQuery(".addDashboardErrorMsg").html(data.statusMsg);
                           jQuery(".dashboardDialog_btAdd .ui-button-text").html(dashboardSmartyData.i18n_Add);
                        } else {
                           var widget = data.widget;

                           // load CSS dependencies
                           jQuery("<link/>", {
                              rel: "stylesheet",
                              type: "text/css",
                              href: "lib/inettuts/inettuts_codevtt.css"
                           }).appendTo("head");

                           //console.log('css files to load:', widget.cssFiles);
                           jQuery.each(widget.cssFiles, function( index, value ) {
                              jQuery("<link/>", {
                                 rel: "stylesheet",
                                 type: "text/css",
                                 href: value
                              }).appendTo("head");
                           });

                           // add new Widget to the dashboard
                           var ulObj = jQuery(".inettuts-column1");
                           var liObj = jQuery('<li></li>').addClass("widget").addClass(widget.color).attr('id', widget.id);
                           var divHeadObj = jQuery('<div></div>').addClass("widget-head").append(jQuery('<h3></h3>').html(widget.title));
                           var divContentObj = jQuery('<div></div>').addClass("widget-content").html(widget.content);
                           var divObj = jQuery('<div style="display: none;" class="attributesJsonStr"/>').html(widget.attributesJsonStr);
                           divObj.addClass(widget.pClassName+'Attr');
                           liObj.append(divObj).append(divHeadObj).append(divContentObj);
                           ulObj.prepend(liObj);

                           // load JS dependencies
                           jQuery.ajax({
                                 async: false,
                                 url: "lib/inettuts/inettuts_codevtt.min.js",
                                 dataType: "script"
                           });
                           //console.log('js files to load:', widget.jsFiles);
                           jQuery.each(widget.jsFiles, function( index, value ) {
                              jQuery.ajax({
                                    async: false,
                                    url: value,
                                    dataType: "script"
                              });
                           });
                           //console.log('js load done');

                           // execute plugin initialization function
                           var functionName = jQuery(widget.content).find('.pluginInitFunction').text();
                           //console.log("pluginInitFunction", functionName);
                           window[functionName]();
                           divContentObj.find('#jqplot_target_1').attr('id', 'jqplot_target_' + widget.id);

                           saveDashboard();
                           jQuery(".addDashboardErrorMsg").html('');
                           jQuery(".dialogAddDashboardPlugin").dialog("close");
                        }
                     }
                  },
                  error:  function(data) {
                     console.error('ERROR data = ', data);
                     jQuery(".dashboardDialog_btAdd .ui-button-text").html(dashboardSmartyData.i18n_Add);
                     jQuery(".addDashboardErrorMsg").html("AddDashboardPlugin ERROR!");
                     //deferred.reject(); // call the 'fail' callback (if defined)
                  }
               });
            }
         },
         {
            text: dashboardSmartyData.i18n_Cancel,
            click: function() {
               jQuery(".addDashboardErrorMsg").html('');
               jQuery(this).dialog("close");
            }
         }

      ]
   });

   // use event bubbling
   jQuery( ".inettuts-columns" ).on( "click", ".collapse", function() {

      // save isCollapsed in the attributesJsonStr so it can be restored
      var widget= $(this).closest( ".widget" );
      var wcontent = widget.children(".widget-content");
      var isCollapsed = !wcontent.is(':visible');

      var attr = widget.children(".attributesJsonStr");
      var attributesJson = jQuery.parseJSON(attr.text());
      attributesJson['isCollapsed'] = isCollapsed;
      var attributesJsonStr = JSON.stringify(attributesJson);
      attr.text(attributesJsonStr);

      // load the widget if it was collapsed (TODO: first time only)
      if (!isCollapsed) {
         var initFct = widget.find(".pluginInitFunction").text();
         window[initFct]();
      }

      //if(!isPageLoad) {
      // TODO: do not save at page load, only on user click
      console.warn('TODO collapse: do not save at page load, only on user click !');
      saveDashboard();
      //}
   });

   jQuery(".remove").click(function() {
      saveDashboard();
   });

   // ==== initialize plugins ====
   jQuery('.pluginInitFunction').each(function() {

      // check if collapsed
      var widget= $(this).closest( ".widget" );
      var attr = widget.children(".attributesJsonStr");
      var attributesJson = jQuery.parseJSON(attr.text());
      var isCollapsed = attributesJson['isCollapsed'];
      if (isCollapsed) {
         widget.find('.collapse').trigger('click');
      }

      // call the pluginInitFunction
      var initFct = this.innerHTML;
      window[initFct]();
   });

});
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
   if (userInfoSmartyData.datepickerLocale != 'en') {
      jQuery.datepicker.setDefaults(jQuery.datepicker.regional[userInfoSmartyData.datepickerLocale]);
   }
   // Set the date
   jQuery("#datepickerStart").datepicker("setDate" ,userInfoSmartyData.datepickerStartDate);
   jQuery("#datepickerEnd").datepicker("setDate" ,userInfoSmartyData.datepickerEndDate);

   jQuery("#displayed_teamid").change(function() {
      jQuery("#displayTeamForm").submit();
   });

   jQuery('#btSetDateRange').click(function() {
      var form = jQuery('#formSetDateRange');
      form.submit();
   });

}); // document ready

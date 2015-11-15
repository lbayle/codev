// Datatable
jQuery(document).ready(function(){
   
   jQuery.ajax({
      url: "lib/DataTables/media/js/jquery.dataTables.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   jQuery.ajax({
      url: "lib/DataTables/extensions/TableTools/js/dataTables.tableTools.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   // simple table: no filtering, no sorting, no export, but with scrollbar
   jQuery('.datatable_minimal').dataTable({
      retrieve: true, // WARN http://datatables.net/manual/tech-notes/3
      "sScrollY": "700px",
      "bPaginate": false,
      "bScrollCollapse": true,
      "bFilter": false,
      "bSort": false,
      "bInfo": false,
      "bAutoWidth": false,
      // Adapt the jQuery css
      //"bJQueryUI": true,
      "sDom": '<"H"r>t'
   });

   // with custom button
   jQuery('.datatable_csv').dataTable({
      retrieve: true, // WARN http://datatables.net/manual/tech-notes/3
      "sScrollY": "700px",
      "bPaginate": false,
      "bScrollCollapse": true,
      "bFilter": true,
      "bSort": true,
      "bInfo": false,
      "bAutoWidth": false,
      "sDom": '<"H"Tfr>t',
      "oTableTools": {
         aButtons: [
            {
               sExtends: 'text',
               sButtonText: '<img src="images/b_export_xls.gif" title="Export to CSV" />',
               //"sButtonClass": "my_button_class",
               "sFieldSeperator": ";",
               "sFieldBoundary": '"',
               "mColumns": "visible",
               fnClick: function (button, conf) {
                  var content = this.fnGetTableData(conf);
                  //console.log("fnGetTableData",  content);
                  saveTextAs(content, 'datatable.csv');
               }
             }   
         ]
      }
   });
});


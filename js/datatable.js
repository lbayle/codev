// Datatable
jQuery(document).ready(function(){
   
   jQuery.ajax({
      //url: "lib/datatables/media/js/jquery.dataTables.min.js",
      url: "lib/DataTables-1.10.5/media/js/jquery.dataTables.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   jQuery.ajax({
      //url: "lib/datatables/extras/TableTools/media/js/TableTools.min.js",
      url: "lib/DataTables-1.10.5/extensions/TableTools/js/dataTables.tableTools.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   
    TableTools.DEFAULTS.aButtons = [ "copy", "xls" ];
    //TableTools.DEFAULTS.sSwfPath = "lib/datatables/extras/TableTools/media/swf/copy_csv_xls.swf";
    TableTools.DEFAULTS.sSwfPath = "lib/DataTables-1.10.5/extensions/TableTools/swf/copy_csv_xls.swf";





   jQuery('.datatable').dataTable({
      "sScrollY": "700px",
      "bPaginate": false,
      "bScrollCollapse": true,
      "bFilter": true,
      "bSort": true,
      "bInfo": false,
      "bAutoWidth": false,
      // Adapt the jQuery css
      //"bJQueryUI": true,
      "sDom": '<"H"Tfr>t'
   });

   // simple table: no filtering, no sorting, no export, but with scrollbar
   jQuery('.datatable_minimal').dataTable({
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

});
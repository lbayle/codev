// Datatable
jQuery(document).ready(function(){
   
   jQuery.ajax({
      url: "lib/datatables/media/js/jquery.dataTables.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   jQuery.ajax({
      url: "lib/datatables/extras/TableTools/media/js/TableTools.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   
    TableTools.DEFAULTS.aButtons = [ "copy", "xls" ];
    TableTools.DEFAULTS.sSwfPath = "lib/datatables/extras/TableTools/media/swf/copy_csv_xls.swf";

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
});
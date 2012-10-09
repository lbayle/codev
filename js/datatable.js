// Datatable
jQuery(document).ready(function(){
   
   jQuery.ajax({
      url: "lib/datatables/media/js/jquery.dataTables.min.js",
      dataType: "script",
      async: false,
      cache: true
   });
   
   jQuery('.datatable').dataTable({
      "sScrollY": "700px",
      "bPaginate": false,
      "bScrollCollapse": true,
      "bFilter": true,
      "bSort": true,
      "bInfo": false,
      "bAutoWidth": false
   });
});
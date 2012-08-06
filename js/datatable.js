// Datatable
jQuery(document).ready(function(){
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
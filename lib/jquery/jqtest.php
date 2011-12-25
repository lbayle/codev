<html>
<head>

	<meta charset="utf-8">
	<title>jQuery Test</title>

   <style>
   @import "./jquery.ui.base.css";
   @import "./jquery.ui.theme.css";
   </style>

   <link type="text/css" href="./ui/css/ui-lightness/jquery-ui-1.8.16.custom.css" rel="Stylesheet" />
	<script src="./jquery.js"></script>
   <script src="./ui/development-bundle/external/jquery.bgiframe-2.1.2.js"></script>
	<script src="./jquery-ui.js"></script>



<script type="text/javascript">

	$(function() {
		$( "#dialog" ).dialog({
			autoOpen: false
		});

		$( "#opener" ).click(function() {
			$( "#dialog" ).dialog( "open" );
			return false;
		});

		$( "#dialog2" ).dialog({
			autoOpen: false
		});

		$( "#opener2" ).click(function() {
			$( "#dialog2" ).dialog( "open" );
			return false;
		});


	});





</script>

</head>
<body>


<div class="demo">

<div id="dialog" title="Basic dialog">
	<p>This is an animated dialog which is useful for displaying information.</p>
</div>

<div id="dialog2" title="dialog on image">
	<p>Here we go !</p>
</div>

<button id="opener">Open Dialog</button>
</br>
</br>
click there: <a id='opener2' href='#'><img title='help' src='../../images/help_icon.gif' /></a>

</div><!-- End demo -->


</body>
</html>

<?php
?>
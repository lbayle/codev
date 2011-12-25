<html>
<head>

	<meta charset="utf-8">
	<title>jQuery Test</title>

   <link type="text/css" href="./css/ui-lightness/jquery-ui-1.8.16.custom.css" rel="Stylesheet" />
	<script src="./jquery.js"></script>
	<script src="./jquery-ui.js"></script>



<script type="text/javascript">

	$(function() {
		$( "#dialog" ).dialog({
			autoOpen: false,
			hide: "fade"
		});

		$( "#opener" ).click(function() {
			$( "#dialog" ).dialog( "open" );
			return false;
		});

		$( "#dialog2" ).dialog({
			autoOpen: false,
			show: "fade",
			hide: "fade",
			width: 350,
			buttons: {
				Ok: function() {
					$( this ).dialog( "close" );
				},
				"Custom": function() {
					$( this ).dialog( "close" );
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}

		});

		$( "#opener2" ).click(function() {
			$( "#dialog2" ).dialog( "open" );
			return false;
		});

		$(function() {
			$( "#datepicker" ).datepicker();
		});

	});

</script>

</head>
<body>



<div id="dialog" title="Basic dialog">
	<p>This is an animated dialog which is useful for displaying information.</p>
	<br>
	<ul>
	<li>one</li>
	<li>two</li>
	<li>three</li>
	</ul>
</div>


<?php
echo "<div id='dialog2' title='dialog on image'>";
echo "<p>Here we go !</p>";
echo "</div>";
?>

<?php # -------- MAIN -------- ?>

<br>
<br>
<button id="opener">Open Dialog</button>

<br>
<br>
<p>Date: <input type="text" id="datepicker"></p>


<br>
<br>
click there: <a id='opener2' href='#'><img title='help' src='../../images/help_icon.gif' /></a>





</body>
</html>

<?php
?>
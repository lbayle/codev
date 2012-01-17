<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';

$_POST['page_name'] = T_("Test Dialog");
include 'header.inc.php';
?>

<?php include_once 'tools.php'; ?>
<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

	<style>
		fieldset { padding:0; border:0; }
		validateTips { border: 1px solid transparent; padding: 0.3em; }
	</style>

	<script>
	$(function() {

		var bugid = $( "#bugid" ),
		    remaining = $( "#remaining" ),
			 allFields = $( [] ).add( remaining ),
			 tips = $( "#validateTips" );

		function updateTips( t ) {
			tips
				.text( t )
				.addClass( "ui-state-highlight" );
			setTimeout(function() {
				tips.removeClass( "ui-state-highlight", 1500 );
			}, 500 );
		}

		function checkRegexp( o, regexp, n ) {
			if ( !( regexp.test( o.val() ) ) ) {
				o.addClass( "ui-state-error" );
				updateTips( n );
				return false;
			} else {
				return true;
			}
		}

		$( "#update_remaining_dialog_form" ).dialog({
			autoOpen: false,
			height: 180,
			width: 300,
			modal: true,
			buttons: {
				"Update": function() {
					var bValid = true;
					allFields.removeClass( "ui-state-error" );
					bValid = bValid && checkRegexp( remaining, /^[0-9]+(\.[0-9]5?)?$/i, "format: '1','0.3' or '1.55'" );

					if ( bValid ) {
						// here, use AJAX to call php func and update remaining on bugid
						//$( this ).dialog( "close" );
						$( "#action" ).val("updateRemainingAction");
						$('#formUpdateRemaining').submit();
					}
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
			close: function() {
				allFields.val( "" ).removeClass( "ui-state-error" );
			}
		});

	});
	</script>
	

<div class="content">


<div id="update_remaining_dialog_form" title="Task XXX - Update Remaining" style='display: none'>
	<p id="validateTips">Set new value</p>
	<form id='formUpdateRemaining' name='formUpdateRemaining' method='post' Action='dialog.php' >
	   <fieldset>
		   <label for="remaining">Remaining: </label>
		   <input type='text'  id='remaining' name='remaining' size='3' class='text' />
	   </fieldset>
      <input type='hidden' id='bugid'  name='bugid'  value=0 >
      <input type='hidden' id='action' name='action' value=noAction >
	</form>
</div>


issue 23 remaining = <a id='update_remaining_link' title='update' href='#' >1.3</a>
<br>
issue 30 remaining = <a id='issue_30_update_remaining_link' title='update' href='#' >3</a>
<br>
<br>
<br>


<?php
$action = isset($_POST['action']) ? $_POST['action'] : '';
$bugid  = isset($_POST['bugid']) ? $_POST['bugid'] : '';
$remaining  = isset($_POST['remaining']) ? $_POST['remaining'] : '';

if ("updateRemainingAction" == $action) {

  echo "Update bugid=$bugid, new_remaining=$remaining<br>";
} else {
   #echo "<br><br>NOTHING TODO: action $action bugid=$bugid<br>";
}


?>

	
<script>
	$(function() {
		
		$( "#update_remaining_link" ).click(function() {
			$( "#bugid" ).val(23);
			$( "#remaining" ).val(1.3); // set default value
			$( "#validateTips" ).text("blah blah Task 23 description");
			$( "#update_remaining_dialog_form" ).dialog('option', 'title', 'Task 23 - Update Remaining');
			$( "#update_remaining_dialog_form" ).dialog( "open" );
		});
		$( "#issue_30_update_remaining_link" ).click(function() {
			$( "#bugid" ).val(30);
			$( "#remaining" ).val(3); // set default value
			$( "#validateTips" ).text("Task 30 short description");
			$( "#update_remaining_dialog_form" ).dialog('option', 'title', 'Task 30 - Update Remaining');
			$( "#update_remaining_dialog_form" ).dialog( "open" );
		});

	});

</script>



</div>


</body>
</html>

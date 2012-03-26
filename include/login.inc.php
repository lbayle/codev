<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?php

include_once "constants.php";
include_once 'i18n.inc.php';
include_once "tools.php";

//
// MAIN
//
echo "<div id='login'>\n";
if (isset($_SESSION['userid'])) {
  echo T_("Logged in as ").$_SESSION['username']." (".$_SESSION['realname'].") <span class='floatr'><a href='".getServerRootURL()."/logout.php' title='logout'>".T_("log out")."</a></span>\n";
} else {
?>

<script type="text/javascript">
    function redirect() {
        // Need to reload the page for being sure all the information are correct
        //jQuery(this).dialog("close");
        window.location.reload();
    }

    // Preload Images
    img1 = new Image(220, 19);
    img1.src = "../images/loader-bar.gif";

    img2 = new Image(16, 16);
    img2.src = "../images/spinner.gif";

    // When DOM is ready
    jQuery(document).ready(function() {
        var username = $("#codev_login" );
        var password = $("#codev_passwd");
        var allFields = $([]).add(username).add(password);
        var tips = $("#validateTips");

        function updateTips(t) {
            tips.text(<?php echo T_(t) ?>).addClass("ui-state-highlight");
                setTimeout(function() {
                    tips.removeClass("ui-state-highlight", 1500);
                }, 500 );
            }

            function checkLength( o, n, min, max ) {
                if (o.val().length > max || o.val().length < min) {
                    o.addClass( "ui-state-error" );
                    updateTips( "Length of " + n + " must be between " +
                        min + " and " + max + "." );
                    return false;
                } else {
                    return true;
                }
            }

            jQuery("#login_container").dialog({
                title: '<?php echo T_("CoDev Login") ?>',
                autoOpen: true,
                closeOnEscape: false,
                modal: true,
                draggable: false,
                resizable: false,
                open: function() {
                    // Select input field contents
                    username.select();
                },
                buttons: {
                    OK: function() {
                        allFields.removeClass( "ui-state-error" );

                        var valid = true;
                        valid = valid && checkLength(username, "<?php echo T_("login") ?>", 1, 256);
                        valid = valid && checkLength(password, "<?php echo T_("password") ?>", 1, 256);

                        if (valid) {
                            jQuery("#login_form").submit();
                        }
                    }
                },
                close: function() {
                    allFields.val("").removeClass("ui-state-error");
                    jQuery("#login_success").hide();
                    jQuery("#login_error").hide();
                    jQuery("#login_form").show();
                    jQuery('.ui-dialog-buttonpane').show();
                    jQuery(".ui-button-text").html("OK");
                }
            });

            // When the form is submitted
            jQuery("#login_form").submit(function(event){
                event.preventDefault();

                tips.empty();
                jQuery(".ui-button-text").html("<img src='../images/spinner.gif' width='16' height='16' alt='Loading' />");

                /* get some values from elements on the page: */
                var form = $(this);
                var url = form.attr('action');

                // 'this' refers to the current submitted form
                var str = form.serialize();

                // -- Start AJAX Call --
                jQuery.ajax({
                    type: "POST",
                    url: url,  // Send the login info to this page
                    data: str,
                    success: function(msg){
                        if(msg == true) {
                            jQuery("#login_form").hide();
                            jQuery('.ui-dialog-buttonpane').hide();
                            jQuery('#login_success').show();
                            // After 1 second redirect
                            setTimeout('redirect()', 1000);
                        }
                        else {
                            jQuery(".ui-button-text").html("OK");
                            updateTips("Unauthorized");
                        }
                    }
                });
            });
        });
</script>
<div id="login_container" style="display:none">
    <form action="../login.php" method="post" name="login_form" id="login_form">
        <fieldset>
            <label><?php echo T_("Login") ?>: </label><br /><input name="codev_login" type="text" id="codev_login" /><br /><br />
            <label><?php echo T_("Password") ?>: </label><br /><input name="codev_passwd" type="password" id="codev_passwd" />
            <input type="hidden" name="action" value="login" />
        </fieldset>
    </form>
    <p id="validateTips"></p>
    <div id="login_success" style="display:none;text-align:center;">
        <img src="../images/loader-bar.gif" width="220" height="19" alt="Redirection" /><br/>
        <p style="margin-top:1em;">You are successfully logged in!<br />Please wait while you're redirected...</p>
    </div>
</div>

<?php
}
echo "</div>";
   
?>

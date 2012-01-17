<?php
require_once('gettext.inc');

# REM: http://localhost/index.php?locale=en   will give you english
#      http://localhost/index.php?locale=fr   will give you french


$locale = "fr"; // BP_LANG
$textdomain="codev";

if (isset($_GET['locale']) && !empty($_GET['locale'])) {
   $locale = $_GET['locale'];
   $_SESSION['locale']=$locale;
}
if (isset($_SESSION['locale']) && !empty($_SESSION['locale'])) {
	$locale =$_SESSION['locale'];
}

#echo "DEBUG locale=$locale<br/>\n";

putenv('LANGUAGE='.$locale);
putenv('LANG='.$locale);
putenv('LC_ALL='.$locale);
putenv('LC_MESSAGES='.$locale);
T_setlocale(LC_ALL,$locale);
T_setlocale(LC_CTYPE,$locale);

# we want 3.5 always to be displayed '3.5' and not '3,5'
setlocale(LC_NUMERIC,"en-US");


$locales_dir = (true == file_exists ( './i18n/locale' )) ? './i18n/locale' : $locales_dir = '../i18n/locale';


T_bindtextdomain($textdomain,$locales_dir);
T_bind_textdomain_codeset($textdomain, 'UTF-8');
T_textdomain($textdomain);


?>

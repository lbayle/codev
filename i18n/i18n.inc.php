<?php
require_once('gettext.inc');

# REM: http://localhost/index.php?locale=en   will give you english
#      http://localhost/index.php?locale=fr   will give you french

$locale = "fr"; // BP_LANG
$textdomain="codev";

if (isset($_GET['locale']) && !empty($_GET['locale'])) {
    $locale = $_GET['locale'];
} elseif (isset($_SESSION['locale']) && !empty($_SESSION['locale'])) {
    $locale = $_SESSION['locale'];
}

// WORKAROUND : Windows hacks
putenv('LANGUAGE='.$locale);
putenv('LANG='.$locale);
putenv('LC_ALL='.$locale);
putenv('LC_MESSAGES='.$locale);
// END WORKAROUND

if($locale == "fr") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"fr_FR","fr","fra","French");
} else if($locale == "en") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"en_US","us","usa","en","eng","English");
} else {
   // No locale set, it's because visitors modify the url, so forbidden reply
   header('HTTP/1.1 403 Forbidden');
   exit;
}

T_setlocale(LC_ALL, $phpLocale);

$_SESSION['locale'] = $locale;

// we want 3.5 always to be displayed '3.5' and not '3,5'
$phpLocale = setlocale(LC_NUMERIC,"en_US","us","usa","en","eng","English");
T_setlocale(LC_NUMERIC,$phpLocale);

$locales_dir = (true == file_exists ( './i18n/locale' )) ? './i18n/locale' : $locales_dir = '../i18n/locale';

T_bindtextdomain($textdomain,$locales_dir);
T_bind_textdomain_codeset($textdomain, 'UTF-8');
T_textdomain($textdomain);

?>

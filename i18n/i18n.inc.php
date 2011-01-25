<?php
require_once('gettext.inc');

# REM: http://localhost/index.php?locale=en   will give you english 
#      http://localhost/index.php?locale=fr   will give you french


$locale = fr;
$textdomain="codev";

if (empty($locale))
   $locale = 'fr';
if (isset($_GET['locale']) && !empty($_GET['locale']))
   $locale = $_GET['locale'];

putenv('LANGUAGE='.$locale);
putenv('LANG='.$locale);
putenv('LC_ALL='.$locale);
putenv('LC_MESSAGES='.$locale);
T_setlocale(LC_ALL,$locale);
T_setlocale(LC_CTYPE,$locale);

$locales_dir = './i18n/locale';
T_bindtextdomain($textdomain,$locales_dir);
T_bind_textdomain_codeset($textdomain, 'UTF-8'); 
T_textdomain($textdomain);
?>

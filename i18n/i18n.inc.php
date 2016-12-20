<?php
require_once('gettext.inc');

# REM: http://localhost/index.php?locale=en   will give you english
#      http://localhost/index.php?locale=fr   will give you french

# REM: run "locale -a" to see locales installed on the system

// WARNING http://php.net/manual/en/function.setlocale.php
//The locale information is maintained per process, not per thread.
//If you are running PHP on a multithreaded server API like IIS, HHVM or Apache on Windows,
//you may experience sudden changes in locale settings while a script is running,
//though the script itself never called setlocale().
//This happens due to other scripts running in different threads of the same process at the same time,
//changing the process-wide locale using setlocale().

// display current value:
//echo "<br>current Locale=".T_setlocale(LC_ALL, 0).'<br>';

$codevLocale = codevGetLocale();
//echo "locale = ".$codevLocale."<br>";

codevSetLocale($codevLocale);

try {
   if (($_SESSION['locale'] != $codevLocale) && Tools::isConnectedUser() && isset($_SESSION['teamid'])) {
      $user =  UserCache::getInstance()->getUser($_SESSION['userid']);
      $user->setDefaultLanguage($codevLocale);
   }
} catch (Exception $e) {
   #$logger->error('could not setDefaultLanguage ('.$locale.') for user '.$_SESSION['userid']);
}

$_SESSION['locale'] = $codevLocale;


// ===========================================================================
/**
 * convert CodevTT localse string to system UTF-8 string
 * and set locale usion gettext
 * 
 * @param type $codevLocale
 * @return type
 */
function codevSetLocale($codevLocale) {
   if($codevLocale === "fr") {
      // Try many values because OS doesn't have the same constants
      // setlocale() always return FALSE on windows, use gettext T_setlocale
      $phpLocale = T_setlocale(LC_ALL, "fr_FR.UTF-8","fr_FR.utf8","fr.UTF-8","fra.UTF-8","French.UTF-8");
   } elseif($codevLocale === "en") {
      $phpLocale = T_setlocale(LC_ALL,"en_US.UTF-8","us.UTF-8","usa.UTF-8","en.UTF-8","English");
   } elseif($codevLocale === "pt_BR") {
      $phpLocale = T_setlocale(LC_ALL,"pt_BR.UTF-8","pt.UTF-8","ptb.UTF-8","Portuguese");
   } elseif($codevLocale === "de_DE") {
      $phpLocale = T_setlocale(LC_ALL,"de_DE.UTF-8","de.UTF-8","German");
   } elseif($codevLocale === "it_IT") {
      $phpLocale = T_setlocale(LC_ALL,"it_IT.UTF-8","it.UTF-8","Italian");
   } elseif($codevLocale === "es_ES") {
      $phpLocale = T_setlocale(LC_ALL,"es_ES.UTF-8","es.UTF-8","Spanish");
   } elseif($codevLocale === "nl_NL") {
      $phpLocale = T_setlocale(LC_ALL,"nl_NL.UTF-8","nl.UTF-8","Netherlands");
   } elseif($codevLocale === "zh_CN") {
      $phpLocale = T_setlocale(LC_ALL, "zh_CN.UTF-8","zh_CN.utf8","cn.UTF-8","Chinese.UTF-8");
   } elseif($codevLocale === "zh_TW") {
      $phpLocale = T_setlocale(LC_ALL,"zh_TW.UTF-8","tw.UTF-8", "cht.UTF-8", "Taiwan.UTF-8", "chinese-traditional.UTF-8");
   } elseif($codevLocale === "lt") {
      $phpLocale = T_setlocale(LC_ALL,"lt_LT.UTF-8","lt.UTF-8","lithuanian");
   } elseif($codevLocale === "ko") {
      $phpLocale = T_setlocale(LC_ALL,"ko_KR.UTF-8", "ko.UTF-8","korean");
   } elseif($codevLocale === "ar") {
      $phpLocale = T_setlocale(LC_ALL,"ar_DZ.UTF-8", "ar.UTF-8","arab");
   } else {
      // set to system default:
      T_setlocale(LC_ALL, NULL);
   }
   //echo "phpLocale = ".var_export($phpLocale, true)."<br>";
   #file_put_contents("/data/codevtt_tmp_log.txt", "phpLocale=<$phpLocale>\n", FILE_APPEND);

   // WORKAROUND : Windows hacks
   putenv('LANGUAGE='.$phpLocale);
   putenv('LANG='.$phpLocale);
   //putenv('LC_ALL='.$phpLocale);
   //putenv('LC_MESSAGES='.$phpLocale);
   // END WORKAROUND

   //TODO --- WINDOWS ERROR ---
   // on windows: 
   // when using gettext.inc, setting LC_NUMERIC to en_US.UTF-8, LC_ALL is set too !
   // when NOT using gettext.inc, locales are not recognized by gettext...

   // we want 3.5 always to be displayed '3.5' and not '3,5'
   if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
      T_setlocale(LC_NUMERIC,"en_US.UTF-8","en_US.utf8","us.utf8","usa.utf8","en.utf8","eng.utf8","English");
   }
   // --------------------------

/*
   echo "<br>current Locale LC_COLLATE=".T_setlocale(LC_COLLATE, 0).'<br>';
   echo "<br>current Locale LC_CTYPE=".T_setlocale(LC_CTYPE, 0).'<br>';
   echo "<br>current Locale LC_MONETARY=".T_setlocale(LC_MONETARY, 0).'<br>';
   echo "<br>current Locale LC_NUMERIC=".T_setlocale(LC_NUMERIC, 0).'<br>';
   echo "<br>current Locale LC_TIME=".T_setlocale(LC_TIME, 0).'<br>';
   echo "<br>current Locale LC_MESSAGES=".T_setlocale(LC_MESSAGES, 0).'<br>';
*/

   $locales_dir = (TRUE === file_exists('./i18n/locale')) ? './i18n/locale' : $locales_dir = '../i18n/locale';

   $textdomain = "codev";
   T_bindtextdomain($textdomain,$locales_dir);
   T_bind_textdomain_codeset($textdomain, 'UTF-8');
   T_textdomain($textdomain);

   // I don't remember why...
   $availableEncoding = mb_detect_order();
   if(!array_search("ISO-8859-1", $availableEncoding)) {
      $newEnconding = array();
      $newEnconding[] = "ISO-8859-1"; // Latin-1
      mb_detect_order(array_merge($availableEncoding,$newEnconding)); // array ( 0 => 'ASCII', 1 => 'UTF-8', 2 => 'ISO-8859-1', )
   }

   //echo "<br>current Locale=".T_setlocale(LC_ALL, 0).'<br>';
   return T_setlocale(LC_ALL, 0);
}

/**
 * Get the user locale, choose in this order :
 * -> The locale from GET url
 * -> The locale set into the SESSION
 * -> The prefered existing locale of the browser
 * @return string The locale
 */
function codevGetLocale() {

   $locale = 'en'; // default language

   if (isset($_GET['locale']) && !empty($_GET['locale'])) {
      $locale = $_GET['locale'];
   } elseif (isset($_SESSION['locale']) && !empty($_SESSION['locale'])) {
      $locale = $_SESSION['locale'];
   } else {
      $langs = array();
      if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
         // break up string into pieces (languages and q factors)
         preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

         if (count($lang_parse[1])) {
            // create a list like "en" => 0.8
            $langs = array_combine($lang_parse[1], $lang_parse[4]);

            // set default to 1 for any without q factor
            foreach ($langs as $lang => $val) {
               if ($val === '') {
                  $langs[$lang] = 1;
               }
            }

            // sort list based on value
            arsort($langs, SORT_NUMERIC);
         }
      }

      // look through sorted list and use first one that matches our languages
      foreach (array_keys($langs) as $lang) {
         if (strpos($lang, 'fr') === 0) {
            $locale = 'fr';
            break;
         } elseif (strpos($lang, 'en') === 0) {
            $locale = 'en';
            break;
         } elseif (strpos($lang, 'de') === 0) {
            $locale = 'de_DE';
            break;
         } elseif (strpos($lang, 'it') === 0) {
            $locale = 'it_IT';
            break;
         } elseif (strpos($lang, 'es') === 0) {
            $locale = 'es_ES';
            break;
         } elseif (strpos($lang, 'nl') === 0) {
            $locale = 'nl_NL';
            break;
         } elseif (strpos($lang, 'pt') === 0) {
            $locale = 'pt_BR';
            break;
         } elseif (strpos($lang, 'cn') === 0) {
            $locale = 'zh_CN';
            break;
         } elseif (strpos($lang, 'tw') === 0) {
            $locale = 'zh_TW';
            break;
         } elseif (strpos($lang, 'lt') === 0) {
            $locale = 'lt';
            break;
         } elseif (strpos($lang, 'ko') === 0) {
            $locale = 'ko';
            break;
         } elseif (strpos($lang, 'ar') === 0) {
            $locale = 'ar';
            break;
         }
      }
   }
   return $locale;
}


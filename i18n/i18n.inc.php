<?php
require_once('gettext.inc');

# REM: http://localhost/index.php?locale=en   will give you english
#      http://localhost/index.php?locale=fr   will give you french

$locale = getLocale();

// WORKAROUND : Windows hacks
putenv('LANGUAGE='.$locale);
putenv('LANG='.$locale);
putenv('LC_ALL='.$locale);
putenv('LC_MESSAGES='.$locale);
// END WORKAROUND

if($locale === "fr") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"fr_FR","fr","fra","French");
} elseif($locale === "en") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"en_US","us","usa","en","eng","English");
} elseif($locale === "pt_BR") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"pt_BR","pt","Portuguese");
} elseif($locale === "de_DE") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"de_DE","de","German");
} elseif($locale === "it_IT") {
   // Try many values because OS doesn't have the same constants
   $phpLocale = setlocale(LC_ALL,"it_IT","it","Italian");
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

$locales_dir = (TRUE === file_exists('./i18n/locale')) ? './i18n/locale' : $locales_dir = '../i18n/locale';

$textdomain = "codev";

T_bindtextdomain($textdomain,$locales_dir);
T_bind_textdomain_codeset($textdomain, 'UTF-8');
T_textdomain($textdomain);

$availableEncoding = mb_detect_order();
if(!array_search("ISO-8859-1", $availableEncoding)) {
   $newEnconding = array();
   $newEnconding[] = "ISO-8859-1";
   mb_detect_order(array_merge($availableEncoding,$newEnconding));
}

/**
 * Get the user locale, choose in this order :
 * -> The locale from GET url
 * -> The locale set into the SESSION
 * -> The prefered existing locale of the browser
 * @return string The locale
 */
function getLocale() {

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
         } elseif (strpos($lang, 'pt') === 0) {
            $locale = 'pt_BR';
            break;
         }
      }
   }
   return $locale;
}

?>

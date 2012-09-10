<?php
/*
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
*/

// toolbox
// LoB 17 May 2010

require_once('lib/log4php/Logger.php');

class Tools {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @static
    * @return string current URL (complete, with ?params=<value>)
    */
   public static function getCurrentURL() {
      $pageURL = 'http';
      if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
      $pageURL .= "://";
      if ($_SERVER["SERVER_PORT"] != "80") {
         $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
      } else {
         $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
      }
      return $pageURL;
   }

   /**
    * @static
    * @return string current URL (no params)
    */
   public static function curPageName() {
      return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
   }

   /**
    * returns an HTML link to the Mantis page for Issue $bugid
    * ex: http://172.24.209.4/mantis/view.php?id=400
    * @static
    * @param int $bugid issue id in mantis DB
    * @param string $title
    * @param bool $isIcon
    * @param bool $inNewTab
    * @return string
    */
   public static function mantisIssueURL($bugid, $title=NULL, $isIcon=false, $inNewTab=true) {
      if (NULL==$title) { $title = "View Mantis Issue $bugid"; }

      $formatedTitle = str_replace("'", " ", $title);
      $formatedTitle = str_replace("\"", " ", $formatedTitle);

      $target = (false == $inNewTab) ? "" : "target='_blank'";

      if (false == $isIcon) {
         $url = "<a href='".Constants::$mantisURL."/view.php?id=$bugid' title='$formatedTitle' $target>$bugid</a>";
      } else {
         $url = "<a href='".Constants::$mantisURL."/view.php?id=$bugid' $target><img title='$formatedTitle' align='absmiddle' src='".Constants::$mantisURL."/images/favicon.ico' /></a>";
      }

      return $url;
   }

   /**
    * returns an HTML link to the TaskInfo page for Issue $bugid
    * ex: http://172.24.209.4/codev/reports/issue_info.php?bugid=60
    * @static
    * @param int $bugid issue id in mantis DB
    * @param string $title
    * @param bool $inNewTab
    * @return string
    */
   public static function issueInfoURL($bugid, $title=NULL, $inNewTab=true) {
      if (NULL==$title) { $title = "View info for Issue $bugid"; }

      $target = (false == $inNewTab) ? "" : "target='_blank'";

      $formatedTitle = str_replace("'", " ", $title);
      $formatedTitle = str_replace("\"", " ", $formatedTitle);

      return "<a  title='$formatedTitle' $target href='".self::getServerRootURL()."/reports/issue_info.php?bugid=$bugid'>$bugid</a>";
   }

   /**
    * Cette fonction transforme de ce format: 2008-09-04 11:13:18 en celui-ci : 1204456892
    * @static
    * @param $string
    * @return int
    */
   public static function datetime2timestamp($string) {
      list($date, $time) = explode(' ', $string);
      list($year, $month, $day) = explode('-', $date);
      list($hour, $minute, $second) = explode(':', $time);

      $timestamp = mktime(intval($hour), intval($minute), intval($second), intval($month), intval($day), intval($year));

      return $timestamp;
   }

   /**
    * Cette fonction transforme de ce format: 2008-09-04 en celui-ci : 1204456892
    * @static
    * @param string $string
    * @return int
    */
   public static function date2timestamp($string) {
      list($year, $month, $day) = explode('-', $string);

      $timestamp = mktime(0, 0, 0, intval($month), intval($day), intval($year));

      return $timestamp;
   }

   /**
    * @static
    * @param int $duration
    * @return string
    */
   public static function getDurationLiteral($duration) {
      if ($duration>=86400)
         /* 86400 = 3600*24 c'est a dire le nombre de secondes dans un seul jour ! donc la on verifie si le nombre de secondes donne contient des jours ou pas */
      {
         // Si c'est le cas on commence nos calculs en incluant les jours

         // on divise le nombre de seconde par 86400 (=3600*24)
         // puis on utilise la fonction floor() pour arrondir au plus petit
         $jour = floor($duration/86400);
         // On extrait le nombre de jours
         $reste = $duration%86400;

         $heure = floor($reste/3600);
         // puis le nombre d'heures
         $reste = $reste%3600;

         $minute = floor($reste/60);
         // puis les minutes

         $seconde = $reste%60;
         // et le reste en secondes

         // on rassemble les resultats en forme de date
         #$result = $jour.'j '.$heure.'h '.$minute.'min '.$seconde.'s';
         $result = $jour.'j '.$heure.'h '.$minute.'min ';
      }
      elseif ($duration < 86400 AND $duration>=3600)
         // si le nombre de secondes ne contient pas de jours mais contient des heures
      {
         // on refait la meme operation sans calculer les jours
         $heure = floor($duration/3600);
         $reste = $duration%3600;

         $minute = floor($reste/60);

         $seconde = $reste%60;

         #$result = $heure.'h '.$minute.'min '.$seconde.' s';
         $result = $heure.'h '.$minute.'min ';
      }
      elseif ($duration<3600 AND $duration>=60)
      {
         // si le nombre de secondes ne contient pas d'heures mais contient des minutes
         $minute = floor($duration/60);
         $seconde = $duration%60;

         #$result = $minute.'min '.$seconde.'s';
         $result = $minute.'min ';
      }
      elseif ($duration < 60)
         // si le nombre de secondes ne contient aucune minutes
      {
         if (0 == $duration) {
            $result =  " ";
         } else {
            $result = $duration.'s';
         }
      }
      return $result;
   }

   /**
    * get the week starting date by giving a week number and the year. Monday first day in week
    * @static
    * @param int $week
    * @param int $year
    * @return int timestamp  monday 0:00 of the given week
    */
   public static function weekStartDate($week,$year) {
      /*
      If you want the timestamp of the start of the ISO Week (i.e. on Monday) as defined by ISO 8601, you can use this one liner:
         $isoWeekStartTime = strtotime(date('o-\\WW')); // {isoYear}-W{isoWeekNumber}

      You can also find out the start of week of any time and format it into an ISO date with another one liner like this:
         $isoWeekStartDate = date('Y-m-d', strtotime(date('o-\\WW', $time)));
      */

      $timestamp        = strtotime("1.1.$year + $week weeks");
      $isoWeekStartDate = strtotime(date('o-\\WW', $timestamp));

      //echo "DEBUG isoWeekStartTime $isoWeekStartDate ".date('Y-m-d', $isoWeekStartDate);

      return $isoWeekStartDate;
   }

   /**
    * Function that returns the timestamp for each day in a week
    * @static
    * @param int $week
    * @param int $year
    * @return int[]
    */
   public static function week_dates($week, $year) {
      $monday = self::weekStartDate($week,$year);

      $week_dates = array();
      $week_dates[1] = $monday; // Monday
      $week_dates[2] = strtotime("+1 day",$monday); // Tuesday
      $week_dates[3] = strtotime("+2 day",$monday); // Wednesday
      $week_dates[4] = strtotime("+3 day",$monday); // Thursday
      $week_dates[5] = strtotime("+4 day",$monday); // Friday
      $week_dates[6] = strtotime("+5 day",$monday); // Saturday
      $week_dates[7] = strtotime("+6 day",$monday); // Sunday

      return $week_dates;
   }

   /**
    * @static
    * @param $tDay
    * @param $year
    * @param string $tFormat
    * @return string
    */
   public static function dayofyear2date($tDay, $year, $tFormat = 'Y-m-d') {
      $day = intval( $tDay );
      $day = ( $day == 0 ) ? $day : $day - 1;
      $offset = intval( intval( $tDay ) * 86400 );
      $str = date( $tFormat, strtotime( 'Jan 1, ' . $year ) + $offset );
      return( $str );
   }

   /**
    * @static
    * @param $tDay
    * @param $year
    * @return int
    */
   public static function dayofyear2timestamp( $tDay, $year) {
      date_default_timezone_set("Europe/Paris");  // GMT, UTC, DST or Europe/Paris

      $day = intval( $tDay );
      $day = ( $day == 0 ) ? $day : $day - 1;
      $offset = intval( intval( $tDay ) * 86400 );
      $timestamp = strtotime( 'Jan 1, ' . $year ) + $offset;

      // Compute current date from day of year (tDay), replace call to dayofyear2date?
      $date = new DateTime($year.'-01-01');
      $date->add(new DateInterval("P".$day."D"));

      //$season = ($date->format('I') == 1) ? "summer" : "winter";
      //echo "DEBUG date=".$date->format('Y-m-d')." (".$season.") <br>";
      if($date->format('I') == 1) {
         $timestamp -= (60 * 60);  // -1 hour in summer
      }

      #echo "DEBUG dayofyear2timestamp $tDay (year $year)= ".date("Y-m-d H:i:s", $timestamp)."<br/>";

      return( $timestamp );
   }

   /**
    * Format the date in locale
    * @static
    * @param string $pattern The pattern to user
    * @param int $timestamp The timestamp to format
    * @return string The localized date
    */
   public static function formatDate($pattern, $timestamp) {
      return utf8_encode(ucwords(strftime($pattern, $timestamp)));
   }

   /**
    * explode string to 2-dimentionnal array
    *
    * Usage:
    * $myArray = doubleExplode(':', ',', "key:value,key2:value2");
    *
    * Example:
    * '10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed'
    *
    * Array
    * (
    *    [10] => 'new'
    *    [20] => 'feedback'
    *    [30] => 'acknowledged'
    *    ...
    *  )
    * @static
    * @param string $del1        delimiter for key:value
    * @param string $del2        delimiter for couples (key,value)
    * @param string $keyvalue       the string to explode
    * @return string[]
    */
   public static function doubleExplode($del1, $del2, $keyvalue) {
      $array1 = explode("$del1", $keyvalue);
      foreach($array1 as $value){
         $array2 = explode("$del2", $value);
         foreach($array2 as $value2){
            $array3[] = $value2;
         }
      }
      $afinal = array();
      for ( $i = 0; $i < count($array3); $i += 2) {
         if($array3[$i]!="") {
            $afinal[trim($array3[$i])] = trim($array3[$i+1]);
         }
      }
      return $afinal;
   }

   /**
    * @static
    * @param string $del1
    * @param string $del2
    * @param array $array
    * @return string
    */
   public static function doubleImplode($del1, $del2, array $array) {
      $keyvalue = '';

      foreach($array as $key => $value) {
         $keyvalue .= $key.$del1.$value.$del2;
      }
      return $keyvalue;
   }

   /**
    * Sort function for Class instances
    * NOTE: the classes must implement Comparable interface.
    * @static
    * @param Comparable[] $a array of instances $a
    * @return bool true on success or false on failure.
    */
   public static function usort(array &$a) {
      if(count($a) > 0) {
         $className = get_class(current($a));
         return @usort($a, array($className, "compare"));
      } else {
         return $a;
      }
   }

   /**
    * QuickSort function for Class instances
    * NOTE: the classes must have a compareTo(objectB) method.
    * @static
    * @param array $a array of instances $a
    * @return mixed
    */
   public static function qsort(&$a) {
      return self::qsort_do($a,0,count($a)-1);
   }

   /**
    * @static
    * @param $a
    * @param $l
    * @param $r
    * @return mixed
    */
   private static function qsort_do(&$a,$l,$r) {
      if ($l < $r) {
         self::qsort_partition($a,$l,$r,$lp,$rp);
         self::qsort_do($a,$l,$lp);
         self::qsort_do($a,$rp,$r);
      }

      return $a;
   }

   /**
    * @static
    * @param $a
    * @param $l
    * @param $r
    * @param $lp
    * @param $rp
    */
   private static function qsort_partition(&$a,$l,$r,&$lp,&$rp) {
      $i = $l+1;
      $j = $l+1;

      while ($j <= $r) {
         if ($a[$j]->compareTo($a[$l])) {
            $tmp = $a[$j];
            $a[$j] = $a[$i];
            $a[$i] = $tmp;
            $i++;
         }
         $j++;
      }

      $x = $a[$l];
      $a[$l] = $a[$i-1];
      $a[$i-1] = $x;

      $lp = $i - 2;
      $rp = $i;
   }

   /**
    * Takes an URL as input and applies url encoding only to the parameter values
    * @static
    * @param string $url
    * @return string
    */
   public static function SmartUrlEncode($url){
      if (strpos($url, '=') == false) {
         return $url;
      } else {
         $startpos = strpos($url, "?");
         $tmpurl=substr($url, 0 , $startpos+1);
         $qryStr=substr($url, $startpos+1 );

         $qryvalues=explode("&", $qryStr);
         foreach($qryvalues as &$value) {
            $buffer=explode("=", $value);
            if (2 == count($buffer)) {
               $buffer[1]=urlencode($buffer[1]);
               $value = implode("=", $buffer);
            }
         }
         $finalqrystr=implode("&amp;", $qryvalues);
         $finalURL=$tmpurl . $finalqrystr;
         return $finalURL;
      }
   }

   /**
    * Parse file and execute commands via PHP mysql lib.
    * @static
    * @param string $sqlFile
    * @return bool
    */
   public static function execSQLscript($sqlFile) {
      $request = "";

      $sql=file($sqlFile);
      foreach($sql as $l){
         $l = trim($l);
         if(strlen($l) > 0) {
            if (substr($l,0,2) != "--"){ // remove comments
               $request .= $l;
            }
         }
      }

      $reqs = split(";",$request);// identify single requests
      foreach($reqs as $req) {
         if(strlen($req) > 0) {
            if (!SqlWrapper::getInstance()->sql_query($req)) {
               die("ERROR : ".$req." : ".SqlWrapper::getInstance()->sql_error());
               //return false;
            }
         }
      }

      return true;
   }

   /**
    * uses system to run 'mysql' cmd
    * @static
    * @param String $sqlFile
    * @return int 0 if Success
    */
   public static function execSQLscript2($sqlFile) {
      $command = "mysql --host=".Constants::$db_mantis_host." --user=".Constants::$db_mantis_user." --password=".Constants::$db_mantis_pass."  ".Constants::$db_mantis_database." < ".$sqlFile;

      #$status = system($command, $retCode);
      $status = exec($command, $output, $retCode);
      //if (0 != $retCode) {
      //   echo "FAILED (err $retCode) could not exec mysql commands from file: $sqlFile</br>";
      //}
      if(0 != $retCode) {
         if(self::execSQLscript($sqlFile)) {
            return 0;
         } else {
            return -1;
         }
      } else {
         return $retCode;
      }
   }

   /**
    * Get a clean up String value by GET
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return string The value or die if there is a problem
    */
   public static function getSecureGETStringValue($key,$defaultValue = NULL) {
      if(isset($_GET[$key])) {
         return Tools::escape_string($_GET[$key]);
      }
      else if(isset($defaultValue)) {
         return $defaultValue;
      }
      else {
         self::sendBadRequest("No GET value for ".$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Get a clean up String value by POST
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return string The value or die if there is a problem
    */
   public static function getSecurePOSTStringValue($key,$defaultValue = NULL) {
      if(isset($_POST[$key])) {
         return Tools::escape_string($_POST[$key]);
      }
      else if(isset($defaultValue)) {
         return $defaultValue;
      }
      else {
         self::sendBadRequest("No POST value for ".$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Get a clean up Integer value by GET
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return int The value or die if there is a problem
    */
   public static function getSecureGETNumberValue($key,$defaultValue = NULL) {
      $value = self::getSecureGETStringValue($key,$defaultValue);
      if(strlen($value) == 0) {
         $value = $defaultValue;
      }
      if (is_numeric($value)) {
         return $value;
      } else {
         self::sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Get a clean up Integer value by POST
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return int The value or die if there is a problem
    */
   public static function getSecurePOSTNumberValue($key,$defaultValue = NULL) {
      $value = self::getSecurePOSTStringValue($key,$defaultValue);
      if(strlen($value) == 0) {
         $value = $defaultValue;
      }
      if (is_numeric($value)) {
         return $value;
      } else {
         self::sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Get a clean up Integer value by GET
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return int The value or die if there is a problem
    */
   public static function getSecureGETIntValue($key,$defaultValue = NULL) {
      $value = self::getSecureGETStringValue($key,$defaultValue);
      if(strlen($value) == 0) {
         $value = $defaultValue;
      }
      if (is_numeric($value)) {
         return intval($value);
      } else {
         self::sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Get a clean up Integer value by POST
    * @static
    * @param string $key The key
    * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
    * @return int The value or die if there is a problem
    */
   public static function getSecurePOSTIntValue($key,$defaultValue = NULL) {
      $value = self::getSecurePOSTStringValue($key,$defaultValue);
      if(strlen($value) == 0) {
         $value = $defaultValue;
      }
      if (is_numeric($value)) {
         return intval($value);
      } else {
         self::sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }
   }

   /**
    * Send an 400 error
    * @static
    * @use Send when a user send a bad request (like weird POST)
    * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    * @param string $message The message for the admin
    */
   public static function sendBadRequest($message) {
      $e = new Exception('SECURITY ALERT: '.$message);
      self::$logger->fatal('EXCEPTION: '.$e->getMessage());
      self::$logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      //header('HTTP/1.1 400 Bad Request');
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }

   /**
    * Send an 401 error
    * @static
    * @use Send when a not logged user request a need to be logged page
    * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    */
   public static function sendUnauthorizedAccess() {
      header('HTTP/1.1 401 Unauthorized');
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }

   /**
    * Send an 403 error
    * @static
    * @use Send when a user request a page without enought rights
    * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    */
   public static function sendForbiddenAccess() {
      header('HTTP/1.1 403 Forbidden');
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }

   /**
    * Send an 404 error
    * @static
    * @use Send when a user request a page without enought rights
    * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
    */
   public static function sendNotFoundAccess() {
      header('HTTP/1.1 404 Not Found');
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }

   /**
    * Convert an array in json
    * If you use a new version of PHP(5.2 or newer), json_encode() function will be directly called
    * @static
    * @param array $arr
    * @return string
    */
   public static function array2json(array $arr) {
      //Lastest versions of PHP already has this functionality.
      if (function_exists('json_encode')) {
         return json_encode($arr);
      }
      $parts = array();
      $is_list = false;

      //Find out if the given array is a numerical array
      $keys = array_keys($arr);
      $max_length = count($arr) - 1;
      //See if the first key is 0 and last key is length - 1
      if (($keys[0] == 0) and ($keys[$max_length] == $max_length)) {
         $is_list = true;
         //See if each key correspondes to its position
         for ($i = 0; $i < count($keys); $i++) {
            // A key fails at position check.
            if ($i != $keys[$i]) {
               // It is an associative array.
               $is_list = false;
               break;
            }
         }
      }

      foreach ($arr as $key => $value) {
         // Custom handling for arrays
         if (is_array($value)) {
            if ($is_list)
               /* :RECURSION: */
               $parts[] = self::array2json($value);
            else
               /* :RECURSION: */
               $parts[] = '"' . $key . '":' . self::array2json($value);
         } else {
            $str = '';
            if (!$is_list) {
               $str = '"' . $key . '":';
            }

            //Custom handling for multiple data types
            if (is_numeric($value)) {
               // Numbers
               $str .= $value;
            }
            elseif ($value === false) {
               // The booleans
               $str .= 'false';
            }
            elseif ($value === true) {
               $str .= 'true';
            }
            else {
               //All other things
               $str .= '"' . addslashes($value) . '"';
            }

            // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
            $parts[] = $str;
         }
      }
      $json = implode(',', $parts);

      if ($is_list) {
         //Return numerical JSON
         return '[' . $json . ']';
      }
      //Return associative JSON
      return '{' . $json . '}';
   }

   /**
    * @static
    * @param $values
    * @return string
    */
   public static function array2plot($values) {
      $formattedValues = NULL;
      foreach ($values as $id => $value) {
         if ($formattedValues != NULL) {
            $formattedValues .= ',';
         }
         if(is_array($value)) {
            $formattedValues .= self::array2plot($value);
         } else {
            $formattedValues .= '["' . $id . '", ' . $value . ']';
         }
      }
      if(NULL != $formattedValues) {
         $formattedValues = '[' . $formattedValues . ']';
      }
      return $formattedValues;
   }

   /**
    * Convert the data in UTF-8 if it's in other encoding
    * @static
    * @param string $data The data to convert
    * @return string The converted data
    */
   public static function convertToUTF8($data) {
      $originalEncoding = mb_detect_encoding($data);
      //echo "encoding = ".$originalEncoding."<br>";

      if(mb_detect_encoding($data[0]) != "UTF-8") {
         return mb_convert_encoding($data,"UTF-8",$originalEncoding);
         //return utf8_encode($data);
      } else {
         return $data;
      }
   }

   /**
    * @static
    * @param number $bytes
    * @param int $precision
    * @return string
    */
   public static function bytesToSize1024($bytes, $precision = 2) {
      // human readable format -- powers of 1024
      $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');

      return @round(
         $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
      ) . ' ' . $unit[$i];
   }

   /**
    * @static
    * @param int $start_timestamp
    * @param int $end_timestamp
    * @param int $interval in days
    * @return int[]
    * @throws Exception
    */
   public static function createTimestampList($start_timestamp, $end_timestamp, $interval) {
      $timestampList = array();

      $timestamp = $start_timestamp;
      while ($timestamp < $end_timestamp) {
         #echo "createTimestampList() timestamp = ".date("Y-m-d H:i:s", $timestamp)." BEFORE<br>";
         // FIXME Weird, the timestamp should change at the end of the loop

         if (0 == $timestamp) {
            $e = new Exception("error strtotime(+$interval day, ".date("Y-m-d H:i:s", $timestamp).")");
            echo $e->getMessage();
            throw $e;
         }
         

         $timestampList[] = $timestamp;
         
         $newTimestamp = strtotime("+$interval day",$timestamp);
         $timestamp = $newTimestamp;
         

         #echo "createTimestampList() timestamp = ".date("Y-m-d H:i:s", $timestamp)." AFTER<br>";
      }
      return $timestampList;
   }

   /**
    * @static
    * @param string $directory
    * @return string
    */
   public static function checkWriteAccess($directory) {
      // Note: the 'ERROR' token in return string will be parsed, so
      //       do not remove it.
      // if path does not exist, try to create it
      if (!file_exists($directory)) {
         if (!mkdir($directory, 0755, true)) {
            return "ERROR : Could not create folder: $directory";
         }
      }

      // create a test file to check write access to the directory
      $testFilename = $directory . DIRECTORY_SEPARATOR . "test.txt";
      $fh = fopen($testFilename, 'w');
      if (!$fh) {
         return "ERROR : could not create test file: $testFilename";
      }

      // write something to the file
      $stringData = date("Y-m-d G:i:s", time()) . " - This is a TEST file generated during CoDev installation, You can remove it.\n";
      if (!fwrite($fh, $stringData)) {
         fclose($fh);
         return "ERROR : could not write to test file: $testFilename";
      }

      fclose($fh);

      if (file_exists($testFilename)) {
         $retCode = unlink($testFilename);
         if (!$retCode) {
            return "ERROR : Could not delete file: " . $testFilename;
         }
      }

      return "SUCCESS !";
   }

   /**
    * Exemple d'utilisation de la fonction : print_r(html2rgb('B8B9B9'));
    * @static
    * @param string $color
    * @return array|bool
    */
   public static function html2rgb($color) {
      if ($color[0] == '#') {
         $color = substr($color, 1);
      }
      if (strlen($color) == 6) {
         list($r, $g, $b) = array($color[0].$color[1],
            $color[2].$color[3],
            $color[4].$color[5]);
      } elseif (strlen($color) == 3) {
         list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1],   $color[2].$color[2]);
      } else {
         return false;
      }

      return array(hexdec($r), hexdec($g), hexdec($b));
   }

   /**
    * @static
    * @return string
    * example: http://127.0.0.1/codev/
    * example: http://55.7.137.27/louis/codev/
    */
   public static function getServerRootURL() {
      #if (isset($_GET['debug'])) {
      #foreach($_SERVER as $key => $value) {
      #   echo "_SERVER key=$key val=$value<br/>";
      #}}

      if(array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] == "on") {
         $protocol = "https";
      } else {
         $protocol = "http";
      }

      $rootURL = "$protocol://".$_SERVER['HTTP_HOST'].substr( $_SERVER['PHP_SELF'], 0 , strrpos( $_SERVER['PHP_SELF'], '/') );
      #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
      $rootURL = str_replace("/classes", "", $rootURL);
      $rootURL = str_replace("/timetracking", "", $rootURL);
      $rootURL = str_replace("/reports", "", $rootURL);
      $rootURL = str_replace("/doc", "", $rootURL);
      $rootURL = str_replace("/images", "", $rootURL);
      $rootURL = str_replace("/admin", "", $rootURL);
      $rootURL = str_replace("/tools", "", $rootURL);
      $rootURL = str_replace("/i18n", "", $rootURL);
      $rootURL = str_replace("/graphs", "", $rootURL);
      $rootURL = str_replace("/install", "", $rootURL);
      $rootURL = str_replace("/tests", "", $rootURL);
      $rootURL = str_replace("/import", "", $rootURL);
      $rootURL = str_replace("/blog", "", $rootURL);
      $rootURL = str_replace("/management", "", $rootURL);
      $rootURL = str_replace("/indicator_plugins", "", $rootURL);

      #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
      return $rootURL;
   }

   /**
    * @static
    * @param mixed[] $values
    * @return int[]
    */
   public static function getStartEndKeys($values) {
      $keys = array_keys($values);
      $start = $keys[0];
      $end = $keys[count($keys) - 1];
      return array($start, $end);
   }

   /**
    * Escapes special characters in a string
    * TODO Don't use mysql_escape_string
    * @static
    * @param string $unescaped_string The string that is to be escaped.
    * @return string the escaped string, or false on error.
    */
   public static function escape_string($unescaped_string) {
      return mysql_escape_string($unescaped_string);
   }

   /**
    * write ini file (read with parse_ini_file)
    * 
    * source: http://www.php.net/manual/en/function.parse-ini-file.php
    * 
    * @param type $array
    * @param type $file
    *
    * @return TRUE if write succeeded
    */
   public static function write_php_ini($array, $file) {
      $res = array();
      foreach($array as $key => $val) {

         // write empty lines
         if (('' === $val) || ("\n" === $val)) {
            $res[] = "";
            continue;
         }

         if(is_array($val)) {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) {
               if (0 === strpos($sval, ';', 0)) {
                  // write comments as is.
                  $res[] = $sval;
               } else {
                  $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
               }
            }
         } else {
            if (0 === strpos($val, ';', 0)) {
               // write comments as is.
               $res[] = $val;
            } else {
               $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
            }

         }
      }
      return self::safeFileRewrite($file, implode("\n", $res));
   }

   /**
    *
    * source: http://www.php.net/manual/en/function.parse-ini-file.php
    *
    * @param type $fileName
    * @param type $dataToSave
    */
   public static function safeFileRewrite($fileName, $dataToSave) {
      $fp = fopen($fileName, 'w');
      if ($fp) {
         $startTime = microtime();
         do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if(!$canWrite) usleep(round(rand(0, 100)*1000));
         } while ((!$canWrite)and((microtime()-$startTime) < 1000));

         //file was locked so now we can store information
         if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
         }
         fclose($fp);
      } else {
         echo "ERROR : safefilerewrite() could not write to file: $fileName<br>";
         return FALSE;
      }
      return TRUE;
   }



}

// Initialize complex static variables
Tools::staticInit();

?>

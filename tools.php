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

$logger = Logger::getLogger("tools");

/**
 * @return string current URL (complete, with ?params=<value>)
 */
function getCurrentURL() {
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
 * returns current URL (no params)
 */
function curPageName() {
   return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
}

/**
 * returns an HTML link to the Mantis page for Issue $bugid
 * ex: http://172.24.209.4/mantis/view.php?id=400
 * @param int $bugid issue id in mantis DB
 */
function mantisIssueURL($bugid, $title=NULL, $isIcon=false, $inNewTab=true) {
   global $mantisURL;
   if (NULL==$title) { $title = "View Mantis Issue $bugid"; }

   $formatedTitle = str_replace("'", " ", $title);
   $formatedTitle = str_replace("\"", " ", $formatedTitle);

   $target = (false == $inNewTab) ? "" : "target='_blank'";

   if (false == $isIcon) {
      $url = "<a href='$mantisURL/view.php?id=$bugid' title='$formatedTitle' $target>$bugid</a>";
   } else {
      $url = "<a href='$mantisURL/view.php?id=$bugid' $target><img title='$formatedTitle' align='absmiddle' src='$mantisURL/images/favicon.ico' /></a>";
   }

   return $url;

}

/**
 * returns an HTML link to the TaskInfo page for Issue $bugid
 * ex: http://172.24.209.4/codev/reports/issue_info.php?bugid=60
 * @param int $bugid issue id in mantis DB
 */
function issueInfoURL($bugid, $title=NULL, $inNewTab=true) {
   if (NULL==$title) { $title = "View info for Issue $bugid"; }

   $target = (false == $inNewTab) ? "" : "target='_blank'";

   $formatedTitle = str_replace("'", " ", $title);
   $formatedTitle = str_replace("\"", " ", $formatedTitle);

   return "<a  title='$formatedTitle' $target href='".getServerRootURL()."/reports/issue_info.php?bugid=$bugid'>$bugid</a>";
}

/**
 * Cette fonction transforme de ce format: 2008-09-04 11:13:18 en celui-ci : 1204456892
 */
function datetime2timestamp($string) {
   list($date, $time) = explode(' ', $string);
   list($year, $month, $day) = explode('-', $date);
   list($hour, $minute, $second) = explode(':', $time);

   $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

   return $timestamp;
}

/** Cette fonction transforme de ce format: 2008-09-04 en celui-ci : 1204456892
 * @param unknown_type $string
 * @return number
 */
function date2timestamp($string) {
   list($year, $month, $day) = explode('-', $string);

   $timestamp = mktime(0, 0, 0, $month, $day, $year);

   return $timestamp;
}

// ---------------------------
function getDurationLiteral($duration) {
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

/** get the week starting date by giving a week number and the year. Monday first day in week
 * @param int $week
 * @param int $year
 *
 * @return timestamp  monday 0:00 of the given week
 */
function weekStartDate($week,$year) {
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
 */
function week_dates($week, $year) {
   $monday = weekStartDate($week,$year);

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

// ---------------------------
function dayofyear2date( $tDay, $year, $tFormat = 'Y-m-d' ) {
   $day = intval( $tDay );
   $day = ( $day == 0 ) ? $day : $day - 1;
   $offset = intval( intval( $tDay ) * 86400 );
   $str = date( $tFormat, strtotime( 'Jan 1, ' . $year ) + $offset );
   return( $str );
}

// ---------------------------
function dayofyear2timestamp( $tDay, $year) {
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
 * @param string $pattern The pattern to user
 * @param int $timestamp The timestamp to format
 * @return string The localized date
 */
function formatDate($pattern, $timestamp) {
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

 * @param char   $del1        delimiter for key:value
 * @param char   $del2        delimiter for couples (key,value)
 * @param string $array       the string to explode
 */
function doubleExplode ($del1, $del2, $keyvalue) {
   $array1 = explode("$del1", $keyvalue);
   foreach($array1 as $key=>$value){
      $array2 = explode("$del2", $value);
      foreach($array2 as $key2=>$value2){
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

function doubleImplode ($del1, $del2, $array) {

   $keyvalue = '';

   foreach($array as $key => $value) {
      $keyvalue .= $key.$del1.$value.$del2;
   }
   return $keyvalue;
}

/**
 * QuickSort function for Class instances
 * NOTE: the classes must have a compareTo(objectB) method.
 * @param array of instances $a
 */
function qsort(&$a) {
   return qsort_do($a,0,count($a)-1);
}

function qsort_do(&$a,$l,$r) {
   if ($l < $r) {
      qsort_partition($a,$l,$r,$lp,$rp);
      qsort_do($a,$l,$lp);
      qsort_do($a,$rp,$r);
   }

   return $a;
}

function qsort_partition(&$a,$l,$r,&$lp,&$rp) {
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
 */
function SmartUrlEncode($url){
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
 * parse file and execute commands via PHP mysql lib.
 * @param $sqlFile
 */
function execSQLscript($sqlFile) {

   $requetes="";

   $sql=file($sqlFile);
   foreach($sql as $l){
      if (substr(trim($l),0,2)!="--"){ // remove comments
         $requetes .= $l;
      }
   }

   $reqs = split(";",$requetes);// identify single requests
   foreach($reqs as $req){
      if (!SqlWrapper::getInstance()->sql_query($req) && trim($req)!="") {
         die("ERROR : ".$req." ---> ".SqlWrapper::getInstance()->sql_error());
      }
   }
}

/**
 * uses system to run 'mysql' cmd
 * @param String $sqlFile
 * @return int 0 if Success
 */
function execSQLscript2($sqlFile) {
   global $db_mantis_host;
   global $db_mantis_user;
   global $db_mantis_pass;
   global $db_mantis_database;

   $command = "mysql --host=$db_mantis_host --user=$db_mantis_user --password=$db_mantis_pass  $db_mantis_database < $sqlFile";

   #$status = system($command, $retCode);
   $status = exec($command, $output, $retCode);
   //if (0 != $retCode) {
   //   echo "FAILED (err $retCode) could not exec mysql commands from file: $sqlFile</br>";
   //}
   return $retCode;
}

/**
 * Get a clean up String value by GET
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return string The value or die if there is a problem
 */
function getSecureGETStringValue($key,$defaultValue = NULL) {
   if(isset($_GET[$key])) {
      return SqlWrapper::getInstance()->sql_real_escape_string($_GET[$key]);
   }
   else if(isset($defaultValue)) {
      return $defaultValue;
   }
   else {
      sendBadRequest("No GET value for ".$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Get a clean up String value by POST
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return string The value or die if there is a problem
 */
function getSecurePOSTStringValue($key,$defaultValue = NULL) {
   if(isset($_POST[$key])) {
      return SqlWrapper::getInstance()->sql_real_escape_string($_POST[$key]);
   }
   else if(isset($defaultValue)) {
      return $defaultValue;
   }
   else {
      sendBadRequest("No POST value for ".$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Get a clean up Integer value by GET
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return int The value or die if there is a problem
 */
function getSecureGETNumberValue($key,$defaultValue = NULL) {
   $value = getSecureGETStringValue($key,$defaultValue);
   if (is_numeric($value)) {
      return $value;
   } else {
      sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Get a clean up Integer value by POST
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return int The value or die if there is a problem
 */
function getSecurePOSTNumberValue($key,$defaultValue = NULL) {
   $value = getSecurePOSTStringValue($key,$defaultValue);
   if (is_numeric($value)) {
      return $value;
   } else {
      sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Get a clean up Integer value by GET
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return int The value or die if there is a problem
 */
function getSecureGETIntValue($key,$defaultValue = NULL) {
   $value = getSecureGETStringValue($key,$defaultValue);
   if (is_numeric($value)) {
      return intval($value);
   } else {
      sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Get a clean up Integer value by POST
 * @param string $key The key
 * @param mixed $defaultValue The value used if no value found. If null, the value is mandatory
 * @return int The value or die if there is a problem
 */
function getSecurePOSTIntValue($key,$defaultValue = NULL) {
   $value = getSecurePOSTStringValue($key,$defaultValue);
   if (is_numeric($value)) {
      return intval($value);
   } else {
      sendBadRequest('Attempt to set non_numeric value ('.$value.') for '.$key);
      die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
   }
}

/**
 * Send an 400 error
 * @use Send when a user send a bad request (like weird POST)
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 * @param string $message The message for the admin
 */
function sendBadRequest($message) {
   global $logger;
   $e = new Exception('SECURITY ALERT: '.$message);
   $logger->fatal('EXCEPTION: '.$e->getMessage());
   $logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
   //header('HTTP/1.1 400 Bad Request');
   die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
}

/**
 * Send an 401 error
 * @use Send when a not logged user request a need to be logged page
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 */
function sendUnauthorizedAccess() {
   header('HTTP/1.1 401 Unauthorized');
   die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
}

/**
 * Send an 403 error
 * @use Send when a user request a page without enought rights
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 */
function sendForbiddenAccess() {
   header('HTTP/1.1 403 Forbidden');
   die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
}

/**
 * Send an 404 error
 * @use Send when a user request a page without enought rights
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 */
function sendNotFoundAccess() {
   header('HTTP/1.1 404 Not Found');
   die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
}

?>

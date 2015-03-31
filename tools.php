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

class Tools {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * array id => name
    */
   private static $customFieldNames;



   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    *
    * @param string $checkVersion
    * @return boolean true if version is recent enough
    */
   public static function checkPhpVersion($checkVersion = "5.3") {

      return (strnatcmp(phpversion(),$checkVersion) >= 0);
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
    * CodevTT needs some output directories to be dedined:
    * logs, reports, smarty, ...
    *
    * @return null if OK, error string if FAILED
    */
   public static function checkOutputDirectories() {

      $isValid = TRUE;
      $message = '';
      $errStr = Tools::checkWriteAccess(dirname(Constants::$codevtt_logfile));
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }

      $errStr = Tools::checkWriteAccess(Constants::$codevOutputDir.'/logs');
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }

      $errStr = Tools::checkWriteAccess(Constants::$codevOutputDir.'/reports');
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }

      $errStr = Tools::checkWriteAccess(Constants::$codevOutputDir.'/tpl');
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }

      $errStr = Tools::checkWriteAccess(Constants::$codevOutputDir.'/cache');
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }

      $errStr = Tools::checkWriteAccess(Constants::$codevOutputDir.'/template_c');
      if (NULL !== $errStr) { $isValid = FALSE; $message .= $errStr."\n"; }
      
      return ($isValid ? NULL : $message);
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
   public static function mantisIssueURL($bugid, $title=NULL, $isIcon=FALSE, $inNewTab=FALSE) {
      $target = $inNewTab ? 'target="_blank"' : '';
      if (is_null($title)) {
         $title = "Open in Mantis";

         if (!$isIcon) {
            return '<a href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" title="'.$title.'" '.$target.'>'.$bugid.'</a>';
         } else {
            return '<a href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" '.$target.'><img title="'.$title.'" align="absmiddle" src="images/mantis_ico.gif" /></a>';
         }
      } else if(is_array($title)) {
         $tooltip = self::getTooltip($title);
         if (!$isIcon) {
            return '<a class="haveTooltip" href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" '.$target.'>'.$bugid.'</a>'.$tooltip;
         } else {
            return '<a class="haveTooltip" href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" '.$target.'><img align="absmiddle" src="images/mantis_ico.gif" /></a>'.$tooltip;
         }
      } else {
         if (!$isIcon) {
            return '<a href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" title="'.$title.'" '.$target.'>'.$bugid.'</a>';
         } else {
            return '<a href="'.Constants::$mantisURL.'/view.php?id='.$bugid.'" '.$target.'><img title="'.$title.'" align="absmiddle" src="images/mantis_ico.gif" /></a>';
         }
      }
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
   public static function issueInfoURL($bugid, $title=NULL, $inNewTab=FALSE) {
      $target = $inNewTab ? 'target="_blank"' : '';
      if (is_null($title)) {
         $title = "View info";
         return '<a '.$target.' href="reports/issue_info.php?bugid='.$bugid.'" title="'.$title.'">'.$bugid.'</a>';
      } else if(is_array($title)) {
         $tooltip = self::getTooltip($title);
         return '<a class="haveTooltip" '.$target.' href="reports/issue_info.php?bugid='.$bugid.'">'.$bugid.'</a>'.$tooltip;
      } else {
         return '<a '.$target.' href="reports/issue_info.php?bugid='.$bugid.'" title="'.$title.'">'.$bugid.'</a>';
      }
   }

   /**
    * return an image with a nice HTML tooltip
    *
    * @param string $img  path to image ex: 'images/toto.png'
    * @param string/array $tooltipAttr tooltip
    * @param string $imgId id or (NULL if none)
    * @param type $imgClass additional js classes (NULL if none)
    * @param string $otherArgs additional attributes (NULL if none)
    * @return string <img /> HTML element
    */
   public static function imgWithTooltip($img, $tooltipAttr, $imgId=NULL,$imgClass=NULL,$otherArgs=NULL) {

      if (!is_null($imgId)) {
         $id = 'id="'.$imgId.'"';
      }

      $imgClass = ''.$imgClass;
      $otherArgs = ''.$otherArgs;

      if(is_array($tooltipAttr)) {
         $tooltip = self::getTooltip($tooltipAttr);
         $imgClass .= ' haveTooltip';
         return '<img '.$id.' class="'.$imgClass.'" title="" align="absmiddle" src="'.$img.'" '.$otherArgs.'/>'.$tooltip;

      } else {
         return '<img '.$id.' class="'.$imgClass.'" title="'.nl2br(htmlspecialchars($tooltipAttr)).'" align="absmiddle" src="'.$img.'"'.$otherArgs.' />';

      }
   }

   /**
    * returns a <div that must be added directly behind the <a tag that will display the tooltip.
    * this <a tag MUST have the class haveTooltip
    *
    * <a class="haveTooltip" href="#">blabla</a> <div tooltip>;
    *
    * @param type $title
    * @return string
    */
   public static function getTooltip($title) {
      $tooltip = '<div class="tooltip ui-helper-hidden">'.
                 '<table style="margin:0;border:0;padding:0;background-color:white;">'.
                 '<tbody>';
      $driftColor = NULL;
      $driftMgrColor = NULL;
      if (array_key_exists('DriftColor', $title)) {
         $driftColor = $title['DriftColor'];
         unset($title['DriftColor']);
      }
      if (array_key_exists('DriftMgrColor', $title)) {
         $driftMgrColor = $title['DriftMgrColor'];
         unset($title['DriftMgrColor']);
      }
      foreach ($title as $key => $value) {
         $tooltip .= '<tr>'.
                     '<td valign="top" style="color:blue;width:35px;">'.$key.'</td>';
         if ($driftColor != NULL && $key == T_('Drift')) {
            $tooltip .= '<td><span style="background-color:#'.$driftColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
         } else if (!is_null($driftMgrColor) && $key == T_('DriftMgr')) {
            $tooltip .= '<td><span style="background-color:#'.$driftMgrColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
         } else {
            $tooltip .= '<td>'.nl2br(htmlspecialchars($value)).'</td>';
         }
         $tooltip .= '</tr>';
      }
      $tooltip .= '</tbody></table></div>';
      return $tooltip;
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

      if ($timestamp < 0) {
         self::$logger->error("datetime2timestamp($string) Failed.");
         $e = new Exception("datetime2timestamp($string) Failed.");
         self::$logger->error("stack-trace:\n".$e->getTraceAsString());
         $timestamp = 0;
      }
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

      if ($timestamp < 0) {
         self::$logger->error("date2timestamp($string) Failed: month=$month day=$day year=$year");
         $e = new Exception("date2timestamp($string) Failed.");
         self::$logger->error("stack-trace:\n".$e->getTraceAsString());
         $timestamp = 0;
      }
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

      $week -= 1;
      $timestamp        = strtotime("1.1.$year + $week weeks");
      $isoWeekStartDate = strtotime(date('o-\\WW', $timestamp));

      #echo "DEBUG isoWeekStartTime $isoWeekStartDate ".date('Y-m-d', $isoWeekStartDate).'<br>';

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
    * Takes an URL as input and applies url encoding only to the parameter values
    * @static
    * @param string $url
    * @return string
    */
   public static function SmartUrlEncode($url){
      if (strpos($url, '=') == FALSE) {
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
      echo "DEBUG 2/3 execSQLscript $sqlFile<br>";
      $request = "SELECT LOAD_FILE('".$sqlFile."')";

      $result = SqlWrapper::getInstance()->sql_query($request);

      if (!$result) {
         $error = "ERROR : ".$request." : ".SqlWrapper::getInstance()->sql_error();
         echo "<span class='error_font'>$error</span><br />";
         exit;
      }

      if (is_null(SqlWrapper::getInstance()->sql_result($result, 0))) {
         $error = 'ERROR : could not LOAD_FILE ('.$sqlFile.') : NULL returned.';
         echo "<span class='error_font'>$error</span><br />";

         // SELECT LOAD_FILE doesn't work on all OS !
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
      }

      return TRUE;
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
         if(self::execSQLscript(dirname(__FILE__).DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$sqlFile)) {
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
      if(strlen(trim($value)) == 0) {
         $value = $defaultValue;
      }
      if (is_numeric(trim($value))) {
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
      $is_list = FALSE;

      //Find out if the given array is a numerical array
      $keys = array_keys($arr);
      $max_length = count($arr) - 1;
      //See if the first key is 0 and last key is length - 1
      if (($keys[0] == 0) and ($keys[$max_length] == $max_length)) {
         $is_list = TRUE;
         //See if each key correspondes to its position
         for ($i = 0; $i < count($keys); $i++) {
            // A key fails at position check.
            if ($i != $keys[$i]) {
               // It is an associative array.
               $is_list = FALSE;
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
            elseif ($value === FALSE) {
               // The booleans
               $str .= 'false';
            }
            elseif ($value === TRUE) {
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
      $unit = array('B', 'K', 'M', 'G', 'T', 'P', 'E');

      return @round(
         $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
      ) . ' ' . $unit[$i];
   }

   /**
    *
    * @param string $from humman readable size (128M, 12G, 100K)
    * @return int bytes
    */
   public static function convertToBytes($from){

       $number=substr($from,0,-1);
       switch(strtoupper(substr($from,-1))){
           case "K":
               return $number*1024;
           case "M":
               return $number*pow(1024,2);
           case "G":
               return $number*pow(1024,3);
           case "T":
               return $number*pow(1024,4);
           default:
               return $from;
       }
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

         #echo "createTimestampList() timestamp = ".date("Y-m-d H:i:s", $timestamp)."<br>";
         $timestampList[] = $timestamp;

         $newTimestamp = strtotime("+$interval day",$timestamp);
         if (0 == $newTimestamp) {
            $e = new Exception("error strtotime(+$interval day, ".date("Y-m-d H:i:s", $newTimestamp).")");
            echo $e->getMessage();
            throw $e;
         }

         $timestamp = $newTimestamp;
      }

      $timestampList[] = $end_timestamp;
      #echo "createTimestampList() latest = ".date("Y-m-d H:i:s", $end_timestamp)."<br>";

      return $timestampList;
   }

   /**
    * @static
    * @param string $directory
    * @return string error string or NULL if success
    */
   public static function checkWriteAccess($directory) {
      // Note: the 'ERROR' token in return string will be parsed, so
      //       do not remove it.
      // if path does not exist, try to create it
      if (!file_exists($directory)) {
         if (!mkdir($directory, 0775, TRUE)) {
            return "ERROR : Could not create folder: $directory";
         }
      }
      if (false === @opendir($directory)) {
         return "ERROR : Could not opendir: $directory";
      }

      // create a test file to check write access to the directory
      $testFilename = $directory . DIRECTORY_SEPARATOR . "test.txt";

      if (FALSE === file_put_contents($testFilename, date("Y-m-d G:i:s")." - This TEST file can be removed\n")) {
         return "ERROR : could not create test file: $testFilename";
      }

      if (file_exists($testFilename)) {
         $retCode = unlink($testFilename);
         if (!$retCode) {
            return "ERROR : Could not delete file: " . $testFilename;
         }
      }

      // SUCCESS
      return NULL;
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
         return FALSE;
      }

      return array(hexdec($r), hexdec($g), hexdec($b));
   }

   /**
    * this method causes big trouble when using CodevTT behind a reverseProxy
    * that would forward HTTPS requests to HTTP
    *
    * this method is also responsible for the 'subdirectory install' problem
    * (installing CodevTT in a folder named '/var/www/html/tools/codevtt' fails)
    *
    * @deprecated
    * @static
    * @return string
    * example: http://127.0.0.1/codev/
    * example: http://55.7.137.27/louis/codev/
    */
   public static function getServerRootURL() {
      if(array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] == "on") {
         $protocol = "https";
      } else {
         $protocol = "http";
      }
      #$rootURL = "$protocol://".$_SERVER['HTTP_HOST'].substr( $_SERVER['PHP_SELF'], 0 , strrpos( $_SERVER['PHP_SELF'], '/') );
      $folders = array("/admin", "/blog", "/classes", "/doc", "filters", "/graphs", "/i18n", "/images", "/import",
                       "/indicator_plugins", "/install", "/management", "/reports", "/tests", "/timetracking", "/tools");

      $rootURL = "$protocol://".$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
      $rootURL = str_replace($folders, "", $rootURL);
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
    * @static
    * @param string $unescaped_string The string that is to be escaped.
    * @return string the escaped string, or false on error.
    */
   public static function escape_string($unescaped_string) {
      return SqlWrapper::getInstance()->sql_real_escape_string($unescaped_string);
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

   public static function isConnectedUser() {
      return array_key_exists('userid',$_SESSION);
   }

   public static function endsWith($haystack, $needle)
   {
      $length = strlen($needle);
      if ($length == 0) {
         return TRUE;
      }

      return (substr($haystack, -$length) === $needle);
   }

   /**
    * create a customField in Mantis (if not exist) & update codev_config_table
    *
    * ex: $install->createCustomField("ExtRef", 0, "customField_ExtId");
    *
    * @param string $fieldName Mantis field name
    * @param int $fieldType Mantis field type
    * @param string $configId  codev_config_table.config_id
    * @param type $attributes
    * @param type $default_value
    * @param type $possible_values
    */
   public static function createCustomField($fieldName, $fieldType, $configId, $attributes = NULL,
                              $default_value = '', $possible_values = '') {
      // get existing Mantis custom fields
      $fieldList = array();

      if (NULL == $attributes) {
         $attributes = array();

         $attributes["access_level_r"] = 10;
         $attributes["access_level_rw"] = 25;
         $attributes["require_report"] = 1;
         $attributes["require_update"] = 1;
         $attributes["require_resolved"] = 0;
         $attributes["require_closed"] = 0;
         $attributes["display_report"] = 1;
         $attributes["display_update"] = 1;
         $attributes["display_resolved"] = 0;
         $attributes["display_closed"] = 0;

         echo "<span class='warn_font'>WARN: using default attributes for CustomField $fieldName</span><br/>";
      }

      $query = "SELECT id, name FROM `mantis_custom_field_table`";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>" . mysql_error() . "</span>");
      while ($row = mysql_fetch_object($result)) {
         $fieldList["$row->name"] = $row->id;
      }

      $fieldId = $fieldList[$fieldName];
      if (!$fieldId) {
         $query2 = "INSERT INTO `mantis_custom_field_table` " .
            "(`name`, `type` ,`access_level_r`," .
            "                 `access_level_rw` ,`require_report` ,`require_update` ,`display_report` ,`display_update` ,`require_resolved` ,`display_resolved` ,`display_closed` ,`require_closed` ";
         $query2 .= ", `possible_values`, `default_value`";

         $query2 .= ") VALUES ('$fieldName', '$fieldType', '" . $attributes["access_level_r"] . "', '" .
            $attributes["access_level_rw"] . "', '" .
            $attributes["require_report"] . "', '" .
            $attributes["require_update"] . "', '" .
            $attributes["display_report"] . "', '" .
            $attributes["display_update"] . "', '" .
            $attributes["require_resolved"] . "', '" .
            $attributes["display_resolved"] . "', '" .
            $attributes["display_closed"] . "', '" .
            $attributes["require_closed"] . "'";

         $query2 .= ", '$possible_values', '$default_value'";
         $query2 .= ");";

         #echo "DEBUG INSERT $fieldName --- query $query2 <br/>";

         $result2 = mysql_query($query2) or die("<span style='color:red'>Query FAILED: $query2 <br/>" . mysql_error() . "</span>");
         $fieldId = mysql_insert_id();

         #echo "custom field '$configId' created.<br/>";
      } else {
         echo "<span class='success_font'>INFO: custom field '$configId' already exists.</span><br/>";
      }

      // add to codev_config_table
      Config::getInstance()->setValue($configId, $fieldId, Config::configType_int);
   }


   /**
    *
    * @return boolean true if IE
    */
   public static function usingBrowserIE()
   {
       $u_agent = $_SERVER['HTTP_USER_AGENT'];
       $ub = FALSE;
       if(preg_match('/MSIE/i',$u_agent))
       {
           $ub = TRUE;
       }
       return $ub;
   }

   /**
    * 
    * $ua=getBrowser();
    * $yourbrowser= "Your browser: " . $ua['name'] . " " . $ua['version'] . " on " .$ua['platform'];
    * print_r($yourbrowser);
    * 
    * @return array
    */
   public static function getBrowser()
   {
       $u_agent = $_SERVER['HTTP_USER_AGENT'];
       $bname = 'Unknown';
       $platform = 'Unknown';
       $version= "";

       //First get the platform?
       if (preg_match('/linux/i', $u_agent)) {
           $platform = 'linux';
       }
       elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
           $platform = 'mac';
       }
       elseif (preg_match('/windows|win32/i', $u_agent)) {
           $platform = 'windows';
       }

       // Next get the name of the useragent yes seperately and for good reason
       if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
       {
           $bname = 'Internet Explorer';
           $ub = "MSIE";
       }
       elseif(preg_match('/Firefox/i',$u_agent))
       {
           $bname = 'Mozilla Firefox';
           $ub = "Firefox";
       }
       elseif(preg_match('/Chrome/i',$u_agent))
       {
           $bname = 'Google Chrome';
           $ub = "Chrome";
       }
       elseif(preg_match('/Safari/i',$u_agent))
       {
           $bname = 'Apple Safari';
           $ub = "Safari";
       }
       elseif(preg_match('/Opera/i',$u_agent))
       {
           $bname = 'Opera';
           $ub = "Opera";
       }
       elseif(preg_match('/Netscape/i',$u_agent))
       {
           $bname = 'Netscape';
           $ub = "Netscape";
       }
   
      // finally get the correct version number
      $known = array('Version', $ub, 'other');
      $pattern = '#(?<browser>' . join('|', $known) .
      ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
      if (!preg_match_all($pattern, $u_agent, $matches)) {
          // we have no matching number just continue
      }

      // see how many we have
      $i = count($matches['browser']);
      if ($i != 1) {
          //we will have two since we are not using 'other' argument yet
          //see if version is before or after the name
          if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
              $version= $matches['version'][0];
          }
          else {
              $version= $matches['version'][1];
          }
      }
      else {
          $version= $matches['version'][0];
      }

      // check if we have a number
      if ($version==null || $version=="") {$version="?";}

      return array(
          'userAgent' => $u_agent,
          'name'      => $bname,
          'version'   => $version,
          'platform'  => $platform,
          'pattern'    => $pattern
      );
   }
   
   /**
    * get customField name from id
    *
    * @param int $customFieldId field id
    * @return string field name
    */
   public static function getCustomFieldName($customFieldId) {

      if (is_null(self::$customFieldNames)) {

         $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);
         $mgrEffortEstimField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
         $effortEstimField = Config::getInstance()->getValue(Config::id_customField_effortEstim);
         $backlogField = Config::getInstance()->getValue(Config::id_customField_backlog);
         $addEffortField = Config::getInstance()->getValue(Config::id_customField_addEffort);
         $deadLineField = Config::getInstance()->getValue(Config::id_customField_deadLine);
         $deliveryDateField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
         #$deliveryIdField = Config::getInstance()->getValue(Config::id_customField_deliveryId);
         $customField_type = Config::getInstance()->getValue(Config::id_customField_type);

         self::$customFieldNames = array();
         $query = "SELECT id, name FROM `mantis_custom_field_table` ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $name = NULL;
            switch (intval($row->id)) {

               case $extIdField:
                  $name = T_('External ID');
                  break;
               case $customField_type:
                  $name = T_('Type');
                  break;
               case $backlogField:
                  $name = T_('Backlog');
                  break;
               case $mgrEffortEstimField:
                  $name = T_('MgrEffortEstim');
                  break;
               case $effortEstimField:
                  $name = T_('EffortEstim');
                  break;
               case $backlogField:
                  $name = T_('Backlog');
                  break;
               case $addEffortField:
                  $name = T_('AddEffortEstim');
                  break;
               case $deadLineField:
                  $name = T_('Deadline');
                  break;
               case $deliveryDateField:
                  $name = T_('Delivery Date');
                  break;
               default:
                  $name = $row->name;
            }
            self::$customFieldNames["$row->id"] = $name;
         }
      }
      return self::$customFieldNames["$customFieldId"];
   }


   /**
    * Converts tooltip field_id to DisplayName
    *
    * $field can be:
    * - mantis_bug_table columns (ex: project_id, status)
    * - customField id prefixed with 'custom_' (ex: custom_23)
    * - CodevTT calculated field prefixed with 'codevtt_' (ex: codevtt_drift)
    *
    * @param string $field
    */
   public static function getTooltipFieldDisplayName($field) {

      $displayName = 'unknown';

      // custom field (ex: custom_23)
      if (0 === strpos($field, 'custom_')) {
         // extract field id
         $cfield_id = intval(preg_replace('/^custom_/', '', $field));
         $displayName = Tools::getCustomFieldName($cfield_id);

      } else if (0 === strpos($field, 'codevtt_')) {

         // extract field id
         $displayName = T_(preg_replace('/^codevtt_/', '', $field));

      } else if (0 === strpos($field, 'mantis_')) {

         // extract field id
         $displayName = T_(preg_replace('/^mantis_/', '', $field));

         if ('tags' == $field) {
            $displayName = T_('Tags');
         }

      } else {
         // mantis field
         if ('project_id' == $field) {
            $displayName = T_('Project');
         } else if ('category_id' == $field) {
            $displayName = T_('Category');
         } else if ('status' == $field) {
            $displayName = T_('Status');
         } else if ('summary' == $field) {
            $displayName = T_('Summary');
         } else if ('handler_id' == $field) {
            $displayName = T_('Assigned');
         } else if ('target_version' == $field) {
            $displayName = T_('Target');
         } else if ('priority' == $field) {
            $displayName = T_('Priority');
         } else if ('severity' == $field) {
            $displayName = T_('Severity');
         } else if ('eta' == $field) {
            $displayName = T_('ETA');
         } else {
            // TODO other known mantis fields
            $displayName = $field;
         }
      }
      return $displayName;
   }


   /**
    *
    * @param type $threshold
    */
   public static function isMemoryLimitReached($threshold = 0.95) {

      $memory_limit = self::convertToBytes(ini_get('memory_limit'));
      $memUsage     = memory_get_usage(true);

      if ($memUsage >= $memory_limit * $threshold) {

         if (self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("memUsage ".Tools::bytesToSize1024($memUsage).' >= '.Tools::bytesToSize1024($memory_limit * $threshold));
         }
         return TRUE;
      }
      return FALSE;
   }


   /**
    * copy Directory with it's content
    *
    * @param type $src
    * @param type $dst
    * @return bool success or failure
    */
   public static function recurse_copy($src, $dst) {

      $dir = opendir($src);
      $result = ($dir === false ? false : true);

      if ($result !== false) {
         $result = @mkdir($dst);

         if ($result === true) {
            while (false !== ( $file = readdir($dir))) {
               if (( $file != '.' ) && ( $file != '..' ) && $result) {
                  if (is_dir($src . '/' . $file)) {
                     $result = self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
                  } else {
                     $result = copy($src . '/' . $file, $dst . '/' . $file);
                  }
               }
            }
            closedir($dir);
         }
      }

      return $result;
   }

   /**
    * delete directory and it's content
    * @param type $dir
    */
   public static function deleteDir($dirPath) {
      if (!is_dir($dirPath)) {
         throw new InvalidArgumentException("$dirPath must be a directory");
      }
      if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
         $dirPath .= '/';
      }
      $files = glob($dirPath . '*', GLOB_MARK);
      foreach ($files as $file) {
         if (is_dir($file)) {
            self::deleteDir($file);
         } else {
            unlink($file);
         }
      }
      rmdir($dirPath);
   }

	/**
	 * check if CodevTT is installed on a MS-Windows server
	 * @return boolean
	 */
	public static function isWindowsServer() {
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
	}

   /**
    * gets the data from a URL
    */
   public static function getUrlContent($url, $timeout = 5) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

      if (!empty(Constants::$proxy)) {
         curl_setopt($ch, CURLOPT_PROXY, Constants::$proxy);
         #curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
      }
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
   }

   /**
    * get Latest info from http://codevtt.org
    * @return array or FALSE
    */
   //
   public static function getLatestVersionInfo($timeout = 5) {

      $currentVersionInfo = FALSE;

      //$iniString = self::getUrlContent('http://codevtt.org/site/files/codevtt_current_version.ini', $timeout);
      $iniString = self::getUrlContent('http://codevtt.org/site/index.php?sdmon=files/codevtt_current_version.ini', $timeout);

      if ( $iniString !== FALSE) {
         $ini_array = parse_ini_string($iniString, true);
         $currentVersionInfo = $ini_array['current_version'];
      }
      return $currentVersionInfo;
   }

   
   public static function createClassMap() {
      //require_once('../lib/dynamic_autoloader/ClassFileMapFactory.php');
      //require_once('../lib/dynamic_autoloader/ClassFileMapAutoloader.php');
      // Set up the include path
      //define('BASE_PATH', realpath(dirname(__FILE__).'/..'));
      //set_include_path(BASE_PATH.PATH_SEPARATOR.get_include_path());

      // TODO check classmap.ser permissions 
      $classmap = Constants::$codevRootDir.'/classmap.ser';
      $classmapCopy = Constants::$codevRootDir.'/classmap.ser.old';
      
      
      if (!is_writable($classmap)) {
         throw new Exception("Please check write permissions to $classmap");
      }
      
      // save previous classmap.ser file        
      if(!rename($classmap,$classmapCopy)){
         $errorRename = "Failed to rename ".$classmap." into ".$classmapCopy;
         self::$logger->error($errorRename);
         //throw new Exception($errorRename);
      }

      // reload classmap, so that new classes are accessible
      $lib_class_map = ClassFileMapFactory::generate(Constants::$codevRootDir);
      $_autoloader = new ClassFileMapAutoloader();
      $_autoloader->addClassFileMap($lib_class_map);
      $_autoloader->registerAutoload();

      // write new classmap to file
      $data = serialize($_autoloader);
      if(!file_put_contents(Constants::$codevRootDir.'/classmap.ser',$data)) {

         if (!file_exists($classmapCopy)) {
            throw new Exception("Classmap creation failed + no backup file found for ".$classmap);
         }
         if(!rename($classmapCopy,$classmap)){
            throw new Exception("Classmap creation failed + could not revert to ".$classmapCopy);
         } else {
            throw new Exception("Classmap creation failed, previous classmap restored.");
         }
      }
   }

   /**
   * Return true if the parameter is an empty string or a string
   * containing only whitespace, false otherwise
   * @param string $p_var String to test whether it is blank.
   * @return boolean
   * @access public
   */
   public static function is_blank( $p_var ) {
      $p_var = trim( $p_var );
      $t_str_len = strlen( $p_var );
      if( 0 == $t_str_len ) {
         return true;
      }
      return false;
   }
}

// Initialize complex static variables
Tools::staticInit();


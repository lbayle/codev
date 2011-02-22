<?php

// MANTIS CoDev History Reports

// toolbox
// LoB 17 May 2010

// example: http://127.0.0.1/codev/
// example: http://55.7.137.27/louis/codev/
function getServerRootURL() {
	
	#if (isset($_GET['debug'])) {
   #foreach($_SERVER as $key => $value) {
   #   echo "_SERVER key=$key val=$value<br/>";
   #}
   
   $rootURL = "http://".$_SERVER['HTTP_HOST'].substr( $_SERVER['PHP_SELF'], 0 , strrpos( $_SERVER['PHP_SELF'], '/') );
   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   $rootURL = str_replace("/classes", "", $rootURL);   
   $rootURL = str_replace("/timetracking", "", $rootURL);   
   $rootURL = str_replace("/reports", "", $rootURL);   
   $rootURL = str_replace("/doc", "", $rootURL);   
   $rootURL = str_replace("/images", "", $rootURL);   
   $rootURL = str_replace("/calendar", "", $rootURL);   
   $rootURL = str_replace("/admin", "", $rootURL);   
   $rootURL = str_replace("/tools", "", $rootURL);   
   
   #if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   return $rootURL;
}

/**
 * returns current URL (complete, with ?params=<value>) 
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
function mantisIssueURL($bugid, $title=NULL) {
	global $mantisURL;
	if (NULL==$title) { $title = "View Mantis Issue $bugid"; }
	
	$formatedTitle = str_replace("'", " ", $title);
   $formatedTitle = str_replace("\"", " ", $formatedTitle);
	
	return "<a  title='$formatedTitle' href='$mantisURL/view.php?id=$bugid'>$bugid</a>";
}

/**
 * returns an HTML link to the TaskInfo page for Issue $bugid
 * ex: http://172.24.209.4/codev/reports/issue_info.php?bugid=60
 * @param int $bugid issue id in mantis DB
 */
function issueInfoURL($bugid, $title=NULL) {
   global $mantisURL;
   if (NULL==$title) { $title = "View info for Issue $bugid"; }
   
   $formatedTitle = str_replace("'", " ", $title);
   $formatedTitle = str_replace("\"", " ", $formatedTitle);
   
   return "<a  title='$formatedTitle' href='".getServerRootURL()."/reports/issue_info.php?bugid=$bugid'>$bugid</a>";
}

// ---------------------------
// Cette fonction transforme de ce format: 2008-09-04 11:13:18 en celui-ci : 1204456892
function datetime2timestamp($string) {
	list($date, $time) = explode(' ', $string);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);

	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);

	return $timestamp;
}

// -----------------------------------
// Cette fonction transforme de ce format: 2008-09-04 en celui-ci : 1204456892
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



// ---------------------------
// Function that returns the timestamp for each day in a week
function week_dates($week, $year)
{
   if(strftime("%W",mktime(0,0,0,01,01,$year))==1)
	$mon_mktime = mktime(0,0,0,01,(01+(($week-1)*7)),$year);
	else
	$mon_mktime = mktime(0,0,0,01,(01+(($week)*7)),$year);

	if(date("w",$mon_mktime)>1)
	$decalage = ((date("w",$mon_mktime)-1)*60*60*24);

	$monday = $mon_mktime - $decalage;

	# WARNING: there is a curious bug, the minutes are not set to '0' ?!?
	$monday   = mktime(0, 0, 0, date("m", $monday), date("d", $monday), date("Y", $monday)); 
   #echo "MONDAY = ".date("Y-m-d H:m:s",$monday)."<br>";
   
   
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

// ---------------------------
// used to convert an array() to a comma separated string used in SQL requests
function valuedListToSQLFormatedString($myArray) {
	  $formatedList = "";
     foreach ($myArray as $id => $value) {
         if ($formatedList != "") { $formatedList .= ', ';}
         $formatedList .= $id;
     }
   return $formatedList;
}



// ---------------------------
/**
 * 
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
function doubleExplode ($del1, $del2, $array){
   $array1 = explode("$del1", $array);
   foreach($array1 as $key=>$value){
      $array2 = explode("$del2", $value);
      foreach($array2 as $key2=>$value2){
         $array3[] = $value2; 
      }
   }
   $afinal = array();
   for ( $i = 0; $i <= count($array3); $i += 2) {
      if($array3[$i]!="") {
         $afinal[trim($array3[$i])] = trim($array3[$i+1]);
      }
   }
   return $afinal;
}


?>

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
   if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   $rootURL = str_replace("/timetracking", "", $rootURL);   
   $rootURL = str_replace("/reports", "", $rootURL);   
   $rootURL = str_replace("/doc", "", $rootURL);   
   $rootURL = str_replace("/images", "", $rootURL);   
   $rootURL = str_replace("/calendar", "", $rootURL);   
   
   if (isset($_GET['debug'])) {echo "DEBUG rootURL=$rootURL<br/>";}
   return $rootURL;
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

	$lundi = $mon_mktime - $decalage;

	# WARNING: there is a curious bug, the minutes are not set to '0' ?!?
	$lundi   = mktime(0, 0, 0, date("m", $lundi), date("d", $lundi), date("Y", $lundi)); 
   #echo "MONDAY = ".date("Y-m-d H:m:s",$lundi)."<br>";
   
   
   $week_dates = array();
   $week_dates[1] = $lundi; // Monday
   $week_dates[2] = strtotime("+1 day",$lundi); // Tuesday
   $week_dates[3] = strtotime("+2 day",$lundi); // Wednesday
   $week_dates[4] = strtotime("+3 day",$lundi); // Thursday
   $week_dates[5] = strtotime("+4 day",$lundi); // Friday
   $week_dates[6] = strtotime("+5 day",$lundi); // Saturday
   $week_dates[7] = strtotime("+6 day",$lundi); // Sunday

   
   return $week_dates;
}

// ---------------------------
function dayofyear2date( $tDay, $tFormat = 'Y-m-d' ) { 
	$day = intval( $tDay ); 
	$day = ( $day == 0 ) ? $day : $day - 1; 
	$offset = intval( intval( $tDay ) * 86400 ); 
	$str = date( $tFormat, strtotime( 'Jan 1, ' . date( 'Y' ) ) + $offset ); 
	return( $str ); 
}

// ---------------------------
function dayofyear2timestamp( $tDay) { 
   $day = intval( $tDay ); 
   $day = ( $day == 0 ) ? $day : $day - 1; 
   $offset = intval( intval( $tDay ) * 86400 ); 
   $timestamp = strtotime( 'Jan 1, ' . date( 'Y' ) ) + $offset; 

   $timestamp -= (60 * 60);
   #echo "DEBUG dayofyear2timestamp $tDay = ".date("Y-m-d H:i:s", $timestamp)."<br>";
   
   return( $timestamp ); 
}


?>

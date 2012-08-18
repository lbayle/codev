<?php

function sed($find, $replace, $input_file, $output_file = NULL){
   $contents = file_get_contents($input_file);

   echo "CONTENT</br>".$contents."</br></br>";

   $contents = preg_replace($find, $replace, $contents);

   echo "NEW CONTENT</br>".$contents."</br></br>";


   if($output_file == NULL) {
      $output_file = $input_file;
   }
   return (bool) file_put_contents($output_file, $contents);
}

function adel2csv($adelFilename, $csvFilename) {

   $contents = file_get_contents($adelFilename);

   echo "CONTENT</br>".$contents."</br></br>";

   // replace '|' by '|;|'
   $contents = preg_replace("/\|/", "|;|", $contents);

   //
   $contents = "|".$contents."|";

   // insert line separators. we know lines start with "01-" or "02-" or "03-"
   // so we replace "\n01-" by "|\n|01-" to end previous line and start new one.
   $contents = preg_replace("/\n01-/", "|\n|01-", $contents);
   $contents = preg_replace("/\n02-/", "|\n|02-", $contents);
   $contents = preg_replace("/\n03-/", "|\n|03-", $contents);
   $contents = preg_replace("/\n04-/", "|\n|04-", $contents);
   $contents = preg_replace("/\n05-/", "|\n|05-", $contents);

   #$contents = preg_replace("/^M$/", "", $contents);


   echo "NEW CONTENT</br>".$contents."</br></br>";



   return (bool) file_put_contents($csvFilename, $contents);
}


// ------------
$adelFilename = "/tmp/adel.txt";
$csvFilename = "/tmp/adel.csv";

adel2csv($adelFilename, $csvFilename);


/*
$string = 'April 15, 2003';
$pattern = '/(\w+) (\d+), (\d+)/i';
$replacement = '${1}1,$3';
echo preg_replace($pattern, $replacement, $string);
*/

$string = "AAA|B BB|CCC";
$pattern = '/\|/';
$replacement = '|;|';
echo $string."</br>";
echo preg_replace($pattern, $replacement, $string);

?>

<?php

function qsort($a,$f) {
       qsort_do(&$a,0,Count($a)-1,$f);
/*       
      echo "DEBUG after quickSort<br/>";
      foreach ($a as $i) {
         echo "$i->bugId<br/>";
      }
*/      
       return $a;
}

function qsort_do($a,$l,$r,$f) {
       if ($l < $r) {
               qsort_partition(&$a,$l,$r,&$lp,&$rp,$f);
               qsort_do(&$a,$l,$lp,$f);
               qsort_do(&$a,$rp,$r,$f);
       }
}

function qsort_partition($a,$l,$r,$lp,$rp,$f) {
       $i = $l+1;
       $j = $l+1;
       
       while ($j <= $r) {
               if ($f($a[$j],$a[$l])) {
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
    * returns true if $issueA has higher priority than $issueB
    * 
    * @param unknown_type $issueA
    * @param unknown_type $issueB
    */
   function isHigherPriority($issueA, $issueB) {
      
      // the one that has NO deadLine is lower priority
      if ((NULL != $issueA->deadLine) && (NULL == $issueB->deadLine)) { 
         #echo "DEBUG isHigherPriority $issueA->bugId higher than $issueB->bugId (B no deadline)<br/>\n";
      	return  true; 
      }
      if ((NULL == $issueA->deadLine) && (NULL != $issueB->deadLine)) { 
         #echo "DEBUG isHigherPriority $issueA->bugId lower than $issueB->bugId (A no deadline)<br/>\n";
      	return  false; 
      }

      // the soonest deadLine has priority
      if ($issueA->deadLine < $issueB->deadLine) { 
         #echo "DEBUG isHigherPriority $issueA->bugId higher than $issueB->bugId (deadline)<br/>\n";
      	return  true; 
      }
      if ($issueA->deadLine > $issueB->deadLine) { 
         #echo "DEBUG isHigherPriority $issueA->bugId lower than $issueB->bugId (deadline)<br/>\n";
      	return  false; 
      }
      
      // if same deadLine, check priority attribute
      if ($issueA->priority > $issueB->priority) {
      	#echo "DEBUG isHigherPriority $issueA->bugId higher than $issueB->bugId (priority attr)<br/>\n";
      	return  true; 
      }
            
      #echo "DEBUG isHigherPriority $issueA->bugId <= $issueB->bugId (priority attr)<br/>\n";
      return false;
   }    


   // --------------------------
   function bubblesort1( $a ) {
      for($x = 0; $x < count($a); $x++) {
         for($y = 0; $y < count($a); $y++) {
            #if($ran[$x] < $ran[$y]) {
            if (!isHigherPriority($a[$y], $a[$x])) {
            	$hold = $a[$x];
               $a[$x] = $a[$y];
               $a[$y] = $hold;
            }
         }
      }
      echo "DEBUG after bubblesort1<br/>";
      foreach ($a as $i) {
         echo "$i->bugId<br/>";
      }
      return $a;
   }
   
?>
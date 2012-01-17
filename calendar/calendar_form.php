<?php
require_once('classes/tc_calendar.php');

$thispage = $_SERVER['PHP_SELF'];

$sld = (isset($_REQUEST["selected_day"])) ? $_REQUEST["selected_day"] : 0;
$slm = (isset($_REQUEST["selected_month"])) ? (int)$_REQUEST["selected_month"] : 0;
$sly = (isset($_REQUEST["selected_year"])) ? (int)$_REQUEST["selected_year"] : 0;

$year_start = (isset($_REQUEST["year_start"])) ? $_REQUEST["year_start"] : 0;
$year_end = (isset($_REQUEST["year_end"])) ? $_REQUEST["year_end"] : 0;

$startMonday = (isset($_REQUEST["mon"])) ? $_REQUEST["mon"] : 0;

$date_allow1 = (isset($_REQUEST["da1"])) ? $_REQUEST["da1"] : "";
$date_allow2 = (isset($_REQUEST["da2"])) ? $_REQUEST["da2"] : "";

$show_not_allow = (isset($_REQUEST["sna"])) ? $_REQUEST["sna"] : true;

$auto_submit = (isset($_REQUEST["aut"])) ? $_REQUEST["aut"] : false;
$form_name = (isset($_REQUEST["frm"])) ? $_REQUEST["frm"] : "";
$target_url = (isset($_REQUEST["tar"])) ? $_REQUEST["tar"] : "";

$show_input = (isset($_REQUEST["inp"])) ? $_REQUEST["inp"] : true;
$date_format = (isset($_REQUEST["fmt"])) ? $_REQUEST["fmt"] : 'd-M-Y';

//echo("date: $sly-$slm-$sld");

if(isset($_REQUEST["m"]))
	$m = $_REQUEST["m"];
else
	$m = ($slm) ? $slm : date('m');

//echo("m: ".$m);

if($m < 1 && $m > 12) $m = date('m');

$cyr = ($sly) ? true : false;
if($sly && $sly < $year_start) $sly = $year_start;
if($sly && $sly > $year_end) $sly = $year_end;

if(isset($_REQUEST["y"]))
	$y = $_REQUEST["y"];
else
	$y = ($cyr) ? $sly : date('Y');

//echo("<br>y: ".$y);

if($y <= 0) $y = date('Y');

$objname = (isset($_REQUEST["objname"])) ? $_REQUEST["objname"] : "";
$dp = (isset($_REQUEST["dp"])) ? $_REQUEST["dp"] : "";

$cobj = new tc_calendar("");
$cobj->startMonday($startMonday);

if(!$year_start || !$year_end){
	$year_start = $cobj->year_start; //get default value of year start
	$year_end = $cobj->year_end; //get default value of year end
}
//$cobj->setDate($sld, $slm, $sly);

$total_thismonth = $cobj->total_days($m, $y);

if($m == 1){
	$previous_month = 12;
	$previous_year = $y-1;
}else{
	$previous_month = $m-1;
	$previous_year = $y;
}

if($m == 12){
	$next_month = 1;
	$next_year = $y+1;
}else{
	$next_month = $m+1;
	$next_year = $y;
}

$total_lastmonth = $cobj->total_days($previous_month, $previous_year);
$today = date('Y-m-d');

//$startdate = $cobj->getDayNum(date('D', strtotime($y."-".$m."-1")));
$startdate = date('w', strtotime($y."-".$m."-1"));

if($startMonday)
	if($startdate == 0)
		$startwrite = $total_lastmonth - 5;
	else
		$startwrite = $total_lastmonth - ($startdate - 2);
else
	$startwrite = $total_lastmonth - ($startdate - 1);
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>TriConsole.com - PHP Calendar Date Picker</title>
<link href="calendar.css" rel="stylesheet" type="text/css" />
<script language="javascript">
<!--
function setValue(){
	var f = document.calendarform;
	var date_selected = padString(f.selected_year.value, 4, "0") + "-" + padString(f.selected_month.value, 2, "0") + "-" + padString(f.selected_day.value, 2, "0");

	window.parent.setValue(f.objname.value, date_selected);
}

function selectDay(d){
	var f = document.calendarform;
	f.selected_day.value = d.toString();
	f.selected_month.value = f.m[f.m.selectedIndex].value;
	f.selected_year.value = f.y[f.y.selectedIndex].value;
	
	setValue();
	
	this.loading();
	f.submit();
	
	submitNow(f.selected_day.value, f.selected_month.value, f.selected_year.value);
}

function hL(E, mo){
	//clear last selected
	if(document.getElementById("select")){
		var selectobj = document.getElementById("select");
		selectobj.Id = "";
	}
	
	while (E.tagName!="TD"){
		E=E.parentElement;
	}
	
	E.Id = "select";
}

function selectMonth(m){
	var f = document.calendarform;
	f.selected_month.value = m;
}

function selectYear(y){
	var f = document.calendarform;
	f.selected_year.value = y;
}

function move(m, y){
	var f = document.calendarform;
	f.m.value = m;
	f.y.value = y;
	
	this.loading();
	f.submit();
}

function closeMe(){
	window.parent.toggleCalendar('<?php echo($objname); ?>');
}

function submitNow(dvalue, mvalue, yvalue){
	<?php
	//write auto submit script
	if($auto_submit){
		echo("if(yvalue>0 && mvalue>0 && dvalue>0){\n");			
		if($form_name){
			//submit value by post form
			echo("window.parent.document.".$form_name.".submit();\n");
		}elseif($target_url){
			//submit value by get method
			echo("var date_selected = yvalue + \"-\" + mvalue + \"-\" + dvalue;\n");
			echo("window.parent.location.href='".$target_url."?".$objname."='+date_selected;\n");
		}
		echo("}\n");
	}	
	?>
}

function padString(stringToPad, padLength, padString) {
	if (stringToPad.length < padLength) {
		while (stringToPad.length < padLength) {
			stringToPad = padString + stringToPad;
		}
	}else {}
/*
	if (stringToPad.length > padLength) {
		stringToPad = stringToPad.substring((stringToPad.length - padLength), padLength);
	} else {}
*/	
	return stringToPad;
}

function loading(){
	document.getElementById('calendar-container').innerHTML = "<div id=\"calendar-body\"><div class=\"refresh\"><div align=\"center\" class=\"txt-container\">Refreshing Calendar...</div></div></div>";
	adjustContainer();
}

function submitCalendar(){
	this.loading();
	document.calendarform.submit();	
}

function getObject(item){	
	if( window.mmIsOpera ) return(document.getElementById(item));
	if(document.all) return(document.all[item]);
	if(document.getElementById) return(document.getElementById(item));
	if(document.layers) return(document.layers[item]);
	return(false);
}

function adjustContainer(){
	var tc_obj = getObject('calendar-page');
	//var tc_obj = frm_obj.contentWindow.getObject('calendar-page');
	if(tc_obj != null){
		var div_obj = window.parent.document.getElementById('div_<?php echo($objname); ?>');

		if(tc_obj.offsetWidth > 0 && tc_obj.offsetHeight > 0){
			div_obj.style.width = tc_obj.offsetWidth+'px';
			div_obj.style.height = tc_obj.offsetHeight+'px';
			//alert(div_obj.style.width+','+div_obj.style.height);
		}
	}	
}

window.onload = function(){ adjustContainer(); setTimeout("adjustContainer()", 1000); };
//-->
</script>
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<div id="calendar-page">
    <div id="calendar-header" align="center">
        <?php if($dp){ ?>
        <div align="right" class="closeme"><a href="javascript:closeMe();"><img src="images/close.jpg" border="0" /></a></div>	
        <?php } ?>
        <form id="calendarform" name="calendarform" method="post" action="<?php echo($thispage);?>" style="margin: 0px;">
          <table width="99%" border="0" align="center" cellpadding="1" cellspacing="0">
            <tr>
              <td align="right"><select name="m" onchange="javascript:submitCalendar();">
              <?php
              $monthnames = $cobj->getMonthNames();		
              for($f=1; $f<=sizeof($monthnames); $f++){
                $selected = ($f == (int)$m) ? " selected" : "";			
                echo("<option value=\"".str_pad($f, 2, "0", STR_PAD_LEFT)."\"$selected>".$monthnames[$f-1]."</option>");
              }
              ?>
              </select></td>
              <td><select name="y" onchange="javascript:submitCalendar();">
              <?php
              $thisyear = date('Y');
              
              //check year to be select in case of date_allow is set
              if(!$show_not_allow && ($date_allow1 || $date_allow2)){
                if($date_allow1 && $date_allow2){
                    $da1Time = strtotime($date_allow1);
                    $da2Time = strtotime($date_allow2);
                    
                    if($da1Time < $da2Time){
                        $year_start = date('Y', $da1Time);
                        $year_end = date('Y', $da2Time);
                    }else{
                        $year_start = date('Y', $da2Time);
                        $year_end = date('Y', $da1Time);
                    }
                }elseif($date_allow1){
                    //only date 1 specified
                    $da1Time = strtotime($date_allow1);
                    $year_start = date('Y', $da1Time);
                }elseif($date_allow2){
                    //only date 2 specified
                    $da2Time = strtotime($date_allow2);
                    $year_end = date('Y', $da2Time);
                }
              }		  
              
              //write year options
              for($year=$year_start; $year<=$year_end; $year++){
                $selected = ($year == $y) ? " selected" : "";
                echo("<option value=\"$year\"$selected>$year</option>");
              }
              ?>
              </select></td>
            </tr>
          </table>
<input name="selected_day" type="hidden" id="selected_day" value="<?php echo($sld);?>" />
            <input name="selected_month" type="hidden" id="selected_month" value="<?php echo($slm);?>" />
            <input name="selected_year" type="hidden" id="selected_year" value="<?php echo($sly);?>" />
            <input name="year_start" type="hidden" id="year_start" value="<?php echo($year_start);?>" />
            <input name="year_end" type="hidden" id="year_end" value="<?php echo($year_end);?>" />
            <input name="objname" type="hidden" id="objname" value="<?php echo($objname);?>" />        
            <input name="dp" type="hidden" id="dp" value="<?php echo($dp);?>" />
            <input name="mon" type="hidden" id="mon" value="<?php echo($startMonday);?>" />
            <input name="da1" type="hidden" id="da1" value="<?php echo($date_allow1);?>" />
            <input name="da2" type="hidden" id="da2" value="<?php echo($date_allow2);?>" />
            <input name="sna" type="hidden" id="sna" value="<?php echo($show_not_allow);?>" />
            <input name="aut" type="hidden" id="aut" value="<?php echo($auto_submit);?>" />
            <input name="frm" type="hidden" id="frm" value="<?php echo($form_name);?>" />
            <input name="tar" type="hidden" id="tar" value="<?php echo($target_url);?>" />
            <input name="inp" type="hidden" id="inp" value="<?php echo($show_input);?>" />
            <input name="fmt" type="hidden" id="fmt" value="<?php echo($date_format);?>" />
      </form>    
  </div>
    <div id="calendar-container">
        <div id="calendar-body">	
        <table border="0" cellspacing="1" cellpadding="3" align="center" class="bg">
            <?php
            $day_headers = array_values($cobj->getDayHeaders());
            
            echo("<tr>");
            //write calendar day header
            foreach($day_headers as $dh){
                echo("<td align=\"center\" class=\"header\">".$dh."</td>");
            }
            echo("</tr>");
                
            echo("<tr>");
        
            $dayinweek_counter = 0;
            $row_count = 0;
            
            //write previous month
            for($day=$startwrite; $day<=$total_lastmonth; $day++){
                echo("<td align=\"center\" class=\"othermonth\">$day</td>");
                $dayinweek_counter++;
            }
        
            $pvMonthTime = strtotime($previous_year."-".$previous_month."-".$total_lastmonth);
            
            //check lastmonth is on allowed date
            if($date_allow1 && !$show_not_allow){
                if($pvMonthTime >= strtotime($date_allow1)){
                    $show_previous = true;
                }else $show_previous = false;
            }else $show_previous = true; //always show when not set
            
            //$date_num = $cobj->getDayNum(date('D', strtotime($previous_year."-".$previous_month."-".$total_lastmonth)));
            $date_num = date('w', $pvMonthTime);
            if((!$startMonday && $date_num == 6) || ($startMonday && $date_num == 0)){
                echo("</tr><tr>");
                $row_count++;
            }
        
            //write current month
            for($day=1; $day<=$total_thismonth; $day++){
                //$date_num = $cobj->getDayNum(date('D', strtotime($y."-".$m."-".$day)));
                $date_num = date('w', strtotime($y."-".$m."-".$day));
                
                $currentTime =  strtotime($y."-".$m."-".$day);
                $htmlClass = array();		
                
                $is_today = $currentTime - strtotime($today);
                $htmlClass[] = ($is_today == 0) ? "today" : "general";
                        
                $is_selected = strtotime($y."-".$m."-".$day) - strtotime($sly."-".$slm."-".$sld);
                if($is_selected == 0) $htmlClass[] = "select";
                
                //check date allowed
                if($date_allow1 || $date_allow2){
                    //at least one date allowed specified
                    $dateLink = true;
                    if($date_allow1 && $date_allow2){
                        //both date specified
                        $da1Time = strtotime($date_allow1);
                        $da2Time = strtotime($date_allow2);
                        
                        if($da1Time < $da2Time){
                            $dateLink = ($da1Time <= $currentTime && $da2Time >= $currentTime) ? true : false;
                        }else{
                            $dateLink = ($da1Time >= $currentTime && $da2Time <= $currentTime) ? true : false;
                        }
                    }elseif($date_allow1){
                        //only date 1 specified
                        $da1Time = strtotime($date_allow1);
                        $dateLink = ($currentTime >= $da1Time) ? true : false;
                    }elseif($date_allow2){
                        //only date 2 specified
                        $da2Time = strtotime($date_allow2);
                        $dateLink = ($currentTime <= $da2Time) ? true : false;
                    }
                }else{
                    //no date allow specified, assume show all
                    $dateLink = true;
                }
                
                if($dateLink){
                    //write date with link
                    $class = implode(" ", $htmlClass);
                    if($class) $class = " class=\"$class\"";
                    
                    echo("<td align=\"center\"$class><a href=\"javascript:selectDay('".str_pad($day, 2, "0", STR_PAD_LEFT)."');\">$day</a></td>");
                }else{
                    $htmlClass[] = "disabledate";
                    
                    $class = implode(" ", $htmlClass);
                    if($class) $class = " class=\"$class\"";
                
                    //write date without link
                    echo("<td align=\"center\"$class>$day</td>");
                }
                if((!$startMonday && $date_num == 6) || ($startMonday && $date_num == 0)){
                    echo("</tr>");
                    if($day < $total_thismonth) echo("<tr>");
                    $row_count++;
                    
                    $dayinweek_counter = 0;
                }else $dayinweek_counter++;
            }	
            
            //write next other month
            $write_end_days = (6-$dayinweek_counter)+1;
            if($write_end_days > 0){
                for($day=1; $day<=$write_end_days; $day++){
                    echo("<td class=\"othermonth\" align=\"center\">$day</td>");
                }
                 echo("</tr>");
                 $row_count++;
            }
            
            //write fulfil row to 6 rows
            for($day=$row_count; $day<=5; $day++){
                echo("<tr>");
                $tmpday = $write_end_days+1;
                for($f=$tmpday; $f<=($tmpday+6); $f++){
                    echo("<td class=\"othermonth\" align=\"center\">$f</td>");
                }
                $write_end_days += 6;
                echo("</tr>");
            }
            
            //check next month is on allowed date
            if($date_allow2 && !$show_not_allow){
                $nxMonthTime = strtotime($next_year."-".$next_month."-1");
                if($nxMonthTime <= strtotime($date_allow2)){
                    $show_next = true;
                }else $show_next = false;
            }else $show_next = true; //always show when not set
            ?>
        </table>
        </div>
        <?php
        if(($previous_year >= $year_start || $next_year <= $year_end) && ($show_previous || $show_next)){
        ?>
        <div id="calendar-footer">
          <div class="btn">
            <div style="float: left; width: 50%;">
            <?php
            if($previous_year >= $year_start && $show_previous){
            ?><a href="javascript:move('<?php echo(str_pad($previous_month, 2, "0", STR_PAD_LEFT));?>', '<?php echo($previous_year);?>');">&lt; Previous</a><?php
            }else echo("&nbsp;");
            ?>
            </div>
            <div style="float: right; width: 50%; text-align: right;">
            <?php
            if($next_year <= $year_end && $show_next){
            ?><a href="javascript:move('<?php echo(str_pad($next_month, 2, "0", STR_PAD_LEFT));?>', '<?php echo($next_year);?>');">Next &gt;</a><?php
            }else echo("&nbsp;");
            ?>
            </div>
            <div style="clear: both;"></div>
          </div>
        </div>	
        <?php
          }
          ?>
    </div>
</div>
<div style="clear: both;"></div>
</body>
</html>

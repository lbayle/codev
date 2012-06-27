<?php
require_once('classes/tc_calendar.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>

<title>TriConsole - Programming, Web Hosting, and Entertainment Directory</title>


<link href="calendar.css" rel="stylesheet" type="text/css" />
<script language="javascript" src="calendar.js"></script>

<style type="text/css">
body { font-size: 11px; font-family: "verdana"; }

pre { font-family: "verdana"; font-size: 10px; background-color: #FFFFCC; padding: 5px 5px 5px 5px; }
pre .comment { color: #008000; }
pre .builtin { color:#FF0000;  }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="100%" border="0" cellspacing="0" cellpadding="5">
  <tr>
    <td><h3><a href="http://www.triconsole.com/php/calendar_datepicker.php" target="_blank">PHP - Calendar, DatePicker Calendar </a></h3>
      <table width="100%" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td><h5>Demo:</h5>
            <form name="form1" method="post" action="">
              <p class="largetxt"><b>Fixed Display Style </b></p>
              <table border="0" cellspacing="0" cellpadding="2">
                <tr>
                  <td valign="top" nowrap>Date 1 :</td>
                  <td valign="top"><?php
	  $myCalendar = new tc_calendar("date2");
	  $myCalendar->setIcon("images/iconCalendar.gif");
	  $myCalendar->setDate(date('d'), date('m'), date('Y'));
	  $myCalendar->setPath("./");
	  $myCalendar->setYearInterval(1970, 2020);
	  $myCalendar->dateAllow('2008-05-13', '2015-03-01', false);
	  $myCalendar->startMonday(true);
	  //$myCalendar->autoSubmit(true, "", "test.php");
	  //$myCalendar->autoSubmit(true, "form1");
	  $myCalendar->writeScript();
	  ?></td>
                  <td valign="top"><ul>
                    <li> Set default date to today date (server time) </li>
                    <li> Set year navigate from 1970 to 2020</li>
                    <li> Allow date selectable from 13 May 2008 to 01 March 2015</li>
                    <li> Not allow to navigate other dates from above </li>
                  </ul></td>
                </tr>
              </table>
              <p><b>Code:</b></p>
              <pre>&lt;?php<br>	  $myCalendar = new tc_calendar(&quot;date2&quot;);<br>	  $myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>	  $myCalendar-&gt;setDate(date('d'), date('m'), date('Y'));<br>	  $myCalendar-&gt;setPath(&quot;./&quot;);<br>	  $myCalendar-&gt;setYearInterval(1970, 2020);<br>	  $myCalendar-&gt;dateAllow('2008-05-13', '2015-03-01', false);<br>	  $myCalendar-&gt;startMonday(true);<br>	  $myCalendar-&gt;writeScript();<br>	  ?&gt;</pre>
              <p class="largetxt"><b>DatePicker Style </b></p>
              <table border="0" cellspacing="0" cellpadding="2">
                <tr>
                  <td nowrap>Date 2 :</td>
                  <td><?php
	  $myCalendar = new tc_calendar("date1", true);
	  $myCalendar->setIcon("images/iconCalendar.gif");
	  $myCalendar->setDate(01, 03, 1960);
	  $myCalendar->setPath("./");
	  $myCalendar->setYearInterval(1960, 2015);
	  $myCalendar->dateAllow('1960-01-01', '2015-03-01');
	  //$myCalendar->setHeight(350);	  
	  //$myCalendar->autoSubmit(true, "form1");
	  $myCalendar->writeScript();
	  ?></td>
                  <td><input type="button" name="button" id="button" value="Check the value" onClick="javascript:alert(this.form.date1.value);"></td>
                </tr>
              </table>
              <ul>
                <li>Default date set to 01 March 1960 </li>
                <li>Set year navigate from 1960 to 2015 </li>
                <li>Allow date selectable from 01 January 1960 to 01 March 2015</li>
                <li>Allow to navigate other dates from above </li>
              </ul>
              <p><b>Code:</b></p>
              <pre>&lt;?php<br>	  $myCalendar = new tc_calendar(&quot;date1&quot;, true);<br>	  $myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>	  $myCalendar-&gt;setDate(01, 03, 1960);<br>	  $myCalendar-&gt;setPath(&quot;./&quot;);<br>	  $myCalendar-&gt;setYearInterval(1960, 2015);<br>	  $myCalendar-&gt;dateAllow('1960-01-01', '2015-03-01');<br>	  $myCalendar-&gt;writeScript();<br>	  ?&gt;</pre>
              <p class="largetxt"><b>DatePicker with no input box</b></p>
              <table border="0" cellspacing="0" cellpadding="2">
                <tr>
                  <td nowrap>Date 3 :</td>
                  <td><?php
	  $myCalendar = new tc_calendar("date5", true, false);
	  $myCalendar->setIcon("images/iconCalendar.gif");
	  $myCalendar->setDate(date('d'), date('m'), date('Y'));
	  $myCalendar->setPath("./");
	  $myCalendar->setYearInterval(2000, 2015);
	  $myCalendar->dateAllow('2008-05-13', '2015-03-01');
	  $myCalendar->setDateFormat('j F Y');
	  //$myCalendar->setHeight(350);	  
	  //$myCalendar->autoSubmit(true, "form1");
	  $myCalendar->writeScript();
	  ?></td>
                  <td><input type="button" name="button" id="button" value="Check the value" onClick="javascript:alert(this.form.date5.value);"></td>
                </tr>
              </table>
              <ul>
                <li>Default date to current server date</li>
                <li>Set year navigate from 2000 to 2015 </li>
                <li>Allow date selectable from 13 May 2008 to 01 March 2015</li>
                <li>Allow to navigate other dates from above </li>
                <li>Date input box set to false </li>
              </ul>
              <p><b>Code:</b></p>
              <pre>&lt;?php<br>	  $myCalendar = new tc_calendar(&quot;date5&quot;, true, false);<br>	  $myCalendar-&gt;setIcon(&quot;calendar/images/iconCalendar.gif&quot;);<br>	  $myCalendar-&gt;setDate(date('d'), date('m'), date('Y'));<br>	  $myCalendar-&gt;setPath(&quot;calendar/&quot;);<br>	  $myCalendar-&gt;setYearInterval(2000, 2015);<br>	  $myCalendar-&gt;dateAllow('2008-05-13', '2015-03-01');<br>	  $myCalendar-&gt;setDateFormat('j F Y');<br>	  $myCalendar-&gt;writeScript();<br>	  ?&gt;</pre>
              <p class="largetxt"><b>Date Pair Example</b></p>
              <div style="float: left;">
                <div style="float: left; padding-right: 3px; line-height: 18px;">from:</div>
                <div style="float: left;">
                  <?php		
	  $thisweek = date('W');
		$thisyear = date('Y');
							
		$dayTimes = getDaysInWeek($thisweek, $thisyear);
		//----------------------------------------
		
		$date1 = date('Y-m-d', $dayTimes[0]);
		$date2 = date('Y-m-d', $dayTimes[(sizeof($dayTimes)-1)]);
						
		function getDaysInWeek ($weekNumber, $year, $dayStart = 1) {		  
		  // Count from '0104' because January 4th is always in week 1
		  // (according to ISO 8601).
		  $time = strtotime($year . '0104 +' . ($weekNumber - 1).' weeks');
		  // Get the time of the first day of the week
		  $dayTime = strtotime('-' . (date('w', $time) - $dayStart) . ' days', $time);
		  // Get the times of days 0 -> 6
		  $dayTimes = array ();
		  for ($i = 0; $i < 7; ++$i) {
			$dayTimes[] = strtotime('+' . $i . ' days', $dayTime);
		  }
		  // Return timestamps for mon-sun.
		  return $dayTimes;
		}
				  
				  
	  $myCalendar = new tc_calendar("date3", true, false);
	  $myCalendar->setIcon("images/iconCalendar.gif");
	  $myCalendar->setDate(date('d', strtotime($date1)), date('m', strtotime($date1)), date('Y', strtotime($date1)));
	  $myCalendar->setPath("./");
	  $myCalendar->setYearInterval(1970, 2020);
	  //$myCalendar->dateAllow('2009-02-20', "", false);
	  $myCalendar->writeScript();	  
	  ?>
                </div>
              </div>
              <div style="float: left;">
                <div style="float: left; padding-left: 3px; padding-right: 3px; line-height: 18px;">to</div>
                <div style="float: left;">
                  <?php
	  $myCalendar = new tc_calendar("date4", true, false);
	  $myCalendar->setIcon("images/iconCalendar.gif");
	  $myCalendar->setDate(date('d', strtotime($date2)), date('m', strtotime($date2)), date('Y', strtotime($date2)));
	  $myCalendar->setPath("./");
	  $myCalendar->setYearInterval(1970, 2020);
	  //$myCalendar->dateAllow("", '2009-11-03', false);
	  $myCalendar->writeScript();	  
	  ?>
                </div>
              </div>
              <p>
                <input type="button" name="button2" id="button2" value="Check the value" onClick="javascript:alert('Date select from '+this.form.date3.value+' to '+this.form.date4.value);">
              </p>
              <p><b>Code:</b></p>
              <pre>&lt;?php					<br>	  $myCalendar = new tc_calendar(&quot;date3&quot;, true, false);<br>	  $myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>	  $myCalendar-&gt;setDate(date('d', strtotime($date1))
            , date('m', strtotime($date1))
            , date('Y', strtotime($date1)));<br>	  $myCalendar-&gt;setPath(&quot;./&quot;);<br>	  $myCalendar-&gt;setYearInterval(1970, 2020);<br>	  $myCalendar-&gt;writeScript();	  <br>	  <br>	  $myCalendar = new tc_calendar(&quot;date4&quot;, true, false);<br>	  $myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>	  $myCalendar-&gt;setDate(date('d', strtotime($date2))
           , date('m', strtotime($date2))
           , date('Y', strtotime($date2)));<br>	  $myCalendar-&gt;setPath(&quot;./&quot;);<br>	  $myCalendar-&gt;setYearInterval(1970, 2020);<br>	  $myCalendar-&gt;writeScript();	  <br>	  ?&gt;</pre>
            </form>
<hr>
              <h5>How to setup: </h5>
            <p>Only 2 steps requires for setup and use this calendar component. </p>
            <p>Put the javascript file(.js) in the head section or somewhere else but it <b>should be include once in a page</b>. </p>
            <pre>&lt;head&gt;
&lt;script language=&quot;javascript&quot; src=&quot;calendar.js&quot;&gt;&lt;/script&gt;
&lt;/head&gt;</pre>
              <p>Create form element in the html and put the following code </p>
            <pre>&lt;form action=&quot;somewhere.php&quot; method=&quot;post&quot;&gt;
&lt;?php
<span class="comment">//get class into the page</span><br>require_once('classes/tc_calendar.php');

<span class="comment">//instantiate class and set properties</span>
$myCalendar = new tc_calendar(&quot;date1&quot;, true);<br>$myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>$myCalendar-&gt;setDate(1, 1, 2000);<br>
<span class="comment">//output the calendar</span><br>$myCalendar-&gt;writeScript();	  <br>?&gt;
&lt;/form&gt;
            </pre>
              <hr>
              <h5>How to get the value?</h5>
            <p>To get the date selected in calendar <b>by php after submit the form</b>, simple write script as the following:</p>
            <pre>&lt;?php
$theDate = isset($_REQUEST[&quot;date1&quot;]) ? $_REQUEST[&quot;date1&quot;] : &quot;&quot;;

?&gt; </pre>
              <p>The above script should be on another file that html form point to. The parameter 'date1' is the object name that you set in the code at the time calendar construct. See further in Functions and Constructor below. </p>
            <p>To get the date  selected <b>by javascript on the current page</b>, write script as the following:</p>
            <pre>&lt;form action=&quot;somewhere.php&quot; method=&quot;post&quot; name=&quot;form1&quot;&gt;
&lt;?php
<span class="comment">//get class into the page</span><br>require_once('classes/tc_calendar.php');

<span class="comment">//instantiate class and set properties</span>
$myCalendar = new tc_calendar(&quot;date1&quot;, true);<br>$myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;);<br>$myCalendar-&gt;setDate(1, 1, 2000);<br>
<span class="comment">//output the calendar</span><br>$myCalendar-&gt;writeScript();	  <br>?&gt;
&lt;/form&gt;


<span class="comment">//use javascript to get the value</span>
&lt;script language=&quot;javascript&quot;&gt;
&lt;!--
function showDateSelected(){
   alert(&quot;Date selected is &quot;+<b>document.form1.date1.value</b>);
}
//--&gt;
&lt;/script&gt;
<span class="comment">//create link to click and check calendar value</span>
&lt;a href=&quot;javascript:showDateSelected();&quot;&gt;Check calendar value&lt;/a&gt;</pre>
              <hr>
              <h5 class="largetxt">Functions</h5>
              <p><b>Constructor</b></p>
              <p><i>tc_calendar (string bindObjectName) </i></p>
              <p><i>tc_calendar (string bindObjectName, boolean date_picker) </i></p>
              <p><i>tc_calendar (string bindObjectName, boolean date_picker, bool show_input)</i></p>
              <blockquote>
                <p> date_picker default value is false.<br>
                  show_input default value is true </p>
              </blockquote>
              <p><b>Methods</b></p>
              <p><i>autoSubmit (bool flag, string form_name, string target_url)</i></p>
              <blockquote>
                <p> Specify the calendar to auto-submit the value. Default value of autosubmit is <b>false</b></p>
                <p>To set calendar auto submit, specify flag to true and you need to specify either <i>form_name</i> or <i>target_url</i> to make the calendar to perform autosubmit correctly</p>
                <p>Ex 1. $myCalendar-&gt;autoSubmit(true, &quot;myForm&quot;); <br>
                  //assume that the calendar is in the form named 'myForm', then tell the calendar to auto submit the value (other values in myForm will be submitted together by html post method) </p>
                <p> Ex 2. $myCalendar-&gt;autoSubmit(true, &quot;&quot;, &quot;anywhere.php&quot;); <br>
                  //tell the calendar to submit the value to 'anywhere.php'. This method will submit only calendar value via html get method </p>
              </blockquote>
              <p><i>dateAllow (date from, date to, bool navigate_not_allowed_date)</i></p>
              <blockquote>
                <p> Specify date range allow to select. Other dates from input will be disabled. The parameter <i>navigate_not_allowed_date</i> will handle the user to navigate over the disable date, default is true (means allow)</p>
                <p>Specify both date <i>from</i> and <i>to</i> will set range of date user can select. <br>
                  Specify only date <i>from</i> or <i>to</i> will set range from/upto year set by setYearInterval method.</p>
                <p>Ex 1. $myCalendar-&gt;dateAllow('2008-05-13', '2010-03-01', false); //allow user select date from 13 May 2008 to 1 Mar 2010 <br>
                  Ex 2. $myCalendar-&gt;dateAllow('2008-05-13', '', false); //allow user select date from 13 May 2008 upto whatever can navigate<br>
                  Ex 3. $myCalendar-&gt;dateAllow('', '2010-03-01', false); //allow user select date from whatever can navigate upto 1 Mar 2010</p>
              </blockquote>
              <p><i>getDate ()</i></p>
              <blockquote>
                <p>Get the calendar value in date format YYYY-MM-DD</p>
                <p>Ex. $myCalendar-&gt;getDate(); //return 2009-06-19 </p>
              </blockquote>
              <p><i>setDate (int day, int month, int year)</i></p>
              <blockquote>
                <p>Optional: Set default selected date to the value input. For month parameter: January=1 and December=12 </p>
                <p> Ex. $myCalendar-&gt;setDate(15, 6, 2005); //Set the date to 15 June 2005 </p>
              </blockquote>
              <p><i>setDateFormat (string format)</i></p>
              <blockquote>
                <p>Optional: Set the format of date display when no input box. Apply with 'showInput' function </p>
                <p> Ex. $myCalendar-&gt;setDateFormat('j F Y'); //date will display like '9 September 2009' </p>
              </blockquote>
              <p><i>setIcon (string url)</i></p>
              <blockquote>
                <p>Optional: Set icon in date picker mode. If the icon is not set the date picker will show text as link. </p>
                <p> Ex. $myCalendar-&gt;setIcon(&quot;images/iconCalendar.gif&quot;); </p>
              </blockquote>
              <p><i>setHeight (int height) </i><b>- deprecated since v2.9</b> - auto sizing applied</p>
              <blockquote>
                <p>Optional: Set height of calendar. Default value is 205 pixels</p>
                <p>Ex. $myCalendar-&gt;setHeight(205); </p>
              </blockquote>
              <p><i>setPath (string path)</i></p>
              <blockquote>
                <p>Optional: Set the path to the 'calendar_form.php' if it is not in the same directory as your script. The path string is a relative path to the script file. </p>
                <p>Ex. $myCalendar-&gt;setPath(&quot;folder1/&quot;);</p>
              </blockquote>
              <p><i>setText (string text) </i></p>
              <blockquote>
                <p>Optional: Set text to display. The text will show in date picker mode when the icon is not set. </p>
                <p>Ex. $myCalendar-&gt;setText(&quot;Click Me&quot;); </p>
              </blockquote>
              <p><i>setWidth (int width) </i><b>- deprecated since v2.9</b> - auto sizing applied</p>
              <blockquote>
                <p>Optional: Set width of calendar. Default value is 150 pixels</p>
                <p>Ex. $myCalendar-&gt;setWidth(150); </p>
              </blockquote>
              <p><i>setYearInterval (int year_start, int year_end) </i></p>
              <blockquote>
                <p>Optional: Set the year start and year end display on calendar combo box. Default value is +15 and -15 from current year (30 years)</p>
                <p>Ex. $myCalendar-&gt;setYearInterval(1970, 2020); </p>
              </blockquote>
              <p><i>showInput (bool flag)</i></p>
              <blockquote>
                <p>Optional: Set the input box display on/off. If showInput set to false, the date will display in panel as example above '<b>DatePicker with no input box</b>'. Default value is true </p>
                <p>Ex. $myCalendar-&gt;showInput(false); </p>
              </blockquote>
              <p><i>startMonday (bool flag) </i></p>
              <blockquote>
                <p>Optional: Set whether the calendar will be start on Sunday or Monday. Set flag to <b>true</b> means the calendar will display  first date as Monday, otherwise <b>false</b> will display first date as Sunday. Default value is false.</p>
                <p>Ex. $myCalendar-&gt;startMonday(true); </p>
              </blockquote>
              <p><i>writeScript()</i></p>
            <blockquote>
              <p>Write the output calendar to the screen </p>
            </blockquote></td>
        </tr>
      </table>
      <p align="center">&copy; Triconsole (<a href="http://www.triconsole.com/" target="_blank">triconsole.com</a>)</p></td>
  </tr>
</table>
</body>
</html>

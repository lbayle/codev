function toggleCalendar(objname){
	var div_obj = document.getElementById('div_'+objname);
	if (div_obj.style.visibility=="hidden") {
	  div_obj.style.visibility = 'visible';
	  document.getElementById(objname+'_frame').contentWindow.adjustContainer();
	}else{
	  div_obj.style.visibility = 'hidden';
	}
}

function setValue(objname, d){
	document.getElementById(objname).value = d;

	var dp = document.getElementById(objname+"_dp").value;
	if(dp == true){
		var date_array = d.split("-");
		
		var inp = document.getElementById(objname+"_inp").value;
		if(inp == true){
			
			document.getElementById(objname+"_day").value = padString(date_array[2].toString(), 2, "0");
			document.getElementById(objname+"_month").value = padString(date_array[1].toString(), 2, "0");
			document.getElementById(objname+"_year").value = padString(date_array[0].toString(), 4, "0");
			
			//check for valid day
			tc_updateDay(objname, date_array[0], date_array[1], date_array[2]);

		}else{
			if(date_array[0] > 0 && date_array[1] > 0 && date_array[2] > 0){			
				//update date pane
				
				var myDate = new Date();
				myDate.setFullYear(date_array[0],(date_array[1]-1),date_array[2]);
				var dateFormat = document.getElementById(objname+"_fmt").value
				
				var dateTxt = myDate.format(dateFormat);
			}else var dateTxt = "Select Date";
			
			document.getElementById("divCalendar_"+objname+"_lbl").innerHTML = dateTxt;
		}
		
		toggleCalendar(objname);
	}
}

function tc_setDay(objname, dvalue, path){
	var obj = document.getElementById(objname);
	var date_array = obj.value.split("-");
	
	//check if date is not allow to select
	if(!isDateAllow(objname, dvalue, date_array[1], date_array[0])){
		alert("This date is not allow to select");
		
		restoreDate(objname);
	}else{		
		if(isDate(dvalue, date_array[1], date_array[0])){			
			obj.value = date_array[0] + "-" + date_array[1] + "-" + dvalue;
			
			var obj = document.getElementById(objname+'_frame');
			
			var year_start = document.getElementById(objname+'_year_start').value;
			var year_end = document.getElementById(objname+'_year_end').value;
			var dp = document.getElementById(objname+'_dp').value;
			var smon = document.getElementById(objname+'_mon').value;
			var da1 = document.getElementById(objname+'_da1').value;
			var da2 = document.getElementById(objname+'_da2').value;
			var sna = document.getElementById(objname+'_sna').value;
			var aut = document.getElementById(objname+'_aut').value;
			var frm = document.getElementById(objname+'_frm').value;
			var tar = document.getElementById(objname+'_tar').value;
			var inp = document.getElementById(objname+'_inp').value;
			var fmt = document.getElementById(objname+'_fmt').value;
			
			obj.src = path+"calendar_form.php?objname="+objname.toString()+"&selected_day="+dvalue+"&selected_month="+date_array[1]+"&selected_year="+date_array[0]+"&year_start="+year_start+"&year_end="+year_end+"&dp="+dp+"&mon="+smon+"&da1="+da1+"&da2="+da2+"&sna="+sna+"&aut="+aut+"&frm="+frm+"&tar="+tar+"&inp="+inp+"&fmt="+fmt;
			
			obj.contentWindow.submitNow(dvalue, date_array[1], date_array[0]);
			
		}else document.getElementById(objname+"_day").selectedIndex = date_array[2];
	}
}

function tc_setMonth(objname, mvalue, path){
	var obj = document.getElementById(objname);
	var date_array = obj.value.split("-");
	
	//check if date is not allow to select
	if(!isDateAllow(objname, date_array[2], mvalue, date_array[0])){
		alert("This date is not allow to select");
		
		restoreDate(objname);
	}else{
		if(document.getElementById(objname+'_dp').value && document.getElementById(objname+'_inp').value){
			//update 'day' combo box
			date_array[2] = tc_updateDay(objname, date_array[0], mvalue, date_array[2]);
		}
		
		if(isDate(date_array[2], mvalue, date_array[0])){
			obj.value = date_array[0] + "-" + mvalue + "-" + date_array[2];
		
			var obj = document.getElementById(objname+'_frame');
			
			var year_start = document.getElementById(objname+'_year_start').value;
			var year_end = document.getElementById(objname+'_year_end').value;
			var dp = document.getElementById(objname+'_dp').value;
			var smon = document.getElementById(objname+'_mon').value;
			var da1 = document.getElementById(objname+'_da1').value;
			var da2 = document.getElementById(objname+'_da2').value;
			var sna = document.getElementById(objname+'_sna').value;
			var aut = document.getElementById(objname+'_aut').value;
			var frm = document.getElementById(objname+'_frm').value;
			var tar = document.getElementById(objname+'_tar').value;
			var inp = document.getElementById(objname+'_inp').value;
			var fmt = document.getElementById(objname+'_fmt').value;
			
			obj.src = path+"calendar_form.php?objname="+objname.toString()+"&selected_day="+date_array[2]+"&selected_month="+mvalue+"&selected_year="+date_array[0]+"&year_start="+year_start+"&year_end="+year_end+"&dp="+dp+"&mon="+smon+"&da1="+da1+"&da2="+da2+"&sna="+sna+"&aut="+aut+"&frm="+frm+"&tar="+tar+"&inp="+inp+"&fmt="+fmt;
			
			obj.contentWindow.submitNow(date_array[2], mvalue, date_array[0]);
			
		}else document.getElementById(objname+"_month").selectedIndex = date_array[1];
	}
}

function tc_setYear(objname, yvalue, path){
	var obj = document.getElementById(objname);
	var date_array = obj.value.split("-");
	
	//check if date is not allow to select
	if(!isDateAllow(objname, date_array[2], date_array[1], yvalue)){
		alert("This date is not allow to select");
		
		restoreDate(objname);
	}else{
		if(document.getElementById(objname+'_dp').value && document.getElementById(objname+'_inp').value){
			//update 'day' combo box
			date_array[2] = tc_updateDay(objname, yvalue, date_array[1], date_array[2]);
		}
		
		if(isDate(date_array[2], date_array[1], yvalue)){
			obj.value = yvalue + "-" + date_array[1] + "-" + date_array[2];
		
			var obj = document.getElementById(objname+'_frame');
			
			var year_start = document.getElementById(objname+'_year_start').value;
			var year_end = document.getElementById(objname+'_year_end').value;
			var dp = document.getElementById(objname+'_dp').value;
			var smon = document.getElementById(objname+'_mon').value;
			var da1 = document.getElementById(objname+'_da1').value;
			var da2 = document.getElementById(objname+'_da2').value;
			var sna = document.getElementById(objname+'_sna').value;
			var aut = document.getElementById(objname+'_aut').value;
			var frm = document.getElementById(objname+'_frm').value;
			var tar = document.getElementById(objname+'_tar').value;
			var inp = document.getElementById(objname+'_inp').value;
			var fmt = document.getElementById(objname+'_fmt').value;
			
			obj.src = path+"calendar_form.php?objname="+objname.toString()+"&selected_day="+date_array[2]+"&selected_month="+date_array[1]+"&selected_year="+yvalue+"&year_start="+year_start+"&year_end="+year_end+"&dp="+dp+"&mon="+smon+"&da1="+da1+"&da2="+da2+"&sna="+sna+"&aut="+aut+"&frm="+frm+"&tar="+tar+"&inp="+inp+"&fmt="+fmt;
			
			obj.contentWindow.submitNow(date_array[2], date_array[1], yvalue);
			
		}else document.getElementById(objname+"_year").value = date_array[0];
	}
}

function yearEnter(e){
	var characterCode;
	
	if(e && e.which){ //if which property of event object is supported (NN4)
		e = e;
		characterCode = e.which; //character code is contained in NN4's which property
	}else{
		e = event;
		characterCode = e.keyCode; //character code is contained in IE's keyCode property
	}
	
	if(characterCode == 13){ 
		//if Enter is pressed, do nothing		
		return true;
	}else return false;
}


// Declaring valid date character, minimum year and maximum year
var minYear=1900;
var maxYear=2100;

function isInteger(s){
	var i;
    for (i = 0; i < s.length; i++){   
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag){
	var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){   
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function is_leapYear(year){
	return (year % 4 == 0) ?
		!(year % 100 == 0 && year % 400 != 0)	: false;
}

function daysInMonth(month, year){
	var days = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	return (month == 2 && is_leapYear(year)) ? 29 : days[month-1];
}
	
function DaysArray(n) {
	for (var i = 1; i <= n; i++) {
		this[i] = 31
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
		if (i==2) {this[i] = 29}
   } 
   return this
}

function isDate(strDay, strMonth, strYear){
/*
	//bypass check date	
	strYr=strYear
	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1)
	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1)
	for (var i = 1; i <= 3; i++) {
		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1)
	}
	month=parseInt(strMonth)
	day=parseInt(strDay)
	year=parseInt(strYr)
	if (strMonth.length<1 || month<1 || month>12){
		alert("Please enter a valid month")
		return false
	}
	if (strDay.length<1 || day<1 || day>31 || day > daysInMonth(month, year)){
		alert("Please enter a valid day")
		return false
	}
	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
		alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear)
		return false
	}*/
	return true
}

function isDateAllow(objname, strDay, strMonth, strYear){	
	var da1 = document.getElementById(objname+"_da1").value;
	var da2 = document.getElementById(objname+"_da2").value;
	
	strDay = parseInt(parseFloat(strDay));
	strMonth = parseInt(parseFloat(strMonth));
	strYear = parseInt(parseFloat(strYear));
	
	//alert(da1+"\n"+da2+"\n"+strDay+"\n"+strMonth+"\n"+strYear);
	
	if(strDay>0 && strMonth>0 && strYear>0){	
		if(da1 || da2){
			var date2Set = new Date();
			date2Set.setFullYear(strYear, strMonth-1, strDay);
			
			if(da1 && da2){
				
				var da1Arr = da1.split('-', 3);			
				var da2Arr = da2.split('-', 3);
				
				da1Arr[0] = parseInt(parseFloat(da1Arr[0]));
				da1Arr[1] = parseInt(parseFloat(da1Arr[1]));
				da1Arr[2] = parseInt(parseFloat(da1Arr[2]));
				
				da2Arr[0] = parseInt(parseFloat(da2Arr[0]));
				da2Arr[1] = parseInt(parseFloat(da2Arr[1]));
				da2Arr[2] = parseInt(parseFloat(da2Arr[2]));
				
				var da1Date=new Date();
				da1Date.setFullYear(da1Arr[0],da1Arr[1]-1,da1Arr[2]);
							
				var da2Date=new Date();
				da2Date.setFullYear(da2Arr[0],da2Arr[1]-1,da2Arr[2]);
				
				//alert(date2Set+"\n"+da1Date+"\n"+da2Date);
				
				return (date2Set>=da1Date && date2Set<=da2Date) ? true : false;
			}else if(da1){
				
				var da1Arr = da1.split('-', 3);			
				
				da1Arr[0] = parseInt(parseFloat(da1Arr[0]));
				da1Arr[1] = parseInt(parseFloat(da1Arr[1]));
				da1Arr[2] = parseInt(parseFloat(da1Arr[2]));
				
				var da1Date=new Date();
				da1Date.setFullYear(da1Arr[0],da1Arr[1]-1,da1Arr[2]);
							
				return (date2Set>=da1Date) ? true : false;
			}else{
				
				var da2Arr = da2.split('-', 3);			
				
				da2Arr[0] = parseInt(parseFloat(da2Arr[0]));
				da2Arr[1] = parseInt(parseFloat(da2Arr[1]));
				da2Arr[2] = parseInt(parseFloat(da2Arr[2]));
				
				var da2Date=new Date();
				da2Date.setFullYear(da2Arr[0],da2Arr[1]-1,da2Arr[2]);
				
				//alert(date2Set);
				//alert(da2Date);
				
				return (date2Set<=da2Date) ? true : false;
			}
		}else return true;
	}else return true; //always return true if date not completely set
}

function restoreDate(objname){
	//get the store value
	var storeValue = document.getElementById(objname).value;
	var storeArr = storeValue.split('-', 3);
	
	//set it
	document.getElementById(objname+'_day').value = storeArr[2];
	document.getElementById(objname+'_month').value = storeArr[1];
	document.getElementById(objname+'_year').value = storeArr[0];
}

//----------------------------------------------------------------
//javascript date format function thanks to
// http://jacwright.com/projects/javascript/date_format
//
// some modification to match the calendar script
//----------------------------------------------------------------

// Simulates PHP's date function
Date.prototype.format = function(format) {
	var returnStr = '';
	var replace = Date.replaceChars;
	for (var i = 0; i < format.length; i++) {
		var curChar = format.charAt(i);
		if (replace[curChar]) {
			returnStr += replace[curChar].call(this);
		} else {
			returnStr += curChar;
		}
	}
	return returnStr;
};
Date.replaceChars = {
	shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	longMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
	shortDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
	longDays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	
	// Day
	d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
	D: function() { return Date.replaceChars.shortDays[this.getDay()]; },
	j: function() { return this.getDate(); },
	l: function() { return Date.replaceChars.longDays[this.getDay()]; },
	N: function() { return this.getDay() + 1; },
	S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
	w: function() { return this.getDay(); },
	z: function() { return "Not Yet Supported"; },
	// Week
	W: function() { return "Not Yet Supported"; },
	// Month
	F: function() { return Date.replaceChars.longMonths[this.getMonth()]; },
	m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
	M: function() { return Date.replaceChars.shortMonths[this.getMonth()]; },
	n: function() { return this.getMonth() + 1; },
	t: function() { return "Not Yet Supported"; },
	// Year
	L: function() { return "Not Yet Supported"; },
	o: function() { return "Not Supported"; },
	Y: function() { return this.getFullYear(); },
	y: function() { return ('' + this.getFullYear()).substr(2); },
	// Time
	a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
	A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
	B: function() { return "Not Yet Supported"; },
	g: function() { return this.getHours() % 12 || 12; },
	G: function() { return this.getHours(); },
	h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
	H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
	i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
	s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
	// Timezone
	e: function() { return "Not Yet Supported"; },
	I: function() { return "Not Supported"; },
	O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
	T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result;},
	Z: function() { return -this.getTimezoneOffset() * 60; },
	// Full Date/Time
	c: function() { return "Not Yet Supported"; },
	r: function() { return this.toString(); },
	U: function() { return this.getTime() / 1000; }
};


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

function tc_updateDay(objname, yearNum, monthNum, daySelected){
	var totalDays = daysInMonth(monthNum, yearNum);
	
	var dayObj = document.getElementById(objname+"_day");
	//var prevSelected = dayObj.value;
	
	if(dayObj.options[0].value == 0 || dayObj.options[0].value == "") 
		dayObj.length = 1;
	else dayObj.length = 0;
	
	for(d=1; d<=totalDays; d++){
		var newOption = document.createElement("OPTION");
		newOption.text = d;
		newOption.value = d;
		
		dayObj.options[d] = new Option(newOption.text, padString(newOption.value, 2, "0"));
	}
	
	if(daySelected > totalDays)
		dayObj.value = padString(totalDays, 2, "0");
	else dayObj.value = padString(daySelected, 2, "0");
	
	return dayObj.value;
}

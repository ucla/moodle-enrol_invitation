/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Wimba, All Rights Reserved.                        *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      It cannot be copied, used, or modified without obtaining an           *
 *      authorization from the authors or a mandated member of Wimba.         *
 *      If such an authorization is provided, any modified version            *
 *      or copy of the software has to contain this header.                   *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is made available by the authors in the hope            *
 *      that it will be useful, but without any warranty.                     *
 *      Wimba is not liable for any consequence related to                    *
 *      the use of the provided software.                                     *
 *                                                                            *
 * Class:  verifForm.js                                                       *
 *                                                                            *
 * Author:  Thomas Rollinger                                                  *
 *                                                                            *
 * Date: May 2007                                                             *
 *                                                                            *
 ******************************************************************************/

/* $Id: verifForm.js 193 2007-08-01 14:04:16Z trollinger $ */
function verifyFormLiveClassRoomUpdate(url) {
	var theForm = window.document.myform;
	theForm.action = url;
	var validated = false;
	var errorMessage = "";
	var roomIdPattern = /^[a-z|A-Z|0-9|_]{1,32}$/;
	var longnamePattern = /^[a-z|A-Z|0-9|_|\s| |\'|!|?|(|)|:|\-|\/|]{1,50}$/;
	if (!longnamePattern.test(theForm.longname.value)) {
		errorMessage += "Please fill in a Title that is 1-50 alphanumeric or space characters or - / : ' ? ! ( )\n";
	}
	if (theForm.longname.length > 50) {
		errorMessage += "The Title you have entered is too long. This field should not exceed 50 characters.\n";
	}
	if (errorMessage.length > 0) {
		alert(errorMessage);
	} else {
		currentIdtab = "Info";
		theForm.submit();
	}
}
function verifyFormLiveClassRoom(url) {
	var theForm = window.document.myform;	
	theForm.action = url;
	var validated = false;
	var errorMessage = "";
	var roomIdPattern = /^[a-z|A-Z|0-9|_]{1,32}$/;
	var userlimitPattern = /^[0-9]+[0-9]*$/;
	var longnamePattern = /^[a-z|A-Z|0-9|_|\s| |\'|!|?|(|)|]{1,50}$/;
	if (!longnamePattern.test(theForm.longname.value)) {
		errorMessage += "Please fill in a Title that is 1-50 alphanumeric or space characters or ' ? ! ( ) \n";
	}
	if (theForm.longname.length > 50) {
		errorMessage += "The Title you have entered is too long. This field should not exceed 50 characters.\n";
	}
	
	var radio = theForm.userlimit;
	var i=0;
	for(i=0;i<radio.length;i++){
	
		if (radio[i].checked && radio[i].value == "true") {
			
			if (theForm.userlimitValue.value == null || theForm.userlimitValue == "") {
				errorMessage += "Please fill in the user limit value\n";
			}
			if (!userlimitPattern.test(theForm.userlimitValue.value)) {
				errorMessage += "User Limit should be an integer\n";
			} else {
				if (parseInt(theForm.userlimitValue.value) > 100) {
					errorMessage += "User Limit should be less than 100\n";
				}
			}
		}
		
	}
	if (errorMessage.length > 0) {
		alert(errorMessage);
	} else {
		currentIdtab = "Info";
		
		theForm.submit();
	}
}
function hwSubmit(action) {
	if (action != null) {
		document.forms[0].action.value = action;
	}
	document.forms[0].submit();
}
function isleap(year) {
	if ((year % 4) == 0) {
		if ((year % 100) == 0) {
			if ((year % 400) == 0) {
				return true;
			}
			return false;
		}
		return true;
	}
}
function check_day_in_month(day, month, year) {
	var maxdays = new Array(-1, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if ((month < 1 || month > 12) && (day >= 1 && day <= 31)) {
		return false;
	}
	if (month != 2 || (month == 2 && isleap(year))) {
		if (day <= maxdays[month]) {
			return true;
		} else {
			return false;
		}
	} else {
		if (day <= 28) {
			return true;
		}
	}
}
function check_hour(fmin, fhour, tmin, thour) {
	if (fhour == "0" && fmin == "0") {
		if (thour != "0" && tmin != "0") {
			alert("Error: End time is not allowed.");
			return false;
		} else {
			return true;
		}
	} else {
		if (fhour != "0" && fmin != "0" && thour == "0" && tmin == "-0") {
			return true;
		}
	}
}
function check_time(fd, fm, fy, fth, ftm, td, tm, ty, tth, ttm) {
	var d = window.document.myform;
	var fday = parseInt(fd);
	var fmon = parseInt(fm);
	var fyear = parseInt(fy);
	var fhour = parseInt(fth);
	var fmin = parseInt(ftm);
	var tday = parseInt(td);
	var tmon = parseInt(tm);
	var tyear = parseInt(ty);
	var thour = parseInt(tth);
	var tmin = parseInt(ttm);
	
	var check_from = 1;
	var check_to = 1;
	var myDate = new Date();
	myDate.setFullYear(tyear, tmon - 1, tday);
	var today = new Date();
	if (d.start_date.checked == false) {
		check_from = 0;
	} else {
		if (d.start_date.checked && isNaN(fday) && isNaN(fmon) && isNaN(fyear)) {
			alert("Error: Invalid From Date");
			return false;
		}
	}
	if (d.end_date.checked == false) {
		check_to = 0;
	}
	if (d.end_date.checked && isNaN(tday) && isNaN(tmon) && isNaN(tyear)) {
		alert("Error: Invalid To Date");
		return false;
	}
	if (d.end_date.checked && (myDate < today)) {
		alert("Error:  End Date < Today");
		return false;
	}
	if (!check_from && !check_to) {
		return true;
	}
	if (check_from == 1 && !check_day_in_month(fday, fmon, fyear)) {
		alert("Error: Invalid From Date");
		return false;
	}
	if (check_to == 1 && !check_day_in_month(tday, tmon, tyear)) {
		alert("Error: Invalid To Date");
		return false;
	}
	if (tyear != 0 && tyear < fyear) {
		alert("Error: End time precedes start time.");
		return false;
	} else {
		if (tyear != 0 && tyear == fyear) {
			if (tmon != 0 && tmon < fmon) {
				alert("Error: End time precedes start time.");
				return false;
			} else {
				if (tmon != 0 && tmon == fmon) {
					if (tday != 0 && tday < fday) {
						alert("Error: End time precedes start time.");
						return false;
					} else {
						if (tday != 0 && tday == fday) {
							if (thour != 0 && thour < fhour) {
								alert("Error: End time precedes start time.");
								return false;
							} else {
								if (thour != 0 && thour == fhour) {
									if (tmin != 0 && tmin < fmin) {
										alert("Error: End time precedes start time.");
										return false;
									} else {
										if (tmin != 0 && tmin == fmin) {
											alert("Error: End time cannot be the same as the start time.");
											return false;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return true;
}
function verifyFormVoiceBoard(url) {
	var d = window.document.myform;
	d.action = url;
	if (d.longname.value == "") {
		alert("Please specify a title.");
		return;
	}
   // alert(document.getElementById("ddd").options[1].value);
	if (d.start_date.checked == false && d.end_date.checked == false) {
	} else {
		var fd = d.start_day.options[d.start_day.selectedIndex].value;
		var fm = d.start_month.options[d.start_month.selectedIndex].value;
		var fy = d.start_year.options[d.start_year.selectedIndex].value;
		var fth = d.start_hr.options[d.start_hr.selectedIndex].value;
		var ftm = d.start_min.options[d.start_min.selectedIndex].value;
		var td = d.end_day.options[d.end_day.selectedIndex].value;
		var tm = d.end_month.options[d.end_month.selectedIndex].value;
		var ty = d.end_year.options[d.end_year.selectedIndex].value;
		var tth = d.end_hr.options[d.end_hr.selectedIndex].value;
		var ttm = d.end_min.options[d.end_min.selectedIndex].value;
		if (!check_time(fd, fm, fy, fth, ftm, td, tm, ty, tth, ttm)) {
			return;
		}
	}
	   // Checks on points possible field
    var gradeSetting = d.grade;
    var gradePoints = d.points_possible;
    // If grade setting is checked
    if (gradeSetting != null && gradeSetting.checked)
    {
      // Check that Points possible field is not empty
      if (trim(gradePoints.value) == "")
      {
        alert("A value must be provided: Points Possible.");
        gradePoints.focus(); //give the focus
        return false;
      }
      // Check that Points possible value is valid
      if (!isNumeric(gradePoints.value))
      {
        alert("A valid numeric value must be entered: Points Possible.");
        gradePoints.value = ""; //empty the field
        gradePoints.focus();    //give the focus
        return false;
      }
    }
    
	currentIdtab = "Info";
	d.submit();
}

function isNumeric(strNumber)
{
  return (strNumber.search(/^(-|\+)?(\d+)?(\.)?\d+$/) != -1);
}
function trim (myString)
{
	return myString.replace(/^\s+/g,'').replace(/\s+$/g,'')
}
function isleap(year) {
	if ((year % 4) == 0) {
		if ((year % 100) == 0) {
			if ((year % 400) == 0) {
				return true;
			}
			return false;
		}
		return true;
	}
}
function check_day_in_month(day, month, year) {
	var maxdays = new Array(-1, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if ((month < 1 || month > 12) && (day >= 1 && day <= 31)) {
		return false;
	}
	if (month != 2 || (month == 2 && isleap(year))) {
		if (day <= maxdays[month]) {
			return true;
		} else {
			return false;
		}
	} else {
		if (day <= 28) {
			return true;
		}
	}
}
function valid_date_duration(s_day, s_mon, s_year, e_day, e_mon, e_year) {
	if (e_year < s_year) {
		return false;
	} else {
		if (e_year > s_year) {
			return true;
		}
	}
	if (e_mon < s_mon) {
		return false;
	} else {
		if (e_mon > s_mon) {
			return true;
		}
	}
	if (e_day < s_day) {
		return false;
	} else {
		if (e_day > s_day) {
			return true;
		}
	}
	return true;
}
function strip_first_zero(str) {
	if (str.charAt(0) == "0") {
		if (str.length == 1) {
			return 0;
		} else {
			return str.charAt(1);
		}
	} else {
		return str;
	}
}
function valid_time(hr, min) {
	if (hr == "--" && min == "--") {
		return true;
	}
	if (hr == "--" || min == "--") {
		return false;
	}
	return true;
}
function valid_time_duration(s_hr, s_min, e_hr, e_min) {
	if (s_hr == "--" && s_min == "--") {
		if (e_hr != "--" && e_min != "--") {
			alert("Error: End time is not allowed.");
			return false;
		} else {
			return true;
		}
	} else {
		if (s_hr != "--" && s_min != "--" && e_hr == "--" && e_min == "--") {
			return true;
		}
	}
	s_hr = strip_first_zero(s_hr);
	s_min = strip_first_zero(s_min);
	e_hr = strip_first_zero(e_hr);
	e_min = strip_first_zero(e_min);
	var sh = parseInt(s_hr);
	var sm = parseInt(s_min);
	var eh = parseInt(e_hr);
	var em = parseInt(e_min);
	if (eh == sh && sm == em) {
		alert("Error:End time cannot be the same as start time.");
		return false;
	}
	if (eh < sh) {
		alert("Error: End time precedes start time.");
		return false;
	} else {
		if (eh > sh) {
			return true;
		}
	}
	if (sm <= em) {
		return true;
	} else {
		alert("Error: End time precedes start time.");
		return false;
	}
}
function verifyCalendarForm(url) {
	var i = 0;
	var f = window.document.myform;
	f.action = url;
	var d = parseInt(f.day.options[f.day.selectedIndex].value);
	var m = parseInt(f.month.options[f.month.selectedIndex].value);
	var y = parseInt(f.year.options[f.year.selectedIndex].value);
	var s_hr = f.start_hr.options[f.start_hr.selectedIndex].value;
	var s_min = f.start_min.options[f.start_min.selectedIndex].value;
	var d_hr = f.dur_hr.options[f.dur_hr.selectedIndex].value;
	var d_min = f.dur_min.options[f.dur_min.selectedIndex].value;
	if (f.summary.value == "") {
		alert("Error: Please enter a summary.");
		i = 1;
	}
	if (!check_day_in_month(d, m, y)) {
		alert("Error: The date is invalid.");
		i = 1;
	} else {
		if ((!valid_time(s_hr, s_min)) || (!valid_time(d_hr, d_min))) {
			alert("Error: Invalid time.");
			i = 1;
		} else {
			if (valid_time_duration(s_hr, s_min, d_hr, d_min)) {
				if (f.add_to_course) {
					chosen_course = get_course_selection();
					if (chosen_course == null) {
						chosen_course = "";
						i = 1;
					}
					f.add_to_course.value = chosen_course;
				}
			}
		}
	}
	if (i == 0) {
		f.submit();
	} else {
		return false;
	}
}
function do_cancel() {
	top.remove_breadcrumb(1, 1, 1, "");
}


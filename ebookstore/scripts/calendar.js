"use strict";


/* Set the date displayed in the calendar - create an instance of the date object */
var thisDay = new Date();

/* Write the calendar to the element with the id 'calendar' */
document.getElementById("calendar").innerHTML = createCalendar(thisDay);

/**
 * Creates the calendar table for the month specified in the
 * calDate parameter. The current date is highlighted in 
 * the table.
 * @param {Date} calDate 
 */
function createCalendar(calDate) {
    // alert(calDate);
    let calendarHTML = "<table id='calendar_table'>";
    calendarHTML += calCaption(calDate);
    calendarHTML += calWeekdayRow();
    calendarHTML += calDays(calDate);
    calendarHTML += '</table>';
    return calendarHTML;
} // end function createCalendar


/**
 * Writes the caption of the calendar table
 * @param {String} calDate 
 */
function calCaption(calDate) {
    // montName array contains the list of month names
    let monthName = ["January", "February", "March", "April",
        "May", "June", "July", "August", "September",
        "October", "November", "December"
    ];

    // Determine the current month
    let thisMonth = calDate.getMonth();
    //alert(thisMonth);
    // Determine the current year
    let thisYear = calDate.getFullYear();
    //alert(thisYear);
    // Write the caption
    let theCaption = '<caption>' + monthName[thisMonth] + " " + thisYear + '</caption>';
    return theCaption;
} // end function calCaption


/**
 * Writes the weekday title rows in the calendar table
 */
function calWeekdayRow() {
    // Array of weekday abbreviations 
    let dayName = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"];
    let rowHTML = '<tr>';

    // Loop through the dayName array
    for (let counter = 0; counter < dayName.length; counter++) {
        rowHTML += "<th class='calendar_weekdays'>" + dayName[counter] + "</th>";
    } // end for loop

    rowHTML += '</tr>';
    return rowHTML;

} // end function calWeekdayRow


/**
 * Returns the number of days in the month from calDate
 * @param {Number} calDate 
 */
function daysInMonth(calDate) {
    // Array of days in each month
    let dayCount = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    // Extract the four digit year and month value
    let thisYear = calDate.getFullYear();
    let thisMonth = calDate.getMonth();

    // Revise the days in February for leap years
    /*
    The year must be evenly divisible by 4;
    If the year can also be evenly divided by 100, it is not a leap year;
    unless...
    The year is also evenly divisible by 400. Then it is a leap year.
    */
    if (thisYear % 4 === 0) {
        if ((thisYear % 100 != 0) || (thisYear % 400 === 0)) {
            dayCount[1] = 29;
        }
    }

    // Return the number of days for the current month
    return dayCount[thisMonth];
} // end function daysInMonth


/**
 * Writes the daily rows in the calendar table, highlighting calDate
 * @param {Number} calDate 
 */
function calDays(calDate) {
    // Writes the daily rows in the calendar table, highlighting calDate
    let day = new Date(calDate.getFullYear(), calDate.getMonth(), 1);
    let weekDay = day.getDay();

    // Write blank cells preceding the starting day
    let htmlCode = "<tr>";
    for (var i = 0; i < weekDay; i++) {
        htmlCode += "<td></td>";
    }

    // Write cells for each day of the month
    let totalDays = daysInMonth(calDate);

    let highlightDay = calDate.getDate();

    for (var i = 1; i <= totalDays; i++) {
        day.setDate(i);
        weekDay = day.getDay();

        if (weekDay === 0) htmlCode += "<tr>";
        if (i === highlightDay) {
            htmlCode += "<td class='calendar_dates' id='calendar_today'>" + i + dayEvent[i] + "</td>";
        } else {
            htmlCode += "<td class='calendar_dates'>" + i + dayEvent[i] + "</td>";
        }
        if (weekDay === 6) htmlCode += "</tr>";
    }
    return htmlCode;

} // end function calDays
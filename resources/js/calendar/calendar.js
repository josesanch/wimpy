/* Author: Mihai Bazon, September 2002
 * http://students.infoiasi.ro/~mishoo
 *
 * Distributed under the GNU General Public License.  Feel free to use the
 * script at your own risk.  Please do not remove or alter this notice.
 */

var month_names = new Array("Ene","Feb","Mar","Abr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
var weekday_names = new Array("Dom","Lun","Mar","Mie","Jue","Vie","Sab","Dom");

var month_no_days = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
Calendar._TT = {}
Calendar._TT["next_year"] = "Prox. año";
Calendar._TT["prev_year"] = "Prev. año";
Calendar._TT["next_month"] = "Prox. mes";
Calendar._TT["prev_month"] = "Prev. mes";
Calendar._TT["close_cal"] = "Cerrar calendario";
Calendar._TT["close"] = "Cerrar";
Calendar._TT["Display Sunday first"] = "Domingo primer día";
Calendar._TT["Display Monday first"] = "Lúnes primer día";
Calendar._TT["day name"] = "Nombre día";


var agt = navigator.userAgent.toLowerCase();
var is_ie = ((agt.indexOf("msie") != -1) && (agt.indexOf("opera") == -1));

function isRelated(el, evt) {
  var related = evt.relatedTarget;
  if (!related) {
    var type = evt.type;
    if (type == "mouseover") related = evt.fromElement;
    else if (type == "mouseout") related = evt.toElement;
  }
  while (related) {
    if (related == el) return true;
    related = related.parentNode;
  }
  return false;
}

function isLeapYear(year) {
  return ((0 == (year%4)) && ( (0 != (year%100)) || (0 == (year%400))));
}

function getMonthDays(year, month) {
  if (isLeapYear(year) && month == 1) return 29;
  else return month_no_days[month];
}

function removeClass(el, className) {
  if (!(el && el.className)) return;
  var classes = el.className.split(" ");
  var newClasses = new Array;
  for (i = 0; i < classes.length; ++i)
    if (classes[i] != className) newClasses.push(classes[i]);
  el.className = newClasses.join(" ");
}

function addClass(el, className) {
  el.className += " " + className;
}

function getElement(ev) {
  if (is_ie) return window.event.srcElement;
  else return ev.currentTarget;
}

function getTargetElement(ev) {
  if (is_ie) return window.event.srcElement;
  else return ev.target;
}

function stopEvent(ev) {
  if (is_ie) {
    window.event.cancelBubble = true;
    window.event.returnValue = false;
  } else {
    ev.preventDefault();
    ev.stopPropagation();
  }
}

function addEvent(el, evname, func) {
  if (is_ie) el.attachEvent("on" + evname, func);
  else el.addEventListener(evname, func, true);
}

function removeEvent(el, evname, func) {
  if (is_ie) el.detachEvent("on" + evname, func);
  else el.removeEventListener(evname, func, true);
}

var g_currentTable = null;

function tableMouseUp(ev) {
  if (!g_currentTable) return;
  var el = g_currentTable.calendar.activeDateEl;
  if (!el) return;
  var target = getTargetElement(ev);
  removeClass(el, "active");
  if (target == el || target.parentNode == el) cellClick(el);
  removeEvent(document, "mouseup", tableMouseUp);
  removeEvent(document, "mouseover", tableMouseOver);
  removeEvent(document, "mousemove", tableMouseOver);
  el = null;
  g_currentTable = null;
  stopEvent(ev);
}

function tableMouseOver(ev) {
  if (!g_currentTable) return;
  var el = g_currentTable.calendar.activeDateEl;
  var target = getTargetElement(ev);
  if (target == el || target.parentNode == el) addClass(el, "hilite active");
  else {
    removeClass(el, "active");
    removeClass(el, "hilite");
  }
  stopEvent(ev);
}

function tableMouseDown(ev) { if (getTargetElement(ev) == getElement(ev)) stopEvent(ev); }

function dayMouseDown(ev) {
  var el = getElement(ev);
  addClass(el, "hilite active");
  el.calendar.activeDateEl = el;
  g_currentTable = el.calendar.element;
  addEvent(document, "mouseup", tableMouseUp);
  addEvent(document, "mouseover", tableMouseOver);
  addEvent(document, "mousemove", tableMouseOver);
  stopEvent(ev);
}

function dayMouseOver(ev) {
  var el = getElement(ev);
  if (isRelated(el, ev) || g_currentTable) return false;
  if (el.ttip) el.calendar.tooltips.firstChild.data = el.ttip;
  addClass(el, "hilite");
  stopEvent(ev);
}

function dayMouseOut(ev) {
  var el = getElement(ev);
  if (isRelated(el, ev) || g_currentTable) return false;
  removeClass(el, "hilite");
  el.calendar.tooltips.firstChild.data = "";
  stopEvent(ev);
}

function cellClick(el) {
  if (el.navtype == undefined) {
    removeClass(el.calendar.currentDateEl, "today");
    addClass(el, "today");
    var closing = el.calendar.currentDateEl == el;
    if (!closing) el.calendar.currentDateEl = el;
    var date = el.caldate;
    el.calendar.date = date;
    if (el.calendar.yourHandler) el.calendar.yourHandler.value = date.getDate() + "/" + (parseInt(date.getMonth())+1) + "/" + date.getFullYear();
    if (el.calendar.yourCloseHandler) el.calendar.yourCloseHandler(el.calendar);
  } else {
    if (el.navtype == 200) {
      if (el.calendar.yourCloseHandler) el.calendar.yourCloseHandler(el.calendar);
      return false;
    }
    var date = (el.navtype == 0) ? new Date() : new Date(el.calendar.date);
    var year = date.getFullYear();
    var mon = date.getMonth();
    var setMonth = function (mon) {
      var day = date.getDate();
      var max = getMonthDays(year, mon);
      if (day > max) date.setDate(max);
      date.setMonth(mon);
    }
    switch (el.navtype) {
    case -2:
      if (year > 1970) date.setFullYear(year - 1);
      break;
    case -1:
      if (mon > 0) setMonth(mon - 1);
      else if (year-- > 1970) {
	date.setFullYear(year);
	setMonth(11);
      }
      break;
    case 1:
      if (mon < 11) setMonth(mon + 1);
      else if (year < 2050) {
	date.setFullYear(year + 1);
	setMonth(0);
      }
      break;
    case 2:
      if (year < 2050) date.setFullYear(year + 1);
      break;
    case 100:
      el.calendar.setMondayFirst(!el.calendar.mondayFirst);
      return true;
    }
    el.calendar.setDate(date);
  }
}

function Calendar_set_date(date) {
  this.init(this.mondayFirst, date);
}

function Calendar_set_mondayFirst(mondayFirst) {
  this.init(mondayFirst, this.date);
  this.displayWeekdays();
}

function Calendar_init(mondayFirst, date) {
  this.mondayFirst = mondayFirst;
  this.date = new Date(date);

  var month = date.getMonth();
  var mday = date.getDate();
  var year = date.getFullYear();
  var no_days = getMonthDays(year, month);
  date.setDate(1);
  var wday = date.getDay();
  var MON = mondayFirst ? 1 : 0;
  var SAT = mondayFirst ? 5 : 6;
  var SUN = mondayFirst ? 6 : 0;
  if (mondayFirst) wday = (wday > 0) ? (wday - 1) : 6;

  var iday = 1;
  var row = this.element.getElementsByTagName("tbody")[0].firstChild;
  for (var i = 0; i < 6; ++i, row = row.nextSibling) {
    var cell = row.firstChild;
    if (i == 5 && iday > no_days) { row.className = "emptyrow"; break; }
    else row.className = "daysrow";
    for (var j = 0; j < 7; ++j, cell = cell.nextSibling) {
      if ((!i && j < wday) || iday > no_days)
	cell.className = "emptycell";
      else {
	cell.firstChild.data = iday;
	cell.className = "day";
	date.setDate(iday);
	cell.caldate = new Date(date);
	cell.ttip = weekday_names[wday + MON] + ", " + month_names[month] + " " + iday + ", " + year;
	if (iday == mday) { addClass(cell, "today"); this.currentDateEl = cell; }
	if (wday == SAT || wday == SUN) addClass(cell, "weekend");
	++wday; ++iday;
	if (wday == 7) wday = 0;
      }
    }
  }

  this.title.firstChild.data = month_names[month] + ", " + year;
}

function Calendar_displayWeekdays() {
  var thead = this.element.getElementsByTagName("thead")[0];
  var MON = this.mondayFirst ? 0 : 1;
  var SUN = this.mondayFirst ? 6 : 0;
  var SAT = this.mondayFirst ? 5 : 6;
  var cell = thead.getElementsByTagName("tr")[1].firstChild;
  for (var i = 0; i < 7; ++i, cell = cell.nextSibling) {
    if (!i)
      if (this.mondayFirst) cell.ttip = Calendar._TT["Display Sunday first"];
      else cell.ttip = Calendar._TT["Display Monday first"];
    cell.className = Calendar._TT["day name"];
    if (i == SUN || i == SAT) addClass(cell, "weekend");
    cell.firstChild.data = weekday_names[i + 1 - MON].substr(0,2);
  }
}

function Calendar(mondayFirst, yourHandler, yourCloseHandler, yourHolder) {
  this.yourHolder = yourHolder;
  this.init = Calendar_init;
  this.setDate = Calendar_set_date;
  this.setMondayFirst = Calendar_set_mondayFirst;
  this.displayWeekdays = Calendar_displayWeekdays;
  if (yourHandler) this.yourHandler = yourHandler;

  if (yourCloseHandler) this.yourCloseHandler = yourCloseHandler;

  var date = null;
  if (this.yourHandler)
  {
  	date = this.yourHandler.value;
  	if(date == "")
  	{
  		date = new Date();
	} else {		
  		var d = date.split("/");  		
  		var ano = parseInt(d[2]);
  		ano = ano < 100 ? ano + 1900 : ano;
  		ano = ano < 1950  ? ano + 100 : ano;  		
  		date = new Date(ano, Number(d[1]) - 1, Number(d[0]));
	}
  }  else {
  	date = new Date();
  }
  var table = document.createElement("table");
  this.element = table;
  table.className = "calendar";
  table.cellSpacing = 0;
  table.cellPadding = 0;
  table.calendar = this;
  addEvent(table, "mousedown", tableMouseDown);

  // header (navigation)
  var thead = document.createElement("thead");
  table.appendChild(thead);
  var row = document.createElement("tr");
  thead.appendChild(row);
  row.className = "headrow";
  var cal = this;
  var cell = null;

  var add_evs = function (el) {
    addEvent(el, "mouseover", dayMouseOver);
    addEvent(el, "mousedown", dayMouseDown);
    addEvent(el, "mouseout", dayMouseOut);
  }

  var hh = function (text, cs, navtype) {
    cell = document.createElement("td");
    row.appendChild(cell);
    if (cs != 1) { cell.colSpan = cs; cell.className = "title"; }
    else cell.className = "head";
    add_evs(cell);
    cell.calendar = cal;
    cell.navtype = navtype;
    cell.appendChild(document.createTextNode(text));
    return cell;
  }

  hh("<<", 1, -2).ttip= Calendar._TT["prev_year"];
  hh("<", 1, -1).ttip= Calendar._TT["prev_month"];
  this.title = hh("now", 3, 0);
  this.title.ttip= Calendar._TT["today"]
  hh(">", 1, 1).ttip= Calendar._TT["next_month"];
  hh(">>", 1, 2).ttip= Calendar._TT["next_year"];

  row = document.createElement("tr");
  thead.appendChild(row);
  row.className = "daynames";
  for (var i = 0; i < 7; ++i) {
    cell = document.createElement("td");
    row.appendChild(cell);
    cell.appendChild(document.createTextNode(""));
    if (!i) {
      cell.navtype = 100;
      cell.calendar = this;
      add_evs(cell);
    }
  }
  this.mondayFirst = mondayFirst;
  this.displayWeekdays();

  var tbody = document.createElement("tbody");
  table.appendChild(tbody);

  for (var i = 0; i < 6; ++i) {
    row = document.createElement("tr");
    tbody.appendChild(row);
    for (var j = 0; j < 7; ++j) {
      cell = document.createElement("td");
      row.appendChild(cell);
      cell.appendChild(document.createTextNode(""));
      cell.calendar = this;
      add_evs(cell);
    }
  }

  var tfoot = document.createElement("tfoot");
  table.appendChild(tfoot);
  row = document.createElement("tr");
  tfoot.appendChild(row);
  row.className = "footrow";
  for (var i = 0; i < 2; ++i) {
    cell = document.createElement("td");
    row.appendChild(cell);
    cell.calendar = this;
    if (!i) {
      this.tooltips = cell;
      cell.className = "ttip";
      cell.colSpan = 5;
      cell.appendChild(document.createTextNode(">;-)"));
    } else {
      cell.colSpan = 2;
      add_evs(cell);
      cell.appendChild(document.createTextNode(Calendar._TT["close"]));
      cell.className = "button";
      cell.navtype = 200;
      cell.ttip = Calendar._TT["close_cal"];
    }
  }

  this.init(mondayFirst, date);
}

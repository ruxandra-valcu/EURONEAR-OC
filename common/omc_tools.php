<?php
//#1 code helpers, formatters etc.
/**
* Given a function and a list of arguments for that function
* returns the JSON representation of that function's return value
* $pretty = false => smaller JSON, $pretty = true => human-readable JSON
*/
function jsonify($function, $arguments, $pretty = FALSE) {
	$result = call_user_func_array($function, $arguments);
	if ($pretty == TRUE) {
		$result = json_encode($result, JSON_PRETTY_PRINT);
 	} else {
		$result = json_encode($result);
	}
	return $result . "\n";	
}
/**
* Given a function, a list of argument for that function and an optional 
* array of field padding lengths (for outputs like MPC where position in line matters)
*	returns the string representation of that function's return value 
*/
function textify($function, $arguments,  $header = false, $spaces = false) {
	$result = call_user_func_array($function, $arguments);
	return formatText($result, $header, $spaces);
}
/**
* Given a data array and optionally a header row and a list of how many characters each column should take
*	returns the data array formatted as a text file. Should the space list be missing, it'll autoformat
*/
function formatText($arr, $header = false, $spaces = false) {
	if (!is_array($arr) ) {
		return strval($arr);
	}
	//set up the spacing array, even if it doesn't exist
	$first = reset($arr);
	if (!is_array($first)) { //we have a single observation line
		$arr = array("0" => $arr);
		$first = reset($arr);
	}
	if (is_array($header)) { // we also want to add names of column, so let's figure out how much spaces we need to add
		$header = array_combine(array_keys($first), $header);
		array_unshift($arr, $header);
	}
	$spaceLength = array();
	$spaces = is_array($spaces) ? array_reverse($spaces) : $spaces;
	foreach($first as $key => $value) { 
		if (is_array($spaces)) {
			$spaceLength[$key] = array_pop($spaces);
		} else { //no space array given, add 1 to length of longest bit of text in column;
			$column = array_column($arr, $key);
			$maxLength = 0;
			foreach ($column as $colValue) {
				$length = strlen(strval($colValue)) + 1;
				$maxLength = $maxLength >= $length ? $maxLength : $length;
			}
			$spaceLength[$key] = $maxLength;
		}
	}
	$txt = "";
	foreach ($arr as $line) {
		$txtLine = "";
		foreach($line as $key => $value) {
			$txtLine .= str_pad($value, $spaceLength[$key], " ");
		}
		$txtLine .= "\n";
		$txt .= $txtLine;
	}
	return $txt;
}
/**
*	Given a data array and a potential header row, print as CSV
*/
function formatCSV($array, $header = FALSE) {
	$first = reset($array);
	if (!is_array($first)) { //we have a single observation line
		$array = array("0" => $array);
	}
	if ($header != FALSE) {
		array_unshift($array, $header);
	}
	$rows = array();
	foreach($array as $key => $value) {
		$rows[$key] = implode(",", $value);
	}
	$csv = implode("\n", $rows);
	return $csv;
}
/**
*	Given a data array and a potential header row, print as HTML table
*/
function formatHTMLTable($array, $header = FALSE) {
	$first = reset($array);
	if (!is_array($first)) { //we have a single observation line
		$array = array("0" => $array);
	}
	$rows = array();
	foreach($array as $key => $value) {
		$rows[$key] = "\t<tr><td align=right>" . implode("</td><td align=right>", $value) . "</td></tr>\n";
	}
	if ($header != FALSE) {
		$htmlH = "\t<tr><th align=right>" . implode("</th><th align=right>", $header) . "</th></tr>\n";
		array_unshift($rows, $htmlH);
	}
	$html_table = "\n<table>" .  implode("", $rows) . "</table>\n";
	return $html_table;
}
/**
*	Given a data array and a list of columns to keep, returns the data array subsetted by the given columns
*/
function subset_array($array, $keys) {
	$keys = array_flip($keys);
	$first = reset($array);
	if (!is_array($first)) { //we have a single observation line
		$array = array("0" => $array);
	}
	$res = array();
	foreach($array as $key  => $value) {
		$res[$key] = array_intersect_key($value, $keys);
	}
	return $res;
}
/**
* given an array of arrays, all with the same keys (say, observation lines), and a list of keys to group by
* groups them by unique key/value combinations, e.g. only observations of the same asteroid from the same observatory
*/
function chunkArray($arr, $keys) {
	if (sameKeys($arr) == false) {
		return(false);
	}
	$groupedArray = array();
	foreach($arr as $line) {
		$groupedKey = createKey($line, $keys);
		if (!isset($groupedArray[$groupedKey])) {
			$groupedArray[$groupedKey] = array();
		}
		array_push($groupedArray[$groupedKey], $line);
	}
	return $groupedArray;
}
/**
* helper function for chunkArray, given an array and a set of keys it returns a single key 
* based on the values said keys have in the array
*/
function createKey($line, $keys) {
	$finalKey = "";
	foreach($keys as $key) {
		$finalKey .= $key . "=" . $line[$key] . ";";
	}
	return $finalKey;
}
/**
* given an array of arrays, checks if all of them have the same keys
* ugly sanity check for php not actually having decent data structures
*/ 
function sameKeys($arr) {
	$first = reset($arr);
	$keys = array_keys($first);
	foreach($arr as $line) {
		if ($keys != array_keys($line)) {
			return(false);
		}
	}
	return true;
}
/**
* given an array, reorders it in the order specified by $keys
*/
function resort($array, $keys) {
  $newArray = array();
  foreach($keys as $key) {
    $newArray[$key] = $array[$key];
  }
  return($newArray);
}
/**
* given a file(by handle) and a parsing function, returns the parsed file 
*/
function parseFile($fileName, $parseFunction) {
	$contents = file_get_contents($fileName);
	if ($contents == FALSE) {
		return "\nERROR: File not found\n";
	}
	return call_user_func_array($parseFunction, array($contents));
}
function lettersToNumbers($letter) {
	$map = array(
		"0" => "0", 
		"1" => "1",
		"2" => "2",
		"3" => "3",
		"4" => "4",
		"5" => "5",
		"6" => "6",
		"7" => "7",
		"8" => "8",
		"9" => "9",
		"A" => "10",
		"B" => "11",
		"C" => "12",
		"D" => "13",
		"E" => "14",
		"F" => "15",
		"G" => "16",
		"H" => "17",
		"I" => "18",
		"J" => "19",
		"K" => "20",
		"L" => "21",
		"M" => "22",
		"N" => "23",
		"O" => "24",
		"P" => "25",
		"Q" => "26",
		"R" => "27",
		"S" => "28",
		"T" => "29",
		"U" => "30",
		"V" => "31",
		"W" => "32",
		"X" => "33",
		"Y" => "34",
		"Z" => "35",
		"a" => "36",
		"b" => "37",
		"c" => "38",
		"d" => "39",
		"e" => "40",
		"f" => "41",
		"g" => "42",
		"h" => "43",
		"i" => "44",
		"j" => "45",
		"k" => "46",
		"l" => "47",
		"m" => "48",
		"n" => "49",
		"o" => "50",
		"p" => "51",
		"q" => "52",
		"r" => "53",
		"s" => "54",
		"t" => "55",
		"u" => "56",
		"v" => "57",
		"w" => "58",
		"x" => "59",
		"y" => "60"
	);
	if (array_key_exists($letter, $map)) {
		return $map[$letter];
	}
	return $letter;
}

/**
* adds a zero to the string representation of a number between -10 and 10
* used to print things nicely
*/
function zeroPrefix($n) {
  if($n >= 10) {
    return $n;
  } 
  if($n <= -10) {
    return $n;
  } 
  return "0" . $n;
}

//end #1
//#2 astronomical calculations and/or queries
/**
*Site name to base query address mapping
*/
function siteMap($site) {
	$map = array(
		"neodys" => "http://newton.dm.unipi.it/neodys/",
		"astdys" => "http://hamilton.dm.unipi.it/astdys/"
	);
	if (array_key_exists($site, $map)) {
		return $map[$site];
	}
	return $site;
}
/**
* calculates JD giving a gregorian calendar date ($day with dec coresp UT)
* ref: J. Meeus, Astronomical Algorithms
* hour must be between 0 and 1, otherwise it'll change the day
*/
function julianDay($year, $month, $day, $hour = 0) {
	if ($month <= 2) {
		$year = $year + 1;
		$month = $month + 12;
	}
	$a = floor($year / 100);
	$b = 2 - $a + floor($a / 4);
	$JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $b - 1524.5 + $hour;
  return $JD;
}
/**
* given a date, a time in % of day and optionally a format, returns JD
*/
function julianDateFormat($date, $time, $format = "j M Y") {
	$parsedDate = date_create_from_format($format, $date);
	$year = date_format($parsedDate	, 'Y');
	$month = date_format($parsedDate, 'n');
	$day = date_format($parsedDate, 'j');
	return julianDay($year, $month, $day, $time);
}
/**
* given a JD, returns the gregorian calendar date as an array of year, month, day, hour, minute
*	ref: J. Meeus, Astronomical Algorithms
*/
function gregorianDate($julianDay, $addMinute = false) {
	if ($addMinute == true) { //for the end of an observation period we want a date 1 minute later than the actual date
		$julianDay += 1 / 1440; 
	}
	$julianDay += 0.5;
	$Z = floor($julianDay);
	$F = $julianDay - floor($julianDay);
	$A = $Z;
	if ($A >= 2291161) {
		$a = floor(($Z - 1867216.25) / 36524.25);
		$A = $Z + 1 + $a - floor($a / 4);
	}
	$B = $A + 1524;
	$C = floor(($B - 122.1) / 365.25);
	$D = floor(365.25 * $C);
	$E = floor(($B - $D) / 30.6001);
	$dayD = $B - $D - floor(30.6001 * $E) + $F;
	$month = $E < 14 ? $E - 1 : $E - 13;
	$year = $month > 2 ? $C - 4716 : $C - 4715;
	$day = floor($dayD);
	$hm = 24 * ($dayD - $day);
	$hour = floor($hm);
	$minute = floor(60 * ($hm - $hour)); 
	$date = array("year" => $year, "month" => $month, "day" => $day, "hour" => $hour, "minute" => $minute);
	return $date;
}
/**
* Given a value represented as degree/minute/second, and optionally sign
* returns the single numeric value corresponding to it.
* If using it for RA make sure to multiply by 15 afterwards
*/
function calcDMS($degree, $minute, $second, $sign = "+") {
	if ($sign == "-") {
		$degree = - $degree;
		$minute = - $minute;
		$second = - $second;
	}
	return $degree + $minute / 60 + $second / 3600;
}
/**
* Given a numeric value, returns its representation as degree/minute/second, and optionally sign
* If using it for RA make sure to divide by 15 beforehand
*/
function getDMS($value, $precision = 2, $keepSign = false) {
	$sign = $value < 0 ? "-" : "+";
	$value = $value < 0 ? -$value : $value;
	$h = floor($value);
	$value = ($value - $h) * 60.0;
	$m = floor($value);
	$s = ($value - $m) * 60.0;
	if ($s > 59.999) {	
		$s -= 60.0;
		$m += 1;
	}
	if ($m >= 60) {
		$m -= 60;
		$h = $sign == "-" ? $h - 1 : $h + 1;
	}
	$h = zeroPrefix($h);
	$m = zeroPrefix($m);
	$s = zeroPrefix(number_format($s, $precision));
	$sh = $keepSign ? array("sign" => $sign, "hour" => $h) : array("degree" => $h);
	$ms = array("minute" => $m, "second" => $s);
	$result = array_merge($sh, $ms);
	return $result;
}
/**
* Given 2 points x1y1 and x2y2 and a value y
* calculates the x that would correspond to y by linear interpolation
*/
function linearInterpolate($y, $x1, $y1, $x2, $y2) {
	$x = $x1 + (($y - $y1) * ($x2 - $x1) / ($y2 - $y1));
	return $x;
}
/**
* Linear interpolation for RA values w/ handling of crossing the vernal equinox
* Supposed to be used for small time intervals, so if the difference between the 
* interpolation RA value is above 1 degree it'll think we crossed the vernal 
* equinox and apply the correction
*/
function linearInterpolateRA($y, $x1, $y1, $x2, $y2) {
	if($x1 - $x2 > 1 )  { //this sort of difference on 2 positions minutes away means we've crossed the 0/360 divide
		$x1 > $x2 ? $x2 += 360 : $x1 += 360;
	}
	$x =  linearInterpolate($y, $x1, $y1, $x2, $y2);
	if ($x >= 360) {
		$x -= 360;
	}
	return $x;
}
//end #2
//3 omc-related stuff - should probably be placed in its own file later 
/**
* parses the contents of a MPC file into a PHP array containing just the observation data
*/
function parseMPC($file) { 
	$contents = explode("\n", $file);
	$contents = array_reverse($contents); //to avoid performance penalty for array_shift
	do {
		$line = array_pop($contents);
	} while (trim($line) != "" && !empty($contents));
	$contents = array_reverse($contents);
	$observations = array();
	$notMPC = false;
	foreach($contents as $line) {
		if (trim($line) === "") {
				continue; //empty line, pointless to go through the parsing
		}
		$obs = array();
		$number = substr($line, 0, 5); //read asteroid number (if exists) and process
		$number = substr_replace($number, lettersToNumbers(substr($number, 0, 1)) , 0, 1);
		$tempDes = substr($line, 5, 7);
		if (trim($tempDes) <> "") {
			$nr1 = lettersToNumbers(substr($tempDes, 0, 1));
			$nr2 = substr($tempDes, 1, 2);
			$nr3 = substr($tempDes, 3, 1);
			$nr4 = substr($tempDes, 6, 1);
			$nr5 = lettersToNumbers(substr($tempDes, 4, 1));
			if ($nr5 == "0") {	
				$nr5 = "";
			}
			$nr6 = substr($tempDes, 5, 1);
			if ($nr6 == "0" && $nr5 == "") {
				$nr6 == "";
			} 
			$tempDes = $nr1 . $nr2 . $nr3 . $nr4 . $nr5 . $nr6;
		}
		$obs["number"] = $number;
		$obs["name"] = $tempDes;
		$obs["id"] = trim($obs["number"]) != "" ? trim($obs["number"]) : trim($obs["name"]);
		$obs["year"] = trim(substr($line, 15, 4));
		$obs["month"] = trim(substr($line, 20, 2));
		$obs["day"] = trim(substr($line, 23, 8));
		$obs["alhr"] = trim(substr($line, 32, 2)); //right ascension
		$obs["almin"] = trim(substr($line, 35, 2)); 
		$obs["alsec"] = trim(substr($line, 38, 5));
		$obs["delsign"] = trim(substr($line, 44, 1));
		$obs["delgr"] = trim(substr($line, 45, 2)); //declination
		$obs["delmin"] = trim(substr($line, 48, 2));
		$obs["delsec"] = trim(substr($line, 51, 4));
		$obs["obscode"] = trim(substr($line, 77, 3));
			
		// verify if in correct format - if not, stop reading
		if (!is_numeric($obs["year"]) || !is_numeric($obs["month"]) || !is_numeric($obs["day"]) ||
				!is_numeric($obs["alhr"]) || !is_numeric($obs["almin"]) || !is_numeric($obs["alsec"]) ||
				!is_numeric($obs["delgr"]) || !is_numeric($obs["delmin"]) || !is_numeric($obs["delsec"])) {
			return "ERROR: File not in MPC format\n";
		}
		$obs["JD"] = julianDay($obs["year"], $obs["month"], $obs["day"]);
		// add it to observation array
		array_push($observations, $obs);
	}
	return $observations;
}
/**
* parses an observation line and returns time parameters for queryNEODYS
* @param $obs observation, as element of the array from parseMPC 
* @param $lastMinute should we include the last minute in the NEODYS request or not?
*/
function timeParameters($obs, $addMinute = false) {
	$JD = $obs["JD"];
	$year = $obs["year"];
	$month = $obs["month"];
	$day = substr($obs["day"], 0, 2);
	$hm = 24 * substr($obs["day"], 2, 6);
	$hour = floor($hm);
	$addMinutes = $addMinute == true ? 1 : 0; //to include the last observation if true
	$minute = floor(60 * ($hm - $hour)) + $addMinutes; 
	$param = array("JD" => $JD, "year" => $year, "month" => $month, "day" => $day, "hour" => $hour, "minute" => $minute);
	return($param);
}
/**
* Queries the specified site for the ephemerid of a certain asteroid, from a certain observatory,
* between two julian dates that must be at most $maxInterval in difference (NEODYS requirement)
*/
function queryEphShort($site, $asteroid, $obscode, $startJD, $endJD, $maxInterval = 3.0) {
	if ($endJD - $startJD > $maxInterval) {
		return "ERROR: query interval is greater than the 3 days allowed by NeoDYS\n";
	}
	$baseURL = "SITEindex.php?pc=1.1.3.1&n=ASTEROID&oc=OBSCODE&y0=Y0&m0=M0&d0=D0&h0=H0&mi0=MI0&y1=Y1&m1=M1&d1=D1&h1=H1&mi1=MI1&ti=1.0&tiu=minutes";
	$startDate = gregorianDate($startJD);
	$endDate = array_combine(array("year2", "month2", "day2", "hour2", "minute2"), gregorianDate($endJD, true));
	$replace = array("site" => sitemap($site),"name" => $asteroid, "obs" => $obscode) + $startDate + $endDate;
	$find = array("SITE", "ASTEROID", "OBSCODE", "Y0", "M0", "D0", "H0", "MI0", "Y1", "M1", "D1", "H1", "MI1");
	$URL = str_replace($find, $replace, $baseURL);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$raw = curl_exec($ch);
	curl_close($ch);
	$regex = '#===\n(.*?)</pre>#s';
	preg_match($regex, $raw, $match);
	return count($match) < 2 ? "" : $match[1];
}
/**
* checks if an asteroid can be found on a certain site
*/
function checkIfOnSite($asteroid, $failString, $site, $queryPart = "index.php?pc=1.1.0&n=") {
	$URL = siteMap($site) . $queryPart . $asteroid;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$raw = curl_exec($ch);
	curl_close($ch);
	if (strpos($raw, $failString) !== false) { //we found the fail pattern
		return false;
	}
	return true;
}
/**
* checks if an asteroid is a NEA and returns the name of the site it can be found in.
*/
function checkIfNEA($asteroid) { 
	if (checkIfOnSite($asteroid, "NEA not found", "neodys") == true) { return "neodys";
	} else if (checkIfOnSite($asteroid, "Asteroid not found", "astdys") == true) { return "astdys";
	} else { return false;
	}
}
/**
* queries neodys/astdys for the ephemerid of an asteroid at a specific observation spot for a specific time range
* returns php array
*/
function queryEph($asteroid, $obscode, $timerange, $maxInterval = 3.0) {
	$startJD = $timerange["start"];
	$endJD = $timerange["stop"]; //TODO replace this later with more complex obs. intervals
	$site = checkIfNEA($asteroid);
	if ($site == false) { //not found on any of the sites we're looking on
		return false;
	}
	//create the query ranges for queryEphShort
	if ($endJD - $startJD <= $maxInterval) {
		$tr = array($startJD, $endJD);
	} else {
		$tr = range($startJD, $endJD, $maxInterval);
		array_push($tr, $endJD);
	}
	$timeRange = array_combine(array_slice($tr, 0, count($tr) - 1), array_slice($tr, 1, count($tr) - 1));
	$raw =  "";
	foreach	($timeRange as $start => $stop) {
		$raw .= queryEphShort($site, $asteroid, $obscode, $start, $stop, $maxInterval);
	}
	$raw = explode("\n", $raw);
	$eph = array();
	foreach($raw as $line) {
		if(trim($line) != "") {
			$point = array(
				"date" => trim(substr($line, 0, 12)),
				"time" => trim(substr($line, 13, 6)),
				"RA_h" => trim(substr($line, 22, 2)),
				"RA_m" => trim(substr($line, 25, 2)),
				"RA_s" => trim(substr($line, 28, 6)),
				"DEC_sign" => trim(substr($line, 35, 2)),
				"DEC_d" => trim(substr($line, 37, 2)),
				"DEC_m" => trim(substr($line, 40, 2)),
				"DEC_s" => trim(substr($line, 43, 5)),
				"Mag" => trim(substr($line, 49, 5)),
				"Alt" => trim(substr($line, 55, 5)),
				"Airmass" => trim(substr($line, 61, 8)),
				"Sun elev." => trim(substr($line, 70, 6)),
				"SolEl" => trim(substr($line, 77, 6)),
				"LunEl" => trim(substr($line, 84, 6)),
				"Phase" => trim(substr($line, 91, 6)),
				"Glat" => trim(substr($line, 98, 6)),
				"Glon" => trim(substr($line, 104, 6)),
				"R" => trim(substr($line, 110, 8)),
				"Delta" => trim(substr($line, 118, 7)),
				"RA*cosDE" => trim(substr($line, 126, 9)),
				"DEC" => trim(substr($line, 137, 9)),
				"Err1" => trim(substr($line, 147, 8)),
				"Err2" => trim(substr($line, 157, 8)),
				"PA" => trim(substr($line, 166, 6))
			);	
			$point["JD"] = julianDateFormat($point["date"], $point["time"] / 24);
			array_push($eph, $point);
		}
	}
	return($eph);
}
/**
*	for now, returns max and min JD found in the array of observations
*/
function getObservationInterval($obs) {
	$jd = array_column($obs, "JD");
	$interval = array("start" => min($jd), "stop" => max($jd));
	return($interval);
}
/**
* Given the contents of a MPC file with potentially multiple objects, it parses it, 
* gets ephemerids for each object from either NEODYS or ASTDYS
* and returns an array of observations, estimated RA and DEC and OC diff
*/
function omc($fileContents) {
	$rawObs = parseMPC($fileContents);	
	if(is_string($rawObs)) { //it's an error message, should return an array
		return($rawObs);
	}	
	$rawObs = chunkArray($rawObs, array("id", "obscode"));
	if ($rawObs == false) {
		return("\nError: parsed file incorrectly\n");
	}
	$enrichedObs = array();
	foreach($rawObs as $key => $obs) {
		$asteroid = reset($obs)["id"];
		$obscode = reset($obs)["obscode"];
		$timerange = getObservationInterval($obs);
		$eph = queryEph($asteroid, $obscode, $timerange);
		$enrichedObs[$key] = addOC($obs, $eph);
	}
	//flatten enrichedObs from array of asteroid arrays containing observations to array of observations
	$flatObs = array();
	$flatKey = 0; //we're doing this by hand because I couldn't find an array_merge variant that will add all values regardless of keys
	foreach($enrichedObs as $ast) {
		foreach($ast as $obs) {
			$flatObs[$flatKey] = $obs;
			$flatKey++;
		}
	}
	return($flatObs);
}
/**
* Given a list of observed positions for a single object and the NEODYS/ASTDYS ephemerid
* calculates estimated positions according to the ephemerid and OC differences
*/
function addOC($obs, $eph, $addEmptyRows = FALSE) {
	foreach($obs as $key => $obsLine) { //adding numeric RA/dec values
		$obsLine["al"] = calcDMS($obsLine["alhr"], $obsLine["almin"], $obsLine["alsec"]) * 15;
		$obsLine["del"] = calcDMS($obsLine["delgr"], $obsLine["delmin"], $obsLine["delsec"], $obsLine["delsign"]);
		$obs[$key] = $obsLine;
	}
	$addVals = array();
	if ($eph == false) {
		//we didn't find the asteroid, treat it as such
		$addVals["found"] = "N";	
	} else {
	  $addVals["found"] = "Y";
	}
	foreach($eph as $key => $ephLine) { //adding numeric RA/dec values
		$ephLine["al"] = calcDMS($ephLine["RA_h"], $ephLine["RA_m"], $ephLine["RA_s"]) * 15;
		$ephLine["del"] = calcDMS($ephLine["DEC_d"], $ephLine["DEC_m"], $ephLine["DEC_s"], $ephLine["DEC_sign"]);
		$eph[$key] = $ephLine;
	}
	$oc = array();
	foreach($obs as $obsLine) {
		$newLine = $obsLine;
		$newLine = array_merge($newLine, $addVals);
		if ($newLine["found"] !== "N") {
			$closest = getClosest($newLine, $eph, "JD", 2);
			//interpolate
			$estAl = linearInterpolateRA($newLine["JD"], $closest[0]["al"], $closest[0]["JD"], $closest[1]["al"], $closest[1]["JD"]);
			$newLine["est_al"] = $estAl;
			$estDel = linearInterpolate($newLine["JD"], $closest[0]["del"], $closest[0]["JD"], $closest[1]["del"], $closest[1]["JD"]);
			$newLine["est_del"] = $estDel;
			$newLine["est_al_print"] = implode(" ", getDMS($estAl / 15.0, 3));
			$newLine["est_del_print"] = implode(" ", getDMS($estDel, 2, true));
			$newLine["O-C RA"] = ($obsLine["al"] - $estAl) * 3600.0 * cos(deg2rad($estDel));
			$newLine["O-C DEC"] = ($obsLine["del"] - $estDel) * 3600.0;
			$newLine["O-C"] = sqrt($newLine["O-C RA"] * $newLine["O-C RA"] + $newLine["O-C DEC"] * $newLine["O-C DEC"]);
		} else {
			$newLine["est_al"] = " ";
			$newLine["est_del"] = " ";
			$newLine["est_al_print"] = "Not found";
			$newLine["est_del_print"] = " ";
			$newLine["O-C RA"] = " ";
			$newLine["O-C DEC"] = " ";
			$newLine["O-C"] = " ";
		}
		array_push($oc, $newLine);
	} 
	foreach($oc as $key => $row) {
	  $oc[$key] = formatOMC($row);
	}
	if ($addEmptyRows === TRUE) {
	  //TODO write a subset by row value function, split the array by arrays, paste it with an empty array in between
	  $emptyRow = array("id" => "", "Date" => "", "RA Observed" => "", "DEC Observed" => "", 
	    "RA Calculated" => "", "DEC Calculated" => "", "O-C RA" => "", "O-C DEC" => "", "O-C" => "");
	}
	return $oc;
}
/**
* given an observation line, a number of possible values, a column to compare and a number of values to return
* it returns the $nr closest values
* e.g. the 2 ephemerid points closest in time to an observation
*/
function getClosest($obs, $possible, $column, $nr) {
	foreach($possible as $key => $value) {
		$value["valuedifference"] = abs($obs[$column] - $value[$column]);
		$possible[$key] = $value;
	}
	usort($possible, function($a, $b) {
		$diff = $a["valuedifference"] - $b["valuedifference"];
		return ($diff > 0) - ($diff < 0); //to avoid php automatically casting to integer - we want any difference to show
	});
	$closest = array_slice($possible, 0, $nr);
	return $closest;
}
/**
* given an OMC data line, formats it nicely
* helper function for omc
*/
function formatOMC($data) {
  $keys = array("id", "Date", "RA Observed", "DEC Observed", "RA Calculated", "DEC Calculated", "O-C RA", "O-C DEC", "O-C");
  foreach($data as $key => $value) {
    $data["Date"] = $data["year"] . " " . $data["month"] . " " . $data["day"];
    $data["RA Observed"] = $data["alhr"] . " " .  $data["almin"] . " " . $data["alsec"];
    $data["DEC Observed"] = $data["delsign"] . $data["delgr"] . " " . $data["delmin"] . " " . $data["delsec"];
    $data["RA Calculated"] = $data["est_al_print"];
    $data["DEC Calculated"] = $data["est_del_print"];
    $data["O-C RA"] = is_numeric($data["O-C RA"]) ? number_format($data["O-C RA"], 2) : $data["O-C RA"];
    $data["O-C DEC"] = is_numeric($data["O-C DEC"]) ? number_format($data["O-C DEC"], 2) : $data["O-C DEC"];
    $data["O-C"] = is_numeric($data["O-C"]) ? number_format($data["O-C"], 2) : $data["O-C"];
  }
  $data = resort($data, $keys);
  return $data;
}
?>

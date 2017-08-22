<?php

//#TODO ask Ovidiu what's with this bit
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
* calculates JD giving a gregorian calendar date ($day with dec coresp UT)
* ref: J. Meeus, Astronomical Algorithms
*/
function julianDay($year, $month, $day) {
	if ($month <= 2) {
		$year = $year + 1;
		$month = $month + 12;
	}
	$a = floor($year / 100);
	$b = 2 - $a + floor($a / 4);
	$JD = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $b - 1524.5;
  return $JD;
}

/**
* parses a MPC file (given by handle) into a PHP array containing just the observation data
*/
function readMPC($fileName) {
	if ($file = fopen($fileName, "r")) {
		// skip header
		do {
			$line = fgets($file, 1024);
		} while (!feof($file) && trim($line) != ""); // there's an empty line between header and data lines

		// read data lines
		$observations = array();
		$notMPC = false;
		// parse lines into arrays
		do {
			$line = fgets($file, 1024);
			if (trim($line) === "") {
				break; //we're on the last, empty line, pointless to go through the parsing
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
				} $nr6 == "";
				$tempDes = $nr1 . $nr2 . $nr3 . $nr4 . $nr5 . $nr6;
			}
			$obs["number"] = $number;
			$obs["name"] = $tempDes;
			$obs["NEODYS"] = trim($obs["number"]) != "" ? trim($obs["number"]) : trim($obs["name"]);
			$obs["year"] = trim(substr($line, 15, 4));
			$obs["month"] = trim(substr($line, 20, 2));
			$obs["day"] = trim(substr($line, 23, 8));
			$obs["alhr"] = trim(substr($line, 32, 2));
			$obs["almin"] = trim(substr($line, 35, 2));
			$obs["alsec"] = trim(substr($line, 38, 5));
			$obs["delgr"] = trim(substr($line, 44, 3));
			$obs["delmin"] = trim(substr($line, 48, 2));
			$obs["delsec"] = trim(substr($line, 51, 4));
			$obs["obscode"] = trim(substr($line, 77, 3));
			
			// verify if in correct format - if not, stop reading
			if (!is_numeric($obs["year"]) || !is_numeric($obs["month"]) || !is_numeric($obs["day"]) ||
					!is_numeric($obs["alhr"]) || !is_numeric($obs["almin"]) || !is_numeric($obs["alsec"]) ||
					!is_numeric($obs["delgr"]) || !is_numeric($obs["delmin"]) || !is_numeric($obs["delsec"])) {
				$notMPC = true;
			}
			$obs["JD"] = julianDay($obs["year"], $obs["month"], $obs["day"]);
			// add it to observation array
			if ($notMPC == false) { 
				array_push($observations, $obs);
			}
		} while (!feof($file) && $notMPC == false && trim($line) != ""); // last line is empty

		if ($notMPC === true) {
			return "ERROR: File not in MPC format";
		}
		return $observations;
	} else {
		return "ERROR: File not found";
	}	
}

/**
* parses a MPC file (given by handle) into a JSON file containing just the observation data
*/
function jsonMPC($fileName, $pretty = false) {
	$data = readMPC($fileName);
	if ($pretty === true) {
		$data = json_encode($data, JSON_PRETTY_PRINT);
	} else {
		$data = json_encode($data);
	}
	return $data;
}


?>

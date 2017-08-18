<?php

//#TODO ask Ovidiu what's with this bit
$lettersToNumbers = array(
	"0" => 0, 
	"1" => 1,
	"2" => 2,
	"3" => 3,
	"4" => 4,
	"5" => 5,
	"6" => 6,
	"7" => 7,
	"8" => 8,
	"9" => 9,
	"A" => 10,
	"B" => 11,
	"C" => 12,
	"D" => 13,
	"E" => 14,
	"F" => 15,
	"G" => 16,
	"H" => 17,
	"I" => 18,
	"J" => 19,
	"K" => 20,
	"L" => 21,
	"M" => 22,
	"N" => 23,
	"O" => 24,
	"P" => 25,
	"Q" => 26,
	"R" => 27,
	"S" => 28,
	"T" => 29,
	"U" => 30,
	"V" => 31,
	"W" => 32,
	"X" => 33,
	"Y" => 34,
	"Z" => 35,
	"a" => 36,
	"b" => 37,
	"c" => 38,
	"d" => 39,
	"e" => 40,
	"f" => 41,
	"g" => 42,
	"h" => 43,
	"i" => 44,
	"j" => 45,
	"k" => 46,
	"l" => 47,
	"m" => 48,
	"n" => 49,
	"o" => 50,
	"p" => 51,
	"q" => 52,
	"r" => 53,
	"s" => 54,
	"t" => 55,
	"u" => 56,
	"v" => 57,
	"w" => 58,
	"x" => 59,
	"y" => 60
);

// calculates JD giving a gregorian calendar date ($day with dec coresp UT)
// ref: J. Meeus, Astronomical Algorithms
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

?>

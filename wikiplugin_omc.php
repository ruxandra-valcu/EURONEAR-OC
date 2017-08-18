<?php
/* Tiki-Wiki plugin example 
 *
 * This is an example plugin to let you know how to create
 * a plugin. Plugins are called using the syntax
 * {NAME(params)}content{NAME}
 * Name must be in uppercase!
 * params is in the form: name=>value,name2=>value2 (don't use quotes!)
 * If the plugin doesn't use params use {NAME()}content{NAME}
 *
 * The function will receive the plugin content in $data and the params
 * in the asociative array $params (using extract to pull the arguments
 * as in the example is a good practice)
 * The function returns some text that will replace the content in the
 * wiki page.
 */ 
//include_once('tiki-setup.php');
//error_reporting(E_ALL);
require_once("config.php");
 
function JD($year, $month, $day)
    {
    // calculates JD giving a gregorian calendar date ($day with dec coresp UT)
    // ref: J. Meeus, Astronomical Algorithms
    $y = $year; $m = $month; $d = $day;
    if ($m <= 2) {
       $y = $y - 1;
       $m = $m + 12;
       }
    $a = floor($y/100);
    $b = 2 - $a + floor($a/4);
    $JD = floor(365.25*($y+4716)) + floor(30.6001*($m+1)) + $d + $b - 1524.5;
    return $JD;
    }

 
 
function wikiplugin_omc_help() {
	return tra("Example").":<br />~np~{EXAMPLE(face=> size=>)}".tra("text")."{EXAMPLE}~/np~";
}
function wikiplugin_omc($data, $params) {
	global $smarty;
	if(is_array($params)) {
	   extract ($params,EXTR_SKIP);
	}
	$ret = "";
	$ret .= '<br><form method="POST" enctype="multipart/form-data">';
	$ret .= '<label for="file">Observations (file in MPC format - example <a href="'.ROOTURL.'data/MPCReportExample.txt">here</a>): </label>';
	$ret .= '<input type="file" name="file" id="file" /><br><br>'; 
        $ret .= '<input type="hidden" name="_submit_check_omc" value="1"/>';
	$ret .= '<input type="submit" name="submit" value="Calculate O-Cs" />';    

	$ret .= '</form>';
	
	if (array_key_exists('_submit_check_omc', $_POST)){
	
	if ($_FILES["file"]["error"] > 0)
	{
		echo "Upload error: " . $_FILES["file"]["error"] . "<br />";
	}
	else
  	{
  	//	echo "Upload file " . $_FILES["file"]["name"] . "...<br/>";
	  	move_uploaded_file($_FILES["file"]["tmp_name"], ROOTPATH."results/OMC/OBS.DAT");
//	  	move_uploaded_file($_FILES["file"]["tmp_name"], "OBS.DAT"); 
  	}

// TEST OBSERVATION FILE FORMAT FOR THE MPC FORMAT...
//echo "Test the observation file for the MPC format... <br />"; 
$handle_OBS = fopen (ROOTPATH."results/OMC/OBS.DAT", "r"); 

   // skip header...
   $first = true;
   $stopflag = false;
   $linie = fgets($handle_OBS, 1024);
   if (feof($handle_OBS)) 
   	$stopflag = true; 
   while ((trim($linie) <> "") && (! $stopflag))
         {
         $linie = fgets($handle_OBS, 1024);
         if (feof($handle_OBS)) { $stopflag = true; }
         }

   // read data lines...
   while ((!feof ($handle_OBS)) && (! $stopflag)) 
         {
         $linie = fgets($handle_OBS, 1024); 
         $astnr = trim(substr($linie, 0, 5)); 
         $astname = trim(substr($linie, 5, 7)); 
         $year = trim(substr($linie, 15, 4)); 
         $month = trim(substr($linie, 20, 2)); 
         $day = trim(substr($linie, 23, 8)); 
         $alhr = trim(substr($linie, 32, 2)); 
         $almin = trim(substr($linie, 35, 2)); 
         $alsec = trim(substr($linie, 38, 5)); 
         $delsgr = trim(substr($linie, 44, 3)); 
         $delmin = trim(substr($linie, 48, 2)); 
         $delsec = trim(substr($linie, 51, 4)); 
         $obscode = trim(substr($linie, 77, 3)); 

         // remember first values to compare with...
         if ($first) {
             $astnr_first = $astnr; 
             $astname_first = $astname; 
//             $astcode_first = $astcode; 
             $obscode_first = $obscode; 
 //            $JD_start = JD(CAL_GREGORIAN, $month, floor($day), $year) + ($day - floor($day));
 		$JD_start = JD($year, $month, $day);
             $first = false; 
         }

         // don't test last empty lines...
         if (trim($linie) != "") {
            // test numeric characters in format...
            if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) ||
                !is_numeric($alhr) || !is_numeric($almin) || !is_numeric($alsec) || 
                !is_numeric($delsgr) || !is_numeric($delmin) || !is_numeric($delsec)) { 
//                      echo "Sorry, the observation file not in MPC format...<br />"; 
                      $ret .= "<br>Sorry, the observation file not in MPC format"; 
                      $stopflag = true; 
                }
            // test asteroid and observatory...
            if (($astnr != $astnr_first) || ($astname != $astname_first) || ($obscode != $obscode_first)) { 
//                      echo "Sorry, the asteroid and observatory must be uniques...<br />"; 
                      $ret .= "<br>Sorry, the asteroid and observatory must be uniques..."; 
                      $stopflag = true;
                }
   //         $JD_end = JD(CAL_GREGORIAN, $month, floor($day), $year) + ($day - floor($day)); 
   		$JD_end = JD($year, $month, $day);
         
	 } // don't test last empty lines

         } // end test obs file

  // test interval (NEODys allows only 500 points = 8.3 hrs)
  if (($JD_end - $JD_start) > 3.0) {
//     echo "Sorry, the observing interval exceeded max 3 days allowed by NEODyS...<br />"; OVIDIU 24 Feb 2016 new NEODyS allows for more steps (min 3 days x 1min) instead of former 500 steps (8.3h)
     $ret .= "<br>Sorry, the observing interval exceeded max 3 days allowed by NEODyS"; 
     $stopflag = true; 
  }

fclose($handle_OBS); 

// EXTRACT DATE LIMITS AND ASTEROID NUMBER FROM OBS FILE...

if (! $stopflag) 
{

//echo "Extract data from the observation file... <br />"; 

$handle_OBS = fopen (ROOTPATH."results/OMC/OBS.DAT", "r");
//  $handle_OBS = fopen ("OBS.DAT", "r"); 
   // skip header...
   $linie = fgets($handle_OBS, 1024); 
   while (trim($linie) <> "") $linie = fgets($handle_OBS, 1024);
 
   // read first obs line...
   $linie = fgets($handle_OBS, 1024); 
   $year0 = substr($linie, 15, 4); 
   $month0 = substr($linie, 20, 2);
   $day0 = substr($linie, 23, 2);
   $t0 = 24 * substr($linie, 25, 6); // hour with decimal
   $hour0 = floor($t0);
   $mins0 = floor(60 * ($t0 - $hour0));  

   // read obs code
   $obscode = substr($linie, 77, 3);

   // read ast number (if exists) from columns 0-5
   $nr_first = ""; 
   $nr_rest = "";
   $ast_number = "";
   $ast_tempdes  = "";
   $ast_NEODYS   = "";

   $ast_number = substr($linie, 0, 5);
   $ast_nr_1 = substr($linie, 0, 1);
   $nr_rest  = substr($linie, 1, 4);
      if (($ast_nr_1 == "0") || ($ast_nr_1 == "2") || ($ast_nr_1 == "3") || ($ast_nr_1 == "4") || ($ast_nr_1 == "5") || ($ast_nr_1 == "6") || ($ast_nr_1 == "7") || ($ast_nr_1 == "8") || ($ast_nr_1 == "9")) { // OVIDIU fix 24 Feb 2016
         $nr_first = $ast_nr_1; 
      } elseif ($ast_nr_1 == "A") {
         $nr_first = "10";
      } elseif ($ast_nr_1 == "B") {
         $nr_first = "11";
      } elseif ($ast_nr_1 == "C") {
         $nr_first = "12";
      } elseif ($ast_nr_1 == "D") {
         $nr_first = "13";
      } elseif ($ast_nr_1 == "E") {
         $nr_first = "14";
      } elseif ($ast_nr_1 == "F") {
         $nr_first = "15";
      } elseif ($ast_nr_1 == "G") {
         $nr_first = "16";
      } elseif ($ast_nr_1 == "H") {
         $nr_first = "17";
      } elseif ($ast_nr_1 == "I") {
         $nr_first = "18"; 
      } elseif ($ast_nr_1 == "J") {
         $nr_first = "19";
      } elseif ($ast_nr_1 == "K") {
         $nr_first = "20";
      } elseif ($ast_nr_1 == "L") {
         $nr_first = "21";
      } elseif ($ast_nr_1 == "M") {
         $nr_first = "22";
      } elseif ($ast_nr_1 == "N") {
         $nr_first = "23";
      } elseif ($ast_nr_1 == "O") {
         $nr_first = "24";
      } elseif ($ast_nr_1 == "P") {
         $nr_first = "25";
      } elseif ($ast_nr_1 == "Q") {
         $nr_first = "26";
      } elseif ($ast_nr_1 == "R") {
         $nr_first = "27";
      } elseif ($ast_nr_1 == "S") {
         $nr_first = "28";
      } elseif ($ast_nr_1 == "T") {
         $nr_first = "29";
      } elseif ($ast_nr_1 == "U") {
         $nr_first = "30";
      } elseif ($ast_nr_1 == "V") {
         $nr_first = "31";
      } elseif ($ast_nr_1 == "W") {
         $nr_first = "32";
      } elseif ($ast_nr_1 == "X") {
         $nr_first = "33";
      } elseif ($ast_nr_1 == "Y") {
         $nr_first = "34";
      } elseif ($ast_nr_1 == "Z") {
         $nr_first = "35";
      } elseif ($ast_nr_1 == "a") {
         $nr_first = "36";
      } elseif ($ast_nr_1 == "b") {
         $nr_first = "37";
      } elseif ($ast_nr_1 == "c") {
         $nr_first = "38";
      } elseif ($ast_nr_1 == "d") {
         $nr_first = "39";
      } elseif ($ast_nr_1 == "e") {
         $nr_first = "40";
      } elseif ($ast_nr_1 == "f") {
         $nr_first = "41";
      } elseif ($ast_nr_1 == "g") {
         $nr_first = "42";
      } elseif ($ast_nr_1 == "h") {
         $nr_first = "43";
      } elseif ($ast_nr_1 == "i") {
         $nr_first = "44";
      } elseif ($ast_nr_1 == "j") {
         $nr_first = "45";
      } elseif ($ast_nr_1 == "k") {
         $nr_first = "46";
      } elseif ($ast_nr_1 == "l") {
         $nr_first = "47";
      } elseif ($ast_nr_1 == "m") {
         $nr_first = "48";
      } elseif ($ast_nr_1 == "n") {
         $nr_first = "49";
      } elseif ($ast_nr_1 == "o") {
         $nr_first = "50";
      } elseif ($ast_nr_1 == "p") {
         $nr_first = "51";
      } elseif ($ast_nr_1 == "q") {
         $nr_first = "52";
      } elseif ($ast_nr_1 == "r") {
         $nr_first = "53";
      } elseif ($ast_nr_1 == "s") {
         $nr_first = "54";
      } elseif ($ast_nr_1 == "t") {
         $nr_first = "55";
      } elseif ($ast_nr_1 == "u") {
         $nr_first = "56";
      } elseif ($ast_nr_1 == "v") {
         $nr_first = "57";
      } elseif ($ast_nr_1 == "w") {
         $nr_first = "58";
      } elseif ($ast_nr_1 == "x") {
         $nr_first = "59";
      } elseif ($ast_nr_1 == "y") {
         $nr_first = "60";
      }


   $ast_number = $nr_first . $nr_rest; 
   echo $ast_number;

   // read asteroid temporary designation (if exists) from columns 6-13
   if (trim($ast_number) == "") {
	   $ast_tempdes = substr($linie, 5, 7);
	   echo $ast_tempdes; 

	   // transform for NEODyS (which does not understand packed format)
	   $ast_tempdes_1 = substr($ast_tempdes, 0, 1);
	      $nr1 = $ast_tempdes_1; 
	      if ($ast_tempdes_1 == "I") {
	          $nr1 = "18"; 
	      } elseif ($ast_tempdes_1 == "J") {
	          $nr1 = "19"; 
	      } elseif ($ast_tempdes_1 == "K") {
	          $nr1 = "20"; 
	      } 

	   $ast_tempdes_23 = substr($ast_tempdes, 1, 2);
	      $nr2 = $ast_tempdes_23; 

	   $ast_tempdes_4 = substr($ast_tempdes, 3, 1);
	      $nr3 = $ast_tempdes_4; 

	   $ast_tempdes_5 = substr($ast_tempdes, 4, 1);
	      $nr5 = $ast_tempdes_5; // pp ca e numar 0..9
	      if ($nr5 == "0") {
	         $nr5 = "";
	      }
	      if ($ast_tempdes_5 == "A") { // OVIDIU probably more cases needed to be added here
	          $nr5 = "10"; 
	      } elseif ($ast_tempdes_5 == "B") {
	          $nr5 = "11"; 
	      } elseif ($ast_tempdes_5 == "C") {
	          $nr5 = "12"; 
	      } elseif ($ast_tempdes_5 == "D") {
	          $nr5 = "13"; 
	      } elseif ($ast_tempdes_5 == "E") {
	          $nr5 = "14"; 
	      } elseif ($ast_tempdes_5 == "F") {
	          $nr5 = "15"; 
	      } elseif ($ast_tempdes_5 == "G") {
	          $nr5 = "16"; 
	      } elseif ($ast_tempdes_5 == "H") {
	          $nr5 = "17"; 
	      } elseif ($ast_tempdes_5 == "I") {
	          $nr5 = "18"; 
	      } elseif ($ast_tempdes_5 == "J") {
	          $nr5 = "19"; 
	      } elseif ($ast_tempdes_5 == "K") {
	          $nr5 = "20"; 
	      } elseif ($ast_tempdes_5 == "L") {
	          $nr5 = "21"; 
	      } elseif ($ast_tempdes_5 == "M") {
	          $nr5 = "22"; 
	      } elseif ($ast_tempdes_5 == "N") {
	          $nr5 = "23"; 
	      } elseif ($ast_tempdes_5 == "O") {
	          $nr5 = "24"; 
	      } elseif ($ast_tempdes_5 == "P") {
	          $nr5 = "25"; 
	      } elseif ($ast_tempdes_5 == "Q") {
	          $nr5 = "26"; 
	      } elseif ($ast_tempdes_5 == "R") {
	          $nr5 = "27"; 
	      } elseif ($ast_tempdes_5 == "S") {
	          $nr5 = "28"; 
	      } elseif ($ast_tempdes_5 == "T") {
	          $nr5 = "29"; 
	      } elseif ($ast_tempdes_5 == "U") {
	          $nr5 = "30"; 
	      } elseif ($ast_tempdes_5 == "V") {
	          $nr5 = "31"; 
	      } elseif ($ast_tempdes_5 == "W") {
	          $nr5 = "32"; 
	      } elseif ($ast_tempdes_5 == "X") {
	          $nr5 = "33"; 
	      } elseif ($ast_tempdes_5 == "Y") {
	          $nr5 = "34"; 
	      } 


	   $ast_tempdes_6 = substr($ast_tempdes, 5, 1);
	   $nr6 = $ast_tempdes_6; 
	   if (($nr6 == "0") && ($nr5 == "")) {
	       $nr6 = ""; // OVIDIU fixed second time 24 Feb 2016 I think it's good now!
	   }

	   $ast_tempdes_7 = substr($ast_tempdes, 6, 1);
	      $nr4 = $ast_tempdes_7; 

	   $ast_tempdes = $nr1 . $nr2 . $nr3 . $nr4 . $nr5 . $nr6;
   }

   if (trim($ast_number) != "") {
       $ast_NEODYS = trim($ast_number); 
   } else {
       $ast_NEODYS = trim($ast_tempdes); 
   }    

echo " " . $ast_NEODYS; 

   // read last obs line...
   while (!feof ($handle_OBS)) 
         {
         $linie = fgets($handle_OBS, 1024); 
         if (strlen(trim($linie)) != 0) 
            {
            $year1 = substr($linie, 15, 4); 
            $month1 = substr($linie, 20, 2);
            $day1 = substr($linie, 23, 2);
            $t1 = 24 * substr($linie, 25, 6); // hour with decimal
            $hour1 = floor($t1);
            $mins1 = floor(60 * ($t1 - $hour1)) + 1; // (+1 to include the last obs)
            }
         }
fclose($handle_OBS); 



// QUERY AND SAVE NEODYS EPHEMERIS FILE...
$ch = curl_init();

//echo "Query NEODyS server.... <br />"; 

//$str_query1 = "http://newton.dm.unipi.it/cgi-bin/neodys/ephpred.pl?";
//$str_query2 = "object=".$ast_NEODYS . "&year0=".$year0 . "&month0=".$month0 . "&day0=".$day0 . "&hour0=".$hour0. "&mins0=".$mins0; 
//$str_query3 = "&year1=".$year1 . "&month1=".$month1 . "&day1=".$day1 . "&hour1=".$hour1 . "&mins1=".$mins1; 
//$str_query4 = "&interval=1&intunit=m&code=".$obscode; 

if( $mins1 >= 60 ) {
 $hour1++;
 $mins1 -= 60;
}

if( $hour1 >=24 ) {
 $day1++;
 $hour1 -= 24;
}

$str_query1 = "http://newton.dm.unipi.it/neodys/index.php?pc=1.1.3.1";
$str_query2 = "&n=" . $ast_NEODYS . "&oc=" . $obscode . "&y0=" . $year0 . "&m0=" . $month0 . "&d0=". $day0 . "&h0=" . $hour0 . "&mi0=" . $mins0;
$str_query3 = "&y1=" . $year1 . "&m1=" . $month1 . "&d1=" . $day1 . "&h1=" . $hour1 . "&mi1=" . $mins1;
$str_query4 = "&ti=1" . "&tiu=minutes"; // use 1 min step to query NEODyS

$str_query = $str_query1 . $str_query2 . $str_query3 . $str_query4;
// echo "</br>" . $str_query; exit;
curl_setopt($ch, CURLOPT_URL, $str_query);
$curl_output = fopen(ROOTPATH."/results/OMC/neodys.htm", "w");
//$curl_output = fopen("neodys.htm", "w");
curl_setopt($ch, CURLOPT_FILE, $curl_output);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
curl_exec($ch);
curl_close($ch);
fclose($curl_output);



$handle_NEODyS = fopen (ROOTPATH."/results/OMC/neodys.htm", "r");
//$handle_NEODyS = fopen ("neodys.htm", "r");

$handle1 = fopen (ROOTPATH."/results/OMC/neoibo.htm", "w"); 
//$handle1 = fopen ("neoibo.htm", "w"); 

$NEODyS_reply = "";

while (!feof ($handle_NEODyS)) // read NEODyS HTML response (line by line)... 
      { 
      $linie = fgets($handle_NEODyS, 1024); 
      $NEODyS_reply = $NEODyS_reply . $linie; 
      }
fwrite($handle1, $NEODyS_reply); 
fclose($handle1); 

// INITIALIZE VARIABLES...

$t2 = 0; 
$handle_OBS = fopen (ROOTPATH."results/OMC/OBS.DAT", "r"); // observations file
//$handle_OBS = fopen ("OBS.DAT", "r"); // observations file
$handle_EPH = fopen (ROOTPATH."results/OMC/neoibo.htm", "r"); // ephemerides file
//$handle_EPH = fopen ("neoibo.htm", "r"); // ephemerides file
$handle_OminC = "";


// Write header in web page...
$ret .= '<br><table border="1" cellspacing="0" cellpadding="3">';
$ret .= "<th>UT</th><th>RA Observed</th><th>DEC Observed</th><th>RA Calculated</th><th>DEC Calculated</th><th>O-C RA</th><th>O-C DEC</th><th>O-C (\")</th>";


// READ FIRST LINE FROM EPHEM FILE...

// skip the header... 
$linie = trim(fgets($handle_EPH, 1024)); 
while (substr($linie, strlen($linie)-5, 5) <> "=====") $linie = trim(fgets($handle_EPH, 1024)); 

// READ FIRST LINE FROM EPH FILE... (t1 = left margin ephem)
$linie = fgets($handle_EPH, 1024); 
$t1 = substr($linie, 13, 6); // hour with decimal
$al1 = substr($linie, 22, 2) + substr($linie, 25, 2)/60.0 + substr($linie, 28, 6)/3600.0; 
$ss = substr($linie, 36, 1); if ($ss == "-") $s = -1; else $s = +1; 
$del1 = $s * (substr($linie, 37, 2) + substr($linie, 40, 2)/60.0 + substr($linie, 43, 5)/3600.0); 
// READ LINE BY LINE FROM OBS FILE...

// skip header...
$linie = fgets($handle_OBS, 1024); 
while (trim($linie) <> "") $linie = fgets($handle_OBS, 1024); 

// read following obs line...
while (!feof ($handle_OBS)) 
{
   $linie = fgets($handle_OBS, 1024); 
   $tobs = 24 * substr($linie, 25, 6); // hour with decimal
   $alobshr = substr($linie, 32, 2); 
   $alobsmin = substr($linie, 35, 2); 
   $alobssec = substr($linie, 38, 5); 
   $alobs = $alobshr + $alobsmin/60.0 + $alobssec/3600.0; 
   
   $delobssig = substr($linie, 44, 1); if ($delobssig == "-") $s = -1; else $s = +1; 
   $delobsgr = substr($linie, 45, 2); 
   $delobsmin = substr($linie, 48, 2); 
   $delobssec = substr($linie, 51, 4); 
   $delobs = $s * ($delobsgr + $delobsmin/60.0 + $delobssec/3600.0); 

   // READ NEXT LINE FROM EPH FILE... (t2 = right margin ephem)
   while ($t2 < $tobs)
   {
      $linie = fgets($handle_EPH, 1024); 
      $t2 = substr($linie, 13, 6); // hour with decimal
      $al2 = substr($linie, 22, 2) + substr($linie, 25, 2)/60.0 + substr($linie, 28, 6)/3600.0; 
      $ss = substr($linie, 36, 1); if ($ss == "-") $s = -1; else $s = +1; 
      $del2 = $s * (substr($linie, 37, 2) + substr($linie, 40, 2)/60.0 + substr($linie, 43, 5)/3600.0); 

     // INTERPOLATE... 
     if (($t1 < $tobs) && ($tobs < $t2))
        {
        $tdif = $tobs - $t1;
        $aldif = $tdif * ($al2 - $al1) / ($t2 - $t1);
        $deldif = $tdif * ($del2 - $del1) / ($t2 - $t1);
        $alcal = $al1 + $aldif;
        $delcal = $del1 + $deldif;

	// CALCULATE O-Cs... 
	$alomc = ($alobs - $alcal) * 3600.0 * 15.0 * cos($delcal*pi()/180.0);
	$delomc = ($delobs - $delcal) * 3600.0;
	$distomc = sqrt($alomc*$alomc + $delomc*$delomc); 

        // transforms alpha from decimal to hexa...
        $alfa = $alcal; 
        $alfahrs = $alfa; 
        $alfah = floor($alfahrs);
        $dif = ($alfahrs-$alfah)*60.0;
        $alfam = floor($dif);
        $alfas = ($dif-$alfam)*60.0;
/*
        if ($alfas > 59.999)
           {
           $alfas = 0.0;
           if ($alfam+1 == 60)
              {
              $alfam = 0;
              $alfah = $alfah+1;
              if ($alfah >= 24) $alfah = 0; 
              }
           }
        else $alfam = $alfam+1; 
        if ($alfam == 60) { $alfam = 0; $alfah = $alfah+1; }
*/

        // transforms delta from decimal to hexa...
        $delta = $delcal; 
        if ($delta < 0.0) 
           { 
           $deltasgn = '-'; 
           $delta1 = -$delta; 
           }
        else 
           { 
           $deltasgn = '+'; 
           $delta1 = $delta; 
           }
        $deltag = floor($delta1);
        $dif = ($delta1-$deltag)*60.0;
        $deltam = floor($dif);
        $deltas = ($dif-$deltam)*60.0;
        if ($deltas > 59.999) 
           {
           $deltas = 0.0;
           if ($deltam+1 == 60)
              {
              $deltam = 0;
              if ($deltasgn == '-') $deltag = $deltag-1; 
              else $deltag = $deltag+1; 
	      }
           else	$deltam = $deltam+1;
           }
        if ($deltam == 60) { $deltam = 0; $deltag = $deltag+1; }

	$handle_OminC .= sprintf("<tr><td>%8.5f</td><td>%02d %02d %02.3f</td><td>%1s%02d %02d %02.2f</td><td>%02d %02d %02.3f</td><td>%1s%02d %02d %02.2f</td><td align=\"center\">%5.2f</td>
	<td align=\"center\">%5.2f</td><td>%5.2f</td></tr>",$tobs,$alobshr, $alobsmin, $alobssec, $delobssig, $delobsgr, $delobsmin, $delobssec,$alfah, $alfam, $alfas, $deltasgn, $deltag, $deltam, $deltas,$alomc, $delomc, $distomc);
        } // interpolate

     $t1 = $t2;
     $al1 = $al2;
     $del1 = $del2;

   } // while 2 
} // while 1 


//fclose($handle_OminC);
fclose($handle_EPH);
fclose($handle_OBS);


// write results to client browser...


$ret .= $handle_OminC;
$ret .= "</table>";

} // if stopflag (due to format, interval)
else
{

   $ret .= "<br>  Please review the format and re-submit your query";

}

/*END*/
	}else
	{

	}
		
	return $ret;
}


?>

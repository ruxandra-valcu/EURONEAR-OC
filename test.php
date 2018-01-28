<?php
require_once("common/omc_tools.php");



$neaFilename = "MPCReportExample.txt";
//$mbaFilename = "MPC_single_asteroids_mba.txt";
//$twoFilename = "MPC_two_asteroids_mba_eu.txt";


$oc = omc(file_get_contents($neaFilename));
print_r($oc);																							//prints as PHP array
file_put_contents("test.txt", formatText($oc, array_keys(reset($oc))))  ;  			//prints as formatted text file

//print_r(formatCSV($oc, array_keys(reset($oc))))  ;				//prints as CSV
//print_r(formatHTMLTable($oc, array_keys(reset($oc))))  ;	//prints as HTML table

//this re-runs the omc function through a JSON-printing wrapper 
//useful if we want to have multiple tools in different languages talk to each other, possibly
//print_r(jsonify("omc", array($neaFilename), TRUE)); 
?>

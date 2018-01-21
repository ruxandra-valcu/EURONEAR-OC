<?php

require_once("common/omc_tools.php");

echo ("\nTest\n");

echo '<br><form method="POST" enctype="multipart/form-data">';
//echo '<label for="file">Observations (file in MPC format - example <a href="'.ROOTURL.'data/MPCReportExample.txt">here</a>): </label>';
echo '<label for="file">Observations (file in MPC format - example <a href="MPCReportExample.txt">here</a>): </label>';
echo '<input type="file" name="omc_file" id="omc_file" /><br><br>'; 
echo '<input type="hidden" name="_submit_check_omc" value="1"/>';
echo '<input type="submit" name="submit" value="Calculate O-Cs" />';    
echo '</form>';

if(isset($_POST["submit"])) {
    if ($_FILES["omc_file"]["error"] > 0) {
		echo "Upload error: " . $_FILES["omc_file"]["error"] . "<br />";
	}
	else {
	    echo "<br>Calculating...<br/>";
  	    $contents = file_get_contents($_FILES["omc_file"]["tmp_name"]);
        $oc = omc_2($contents);
        if (is_string($oc)) {
            echo "<br>" . $oc . "</br>";
        }
        //print_r($oc);
        echo formatHTMLTable($oc, array_keys(reset($oc))) ;
  	}
}

/*
if (array_key_exists('_submit_check_omc', $_POST)) {
    if ($_FILES["file"]["error"] > 0) {
		echo "Upload error: " . $_FILES["file"]["error"] . "<br />";
	}
	else {
  	    $contents = file_get_contents($_FILES["file"]["tmp_name"]);
  	}

# $neaFilename = "MPCReportExample.txt";
# $contents = file_get_contents($neaFilename);

$oc = omc_2($contents);
echo("\nTest2\n");
echo(formatHTMLTable($oc, array_keys(reset($oc))))  ;
echo("\nTest3\n");
*/
?>

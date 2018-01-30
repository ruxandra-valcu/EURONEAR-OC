<HTML>
<BODY style = "font-family:georgia;">

<?php include '../header.php';?>

<br>
<font style="font-size: 18pt;"><b>&nbsp; &nbsp; 
EURONEAR - Tools - O-C Calculator v.2
</b></font> 
<br><br>

Description: Derives observed minus calculated (O-C) residuals for known asteroids (NEAs or others) continuous observation.  <br> 
Input: Observing report (including header) including asteroid observations, in Minor Planet Centre (MPC) format. <br>
Output: Offset table showing for each object the O-C in right ascension and declination. <br>
Queries: The <a href="http://newton.dm.unipi.it/neodys/">NEODyS</a> and <a href="http://hamilton.dm.unipi.it/astdys/">AstDyS</a> 
         services (Milani et al., Univ Pisa). 

<br>

<!----------------------------------------->
<!----- RUXANDRA PHP CODE STARTS HERE ----->
<!----------------------------------------->


<?php

require_once("common/omc_tools.php");

// echo ("\nTest\n");

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
        $oc = omc($contents);
        if (is_string($oc)) {
            echo "<br>" . $oc . "</br>";
        }
        //print_r($oc);
        echo formatHTMLTable($oc, array_keys(reset($oc))) ;
  	}
}
?>


<!----------------------------------------->
<!------ RUXANDRA PHP CODE END HERE ------->
<!----------------------------------------->


<!------- TOOL PAGE FOOTER NOW -------->
<br>
Reference: Please include the following acknowledgement in any publication using this service: <br>
- Include in the acknowledgement: "This paper used the EURONEAR O-C Calculator v.2 \footnote{http://www.euronear.org/tools/omc2.php}". <br>

<br>
Author: Ruxandra Valcu (2018)
<br><br>

</BODY>
</HTML>


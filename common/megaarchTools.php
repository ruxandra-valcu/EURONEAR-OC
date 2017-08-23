<?php

function getInstrumentNames($dao,$selection) {	
	$all = !(is_array($selection) && sizeof($selection)>0);
	$tempres = $dao->getInstrumentsWithDetails();
	$first = true;
	$listBuild = "";
	foreach($tempres as $line) {		
		if($all || in_array($line["id"],$selection)) {
			if(!$first) {
				$listBuild.=",";
			}
			$listBuild.=$line["name"];
			$first = false;
		}		
	}
	return $listBuild;
}


function buildArchieveSelector($dao,$selection,$hiddenFields=array()) {	
	if(is_array($selection) && sizeof($selection)>0 ) {
		$selected = $selection;
	} else {
		$selected = array();
	}
	$tempres = $dao->getInstrumentsWithDetails();
	$res = array();
	$newline = array();
	$newline["ID"] = 0;
	$jsBuild = "";
	$i=1;
	foreach($tempres as $line) {
		$jsBuild.=sprintf("document.getElementById(\"archcb%d\").checked = false;",$i++);
	}
	$newline["Instrument"]= sprintf("<input type=checkbox id=archcb0 name=arch_sel[] value=%d onclick='%s' %s>%s",0,$jsBuild,(in_array(0,$selected))?"checked":"","ALL Instruments");
	$newline["AΩA"]= "";
	$newline["FOV(sq.')"]= "";
	$newline["Start Date"]= "";
	$newline["End Date"]= "";
	$newline["Images"]= "";
	if(!in_array("Magnitude",$hiddenFields)) {
		$newline["Mag Limit"]= "";
	}
	if(!in_array("MPC",$hiddenFields)) {
		$newline["MPC Code"]= "";
	}
	//for(var i=0;i<%d;i++) { document.getElementsById('archcb'+'%d').checked = false; }
	$res[] = $newline;
	foreach($tempres as $line) {	
		$newline = array();
		$id = $line["id"];
		$newline["ID"] = $id;
		$newline["Instrument"]= sprintf("<input type=checkbox id=archcb%d name=arch_sel[] value=%d %s onclick='document.getElementById(\"archcb0\").checked = false;'><a href=%s target=_blank>%s</a>",$id,$id,(in_array($id,$selected))?"checked":"",$line["URL"],$line["name"]);//$line["abbreviation"]);
	//$newline["Name"]= sprintf("<A href=%s>%s</A>",$line["URL"],$line["name"]);
		$newline["AΩA"]= addRightAlign(sprintf("%.2f",$line["area"]*$line["items_count"]*$line["fieldX"] * $line["fieldY"]));
		$newline["FOV(sq.')"]= addRightAlign(sprintf("%.2f",$line["fieldX"] * $line["fieldY"] * 3600));
		$newline["Start Date"]= addRightAlign(jdtodatestr($line["start_date"]));
		$newline["End Date"]= addRightAlign(jdtodatestr($line["end_date"]));
		//$newline["Interval"]=jdtodatestr($line["start_date"])."->".jdtodatestr($line["end_date"]);
		$newline["Images"]= addRightAlign($line["items_count"]);
		if(!in_array("Magnitude",$hiddenFields)) {
			$newline["Mag Limit"]= $line["magnitude_limit"];
		}
		if(!in_array("MPC",$hiddenFields)) {
			$newline["MPC Code"]= $line["MPC_code"];				
		}
		$res[] = $newline;
	}
	printArrayInTable($res);
	return;
}



//printArrayInTable($dao->getInstruments());
function getPointMatches($dao,$ra,$dec,$instruments,$isRaw,$maglim,$safety) {
	$safetyBand = $safety;
	$field = $dao->getMaxField() + $safetyBand;
	if(is_array($instruments) && sizeof($instruments)>0 ) {
		$instrlist = implode(",",$instruments);
	} else {
		$instrlist = null;
	}
	$tempres = $dao->getMatchesAround($ra,$dec,$field,$field,$instrlist);
	$res = array();
	foreach($tempres as $line) {
		$newline = array();
		$fieldX = $line["fieldX"] + 2*$safetyBand;
		$fieldY = $line["fieldY"] + 2*$safetyBand;
		$foundRA = $line["right_ascension"];
		$foundDEC = $line["declination"];
		$dist = sqrt(pow(($foundRA-$ra)*15,2)+pow($foundDEC-$dec,2));
		$distpercent = ($dist*100)/( sqrt(pow(($fieldX/2),2)+pow(($fieldY/2),2)) );
		$newline["Name"]= $line["name"];
		if(!$isRaw) {
			$url = $line["URL"];
			/*if(strpos($url,"noao")!==false) {
				//$url.= "/";
				$url.=$line["image_id"];
			}*/
			$newline["Image ID"]=sprintf("<A href=%s target=_blank>%s</A>",$url,$line["image_id"]);
		} else {
			$newline["Image ID"]= $line["image_id"];
		}
		
		$newline["Time"]= jdtodatetimestr($line["start_exposure_jd"]+0);
		$newline["Exp."]= addRightAlign($line["exposure_length"]);
		$imgmag = $line["magnitude_limit"];
		$newline["Mag."]= addRightAlign($imgmag);
		$newline["Filter"]= $line["filter"];		
		//$newline["URL"]= $isRaw?$line["URL"]:sprintf("<A href=%s>%s</A>",$line["URL"],$line["URL"]);
		$newline["Center dist."]= sprintf("%.2f (%.1f%%)",$dist,$distpercent);						 
		if( $ra > $foundRA - ($fieldX/30) && 
		    $ra < $foundRA + ($fieldX/30) && 
			$dec > $foundDEC - ($fieldY/2) && 
			$dec < $foundDEC + ($fieldY/2) && 
			(trim($maglim)=="" ||  $imgmag <= $maglim)  
			) {
			$res[] = $newline;
		} 
		
	}
	return $res;
}

?>

<?php
function jdtodatetimestr($jd) {
	$jdtemp = jdtounix($jd+0.5);
	$time = (($jd+0.5) - (int)($jd+0.5))*86400;
	return gmdate("Y/m/d H:i:s", $jdtemp+$time);
}

function jdtodatestr($jd) {
	$jdtemp = jdtounix($jd+0.5);
	$time = (($jd+0.5) - (int)($jd+0.5))*86400;
	return gmdate("Y/m/d", $jdtemp+$time);
}

function nameResolver($name) {
	$ret = "";
	if($name!=null && trim($name)!="") {
		$ch = curl_init(); 
		$url = "http://vizier.cfa.harvard.edu/viz-bin/nph-sesame/-oi/SNV?".$name;
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch); 
		curl_close($ch);			
		$result_arr = explode("\n",$output);			
		if(is_array($result_arr)) {
			foreach($result_arr as $line) {
				//echo substr(trim($line),0,3);
				if(substr(trim($line),0,3)=="%J ") {
					$coordSplit = explode("=",trim($line));
					if( is_array($coordSplit) && sizeof($coordSplit)==2) {
						$ret = trim($coordSplit[1]);
					}
				}
			}
		} else {
			echo "Empty SIMBAD result";
		}
	} 
	return $ret;
}

function parseCoords($value) {
	$result = array();
	//$value = str_replace(" " ,"",$value);
	$value = trim( $value );
	$value = preg_replace('!\s+!', ' ', $value);
//$strarr = str_split($value);$i=0;
//foreach($strarr as $item) printf("char[%d]=%d %d<BR>",$i++,ord($item),$item);
	$tempSplit = explode(" ",$value);
	$firstSplit = array();
	/*if(sizeof($tempSplit)%2!=0) {
		return "Missing coordinates!";
	}*/
	
	$firstCoord = "";
	foreach($tempSplit as $item){
		$firstChar = substr(trim($item),0,1);
		if(is_numeric($firstChar) || $firstChar=="+" || $firstChar=="-") {
			if($firstCoord=="") {
				$firstCoord = trim($item);
			} else {
				$firstSplit[] = sprintf("%s %s",$firstCoord,trim($item));
				$firstCoord = "";
			}
		} else {
			$rescoord = nameResolver(trim($item));
			if($rescoord!="") {
				$rescoord = trim(preg_replace('!\s+!', ' ', $rescoord));
				$tempa = explode(" ",$rescoord);
				if(sizeof($tempa)==6) {
					$firstSplit[] = sprintf("%s:%s:%s %s:%s:%s",$tempa[0],$tempa[1],$tempa[2],$tempa[3],$tempa[4],$tempa[5]);
				} else {
					return "Resulted SIMBAD coordonates not as expected:".$rescoord;
				}
			} else {
				return "Coords issue:Unable to understand object :".$item;
			}
		}
	}
	
	if($firstCoord!="") {
		return "Found not paired coordonates";
	}
	
	//print_r($firstSplit);
/*	for( $i = 0; $i < sizeof($tempSplit) ; $i+=2 ) {
		$firstSplit[] = sprintf("%s,%s",$tempSplit[$i],$tempSplit[$i+1]);
	}*/

//	$firstSplit = explode("\r",$value);
	//echo "<TEXTAREA>".$value."</TEXTAREA>";
	if( sizeof($firstSplit) > 0 ) {
		foreach( $firstSplit as $line ) {
			$line = trim($line);
			$secondSplit = explode(" ",$line);
			if( sizeof($secondSplit)==2 ) {				
					$thirdSplitLeft = explode(":",$secondSplit[0]);
					$thirdSplitRight = explode(":",$secondSplit[1]);

					if( sizeof($thirdSplitLeft)<1 ) {						
						$asc_h = $secondSplit[0];
						//return "Error parsing:".$secondSplit[0]." Expected : separated numbers";
					} else {					
						$asc_h = $thirdSplitLeft[0];
					}
					$asc_m = sizeof($thirdSplitLeft) > 1 ? $thirdSplitLeft[1] : "0";
					$asc_s = floatval(sizeof($thirdSplitLeft) > 2 ? $thirdSplitLeft[2] : "0");
					//echo $asc_s;
					
					if(!is_numeric($asc_h) || !is_numeric($asc_m) || !is_numeric($asc_s ) ) {
						return "Error parsing:".$secondSplit[0]." Expected : separated numbers. One of the next strings are not numbers".$asc_h.",".$asc_m.",".$asc_s;
					}
					
					if( sizeof($thirdSplitRight)<1 ) {
						
						$dec_d = $secondSplit[1];
						//return "Error parsing:".$secondSplit[1]." Expected : separated numbers";
					} else {
						$dec_d = $thirdSplitRight[0];
					}

					$dec_m = sizeof($thirdSplitRight) > 1 ? $thirdSplitRight[1] : "0";
					$dec_s = floatval(sizeof($thirdSplitRight) > 2 ? $thirdSplitRight[2] : "0");

					if(!is_numeric($dec_d) || !is_numeric($dec_m) || !is_numeric($dec_s ) ) {
						return "Error parsing:".$secondSplit[1]." Expected : separated numbers. One of the next strings are not numbers".$dec_d.",".$dec_m.",".$dec_s;
					}
					
					$resline = array();
					$resline['ASC'] = '0' + $asc_h + ($asc_m / 60) + ($asc_s/3600);
					$resline['DEC'] = ('0' + abs($dec_d) + ($dec_m / 60) + ($dec_s/3600));
					$resline['original'] = $line;
					if( $dec_d < 0 ) {
						$resline['DEC'] = (-1)*$resline['DEC'];
					} else {
						if( ($dec_d == 0 ) && strpos($secondSplit[1],"-")!==false ) {
							$resline['DEC'] = (-1)*$resline['DEC'];
						}
					}
					$result[] = $resline;
					
			} else {
				return "Error parsing:".$secondSplit." Expected space separated coords";
			}
		}
		return $result;
	} else {
		return "Empty input";
	}
} 

?>
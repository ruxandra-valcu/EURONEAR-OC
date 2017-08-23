<?php


function printArrayInTable($arr) {
	$headerPrinted = false;
	echo "<TABLE border=1 width=100%>\n";
	foreach( $arr as  &$line) {
		echo "<TR>\n";
			if( !$headerPrinted ) {
				$headerPrinted = true;
				foreach( $line as $key => $val ) {
					printf("<TD><B>%s</B>",$key);
				}
				echo "<TR>\n";
			}
		
			foreach( $line as $key => $val ) {
				printf("<TD>%s",$val);
			}

	}
	echo "</TABLE>\n";
	return;
}

function printArrayAsCSV($arr) {
	$headerPrinted = false;
	foreach( $arr as  &$line) {		
			if( !$headerPrinted ) {
				$headerPrinted = true;
				$first = true;
				foreach( $line as $key => $val ) {
					if(!$first) {
						echo ",";
					}
					printf("%s",$key);
					$first = false;					
				}
				echo "\n";
			} else {
				echo "\n";
			}
		
			$first = true;
			foreach( $line as $key => $val ) {
					if(!$first) {
						echo ",";
					}
					printf("%s",$val);
					$first = false;					
			}

	}
	echo "\n";
	return;
}

function printArrayWithLegend($array,$legend) {
	echo "<TABLE>\n<TR><TD width=80%>\n";
	printArrayInTable($array);
	echo "</TD><TD style=vertical-align:top>\n";
	echo "<TABLE border=1><TR><TD><B>Legend</b></TD></TR>\n<TR><TD>\n";
	echo $legend;
	echo "</TD></TABLE>\n";
	echo "</TD></TABLE>\n";
}

function addRightAlign($text) {
	return sprintf("<div align=right>%s</div>",$text);
}


?>

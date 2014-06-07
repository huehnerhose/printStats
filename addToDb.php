<?php
require("db.php");

function prependZero($int){
	if(strlen($int) == 1)
		$int = "0".$int;
	return $int;
}

if($_REQUEST["format"] == "accsnmp"){

	accsnmpLog2db( file_get_contents("/tmp/page_acc_log.1") );

}else if( $_REQUEST["format"] == "cups" ){

	cupsLog2db( file_get_contents("/tmp/page_log.1") );

}else if($_REQUEST["format"] == "test"){
	var_dump( getCostcenterByUser( "huehnerhose" ) );
}else{

	die("no given format");

}



function cupsLog2db($log){
	$lines = preg_split("/\n/", $log);
	
	$entries = array();

	foreach($lines as $line){
		
		// [0] => fh844_test
		// [1] => huehnerhose
		// [2] => 1586
		// [3] => [04/May/2014:11:02:13
		// [4] => +0200]
		// [5] => 33
		// [6] => 1
		// [7] => -
		// [8] => 130.149.69.248
		// [9] => https://www.isis.tu-berl...of
		// [10] => Professions_1-31.pdf
		// [11] => -
		// [12] => -

		$tmp = preg_split("/ /", $line);

		if( count($tmp) > 8 ){
			$entries[] = array(

				"printer" 	=> $tmp[0],
				"user"		=> $tmp[1],
				"job"		=> $tmp[2],
				"date"		=> preg_replace("/\[/", "", $tmp[3]),
				"page_num"	=> $tmp[5],
				"copies"	=> $tmp[6],
				"host"		=> $tmp[8]

			);
		}
		
	}

	$printJob["costcenter"] = getCostcenterByUser($printJob["user"]);

	$logByJobs = groupBy($entries, "job");
	$jobLog = standardizeJobs($logByJobs);
	$db = new DB;

	foreach( $jobLog as $printJob ){
		$db->insert( 
			$printJob["job"], 
			$printJob["printer"], 
			$printJob["date"]->format($db->dateFormat) ,
			$printJob["user"],
			$printJob["pages"],
			$printJob["costcenter"]
		);

	}
}


function getCostcenterByUser($user){
	$db = new DB;
	$costcenter = $db->getUser2CCbyUser($user);
	return $costcenter;
}


function accsnmpLog2db( $log ){
	$lines = preg_split("/\n/", $log);
	$db = new DB;
	$logs = array();
	foreach ($lines as $line) {
		$job = preg_split("/ /", $line);
		
		// 	0 = printer
		// 	1 = user
		// 	2 = job id
		// 	3 = Date
		// 	4 = Timezone
		// 	5 = pages
		// 	6-8 = nn
		
		if(isset($job[3])){
			$time = split('\[', $job[3]);
			$time = split(':', $time[1]);
			$date = split('\/', $time[0]);
			$date[2] = substr($date[2], 1);
			$date[0] = prependZero($date[0]);
			$date[2] = prependZero($date[2]);
			$time[1] = prependZero($time[1]);
			$time[2] = prependZero($time[2]);
			$time[3] = prependZero($time[3]);
		
			$dateObj = date_create_from_format("d M y G i s", $date[0].' '.$date[1].' '.$date[2].' '.$time[1].' '.$time[2].' '.$time[3]);
			if(!$dateObj){
				print_r($date);
				print_r($time);
				echo $line, "\n";
			}
		}else{
			print $line;
		}	

		$db->insert($job[2], $job[0], $dateObj->format($db->dateFormat), $job[1], $job[5]);
	}


}

function standardizeJobs($jobsArray){

	$reducedJobSet = array();

	foreach( $jobsArray as $id => $jobArray ){
		$jobArray = unique($jobArray, "page_num");
		$job = $jobArray[0];
		unset( $job["page_num"] );
		unset( $job["copies"] );
		$job["pages"] = 0;
		$job["date"] = date_create_from_format("d/M/Y:G:i:s", $job["date"]);

		foreach( $jobArray as $pageEntry ){
			$job["pages"] += $pageEntry["copies"];
		}

		$reducedJobSet[] = $job;

	}

	return $reducedJobSet;

}

function unique($array, $key){
	$return = array();
	$keys = array();
	foreach($array as $data){
		if(!in_array($data[$key], $keys)){
			$keys[] = $data[$key];
			$return[] = $data;
		}
	}

	return $return;
}

function groupBy( $array, $key ){


	if(!is_array($array)){
		die("only arrays accepted");
	}

	if(!isset($key)){
		die("no group attribute given");
	}

	$returnArray = array();

	foreach( $array as $value ){

		if(isset( $value[$key] )){

			$vkey = $value[$key];

			if( !isset( $returnArray[$vkey] ) ){
				$returnArray[$vkey] = array();
			}

			$returnArray[$vkey][] = $value;

		}

	}

	return $returnArray;

}





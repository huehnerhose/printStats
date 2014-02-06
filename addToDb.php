<?php
require("db.php");

$db = new DB;

function prependZero($int){
	if(strlen($int) == 1)
		$int = "0".$int;
	return $int;
}

$log = file_get_contents("/tmp/page_acc_log.1");
$lines = preg_split("/\n/", $log);

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

	// echo $dateObj->format("Y-m-d H:i:s"), "\n";
}

// print_r($logs);
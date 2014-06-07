<?php 
	require("db.php");

	$json = file_get_contents("php://input");
	$data = json_decode($json);

	if(!isset($_REQUEST["user"]) && !isset($data->user)){
		die("Need Username");
	}

	$user = isset($data->user) ? $data->user : $_REQUEST["user"];
	$costcenter = isset($data->costcenter) ? $data->costcenter : $_REQUEST["costcenter"];

	$db = new DB;

	if(isset($costcenter)){
		$db->insertUser2CC($user, (int)($costcenter));
	}

	$costcenter = $db->getUser2CCbyUser($user);

	if(is_null( $costcenter )){
		die("No Costcenter for User: ".$user);
	}

	$uidString = implode( ",", $db->getUserPrintJobsWithoutCC( $user ) );

	$db->updatePrintLog($uidString, $costcenter);

?>
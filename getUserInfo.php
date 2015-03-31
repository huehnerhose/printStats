<?php

	if(!isset($_REQUEST["user"])){
		die("Fuck off");
	}

	$users = $_REQUEST["user"];

	if(!is_array($users) && !isset($_REQUEST["test"])){
		die("Wrong Data");
	}

	if(isset($_REQUEST["test"]))
		$users = array($users);


	require("ldapConfig.php");
	require("userInfoFunctions.php");
	$ldap = ldap_connect($ldapserver, $ldapport) or die("No connection");
	if($ldap){
		$ldapbind = ldap_bind($ldap, $ldapuser, $ldappw) or die ("Error trying to bind: ".ldap_error($ldap));
	}



	$output = array();

	require("db.php");
	$db = new DB;


	for($i = 0; count($users) > $i; $i++){
		$result = getRawUserData($users[$i], $ldap);

		if($result){
			$output[] = array(
				"user" => $users[$i],
				"displayname" => iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode(getDisplayName($result))),
				"costcenter" => $db->getUser2CCbyUser($users[$i]),
				"groups" => getGroups($result)
			);
		}


	}


 	$json = json_encode(array( "users" => $output ));

	echo $json;


?>
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


	function getRawUserData($user, $ldap){

		if($user === "" || is_null($user)){
			return false;
		}

		$firstChar = strtoupper(substr($user, 0, 1));

		$search = ldap_search(
			$ldap,
			"CN=".$user.",OU=".$firstChar.",OU=User,dc=win,dc=tu-berlin,dc=de",
			"(objectclass=*)"
		);

		if(!$search){
			return false;
		}

		$result = ldap_get_entries($ldap, $search);
		return $result;
	}

	function getGroups($result){

        $output = array();

        if(isset($result[0])){
        	if(isset($result[0]["memberof"])){
        		foreach( $result[0]["memberof"] as $key => $group){
        			if(is_int($key))
        				$output[] = $group;
        		}
        	}

        }



        return $output;
	}

	function getDisplayName($result){
        $displayname = ($result[0]["displayname"][0]);
        return $displayname;
	}


 	$json = json_encode(array( "users" => $output ));

	echo $json;


?>
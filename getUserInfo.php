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
		$ldapbind = ldap_bind($ldap, $ldapuser, $ldappw) or die ("Error trying to bind: ".ldap_error($ldapconn));
	}



	$output = array();

	require("db.php");
	$db = new DB;

	foreach($users as $user){

		$result = getRawUserData($user, $ldap);

		$output[] = array(
			"user" => $user,
			"displayname" => getDisplayName($result),
			"costcenter" => $db->getUser2CCbyUser($user),
			"groups" => getGroups($result)
		);
	}

	function getRawUserData($user, $ldap){
		$search = ldap_search($ldap, "CN=".$user.",OU=".strtoupper(substr($user, 0, 1)).",OU=User,dc=win,dc=tu-berlin,dc=de", "(objectclass=*)");
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

	    // $search = ldap_search($ldap, "CN=".$user.",OU=".strtoupper(substr($user, 0, 1)).",OU=User,dc=win,dc=tu-berlin,dc=de", "(objectclass=*)");
     //    $result = ldap_get_entries($ldap, $search);

        $displayname = ($result[0]["displayname"][0]);

        return $displayname;
	}
echo "here";
	echo serialize(array( "users" => $output ));

// 	$json = json_encode(array( "users" => $output ));
// var_dump($output);
	// echo $json;

?>
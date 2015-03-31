<?php


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
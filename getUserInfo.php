<?php

	if(!isset($_REQUEST["user"])){
		die("Fuck off");
	}

	require("ldapConfig.php");
	$ldap = ldap_connect($ldapserver) or die("No connection");
	if($ldap){
		$ldapbind = ldap_bind($ldapconn, $ldapuser, $ldappass) or die ("Error trying to bind: ".ldap_error($ldapconn));
	}

	if ($ldapbind) {
        echo "LDAP bind successful...<br /><br />";
    }

	require("db.php");
	$db = new DB;
	$userWithCC = $db->getUser2CC();

	$usernames = $_REQUEST["user"];

	$return = array();

	// Clean usernames from users we allready know
	foreach( $userWithCC as $user ){
		$index = array_search($user["username"], $usernames);
		if($index){
			unset($usernames[$index]);
		}
	}






?>
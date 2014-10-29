<?php
	session_start();
	if(!isset($_SESSION["user"])){
		die("Please Login!");
	}
	$ldaptree = "OU=User, DC=win, DC=tu-berlin, DC=de";
	$ldapserver = "win.tu-berlin.de";
	$ldapport = "389";
	$ldapuser = $_SESSION["user"] ."@".$ldapserver;
	$ldappw = $_SESSION["password"];


	/*
	$ldaphost = 'win.tu-berlin.de';
	$ldapport = 389;
	$ds = ldap_connect($ldaphost, $ldapport) or die ("Connection Failure LDAP");

	$user .= "@".$ldaphost;

	if(ldap_bind($ds, $user, $pw)){
		$search = ldap_search($ds, "CN=huehnerhose,OU=H,OU=User,dc=win,dc=tu-berlin,dc=de", "(objectclass=*)");
		$search = ldap_get_entries($ds, $search);
		if($search["count"] == 1){
			$groups = $search[0]["memberof"];
			foreach ($groups as $group) {
				$group = preg_split('/,/', $group);
				$group = $group[0];
				if($group == "CN=soz.itadmin"){
					return true;
				}
			}

		}
	}
	 */
?>

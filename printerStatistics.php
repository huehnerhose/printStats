<?php

// der hier muss noch aus dem Frontend Zeitdaten für Anfang und ende bekommen und die an die DB Funktion weitergeben

require("db.php");
$db = new DB;
$rows = $db->getAll();

if($_REQUEST["subset"]){
	$rows = array_slice($rows, 15, 10);
}

$json = json_encode($rows);
echo $json;

?>
<?php

// der hier muss noch aus dem Frontend Zeitdaten für Anfang und ende bekommen und die an die DB Funktion weitergeben

require("db.php");
$db = new DB;
$rows = $db->getAll();

$json = json_encode($rows);
echo $json;

?>
<?php

require("db.php");
$db = new DB;
$rows = $db->getUser2CC();

$json = json_encode($rows);
echo $json;

?>
<?php

require("db.php");
$db = new DB;
$rows = $db->getCostcenter();

$json = json_encode($rows);
echo $json;


?>
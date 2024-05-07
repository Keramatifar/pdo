<?php
define('DB_SERVER' , 'localhost');
define('DB_USERNAME' , 'root');
define('DB_PASSWORD' , '');
define('DB_DATABASE' , 'keramatifar');
define('DB_PERSISTENCY' ,'true');
define('PDO_DSN', 'mysql:host=' . DB_SERVER . ';dbname=' . DB_DATABASE);

include 'class.db.php';

$query = "SELECT * FROM users WHERE id = :id and username = :username";
$params = [
    ":id" => '1'
    ,":username" => 'gholi'
];
$result = DB::GetAll($query, $params);

$result = DB::GetRow($query, $params);

var_dump($result);
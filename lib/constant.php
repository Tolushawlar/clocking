<?php

date_default_timezone_set('Africa/Lagos');

// define("DB_SERVER", "localhost");
// define("DB_USER", "paamhgxr_clocking");//enter your database username
// define("DB_PASS", "Clocking@2026");   //databse password
// define("DB_NAME", "paamhgxr_clocking") clockit;//database name

// localhost:8889
define("DB_SERVER", "localhost:8889");
define("DB_USER", "root"); //enter your database username
define("DB_PASS", "root");   //databse password
define("DB_NAME", "Clocking"); //database name



define("NAIRA", '₦');
define("CTIME", time());



define("TODAY", date("Y-m-d"));


/**
 * Cookie Constants - these are the parameters

 */
define("COOKIE_EXPIRE", 60 * 60 * 24 * 730);  //365 days by default
define("COOKIE_PATH", "/");  //Avaible in whole domain

$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
$db->set_charset("utf8mb4");
$offset = "+01:00";
$db->query("SET time_zone='" . $offset . "';");

// $sql = $db->query("SELECT * FROM users ");
// while($row = $sql->fetch_assoc()){
//     echo strtoupper($row['firstname']) . "<br>";
// }

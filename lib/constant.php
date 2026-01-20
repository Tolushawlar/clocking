<?php

date_default_timezone_set('Africa/Lagos');

// Load environment variables from .env file
function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception('.env file not found at: ' . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\"');

            // Set as environment variable and make accessible via getenv()
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load .env file
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);

// Define database constants from environment variables
define("DB_SERVER", getenv('DB_SERVER'));
define("DB_USER", getenv('DB_USER'));
define("DB_PASS", getenv('DB_PASS'));
define("DB_NAME", getenv('DB_NAME'));



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

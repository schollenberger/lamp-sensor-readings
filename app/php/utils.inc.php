<?php

// Common startup tasks and utility functionses for php pages in this project

if (isset($_SERVER['SERVER_ADDR'])) {
	$server = $_SERVER['SERVER_ADDR'];
}
else {
	$server = "unknown";
}

// Open database connection (at the beginning of every page)
$db = open_db_connection($db_hostname, $db_port, $db_database, $db_username, $db_password);

// Logs debug info to the apache error file

function log_debug($log_text) {
	// Writes debug message to the Apache error file
	global $debug_log;  // defined outside in config.php
	// log message to apache error
	if ($debug_log) {
		file_put_contents('php://stderr', "Debug: [".$log_text."]\n");
	}
}

function escape_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function guidv4() {
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/*
 * define some functions that are not available in PHP 7
 */
// source: Laravel Framework
// https://github.com/laravel/framework/blob/8.x/src/Illuminate/Support/Str.php
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

/* Get header Authorization
 * from: https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens
 *
 */
function getAuthorizationHeader(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
				$headers = trim($_SERVER["Authorization"]);
				//log_debug("Found auth header in _SERVER[Authorization]");
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
				$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
				//log_debug("Found auth header in _SERVER[HTTP_AUTHORIZATION]");
		} elseif (function_exists('apache_request_headers')) {
				$requestHeaders = apache_request_headers();
				// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
				$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
				//log_debug("Found auth header via apache_request_headers()");
				if (isset($requestHeaders['Authorization'])) {
						$headers = trim($requestHeaders['Authorization']);
				}
		}
		return $headers;
}

function handle_PDO_exception($exception, $statement, $place) {
	//echo "SQL Error: ".$e->get_message()." <br>";
	error_log("INT102: SQL Exception occurred in".$place.".");
	//error_log("INT101: SQL Query: ".$statement->queryString);
	//error_log("INT101: SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]);
	//error_log("INT101: SQL Error Message: ".$statement->errorInfo()[2]);
	error_log("INT101: Exception: ".$exception);

	echo "<br><H1>SQL Exception occurred in ".$place."</H1>";
	echo "SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]."<br>";
	echo "SQL Error Message: ".$statement->errorInfo()[2]."<br>";
	http_response_code(500);
	die("<H2>Aborting request.<H2>");

}

function open_db_connection($hostname, $port, $database, $username, $password) {
	log_debug("Opening DB connection...");
	// Open a connection to the database
	$db = new PDO("mysql:host=$hostname;port=$port;dbname=$database;charset=utf8", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  return $db;
}

function update_user_login_ts($db, $username){
  $success = false;

	$sql = "UPDATE users SET ts_last_login = now() WHERE binary username = ?";

	$statement = $db->prepare($sql);
	try {
		$statement->execute(array($username));
		$rescount = $statement->rowcount();
	}
	catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "authenticate_user() - user check");
	}

  if ($rescount == 1) {
		$success = true;
	}
	return $success;
}
/* authenticate_user ():
 * Authenticates via username and password agains the db table 'users'.
 * !! Usernames with special characters have to be HTML encoded to avoid
 * !! cross-site scripting issues when displaying them.
 * It returns the username from the user db table as well
*/
function authenticate_user($db, $username, $password) {

  // simple sql queries with parameters are not save in a web app
	//$sql = "SELECT * FROM users WHERE BINARY username = '$username'";
	//$statement = $db->query($sql);
  // Let's use a prepared statement
  $sql = "SELECT * FROM users WHERE BINARY username = ?";

	//$sql = "SELECT * FROM users WHERE username like ?";  // for test purposes
	$statement = $db->prepare($sql);

	try {
		$statement->execute(array($username));
		$rescount = $statement->rowcount();
	}
	catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "authenticate_user() - user check");
	}

	$success = false;
	$db_username = $username;

	if ($rescount == 0) {
		log_debug("Username [".$username."] does not exist.");
	} elseif ($rescount > 1) {
		log_debug("Duplicated username [".$username."] - it exists ".$rescount." times.");
	}
	else {
		$row = $statement->fetch();
		if ($row['pw_hash'] == 'plain') {
			$hash_pw = $password;
		}
		else {
			$hash_pw = hash($row['pw_hash'], $username.$password);
		}
		if ($row['password'] == $hash_pw && ! $row['locked']) {
			$success = true;
			$db_username = $row['username'];
			if (update_user_login_ts($db, $db_username)) {
				log_debug("User [".$db_username."] logged in successfully.");
			}
			else {
				log_debug("Failed to update last_login timestamp for user [".$db_username."].");
			}
		}
		else {
			if ($row['locked']) {
				log_debug("Username [".$username."] is locked.");
			}
			else {
				log_debug("Username [".$username."]  - Incorrect password.");
				log_debug("Hash type: ".$row['pw_hash']."  - hashed PW: [".$hash_pw."]  - DB PW:  [".$row['password']."]." );
			}
		}
	}
	return array("success" => $success, "db_username" => $db_username);
}

/* authenticate_user ():
 * Authenticates via username and password agains the db table 'users'.
 * !! Usernames with special characters have to be HTML encoded to avoid
 * !! cross-site scripting issues when displaying them.
 * It returns the username from the user db table as well
*/
function authenticate_token($db, $token) {

	$success = false;
	$db_username = "";
	$rescount = 0;

	if (strlen($token) == 0) {
    log_debug("authenticate_token(): empty access token.");
	}
	else {
		$sql = "SELECT * FROM users WHERE BINARY access_token = ?";
	  //$sql = "SELECT * FROM users WHERE username like ?";  // for test purposes
		$statement = $db->prepare($sql);

	  try {
		  $statement->execute(array($token));
		  $rescount = $statement->rowcount();
		}
	  catch (PDOException  $e) {
			handle_PDO_exception($e, $statement, "authenticate_token() - access token check");
	  }
	}

	if ($rescount == 0) {
		log_debug("Cannot find entry for token [".$token."].");
	} elseif ($rescount > 1) {
		log_debug("Duplicated access token [".$token."] - it exists ".$rescount." times.");
	}
	else {
		$row = $statement->fetch();
		$db_username = $row['username'];
		if (! $row['locked']) {
			$success = true;
		}
		else {
			log_debug("Corresponding user [".$db_username."] is locked.");
		}
	}
	return array("success" => $success, "db_username" => $db_username);
}

function get_user($db, $username) {
	$sql = "SELECT * FROM users WHERE BINARY username = ?";
	//$sql = "SELECT * FROM users WHERE username like ?";  // for test purposes
	$statement = $db->prepare($sql);

	try {
		$statement->execute(array($username));
		$rescount = $statement->rowcount();
	}
	catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "get_user()");
	}

	$success = false;
  $row = array("success" => $success);

	if ($rescount == 0) {
		log_debug("Username [".$username."] does not exist.");
	} elseif ($rescount > 1) {
		log_debug("Duplicated username [".$username."] - it exists ".$rescount." times.");
	}
	else {
		$row = $statement->fetch();
		$row['success'] = true;
	}
	return $row;
}

function set_user_token($db, $username, $token) {
	$success = false;

	$sql = "UPDATE users SET access_token = ? WHERE BINARY username = ?";
	//$sql = "SELECT * FROM users WHERE username like ?";  // for test purposes
	$statement = $db->prepare($sql);

	try {
  $statement->execute(array($token, $username));
	$rescount = $statement->rowcount();
	}
	catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "set_user_token() - db update");
	}

	if ($rescount == 1) {
		$success = true;
	}
	else {
		log_debug("set_user_token(): Update command failed for username [".$username."] - result count = ".$rescount.".");
	}
	return $success;
}


function get_recent_sensor_values($db, $db_table, $count, $username) {
	log_debug("In get_recent_sensor_values():");
  if ($username) {
		log_debug("Getting latest $count records from database table $db_table for user $username.");
		// Geting the latest records from the upload_images table
		$sql = "SELECT * FROM $db_table WHERE username = '$username' ORDER BY timestamp DESC LIMIT $count";
		$statement = $db->prepare($sql);
	} else {
		log_debug("Getting latest $count records from database table $db_table - no username set.");
		// Geting the latest records from the upload_images table
		$sql = "SELECT * FROM $db_table ORDER BY timestamp DESC LIMIT $count";
		$statement = $db->prepare($sql);
  }
	$rows = array();
	try {
	  $statement->execute();
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		log_debug("SQL SELECT returned ".$statement->rowcount()." entries.");
  }
	catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "get_recent_sensor_values() - reading sensor data");
	}
	return $rows;
}

function add_sensor_reading($db, $username, $sensor_name, $location, $sensor_value, $battery_value) {
  // Add a new record to the sensor_readings table
	log_debug("Adding sensor reading to DB...");
  $sql = "INSERT INTO sensor_readings (username, sensor_name, location, value, battery) VALUES (?, ?, ?, ?, ?)";
  $params = array($username, $sensor_name, $location ,$sensor_value, $battery_value);
  $statement = $db->prepare($sql);

  $rowcount = 0;
  try {
    $statement->execute($params);
    $rowcount = $statement->rowcount();
  }
  catch (PDOException  $e) {
		handle_PDO_exception($e, $statement, "add_sensor_reading() - db insert");
  }
  return $rowcount;
}

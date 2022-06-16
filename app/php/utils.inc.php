<?php

// Common startup tasks and utility functionses for php pages in this project


$server   = $_SERVER['SERVER_ADDR'];

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

function open_db_connection($hostname, $port, $database, $username, $password) {
	log_debug("Opening DB connection...");
	// Open a connection to the database
	$db = new PDO("mysql:host=$hostname;port=$port;dbname=$database;charset=utf8", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  return $db;
}

function get_recent_sensor_values($db, $db_table, $count) {
	log_debug("In get_recent_sensor_values():");

	// Print a message so that the user knows these records come from the DB.
	echo "Getting latest $count records from database table $db_table.<br>";

	// Geting the latest records from the upload_images table
	$sql = "SELECT * FROM $db_table ORDER BY timestamp DESC LIMIT $count";
	$statement = $db->prepare($sql);
	$rows = array();
	try {
	  $statement->execute();
	  $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
		log_debug("SQL SELECT returned ".$statement->rowcount()." entries.");
  }
	catch (PDOException  $e) {
		error_log("INT001: SQL Exception occurred on reading sensor data table.");
    //error_log("INT001: SQL Query: ".$statement->queryString);
    //error_log("INT001: SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]);
    //error_log("INT001: SQL Error Message: ".$statement->errorInfo()[2]);
    error_log("INT001: Exception: ".$e);

		echo "<br><H1>SQL Exception occurred on reading sensor data</H1>";
    echo "SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]."<br>";
    echo "SQL Error Message: ".$statement->errorInfo()[2]."<br>";
	}
	return $rows;
}

function add_sensor_reading($db, $username, $sensor_name, $location, $sensor_value) {
  // Add a new record to the sensor_readings table
	log_debug("Adding sensor reading to DB...");
  $sql = "INSERT INTO sensor_readings (username, sensor_name, location, value) VALUES (?, ?, ?, ?)";
  $params = array($username, $sensor_name, $location ,$sensor_value);
  $statement = $db->prepare($sql);

  $rowcount = 0;
  try {
    $statement->execute($params);
    $rowcount = $statement->rowcount();
  }
  catch (PDOException  $e) {
    //echo "SQL Error: ".$e->get_message()." <br>";
    error_log("INT101: SQL Exception occurred on insert of a new sensor reading.");
    //error_log("INT101: SQL Query: ".$statement->queryString);
    //error_log("INT101: SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]);
    //error_log("INT101: SQL Error Message: ".$statement->errorInfo()[2]);
    error_log("INT101: Exception: ".$e);

    echo "<br><H1>SQL Exception occurred on insert of a new sensor reading</H1>";
    echo "SQLSTATE[".$statement->errorInfo()[0]."] ErrorNo [".$statement->errorInfo()[1]."<br>";
    echo "SQL Error Message: ".$statement->errorInfo()[2]."<br>";
    die("<H2>Aborting request.<H2>");
  }
  return $rowcount;
}

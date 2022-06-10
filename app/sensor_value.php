<?php

// Insert Sensor value to DB.

include("config.php");
session_start();

// Page header
?>

<html>
  <head>
     <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
     <title>Scalable Web Application</title>
   </head>
<body>


<?php
/*
 * The first part handles application logic.
 *
 */

$server   = $_SERVER['SERVER_ADDR'];
$db = open_db_connection($db_hostname, $db_port, $db_database, $db_username, $db_password);

function log_debug($log_text)
{
	global $debug_log;
	// log message to apache error
	if ($debug_log)
	{
		file_put_contents('php://stderr', "Debug: [");
	  file_put_contents('php://stderr', $log_text);
		file_put_contents('php://stderr', "]\n");
	}
}

function open_db_connection($hostname, $port, $database, $username, $password)
{
	log_debug("Opening DB connection...");
	// Open a connection to the database
	$db = new PDO("mysql:host=$hostname;port=$port;dbname=$database;charset=utf8", $username, $password);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $db;
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

// Simulate latency
sleep($latency);

// log message to apache error
log_debug("Procesing page sensor_value.php...");

// Display HTTP Request attributes
log_debug("Get attributes:");
foreach($_GET as $key => $value)
{
	log_debug("_GET[$key] = $value");
}

log_debug("POST attributes:");
foreach($_POST as $key => $value)
{
	log_debug("_POST[$key] = $value");
}
log_debug("------------");

if (isset($_SESSION['username']))
{
  // User is logged in
  $username = $_SESSION['username'];
  log_debug("Logged in as user [$username] on server [$server]");
  echo "Hello user [$username].<br>";
  if (isset($_GET['sensor_read']) && ! empty($_POST))
  {
    log_debug("received a sensor read...");

    if(isset($_POST['sname']) && isset($_POST['slocation'])&& isset($_POST['svalue']))
    {
      $sname = $_POST['sname'];
      $slocation = $_POST['slocation'];
      $svalue = $_POST['svalue'];
      log_debug("... it is a valid HTTP-POST call.");
      if (! empty($sname) && ! empty($svalue) && is_numeric($svalue))
      {
        log_debug("... sensor read is valid: ($username,$sname,$slocation,$svalue)");
        $rc = add_sensor_reading($db, $username, $sname, $slocation, $svalue);
        if ($rc == 1) {
          echo "New sensor value has been added.<br>";
          echo "<table width=100% border = 1>";
            echo "<tr>";
              echo "<td>Username</td><td>Sensor Name</td><td>Location</td><td>Sensor Value</td>";
            echo "</tr><tr>";
            echo "<td>$username</td><td>$sname</td><td>$slocation</td><td>$svalue</td>";
            echo "</tr>";
          echo "<table>";
        }
        else {
          error_log("INT102: Unexpected error - SQL insert statement returned row count of $rc");
          echo "Unexpected error when inserting new sensor value - check server logs.<br>";
        }
      }
      else {
        echo "<br>Sensor Name and Sensor Value cannot be empty.<br>";
        echo "Sensor Value has to be numeric.<br><br>";
      }
    }
    else {
      echo "<br>Invalid form.<br><br>";
    }
  }
  else {
    echo "<br>Invalid request.<br><br>";
  }
}
else
{
  log_debug("Not logged in returning HTTP 401");
  echo "<H1>Please log in !!</H1><br>";
}

?>

<?php
/*
 *
 * The second part handles user interface.
 *
 */
echo "<a href='index.php'>Back</a>";

?>
</body>

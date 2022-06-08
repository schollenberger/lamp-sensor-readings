<?php
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
 * Insert Sensor value to DB.
 * The first part handles application logic.
 *
 */

include("config.php");
session_start();
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
	return $db;
}

function add_sensor_reading($db, $username, $sensor_name, $location, $sensor_value)
{
  // Add a new record to the sensor_readings table
	log_debug("Adding sensor reading to DB...");
  $sql = "INSERT INTO sensor_readings (username, sensor_name, location, value) VALUES (?, ?, ?, ?)";
  $statement = $db->prepare($sql);
	$statement->execute(array($username, $sensor_name, $location ,$sensor_value));
}

// Simulate latency
sleep($latency);

// log message to apache error
log_debug("in module: sensor_value.php");

if (isset($_SESSION['username']))
{
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
      if (! empty($sname) && ! empty($slocation) && ! empty($svalue))
      {
        log_debug("... sensor read is valid: ($username,$sname,$slocation,$svalue)");
        add_sensor_reading($db, $username, $sname, $slocation, $svalue);
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
        echo "<br>Incomplete data.<br><br>";
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

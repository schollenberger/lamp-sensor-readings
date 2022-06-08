<?php
/*
 *
 * The first part handles application logic.
 *
 */

include("config.php");
session_start();
$server   = $_SERVER['SERVER_ADDR'];
$db = open_db_connection($db_hostname, $db_port, $db_database, $db_username, $db_password);


function log_debug($log_text)
{
	// Writes debug message to the Apache error file
	global $debug_log;  // defined outside in config.php
	// log message to apache error
	if ($debug_log)
	{
		file_put_contents('php://stderr', "Debug: [".$log_text."]\n");
	}
}
// Simulate latency
sleep($latency);

log_debug("Procesing page...");

if (isset($_POST['username']))
{
	// This is a login request
	log_debug("Login request received...");
	process_login($_POST['username']);
}

if (isset($_GET['logout']))
{
	log_debug("Logout request received...");
	// This is a logout request
	process_logout();
}

function process_login($username)
{
	// Simply write username to session data
	$_SESSION['username'] = $username;
}

function process_logout()
{
	// Unset all of the session variables.
	$_SESSION = array();

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies"))
	{
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	}

	// Finally, destroy the session.
	session_destroy();
}

function open_db_connection($hostname, $port, $database, $username, $password)
{
	log_debug("Opening DB connection...");
	// Open a connection to the database
	$db = new PDO("mysql:host=$hostname;port=$port;dbname=$database;charset=utf8", $username, $password);
	return $db;
}


function retrieve_recent_uploads($db, $count)
{
	log_debug("In retrieve_recent_uploads():");

	// Print a message so that the user knows these records come from the DB.
	echo "Getting latest $count records from database.<br>";

	// Geting the latest records from the upload_images table
	$sql = "SELECT * FROM sensor_readings ORDER BY timestamp DESC LIMIT $count";
	$statement = $db->prepare($sql);
	$statement->execute();
	$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
	return $rows;
}

function open_memcache_connection($hostname)
{
	// Open a connection to the memcache server
	$mem = new Memcached();
	$mem->addServer($hostname, 11211);
	return $mem;
}

?>

<?php
/*
 *
 * The second part handles user interface.
 *
 */
echo "<html>";
echo "<head>";
echo "<META http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
echo "<title>Sensor Readings Application</title>";
//echo "<script src='demo.js'></script>";
echo "</head>";
echo "<body>";

if (isset($_SESSION['username']))
{
  $username = $_SESSION['username'];
	// This section is shown when user is login
	echo "<table width=100% border=0>";
	echo "<tr>";
		echo "<td><H1>$server</H1></td>";
		echo "<td align='right'>";
			echo "$username<br>";
			echo "<a href='index.php?logout=yes'>Logout</a>";
		echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<HR>";

	echo "This application displays sensor readings, that you can manually";
	echo "enter under your user name.<br>&nbsp;<br>";
	echo "<br>";
	echo "<form action='sensor_value.php?sensor_read=yes' method='post'>";
		echo "<table border=2>";
			echo "<tr>";
				echo "<td><label col='sensor'>Sensor Name:</label></td>";
				echo "<td><input type='text' name='sname' id='sname'></td>";
			echo "</tr><tr>";
				echo "<td><label col='user'>Location:</label></td>";
				echo "<td><input type='text' name='slocation' id='slocation'></td>";
			echo "</tr><tr>";
			        echo "<td><label col='user'>Sensor Value:</label></td>";
				echo "<td><input type='text' name='svalue' id='svalue'></td>";
			echo "</tr>";
		echo "</table>";
		echo "<input type='submit' value='Go' id='submit_button' name='submit_button' enabled>";
	echo "</form>";
}
else
{
	// This section is shown when user is not login
	echo "<table width=100% border=0>";
		echo "<tr>";
			echo "<td><H1>$server</H1></td>";
			echo "<td align='right'>";
				echo "<form action='index.php' method='post'>";
					echo "Enter Your Name: <br>";
					echo "<input type='text' id='username' name ='username' size=20><br>";
					echo "<input type='submit' value='login'/>";
				echo "</form>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";
	echo "<HR>";

	echo "Login to start uploading sensor values.<br>&nbsp;<br>";
}

// Get the most recent N images
if ($enable_cache)
{
	// Attemp to get the cached records for the front page
	$mem = open_memcache_connection($cache_server);
	$readings = $mem->get("front_page");
	if (!$readings)
	{
		// If there is no such cached record, get it from the database
		$readings = retrieve_recent_uploads($db, 10);
		// Then put the record into cache
		$mem->set("front_page", $readings, time()+86400);
	}
}
else
{
	// This statement get the last 10 records from the database
	$readings = retrieve_recent_uploads($db, 10);
}

// Display the sensor readings
echo "<br>&nbsp;<br>";
echo "<table width=100% border=1>";
foreach ($readings as $reading)
{
	$user = $reading["username"];
	$ts = $reading["timestamp"];
	$sname = $reading["sensor_name"];
	$loc = $reading["location"];
	$sval = $reading["value"];
	echo "<tr>";
	echo "<td align='left'>$user</td>";
	echo "<td align='center'>$ts</td>";
	echo "<td align='left'>$sname</td>";
	echo "<td align='left'>$loc</td>";
	echo "<td align='left'>$sval</td>";
	echo "</tr>";
}
echo "</table>";
echo "<HR>";

$session_id = session_id();
echo "<hr>";
echo "Session ID: ".$session_id;
echo "</body>";
echo "</html>";

log_debug("Procesing page...Done.");

?>

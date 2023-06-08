<?php
/*
 * Main page
 *
 * The first part handles application logic.
 *
 */

require("php/config.php");
session_start();
require("php/utils.inc.php"); // this include opens the DB connection.

// funcion definitions

function process_login($username) {
	// Simply write username to session data
	$_SESSION['username'] = $username;
}

function process_logout() {
	// Unset all of the session variables.
	$_SESSION = array();

	// If it's desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (ini_get("session.use_cookies")) 	{
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	}

	// Finally, destroy the session.
	session_destroy();
}

function open_memcache_connection($hostname) {
	// Open a connection to the memcache server
	$mem = new Memcached();
	$mem->addServer($hostname, 11211);
	return $mem;
}

function help_text() {
  $htxt = "This application captures sensor readings in a database. ";
	$htxt .= "The main page displays the last 10 sensor readings. ";
	$htxt .= "The app supports different users. They log in via their ";
	$htxt .= "username and password. ";
	$htxt .= "<br>";
	$htxt .= "Being logged in:";
	$htxt .= "<ul>";
	$htxt .= "<li>The list of sensor readings contains own values only.</li>";
	$htxt .= "<li>Users may add sensor reading using the Web UI.</li>";
	$htxt .= "<li>Sensor readings may be displayed in a diagram.</li>";
	$htxt .= "</ul>";
  $htxt .= "Water plant when moisture value is above 1.2";
  $htxt .= "<br>";
  $htxt .= "Charge battery when battery value is below of 3.7";
  //	$htxt .= "<br>&nbsp;<br>";
	$htxt .= "";
	$htxt .= "";

	return $htxt;
}

// Simulate latency
sleep($latency);

log_debug("Procesing page index.php...");

log_debug("_GET = ".print_r($_GET, true));
log_debug("_POST = ".print_r($_POST, true));
log_debug("_SESSION = ".print_r($_SESSION, true));
log_debug("params = ".print_r($_SESSION, true));

// Handle different page invokations
if (isset($_POST['username'])) {
	// This is a login request
	log_debug("Login request received for user [".$_POST['username']."] ...");

	$username = escape_input($_POST['username']);
  $success = false;

	if(isset($_POST['password'])) {
		$result = authenticate_user($db, $username, $_POST['password']);
		if ($result['success']) {
			$db_username = $result['db_username'];
			process_login($db_username);
			$success = true;
			log_debug("... success with db user [".$db_username."].");
		}
		else {
			log_debug("User [".$username."] - Login failed.");
		}
	}
	else {
		log_debug("User [".$username."] - No password field in request.");
		echo "<H2>No Password provided !!</H2>";
	}
	if (! $success) {
		echo "<H1>Login failed !!</H1>";
		echo "<a href='index.php'>Back</a>";
		die("<br");
	}
}

if (isset($_GET['logout'])) {
	log_debug("Logout request received...");
	// This is a logout request
	process_logout();
}

// retrieve data to display later for logged in user

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];
}
else {
	$username = Null;
}

if ($enable_cache) {
	// Attemp to get the cached records for the front page
	$mem = open_memcache_connection($cache_server);
	$readings = $mem->get("front_page");
	if (!$readings)
	{
		log_debug("Could not find sensor readings in memchache");
		// If there is no such cached record, get it from the database
		$readings = get_recent_sensor_values($db, $db_sensor_table, 10, $username);
		// Then put the record into cache
		$mem->set("front_page", $readings, time()+86400);
	}
}
else {
	// This statement get the last 10 records from the database
	$readings = get_recent_sensor_values($db, $db_sensor_table, 10, $username);
}


if ($username) {
	$reading = $readings[0];
	log_debug("First reading for user '$username':".print_r($reading, true));

	if (isset($_SESSION['sloc']) && strlen($_SESSION['sloc']) > 0 ) {
		$session_sloc = $_SESSION['sloc'];
		log_debug("Work Location found in session: [$session_sloc]");
	}
	else {
		$session_sloc = $reading["location"];
		log_debug("Work Location not found in session using default <$session_sloc>.");
	}

	if (isset($_SESSION['sname']) && strlen($_SESSION['sname']) > 0) {
		$session_sname = $_SESSION['sname'];
		log_debug("Sensor Name found in session: [$session_sname]");
	}
	else {
		$session_sname = $reading["sensor_name"];
		log_debug("Sensor Name not found in session using default <$session_sname>.");
	}

	if (isset($_SESSION['limit']) && strlen($_SESSION['limit']) > 0) {
		$session_limit = $_SESSION['limit'];
		log_debug("Limit found in session: [$session_limit]");
	}
	else {
		$session_limit = "";
		log_debug("Limit not found in session - parameter is optional");
	}
}

?>

<?php
/*
 *
 * The second part handles user interface.
 *
 */

log_debug("Displaying page 'index.php'...");

echo "<html>";
echo "<head>";
echo "<META http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
echo "<title>$page_title</title>";
//echo "<script src='demo.js'></script>";
echo "</head>";
echo "<body>";

if (isset($_SESSION['username'])) {

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
	echo help_text();
	echo "<HR>";
	echo "Add a sensor reading under your user name:<br>&nbsp;<br>";
	//echo "<br>";
	echo "<form action='sensor_values.php' method='post'>";
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
			echo "</tr><tr>";
				echo "<td><label col='user'>Battery Value:</label></td>";
				echo "<td><input type='text' name='sbattery' id='sbattery'></td>";
			echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "<input type='submit' value='Go' id='submit_button' name='submit_button' enabled>";
	echo "</form>";
	echo "<HR>";

	echo "Display diagram for a specific location and a sensor name (Point Limit default is 100):<br>&nbsp;<br>";
	//echo "<br>";
	echo "<form action='show_diagram.php' method='get'>";
		echo "<table border=2>";
			echo "<tr>";
				echo "<td><label col='user'>Location:</label></td>";
				echo "<td><input type='text' name='sloc' id='sloc' value='$session_sloc'></td>";
			echo "</tr><tr>";
				echo "<td><label col='sensor'>Sensor Name:</label></td>";
				echo "<td><input type='text' name='sname' id='sname' value='$session_sname'></td>";
			echo "</tr><tr>";
				echo "<td><label col='user'>Point Limit:</label></td>";
				echo "<td><input type='text' name='limit' id='limit' value='$session_limit'></td>";
			echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "<input type='submit' value='Display Diagram' id='submit_diagram' name='submit_diagram' enabled>";
	echo "</form>";
  //echo "<a href='show_diagram.php?sname=Temp&sloc=Kitchen'>Diagram</a>";
	echo "<HR>";

	echo "Last 10 sensor readings for user $username:";
	echo "<br>&nbsp;<br>";
}
else {
	// This section is shown when user is not logged in
	echo "<table width=100% border=0>";
		echo "<tr>";
			echo "<td width=\"60%\"><H1>$server</H1></td>";
			echo "<td align='left'>";
				echo "<form action='index.php' method='post'>";
					echo "Please login: <br>";
					echo "<nobr>User Name: <input type='text' id='username' name ='username' size=20></nobr>&nbsp;";
					echo "<nobr>Password: <input type='password' id='password' name ='password' size=20></nobr><br>";
					echo "<input type='submit' value='login'/>";
				echo "</form>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";
	echo "<HR>";
	echo help_text();
	echo "<HR>";
	echo "Login to start uploading sensor values or display diagrams.<br>&nbsp;<br>";
	echo "<HR>";
	echo "Last 10 sensor readings:";
	echo "<br>&nbsp;<br>";
}


// Display the sensor readings in a table
echo "<table width=100% border=1>";
foreach ($readings as $reading) {
	$user = $reading["username"];
	$ts = $reading["timestamp"];
	$sname = $reading["sensor_name"];
	$loc = $reading["location"];
	$sval = $reading["value"];
	$sbattery = $reading["battery"];
	echo "<tr>";
	echo "<td align='left'>$user</td>";
	echo "<td align='center'>$ts</td>";
	echo "<td align='left'>$sname</td>";
	echo "<td align='left'>$loc</td>";
	echo "<td align='left'>$sval</td>";
	echo "<td align='left'>$sbattery</td>";
	echo "</tr>";
}
echo "</table>";
echo "<HR>";

$session_id = session_id();
echo "<hr>";
echo "Session ID: ".$session_id;
echo "</body>";
echo "</html>";

log_debug("... done for this page.");

?>

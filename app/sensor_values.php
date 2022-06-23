<?php

// Insert Sensor value to DB.

require("php/config.php");
session_start();
require("php/utils.inc.php"); // this include opens the DB connection.

?>

<!-- Page header -->

<html>
<head>
  <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
  <title><?php echo $page_title; ?></title>
</head>
<body>

<?php
/*
 * The first part handles application logic.
 *
 */

// Simulate latency
sleep($latency);

// log message to apache error
log_debug("Procesing page sensor_values.php...");

//log_debug("_GET = ".print_r($_GET, true));
//log_debug("_POST = ".print_r($_POST, true));
//log_debug("_SESSION = ".print_r($_SESSION, true));

//log_debug(var_export($_SERVER, true));
//var_dump($_SERVER);

$username = '';
$authenticated = false;

if (isset($_SESSION['username'])) {
  // User is logged in
  $username = $_SESSION['username'];
  log_debug("Logged in as user [$username] on server [$server]");
  $authenticated = true;
}
elseif($auth_header = getAuthorizationHeader()) {
  log_debug("Authorization header provided: ".$auth_header);
  if (! str_starts_with($auth_header, 'Bearer')) {
    log_debug("Invalid authorization header: [".$auth_header."] - must start with Bearer.");
  }
  else {
    $auth_token = substr($auth_header, 7);
    // check for token in users table
    $res = authenticate_token($db, $auth_token);
    if ($res['success']) {
      $authenticated = true;
      $username = ($res['db_username']);
    }
  }
}

if (! $authenticated) {
  log_debug("Not authenticated returning HTTP 401");
  echo "<H1>Please log in !!</H1><br>";
  echo "<a href='index.php'>Back</a>";
  die("<br");
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  log_debug("Get sensor values for user [$username]");
  echo "Sensor valeus für user [".$username."].<br>";
  $sname = "Test Sensor";
  $slocation = "Test Location";
  $svalue = 22.67;

  echo "<table width=100% border = 1>";
    echo "<tr>";
      echo "<td>Username</td><td>Sensor Name</td><td>Location</td><td>Sensor Value</td>";
    echo "</tr><tr>";
    echo "<td>Test$username</td><td>$sname</td><td>$slocation</td><td>$svalue</td>";
    echo "</tr>";
  echo "<table>";

}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
  log_debug("Processing new sensor read...");
  if(isset($_POST['sname']) && isset($_POST['slocation'])&& isset($_POST['svalue'])) {
    $sname = $_POST['sname'];
    $slocation = $_POST['slocation'];
    $svalue = $_POST['svalue'];
    // parameter check
    if (! empty($sname) && ! empty($svalue) && is_numeric($svalue))
    {
      log_debug("... sensor read is valid: ($username,$sname,$slocation,$svalue)");
      $rc = add_sensor_reading($db, $username, $sname, $slocation, $svalue);
      if ($rc == 1) {
        log_debug("New entry added: User: ".$username." - SensorName: ".$sname." - Location: ".$slocation." - Value: ".$svalue.".");
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
      http_response_code(400);
    }
  }
  else {
    echo "<br>Invalid POST request parameters.<br><br>";
    http_response_code(400);
  }
}
else {
  log_debug("Unhandled request to access_token.php - Session user [".$username."] Method [".$_SERVER['REQUEST_METHOD']."]");
}


?>

<?php
/*
 *
 * The second part handles user interface.
 *
 */
echo "<a href='index.php'>Back</a>";
log_debug("... done for this page.");
?>
</body>
</html>

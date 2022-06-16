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
log_debug("Procesing page sensor_value.php...");

log_debug("_GET = ".print_r($_GET, true));
log_debug("_POST = ".print_r($_POST, true));
log_debug("_SESSION = ".print_r($_SESSION, true));

if (! isset($_SESSION['username'])) {
  log_debug("Not logged in returning HTTP 401");
  echo "<H1>Please log in !!</H1><br>";
  echo "<a href='index.php'>Back</a>";
  die("<br");
}
else {
  // User is logged in
  $username = $_SESSION['username'];
  log_debug("Logged in as user [$username] on server [$server]");
  if (isset($_GET['sensor_read']) && ! empty($_POST)) {
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

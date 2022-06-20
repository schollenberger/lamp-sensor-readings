<?php

// REST functions around the access token of a logged in user.

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

// Simulate latency
sleep($latency);

// log message to apache error
log_debug("Procesing page access_token.php...");

log_debug("Received request: ".$_SERVER['REQUEST_METHOD']);

//var_dump($_SERVER);

//log_debug("_GET = ".print_r($_GET, true));
//log_debug("_POST = ".print_r($_POST, true));
//log_debug("_SESSION = ".print_r($_SESSION, true));

if (! isset($_SESSION['username'])) {
  log_debug("Not logged in returning HTTP 401");
  http_response_code(401);
  echo "<H1>Please log in !!</H1><br>";
  echo "<a href='index.php'>Back</a>";
  die("<br");
}

// User is logged in
$username = $_SESSION['username'];
log_debug("Logged in as user [$username] on server [$server]");

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $retval = get_user($db, $username);
  $token = $retval['access_token'];
  if (strlen($token) == 0 ) {
    echo "Access token of user [".$username."] is empty.";
    http_response_code(404);
  }
  echo $token."<br>";
  //var_dump($retval);
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // generate new token
  $token = guidv4();
  log_debug("Setting new token [".$token ."] for user [".$username."]");
  if (set_user_token($db, $username, $token)) {
    echo $token."<br>";
  }
  else {
    log_debug("Failed to set access new token for user [".$username."]");
    echo "Failed to set new access token for user [".$username."]<br>";
    http_response_code(500);
  }
}
else {
  log_debug("Unhandled request to access_token.php - Session user [".$username."] Method [".$_SERVER['REQUEST_METHOD']."]");
}

?>
<?php
/*
 *
 * HTML trailer for back button
 */
echo "<a href='index.php'>Back</a>";
log_debug("... done for this page.");
?>
</body>
</html>

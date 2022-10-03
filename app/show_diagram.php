<?php

/* Display Diagram of Moisture values
 * You need to be logged in in order to use this pages

 */

require("php/config.php");
session_start();
require("php/utils.inc.php"); // this include opens the DB connection.
require('php/get_moisture.inc.php');

if (! isset($_SESSION['username'])) {
  log_debug("Not logged in returning HTTP 401");
  echo "<H1>Please log in !!</H1><br>";
  echo "<a href='index.php'>Back</a>";
  die("<br");
}

// User is logged in
$username = $_SESSION['username'];
$sname = $_GET['sname'];
$sloc = $_GET['sloc'];
$limit = $_GET['limit'];

if(!$sname) {
  $sname = "%";
}
if(!$sloc) {
  $sloc = "Test";
}

log_debug("Logged in as user [$username] on server [$server]");
log_debug("Show diagram for sensor: [$sname] in location [$sloc] and limit [$limit].");

$table = get_selected_sensor_values($db, $db_sensor_table, $username, $sname, $sloc, $limit);

// The data is stored in varable $table - the rest is javascript
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Sensor Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
	  <link rel='shortcut icon' type='image/x-icon' href='/favicon.ico' />
  	<link rel='icon' type='image/x-icon' href='/favicon.ico' />
    <link rel="stylesheet" href="/css/bootstrap.css" media="screen">
    <link rel="stylesheet" href="/css/main.css">
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript">

    var jsonData = <?php echo json_encode($table); ?>;

    // Load the Visualization API and the piechart package.
    google.load('visualization', '1', {'packages':['corechart']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.setOnLoadCallback(drawChart);

    function drawChart() {

      // Create our data table out of JSON data loaded from server.
      var data = new google.visualization.DataTable(jsonData);
      var options = {
		  'height':500
        };
      // Instantiate and draw our chart, passing in some options.
      // Do not forget to check your div ID
      var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
      chart.draw(data, options);
    }
    </script>
  </head>
  <body>
    <div class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <a href='index.php'><H2>Back</H2></a>

        </div>
      </div>
    </div>

    <div class="container">
      <div class="page-header">
        <div class="row">
          <div class="col-lg-6">
		  </div>
          </div>
          <div class="col-lg-6">
        </div>
      </div>

	  <div class="main" id="content">
	  	<div class="col-lg-4">
	  	</div>
	  	<div class="col-lg-4">
	  	  	<h3 style="text-align:center"><?php echo $sloc?></h3>
			  <table class="table table-striped table-hover">

			  </table>
	  	</div>
	  	<div class="col-lg-4">
	  	</div>
	  </div>
	 </div>

	 <div class="container">
	  <div id="chart_div" style="width:100%"></div>
	 </div>

	 <div class="container">
      <footer>
        <div class="row">
          <div class="col-lg-12">
          </div>
        </div>
      </footer>
    </div>
    <script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>

  </body>
  <?php log_debug("... done for this page."); ?>

</html>

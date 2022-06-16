<?php
// Database connection parameters
$db_hostname = "localhost";
$db_port     = 3306;
$db_database = "sensor_data";
$db_sensor_table = "sensor_readings";

$db_username = "username";
$db_password = "password";

// Simulate latency, in seconds
$latency = 0;

//$debug_log = true;
$debug_log = false;

// Cache configuration
$enable_cache = false;
$cache_server = "dns-or-ip-of-memcached-server";

// common page properties
$page_title="Sensor Reading Application"

?>

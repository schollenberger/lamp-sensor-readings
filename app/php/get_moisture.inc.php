<?php

function get_selected_sensor_values($db, $db_table, $username, $sname, $sloc, $limit) {
  log_debug("In get_selected_sensor_values():");

  $table = array();
  $rows = array();
  if(!$limit) {
    $limit = 100;
  }
  try {

    $result = $db->query("SELECT timestamp, value FROM $db_table where username = '$username' and sensor_name like '$sname' and location like '$sloc' limit $limit ;");

    $table['cols'] = array(array('label' => 'Datetime', 'type' => 'string'),array('label' => $sname, 'type' => 'number'));

    foreach($result as $r) {
      $data = array();
      $data[] = array('v' => (string) $r['timestamp']);
      $data[] = array('v' => (float) $r['value']);

      $rows[] = array('c' => $data);
    }


    $table['rows'] = $rows;

  } catch(PDOException $e) {
      log_debug("PODException: ".$e);
      echo 'ERROR: ' . $e->getMessage();
  }
  /*
  try {
     $result2 = $conn->prepare("SELECT `temperature`,`humidity`, `datetime` from data;");

    $result2->execute();

  } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
  }
  */

  //echo "<br>Show reading:<br>";
  //echo json_encode($table)."<br>";
  return $table;
}
?>

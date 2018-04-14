<?php

// Configuration Options
$sqlFile = ""; // SQLite database file

// Open database
class PVOutputDB extends SQLite3 {
   function __construct($sqlFile) {
      $this->open($sqlFile);
   }
}
$db = new PVOutputDB($sqlFile);
if(!$db) {
   echo $db->lastErrorMsg();
} else {
   echo "Opened database successfully\n";
}

// Output sensor details in JSON format
$vals = array(
   'date' => 0, 
   'time' => 0, 
   'inverter_energy_day_total' => 0, 
   'inverter_power_live' => 0, 
   'inverter_voltage_live' => 0,
   'consumption_energy_day_total' => 0,
   'consumption_power_live' => 0,
   'meter_export_day_total' => 0,
   'meter_import_day_total' => 0,
   'meter_power_live' => 0,
   'meter_power_live_export' => 0,
   'meter_power_live_import' => 0
);

echo json_encode($vals);

?>

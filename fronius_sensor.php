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
   #echo "Opened database successfully\n";
}

// Query latest previous statistics
$results = $db->query('SELECT * FROM pvoutput ORDER BY date desc, time desc LIMIT 1');
$row = $results->fetchArray();

// Output sensor details in JSON format
$vals = array(
   'date' => $row['date'],
   'time' => $row['time'],
   'inverter_energy_day_total' => $row['iEnergyDayTotal'],
   'inverter_power_live' => $row['iPowerLive'],
   'inverter_voltage_live' => $row['iVoltageLive'],
   'consumption_energy_day_total' => $row['cEnergyDayTotal'],
   'consumption_power_live' => $row['cPowerLive'],
   'meter_export_day_total' => $row['mExportDayTotal'],
   'meter_import_day_total' => $row['mImportDayTotal'],
   'meter_power_live' => $row['mPowerLive'],
   'meter_power_live_export' => $row['mPowerLiveExport'],
   'meter_power_live_import' => $row['mPowerLiveImport']
);

echo json_encode($vals);

?>

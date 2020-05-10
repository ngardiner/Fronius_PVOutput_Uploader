#!/usr/bin/php
<?php

// Configuration Options
$dataManagerIP = ""; // base ip for stats. this is usually the IP of the WiFi / Ethernet Device
$sqlFile = ""; // SQLite database file
$pvOutputApiURL = "http://pvoutput.org/service/r2/addstatus.jsp?";
$pvOutputApiKEY = "";
$pvOutputSID = "";
$pvInverters = 1;   // number of inverters in string

// Inverter & Smart Meter API URLs
$inverterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetInverterRealtimeData.cgi?Scope=Device&DeviceID=%%id%%&DataCollection=CommonInverterData";
$meterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetMeterRealtimeData.cgi?Scope=Device&DeviceId=0";

// Define Date & Time
date_default_timezone_set("Australia/Melbourne");
$system_time= time();
$date = date('Ymd', time());
$time = date('H:i', time());

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

// Read Meter Data
$meterPowerLive = 0;
$meterImportTotal = 0;
$meterExportTotal = 0;
$meterVoltageLive = 0;

do {
    sleep(5);
    $meterJSON = file_get_contents($meterDataURL);
    $meterData = json_decode($meterJSON, true);
    if (empty($meterData["Body"]) || empty($meterData["Body"]["Data"]))
      break;
    $meterPowerLive = $meterData["Body"]["Data"]["PowerReal_P_Sum"];
    $meterImportTotal = $meterData["Body"]["Data"]["EnergyReal_WAC_Plus_Absolute"];
    $meterExportTotal = $meterData["Body"]["Data"]["EnergyReal_WAC_Minus_Absolute"];
    $meterVoltageLive = $meterData["Body"]["Data"]["Voltage_AC_Phase_1"];
} while (empty($meterPowerLive) || empty($meterImportTotal) || empty($meterExportTotal));

// Read Inverter Data
$inverterPowerLive = 0;
$inverterEnergyDayTotal = 0;
$inverterVoltageLive = 0;

for($i=1;$i<($pvInverters+1);$i++){
    sleep(2);
    echo "Reading Inverter $i \r\n";
    $inverterJSON = file_get_contents(str_replace("%%id%%","$i",$inverterDataURL));
    $inverterData = json_decode($inverterJSON, true);
    if (array_key_exists("PAC", $inverterData["Body"]["Data"])) {
      $inverterPowerLive += (int)($inverterData["Body"]["Data"]["PAC"]["Value"]);
    }
    $inverterEnergyDayTotal += (int)($inverterData["Body"]["Data"]["DAY_ENERGY"]["Value"]);
    if (array_key_exists("UAC", $inverterData["Body"]["Data"])) {
      $inverterVoltageLive += (int)($inverterData["Body"]["Data"]["UAC"]["Value"]);
    }

    // if invertor voltage has no output (no sun), use voltage data of smart meter
    if ($inverterVoltageLive == null) {
      $inverterVoltageLive += $meterVoltageLive;
    }

}

// Read Previous Days Meter Totals From Database
$results = $db->query('SELECT import, export FROM eod ORDER BY date desc LIMIT 1');
$row = $results->fetchArray();
$meterImportDayStartTotal = $row['import'];
$meterExportDayStartTotal = $row['export'];

// Calculate Day Totals For Meter Data
$meterImportDayTotal = $meterImportTotal - $meterImportDayStartTotal;
$meterExportDayTotal = $meterExportTotal - $meterExportDayStartTotal;

// Calculate Consumption Data
$consumptionPowerLive = $inverterPowerLive + $meterPowerLive;
$consumptionEnergyDayTotal = $inverterEnergyDayTotal + $meterImportDayTotal - $meterExportDayTotal;

// Calculate Live Import/Export Values
if ($meterPowerLive > 0) {
    $meterPowerLiveImport = $meterPowerLive;
    $meterPowerLiveExport = 0;
} else {
    $meterPowerLiveImport = 0;
    $meterPowerLiveExport = $meterPowerLive;
}

// Log statistics to database
$sql =<<<EOF
  INSERT INTO pvoutput
    (date,time,iEnergyDayTotal,iPowerLive,iVoltageLive,
     cEnergyDayTotal,cPowerLive,mExportDayTotal,
     mImportDayTotal,mPowerLive,mPowerLiveExport,mPowerLiveImport)
  VALUES
    ('$date','$time','$inverterEnergyDayTotal','$inverterPowerLive',
     '$inverterVoltageLive','$consumptionEnergyDayTotal',
     '$consumptionPowerLive','$meterExportDayTotal','$meterImportDayTotal',
     '$meterPowerLive','$meterPowerLiveExport','$meterPowerLiveImport')
EOF;

$ret = $db->exec($sql);
if(!$ret){
   echo $db->lastErrorMsg();
} else {
   echo "Values added to database successfully\n";
}

// Push to PVOutput
$pvOutputURL = $pvOutputApiURL
                . "key=" .  $pvOutputApiKEY
                . "&sid=" . $pvOutputSID
                . "&d=" .   $date
                . "&t=" .   $time
                . "&v1=" .  $inverterEnergyDayTotal
                . "&v2=" .  $inverterPowerLive
                . "&v3=" .  $consumptionEnergyDayTotal
                . "&v4=" .  $consumptionPowerLive
                . "&v6=" .  $inverterVoltageLive
                . "&v7=" .  $meterExportDayTotal
                . "&v8=" .  $meterImportDayTotal
                . "&v9=" .  $meterPowerLive
                . "&v10=" . $meterPowerLiveExport
                . "&v11=" . $meterPowerLiveImport;
file_get_contents(trim($pvOutputURL));

//Print Values to Console
Echo "\n";
Echo "d \t $date\n";
Echo "t \t $time\n";
Echo "v1 \t $inverterEnergyDayTotal\n";
Echo "v2 \t $inverterPowerLive\n";
Echo "v3 \t $consumptionEnergyDayTotal\n";
Echo "v4 \t $consumptionPowerLive\n";
Echo "v6 \t $inverterVoltageLive\n";
Echo "v7 \t $meterExportDayTotal\n";
Echo "v8 \t $meterImportDayTotal\n";
Echo "v9 \t $meterPowerLive\n";
Echo "v10 \t $meterPowerLiveExport\n";
Echo "v11 \t $meterPowerLiveImport\n";
Echo "\n";
Echo "Sending data to PVOutput.org \n";
Echo "$pvOutputURL \n";
Echo "\n";

// Update data file with new EOD totals
if ($system_time > strtotime('Today 11:55pm') && $system_time < strtotime('Today 11:59pm')) {
   
  // Log end of day totals to database
  $sql = "INSERT INTO eod (date,import,export) VALUES ('$date','$meterImportTotal','$meterExportTotal')"
  $ret = $db->exec($sql);
  if(!$ret){
    echo $db->lastErrorMsg();
  } else {
    echo "Values added to database successfully\n";
  }

}

$db->close();

?>

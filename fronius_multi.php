<?php
// Configuration Options
$dataManagerIP = ""; // base ip for stats. this is usually the IP of the WiFi / Ethernet Device
$dataFile = ""; 
$pvOutputApiURL = "http://pvoutput.org/service/r2/addstatus.jsp?";
$pvOutputApiKEY = "";
$pvOutputSID = "";
$pvInverters = 1;   // number of inverters in string
// Inverter & Smart Meter API URLs
$inverterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetInverterRealtimeData.cgi?Scope=Device&DeviceID=%%id%%&DataCollection=CommonInverterData";
$meterDataURL = "http://".$dataManagerIP."/solar_api/v1/GetMeterRealtimeData.cgi?Scope=Device&DeviceId=1";
// Define Date & Time
date_default_timezone_set("Australia/Brisbane");
$system_time= time();
$date = date('Ymd', time());
$time = date('H:i', time());
// Read Meter Data
do {
    sleep(3);
    $meterJSON = file_get_contents($meterDataURL);
    $meterData = json_decode($meterJSON, true);
    $meterPowerLive = $meterData["Body"]["Data"]["PowerReal_P_Sum"];
    $meterImportTotal = $meterData["Body"]["Data"]["EnergyReal_WAC_Plus_Absolute"];
    $meterExportTotal = $meterData["Body"]["Data"]["EnergyReal_WAC_Minus_Absolute"];
} while (empty($meterPowerLive) || empty($meterImportTotal) || empty($meterExportTotal));
// Read Inverter Data
sleep(1);
$inverterPowerLive = 0;
$inverterEnergyDayTotal = 0;
$inverterVoltageLive = 0;
$inverterEnergyDayTotal = 0;
for($i=0;$i<$pvInverters;$i++){
    sleep(2);
    echo "Reading Inverter $i \r\n";
    $inverterJSON = file_get_contents(str_replace("%%id%%","$i",$inverterDataURL));
    $inverterData = json_decode($inverterJSON, true);
    $inverterPowerLive += (int)($inverterData["Body"]["Data"]["PAC"]["Value"]);
    $inverterEnergyDayTotal += (int)($inverterData["Body"]["Data"]["DAY_ENERGY"]["Value"]);
    $inverterVoltageLive += (int)($inverterData["Body"]["Data"]["UAC"]["Value"]);
}
// Read Previous Days Meter Totals From Data File
if (file_exists($dataFile)) {
    echo "Reading data from $dataFile\n";
} else {
    echo "The file $dataFile does not exist, creating... \n";
    $saveData = serialize(array('import' => $meterImportTotal, 'export' => $meterExportTotal));
    file_put_contents($dataFile, $saveData);
}
$readData = unserialize(file_get_contents($dataFile));
$meterImportDayStartTotal = $readData['import'];
$meterExportDayStartTotal = $readData['export'];
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
Echo "Inverter Day Kwh     v1 \t $inverterEnergyDayTotal\n";
Echo "Inverter Power Watts v2 \t $inverterPowerLive\n";
Echo "Consumption Kwh      v3 \t $consumptionEnergyDayTotal\n";
Echo "Consumption Watts    v4 \t $consumptionPowerLive\n";
Echo "Inverter Voltage     v6 \t $inverterVoltageLive\n";
Echo "Export Kwh           v7 \t $meterExportDayTotal\n";
Echo "Import Kwh           v8 \t $meterImportDayTotal\n";
Echo "Meter Watts          v9 \t $meterPowerLive\n";
Echo "Export Watts        v10 \t $meterPowerLiveExport\n";
Echo "Import Watts        v11 \t $meterPowerLiveImport\n";
Echo "\n";
Echo "Sending data to PVOutput.org \n";
Echo "$pvOutputURL \n";
Echo "\n";
// Update data file with new EOD totals
if ($system_time > strtotime('Today 11:55pm') && $system_time < strtotime('Today 11:59pm')) {
$saveData = serialize(array('import' => $meterImportTotal, 'export' => $meterExportTotal));
file_put_contents($dataFile, $saveData);
}
?>

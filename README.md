# Fronius_PVOutput_Uploader
Upload data from Fronius Solar Inverter with Fronius Smart Meter to PVOutput.org, with added HomeAssistant REST sensor component.

Added Fronius Smart Meter support to a script originally authored by Terence Eden, with additional modifications by b33st, SkullKill and Scobber. 
https://shkspr.mobi/blog/2014/11/fronius-and-pvoutput/

## Changelog

   * 19 Feb 2017 - Voltage value taken from smart meter when the inverter is not producing power. thus giving voltage graph for 24 hours instead of just when the inverter was producing power.
   * 07 Apr 2018 - Modified to add local SQLite logging, so that HomeAssistant can query a local server rather than PVOutput.

# Cron Scheduling

The following commands will schedule the script to fetch data from the inverter every 5 minutes

[root@server ~]# chmod +x /var/www/html/fronius/fronius.php 

[root@server ~]# echo "*/5 * * * * root /var/www/html/fronius/fronius.php > /dev/null" > /etc/cron.d/fronius

## Running more frequently

The key limiting factor to the frequency that the script will run is the ability for PVOutput.org to accept data updates. An upcoming commit will allow the script to be run more frequently without updating the website.

# Database

This script will use a referenced SQLite3 database to store the data collected from the Fronius inverters, which can be accessed by calling the fronius_sensor.php script. See below for a HTTP sensor configuration for HomeAssistant which fetches these values.

## Database Schema

The SQLite3 database can be instatiated with the following command

```
echo "CREATE TABLE pvoutput (date text, time text, iEnergyDayTotal real, iPowerLive real, iVoltageLive real, cEnergyDayTotal real, cPowerLive real, mExportDayTotal real, mImportDayTotal real, mPowerLive real, mPowerLiveExport real, mPowerLiveImport real, PRIMARY KEY (date, time));" | sqlite3 /var/www/html/fronius/fronius.db3 
```

# HomeAssistant (HASS) Integration

The fronius_sensor.php file implements a JSON sensor response for HomeAssistant in order to provide real-time monitoring of fronius inverters. The following template 

```
sensor:
  - platform: rest
    resource: http://[server ip]/[script location]/fronius_sensor.php
    name: Fronius Inverter
    json_attributes:
      - date
      - time
      - inverter_energy_day_total
      - inverter_power_live
      - inverter_voltage_live
      - consumption_energy_day_total
      - consumption_power_live
      - meter_export_day_total
      - meter_import_day_total
      - meter_power_live
      - meter_power_live_export
      - meter_power_live_import
    value_template: '{{ value_json.time }}'
```

# FAQ

## Is there any need for the data file given a database is being specified?

For the moment, yes. The data file stores the last recorded values to enable delta values to be determined. In an upcoming commit, this file will be removed and the data retrieved from the DB instead.

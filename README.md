# Fronius_PVOutput_Uploader
Upload data from Fronius Solar Inverter with Fronius Smart Meter to PVOutput.org, with added HomeAssistant REST sensor component.

Added Fronius Smart Meter support to a script originally authored by Terence Eden, with additional modifications by b33st, SkullKill and Scobber. 
https://shkspr.mobi/blog/2014/11/fronius-and-pvoutput/

## Changelog

   * 19 Feb 2017 - Voltage value taken from smart meter when the inverter is not producing power. thus giving voltage graph for 24 hours instead of just when the inverter was producing power.
   * 07 Apr 2018 - Modified to add local SQLite logging, so that HomeAssistant can query a local server rather than PVOutput.

## Purpose

Accessing Fronius solar generation data in HomeAssistant is traditionally performed through the use of the PVOutput sensor. This requires a script to run regularly, pushing solar generation data from the local wifi interface up to the Internet, before fetching it again through HomeAssistant.

This is an inefficient way of obtaining this data, for the following reasons:

   * A script is already required to fetch and upload the data, however no local caching is performed, causing double-handing of data. (Note that in fairness, there is an inbuilt data upload mechanism built into the Fronius inverter, but it has been reported to cease uploading without warning, hence the creation of this script).
   * Any outage to internet access will both cause PVOutput data not to be updated and cause HomeAssistant to not be able to fetch the data.
   * A lack of fidelity and precision can occur associated with the conversion of the raw fronius data into specific PVOutput data fields and storage in the PVOutput dataabse.
   * API fetch and push limits restrict the potential refresh rate of the data when relying on the PVOutput site.
   
In order to resolve these concerns, the Fronius smart meter upload script that was forked for this project was modified to:

   * Record end of day utilization within the database, providing the capability to record generation and usage per day and removing the need for a local cache to store previous day data.
   * Provide a JSON-formatted local page to allow fetching of recorded data with a rate of up to 1 per 5 seconds.
   * Record the data locally in an SQLite3 database to remove the 

# Cron Scheduling

The following commands will schedule the script to fetch data from the inverter every 5 minutes

[root@server ~]# chmod +x /var/www/html/fronius/fronius.php 

[root@server ~]# echo "*/5 * * * * root /var/www/html/fronius/fronius.php > /dev/null" > /etc/cron.d/fronius

## Running more frequently

The key limiting factor to the frequency that the script will run is the ability for PVOutput.org to accept data updates. An upcoming commit will allow the script to be run more frequently without updating the website.

# Database

This script will use a referenced SQLite3 database to store the data collected from the Fronius inverters, which can be accessed by calling the fronius_sensor.php script. See below for a HTTP sensor configuration for HomeAssistant which fetches these values.

## Database Schema

The SQLite3 database can be instatiated with the following command:

```
cat schema.sql | sqlite3 /var/www/html/fronius/fronius.db3
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

Accessing individual sensors can be achieved through the use of template sensors:

```
- platform: template
  sensors:
  
    inverter_energy_day_total:
      friendly_name: Inverter Daily Total
      value_template: '{{ states.sensor.fronius_inverter.attributes.inverter_energy_day_total }}'
      unit_of_measurement: "kWh"
      
    inverter_power_live:
      friendly_name: Live Inverter Power Generation
      value_template: '{{ states.sensor.fronius_inverter.attributes.inverter_power_live }}'
      unit_of_measurement: "W"
      
    inverter_voltage_live:
      friendly_name: Live Inverter Voltage
      value_template: '{{ states.sensor.fronius_inverter.attributes.inverter_voltage_live }}'
      unit_of_measurement: "V"

    consumption_energy_day_total:
      friendly_name: Daily Energy Consumption
      value_template: '{{ states.sensor.fronius_inverter.attributes.consumption_energy_day_total }}'
      unit_of_measurement: "kWh"

    consumption_power_live:
      friendly_name: Live Consumption
      value_template: '{{ states.sensor.fronius_inverter.attributes.consumption_power_live }}'
      unit_of_measurement: "W"

    meter_export_day_total:
      friendly_name: Meter Export
      value_template: '{{ states.sensor.fronius_inverter.attributes.meter_export_day_total }}'
      unit_of_measurement: "kWh"

    meter_import_day_total:
      friendly_name: Meter Import
      value_template: '{{ states.sensor.fronius_inverter.attributes.meter_import_day_total }}'
      unit_of_measurement: "kWh"

    meter_power_live:
      friendly_name: Live Consumption at Meter
      value_template: '{{ states.sensor.fronius_inverter.attributes.meter_power_live }}'
      unit_of_measurement: "W"

    meter_power_live_export:
      friendly_name: Live Export at Meter
      value_template: '{{ states.sensor.fronius_inverter.attributes.meter_power_live_export }}'
      unit_of_measurement: "W"

    meter_power_live_import:
      friendly_name: Live Import at Meter
      value_template: '{{ states.sensor.fronius_inverter.attributes.meter_power_live_import }}'
      unit_of_measurement: "W"
```

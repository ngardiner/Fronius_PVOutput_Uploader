# Fronius_PVOutput_Uploader
Upload data from Fronius Solar Inverter with Fronius Smart Meter to PVOutput.org

Added Fronius Smart Meter support to a script originally authored by Terence Eden, with additional modifications by b33st, SkullKill and Scobber. 
https://shkspr.mobi/blog/2014/11/fronius-and-pvoutput/

19/02/2017    Voltage value taken from smart meter when the inverter is not producing power. thus giving voltage graph for 24 hours instead of just when the inverter was producing power.

Apr 2018 - Modified to add local SQLite logging, so that HomeAssistant can query a local server rather than PVOutput.

# Cron Scheduling

[root@server ~]# chmod +x /var/www/html/fronius/fronius.php 

[root@server ~]# echo "*/5 * * * * root /var/www/html/fronius/fronius.php" > /etc/cron.d/fronius

## Running more frequently

The key limiting factor to the frequency that the script will run is the ability for PVOutput.org to accept data updates. An upcoming commit will allow the script to be run more frequently without updating the website.

# Database

This script will use a referenced SQLite3 database to store the data collected from the Fronius inverters, which can be accessed by calling the fronius_fetch.php script. See below for a HTTP sensor configuration for HomeAssistant which fetches these values.

## Database Schema

The SQLite3 database can be instatiated with the following command

```
echo "CREATE TABLE pvoutput (date text, time text, iEnergyDayTotal real, iPowerLive real, iVoltageLive real, cEnergyDayTotal real, cPowerLive real, mExportDayTotal real, mImportDayTotal real, mPowerLive real, mPowerLiveExport real, mPowerLiveImport real, PRIMARY KEY (date, time));" | sqlite3 /var/www/html/fronius/fronius.db3 
```

# FAQ

## Is there any need for the data file given a database is being specified?

For the moment, yes. The data file stores the last recorded values to enable delta values to be determined. In an upcoming commit, this file will be removed and the data retrieved from the DB instead.

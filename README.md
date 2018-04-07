# Fronius_PVOutput_Uploader
Upload data from Fronius Solar Inverter with Fronius Smart Meter to PVOutput.org

Added Fronius Smart Meter support to a script originally authored by Terence Eden. 
https://shkspr.mobi/blog/2014/11/fronius-and-pvoutput/

19/02/2017    Voltage value taken from smart meter when the inverter is not producing power. thus giving voltage graph for 24 hours instead of just when the inverter was producing power.

Apr 2018 - Modified to add local SQLite logging, so that HomeAssistant can query a local server rather than PVOutput.

# How to schedule it to run every 5 min. and example of crontab

[root@server ~]# chmod +x /usr/local/sbin/fronius.php 

[root@server ~]# vi /etc/crontab 

*/5 * * * * root php /usr/local/sbin/fronius.php


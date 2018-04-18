CREATE TABLE pvoutput 
  (date text, time text, iEnergyDayTotal real, iPowerLive real, iVoltageLive real, cEnergyDayTotal real, 
   cPowerLive real, mExportDayTotal real, mImportDayTotal real, mPowerLive real, mPowerLiveExport real, 
   mPowerLiveImport real, PRIMARY KEY (date, time));
   
CREATE TABLE eod (date text, import real, export real, primary key(date));

CREATE TABLE state (parameter varchar(25), int_value int, string_value varchar(50), primary key(parameter));

INSERT INTO eod values ('20180101','0','0');
INSERT INTO state values ('last_upload','0','');

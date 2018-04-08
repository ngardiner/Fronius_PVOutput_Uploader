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

?>

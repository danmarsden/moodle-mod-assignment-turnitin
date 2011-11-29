<?php
/**
 * Generic Logger Class
 * v 1.0 2006/06/13 10:00:00 Northumbria Learning
 */
class Logger {
    var $file;
    var $filename;

    function log($message = '') {
        global $CFG;
        $this->filename = TII_LOGGING_LOCATION.'log.txt';
        $this->file = fopen($this->filename, "a+");
        $message = '['.date('d/m/Y H:i:s').'] - '.$message;
        fwrite($this->file, $message."\r\n");
        fclose($this->file);
    }
}

?>

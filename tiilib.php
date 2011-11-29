<?php
/**
 * TII Assignemnt Library
 *
 * v 1.1 2006/06/13 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 8:50:14 Northumbria Learning
 * Adapted for Moodle 1.8
 */
// CONSTANTS
define("TII_FID_CREATEASSIGNMENT",4);
define("TII_FID_SUBMITASSIGNMENTSTAFF",5);
define("TII_FID_VIEWPAPER",7);
define("TII_FID_SUBMITASSIGNMENTSTUDENT",10);
define("TII_FCMD_LOGGEDIN",1);
define("TII_FCMD_NOTLOGGEDIN",2);
define("TII_FCMD_DELETEASSIGNMENT",4);
define("TII_FCMD_EDITASSIGNMENT",5);
define("TII_DIAGNOSTIC",0);
define("TII_ENCRYPT",0);
define("TII_SRC",6);
define("TII_BASEURL",'https://api.turnitin.com/api.asp');
define("TII_VERSION",'2.3.1');
define("TII_LOGGING",false);
define("TII_LOGGING_LOCATION","");

// CONFIGURABLE SECTION FOR SETUP
//====================================================================
define("TII_AID",'53305');
define("TII_SHAREDKEY",'PearlD1v');
define("TII_WEBSERVICEURL",'http://webdev.polytechnic.bh/moodle/mod/assignment/type/turnitin/ws/dispatcher.php');

//====================================================================

?>

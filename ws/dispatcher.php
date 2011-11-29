<?php
/*
 * Dispatch handler
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 8:50:14 Northumbria Learning
 * Adapted for Moodle 1.8
 */
require_once("../../../../../config.php");
require_once($CFG->dirroot."/mod/assignment/lib.php");
require_once("../tiilib.php");
require_once("../assignment.class.php");
require_once("tiiws.class.php");

if (TII_LOGGING == true) {
    require_once($CFG->dirroot."/mod/assignment/type/turnitin/logger.class.php");
    $logger = new Logger;
    $somecontent = "?";
    foreach ($_REQUEST as $key => $value) {
        $somecontent .= $key."=".$value.'&';
        $logger->log("VARS:".$key."=".$value);
    }
    $somecontent = substr($somecontent,0,strlen($somecontent)-1);
    $logger->log("DISPATCHER:".$somecontent);
}

$data = pack('H*',$_REQUEST['message']);
$chopped = split('&',$data);
foreach ($chopped as $data) {
    $info = split('=',$data);
    $_REQUEST[$info[0]] = $info[1];
    if (TII_LOGGING == true) {
        $logger->log("VARS:".$info[0]." = ".$info[1]);
    }
}

$ws = new tii_ws;
if (!$ws->check_md5()) {
    print $ws->generate_message();
} else {
    if ($_REQUEST['action'] == 'ASSIGNMENT_CREATE') {
        $ws->create_assignment();
        print $ws->generate_message();
    } elseif ($_REQUEST['action'] == 'ASSIGNMENT_MODIFY') {
        $ws->modify_assignment();
        print $ws->generate_message();
    } elseif ($_REQUEST['action'] == 'ASSIGNMENT_SUBMISSION') {
        $ws->submit_assignment();
        print $ws->generate_message();
    } elseif ($_REQUEST['action'] == 'SET_GRADE') {
        $ws->set_grade();
        print $ws->generate_message();
    } elseif ($_REQUEST['action'] == 'REMOVE_SUBMISSION') {
        $ws->delete_submission();
        print $ws->generate_message();
    } elseif ($_REQUEST['action'] == 'RETRIEVE_ROSTER') {
        print $ws->get_roster();
    } else {
        $ws->system_error();
	    print $ws->generate_message();
    }
}


?>
